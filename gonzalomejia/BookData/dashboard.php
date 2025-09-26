<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// Obtener todas las categorías únicas
$categories = get_unique_categories();

// Obtener la categoría seleccionada
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Obtener la consulta de búsqueda
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Obtener libros según los filtros
if ($search_query !== '') {
    // Si hay una búsqueda, filtrar por búsqueda y categoría
    $available_books = get_books_by_search_and_category($search_query, $selected_category);
} else if ($selected_category !== 'all') {
    // Si hay una categoría seleccionada, filtrar solo por categoría
    $available_books = get_books_by_category($selected_category);
} else {
    // Si no hay filtros, obtener todos los libros disponibles
    $available_books = get_available_books();
}

$borrowed_books = get_user_borrowed_books($_SESSION['user_id']);
$borrow_history = get_user_borrow_history($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            height: 100%;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .book-info {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .genre-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background-color: white;
            transition: all 0.2s;
            cursor: pointer;
        }
        .genre-btn:hover {
            background-color: #e9ecef;
        }
        .genre-btn.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .gap-2 {
            gap: 0.5rem !important;
        }
        .main-content {
            padding-top: 2rem;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1 class="text-center mb-4">BookData</h1>
            
            <!-- Barra de búsqueda -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-6">
                    <form class="d-flex" method="GET">
                        <input class="form-control me-2" type="search" placeholder="Buscar libros..." aria-label="Search" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php if (isset($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
                        <?php endif; ?>
                        <button class="btn btn-outline-primary" type="submit">Buscar</button>
                    </form>
                </div>
            </div>

            <!-- Filtros de categoría -->
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                <a href="?category=all<?php echo $search_query ? '&query=' . urlencode($search_query) : ''; ?>" 
                   class="genre-btn <?php echo (!isset($_GET['category']) || $_GET['category'] === 'all') ? 'active' : ''; ?>">Todos</a>
                <?php foreach ($categories as $category): 
                    $category_slug = strtolower(str_replace(' ', '-', $category));
                ?>
                    <a href="?category=<?php echo urlencode($category_slug); ?><?php echo $search_query ? '&query=' . urlencode($search_query) : ''; ?>" 
                       class="genre-btn <?php echo (isset($_GET['category']) && $_GET['category'] === $category_slug) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="section-title mt-5">Catálogo de libros</div>
            <div class="row">
                <?php foreach ($available_books as $book): ?>
                    <div class="col-md-3 mb-4 book-card" data-category="<?php echo strtolower(str_replace(' ', '-', $book['category'])); ?>">
                        <div class="card h-100">
                            <img src="<?php echo $book['cover_image'] ?: 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                <p class="book-info">Autor: <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="book-info">Categoría: <?php echo htmlspecialchars($book['category']); ?></p>
                                <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">Alquilar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Funciones específicas del dashboard
        function renewBook(borrowId) {
            BookData.confirm('¿Estás seguro de que quieres renovar este libro?', () => {
                window.location.href = 'renew_book.php?id=' + borrowId;
            });
        }
        
        function returnBook(borrowId) {
            BookData.confirm('¿Estás seguro de que quieres devolver este libro?', () => {
                window.location.href = 'return_book.php?id=' + borrowId;
            });
        }
    </script>
</body>
</html>