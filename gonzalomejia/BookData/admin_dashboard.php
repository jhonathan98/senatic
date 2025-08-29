<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_books FROM books");
$stmt->execute();
$total_books = $stmt->fetch()['total_books'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active_borrows FROM borrowed_books WHERE status = 'active'");
$stmt->execute();
$active_borrows = $stmt->fetch()['active_borrows'];

$stmt = $pdo->prepare("SELECT COUNT(*) as overdue_borrows FROM borrowed_books WHERE status = 'overdue'");
$stmt->execute();
$overdue_borrows = $stmt->fetch()['overdue_borrows'];

$stmt = $pdo->prepare("SELECT b.title, COUNT(bb.id) as borrow_count FROM books b JOIN borrowed_books bb ON b.id = bb.book_id GROUP BY b.id ORDER BY borrow_count DESC LIMIT 5");
$stmt->execute();
$popular_books = $stmt->fetchAll();

// Recent activity
$stmt = $pdo->prepare("SELECT u.full_name, b.title, bb.borrow_date, bb.status FROM borrowed_books bb JOIN users u ON bb.user_id = u.id JOIN books b ON bb.book_id = b.id ORDER BY bb.borrow_date DESC LIMIT 10");
$stmt->execute();
$recent_activity = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - Panel Administrador</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .action-btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background-color: #ffc107;
            color: black;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-edit:hover {
            background-color: #e0a800;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .activity-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            margin-right: 10px;
        }
        .activity-active {
            background-color: #d4edda;
            color: #155724;
        }
        .activity-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
        .activity-returned {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .table td {
            vertical-align: middle;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="section-title">Panel de Control</h2>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Usuarios Registrados</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Libros Disponibles</div>
                        <div class="stat-value"><?php echo $total_books; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Préstamos Activos</div>
                        <div class="stat-value"><?php echo $active_borrows; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Préstamos Vencidos</div>
                        <div class="stat-value"><?php echo $overdue_borrows; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-md-6">
                    <div class="section-title">Libros Más Populares</div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Cantidad de Préstamos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_books as $book): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo $book['borrow_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-title">Actividad Reciente</div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Libro</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td><?php echo $activity['borrow_date']; ?></td>
                                        <td>
                                            <span class="activity-badge <?php echo $activity['status'] === 'active' ? 'activity-active' : ($activity['status'] === 'overdue' ? 'activity-overdue' : 'activity-returned'); ?>">
                                                <?php echo ucfirst($activity['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="section-title">Lista de Usuarios</div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Fecha de Registro</th>
                                    <th>Total de Préstamos</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT u.*, COUNT(bb.id) as total_borrows FROM users u LEFT JOIN borrowed_books bb ON u.id = bb.user_id AND bb.status != 'returned' GROUP BY u.id ORDER BY u.created_at DESC");
                                $stmt->execute();
                                $users = $stmt->fetchAll();
                                
                                foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['created_at']; ?></td>
                                        <td><?php echo $user['total_borrows']; ?></td>
                                        <td><?php echo ucfirst($user['role']); ?></td>
                                        <td>
                                            <button class="action-btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">Editar</button>
                                            <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            if (confirm('¿Estás seguro de que quieres editar este usuario?')) {
                window.location.href = 'edit_user.php?id=' + userId;
            }
        }
        
        function deleteUser(userId) {
            if (confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.')) {
                window.location.href = 'delete_user.php?id=' + userId;
            }
        }
    </script>
</body>
</html>