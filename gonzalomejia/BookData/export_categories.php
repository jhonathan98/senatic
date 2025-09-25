<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get categories with book counts
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(b.id) as book_count,
           COUNT(CASE WHEN b.availability = 'available' THEN 1 END) as available_books,
           COUNT(CASE WHEN b.availability = 'borrowed' THEN 1 END) as borrowed_books
    FROM categories c
    LEFT JOIN books b ON c.name = b.category
    GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="categorias_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Categorías - BookData</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .center { text-align: center; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="title">Reporte de Categorías - BookData</div>
    <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Libros</th>
                <th>Libros Disponibles</th>
                <th>Libros Prestados</th>
                <th>Fecha Creación</th>
                <th>Última Actualización</th>
            </tr>
        </thead>
        <tbody>';

foreach ($categories as $category) {
    echo '<tr>
        <td class="center">' . htmlspecialchars($category['id']) . '</td>
        <td>' . htmlspecialchars($category['name']) . '</td>
        <td>' . htmlspecialchars($category['description'] ?: 'Sin descripción') . '</td>
        <td class="center">' . $category['book_count'] . '</td>
        <td class="center">' . $category['available_books'] . '</td>
        <td class="center">' . $category['borrowed_books'] . '</td>
        <td class="center">' . date('d/m/Y', strtotime($category['created_at'])) . '</td>
        <td class="center">' . date('d/m/Y H:i', strtotime($category['updated_at'])) . '</td>
    </tr>';
}

echo '        </tbody>
    </table>
    
    <br><br>
    <h3>Resumen:</h3>
    <p>Total de categorías: ' . count($categories) . '</p>
    <p>Categorías con libros: ' . count(array_filter($categories, function($c) { return $c['book_count'] > 0; })) . '</p>
    <p>Categorías vacías: ' . count(array_filter($categories, function($c) { return $c['book_count'] == 0; })) . '</p>
    
</body>
</html>';
?>
