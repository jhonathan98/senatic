<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if book ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$book_id = $_GET['id'];
$book = get_book_by_id($book_id);
$user = get_user_by_id($_SESSION['user_id']);

// If book doesn't exist, redirect to dashboard
if (!$book) {
    header("Location: dashboard.php");
    exit();
}

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrow_date = $_POST['borrow_date'];
    $due_date = $_POST['due_date'];
    
    $result = borrow_book_with_dates($_SESSION['user_id'], $book_id, $borrow_date, $due_date);
    
    if ($result['success']) {
        $message = $result['message'];
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Libro - <?php echo htmlspecialchars($book['title']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .book-cover {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .book-info {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .main-content {
            padding-top: 5rem;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <img src="<?php echo $book['cover_image'] ?: 'assets/images/book-placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="book-cover mb-4">
                </div>
                <div class="col-md-8">
                    <div class="book-info">
                        <h1 class="mb-4"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <p class="lead"><strong>Autor:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Categoría:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                        <p><strong>Copias disponibles:</strong> <?php echo $book['copies_available']; ?></p>
                        <p><strong>Descripción:</strong><br><?php echo nl2br(htmlspecialchars($book['description'] ?? 'No hay descripción disponible.')); ?></p>
                        
                        <?php if ($book['copies_available'] > 0): ?>
                            <form method="POST" class="mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="borrow_date" class="form-label">Fecha de préstamo</label>
                                            <input type="date" class="form-control" id="borrow_date" name="borrow_date" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Fecha de devolución</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Alquilar libro</button>
                                <a href="dashboard.php" class="btn btn-secondary">Volver al catálogo</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Este libro no está disponible actualmente.
                            </div>
                            <a href="dashboard.php" class="btn btn-secondary">Volver al catálogo</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de fechas en el cliente
        document.addEventListener('DOMContentLoaded', function() {
            const borrowDate = document.getElementById('borrow_date');
            const dueDate = document.getElementById('due_date');
            
            borrowDate.addEventListener('change', function() {
                // La fecha de devolución debe ser al menos un día después de la fecha de préstamo
                const minDueDate = new Date(borrowDate.value);
                minDueDate.setDate(minDueDate.getDate() + 1);
                dueDate.min = minDueDate.toISOString().split('T')[0];
                
                // Si la fecha de devolución es anterior a la nueva fecha mínima, actualizarla
                if (dueDate.value && new Date(dueDate.value) < minDueDate) {
                    dueDate.value = minDueDate.toISOString().split('T')[0];
                }
            });
        });
    </script>
</body>
</html>
