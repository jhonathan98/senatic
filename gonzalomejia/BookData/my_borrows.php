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
$page_title = "Mis Préstamos";

// Handle book return
if (isset($_POST['return_book'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    if (return_book($borrow_id)) {
        $success_message = "Libro devuelto exitosamente.";
    } else {
        $error_message = "Error al devolver el libro.";
    }
}

// Handle book renewal
if (isset($_POST['renew_book'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    if (renew_book($borrow_id)) {
        $success_message = "Libro renovado exitosamente.";
    } else {
        $error_message = "No se pudo renovar el libro. Verifica si has alcanzado el límite de renovaciones.";
    }
}

// Get user's current borrowed books with detailed information
$borrowed_books = get_user_borrowed_books_detailed($_SESSION['user_id']);

// Get user's borrowing history with detailed information
$borrow_history = get_user_borrow_history_detailed($_SESSION['user_id']);

// Get user borrowing statistics
$stats = get_user_borrow_stats($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - Mis Préstamos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .main-content {
            padding-top: 6rem;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card .card-body {
            padding: 1.5rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .book-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .book-cover {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-overdue {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        
        .status-returned {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-success-custom:hover {
            background: linear-gradient(135deg, #218838, #1c9979);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .btn-warning-custom:hover {
            background: linear-gradient(135deg, #e0a800, #e8690b);
            transform: translateY(-2px);
            color: white;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .alert-custom {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }
        
        .due-date-warning {
            color: #dc3545;
            font-weight: bold;
        }
        
        .due-date-soon {
            color: #ffc107;
            font-weight: bold;
        }
        
        .due-date-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-group .btn-check:checked + .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-success:hover,
        .btn-outline-danger:hover {
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-btn {
            position: relative;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="text-center mb-3">
                        <i class="fas fa-bookmark me-3"></i>Mis Préstamos
                    </h1>
                    <p class="text-center text-muted">Gestiona todos tus libros prestados y su historial</p>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-5">
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <div class="stats-number"><?php echo $stats['active_borrows']; ?></div>
                            <div class="stats-label">
                                <i class="fas fa-book-open me-1"></i>
                                Préstamos Activos
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <div class="stats-number"><?php echo $stats['overdue_borrows']; ?></div>
                            <div class="stats-label">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Libros Vencidos
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <div class="stats-number"><?php echo $stats['due_soon']; ?></div>
                            <div class="stats-label">
                                <i class="fas fa-clock me-1"></i>
                                Vencen Pronto
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <div class="stats-number"><?php echo $stats['total_borrows']; ?></div>
                            <div class="stats-label">
                                <i class="fas fa-history me-1"></i>
                                Historial Total
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Borrowed Books -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="section-title mb-0">
                            <i class="fas fa-book-reader me-2"></i>
                            Préstamos Actuales
                        </h2>
                        
                        <?php if (!empty($borrowed_books)): ?>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="borrowSearch" 
                                           placeholder="Buscar en mis préstamos...">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($borrowed_books)): ?>
                        <div class="card book-card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-book-open"></i>
                                    <h5>No tienes préstamos activos</h5>
                                    <p class="mb-3">¡Explora nuestro catálogo y encuentra tu próximo libro favorito!</p>
                                    <a href="dashboard.php" class="btn btn-primary-custom">
                                        <i class="fas fa-search me-2"></i>Explorar Catálogo
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrowed_books as $book): ?>
                            <?php
                            $is_overdue = $book['status_detail'] == 'overdue';
                            $is_due_soon = $book['status_detail'] == 'due_soon';
                            $days_until_due = $book['days_until_due'];
                            ?>
                            <div class="card book-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center mb-3 mb-md-0">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                     class="book-cover">
                                            <?php else: ?>
                                                <div class="book-cover d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-book fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($book['title']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </p>
                                            
                                            <div class="row text-sm">
                                                <div class="col-sm-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-plus me-1"></i>
                                                        Prestado: <?php echo date('d/m/Y', strtotime($book['borrow_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="col-sm-6">
                                                    <small class="<?php echo $is_overdue ? 'due-date-warning' : ($is_due_soon ? 'due-date-soon' : 'due-date-ok'); ?>">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        Vence: <?php echo date('d/m/Y', strtotime($book['due_date'])); ?>
                                                        <?php if ($is_overdue): ?>
                                                            (<?php echo abs(floor($days_until_due)); ?> días vencido)
                                                        <?php elseif ($is_due_soon): ?>
                                                            (<?php echo ceil($days_until_due); ?> días restantes)
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($book['renewal_count']) && $book['renewal_count'] > 0): ?>
                                                <small class="text-info">
                                                    <i class="fas fa-redo me-1"></i>
                                                    Renovado <?php echo $book['renewal_count']; ?> vez(es)
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <span class="status-badge <?php echo $is_overdue ? 'status-overdue' : 'status-active'; ?>">
                                                <?php echo $is_overdue ? 'Vencido' : 'Activo'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <div class="d-grid gap-2">
                                                <!-- Return Book Form -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                                    <button type="submit" name="return_book" class="btn btn-success-custom btn-sm" 
                                                            onclick="return confirm('¿Estás seguro de que quieres devolver este libro?')">
                                                        <i class="fas fa-check me-1"></i>Devolver
                                                    </button>
                                                </form>
                                                
                                                <!-- Renew Book Form -->
                                                <?php if (!$is_overdue && (!isset($book['renewal_count']) || $book['renewal_count'] < 2)): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" name="renew_book" class="btn btn-warning-custom btn-sm">
                                                            <i class="fas fa-redo me-1"></i>Renovar
                                                        </button>
                                                    </form>
                                                <?php elseif (isset($book['renewal_count']) && $book['renewal_count'] >= 2): ?>
                                                    <small class="text-muted">Límite de renovaciones alcanzado</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Statistics -->
            <?php if ($stats['total_borrows'] > 0): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card book-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estadísticas de Lectura
                                </h5>
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <div class="h4 text-success"><?php echo $stats['returned_on_time']; ?></div>
                                            <small class="text-muted">Devueltos a Tiempo</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <div class="h4 text-info"><?php echo $stats['avg_borrow_days']; ?></div>
                                            <small class="text-muted">Días Promedio de Préstamo</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <div class="h4 text-primary">
                                                <?php echo $stats['total_borrows'] > 0 ? round(($stats['returned_on_time'] / $stats['total_borrows']) * 100, 1) : 0; ?>%
                                            </div>
                                            <small class="text-muted">Tasa de Puntualidad</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <div class="h4 text-warning"><?php echo date('Y') - 2020; ?></div>
                                            <small class="text-muted">Años Leyendo</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Borrowing History -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="section-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historial de Préstamos
                        </h2>
                        
                        <?php if (!empty($borrow_history)): ?>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="historyFilter" id="all" data-filter="all" checked>
                                <label class="btn btn-outline-primary" for="all">Todos</label>
                                
                                <input type="radio" class="btn-check" name="historyFilter" id="returned" data-filter="returned">
                                <label class="btn btn-outline-success" for="returned">Devueltos</label>
                                
                                <input type="radio" class="btn-check" name="historyFilter" id="overdue" data-filter="overdue">
                                <label class="btn btn-outline-danger" for="overdue">Vencidos</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($borrow_history)): ?>
                        <div class="card book-card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-clock"></i>
                                    <h5>Sin historial de préstamos</h5>
                                    <p>Aquí aparecerán los libros que hayas devuelto anteriormente.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrow_history as $book): ?>
                            <div class="card book-card <?php echo $book['status']; ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center mb-3 mb-md-0">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                     class="book-cover">
                                            <?php else: ?>
                                                <div class="book-cover d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-book fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($book['title']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </p>
                                            
                                            <div class="row text-sm">
                                                <div class="col-sm-4">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-plus me-1"></i>
                                                        Prestado: <?php echo date('d/m/Y', strtotime($book['borrow_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="col-sm-4">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        Debía vencer: <?php echo date('d/m/Y', strtotime($book['due_date'])); ?>
                                                    </small>
                                                </div>
                                                <?php if ($book['return_date']): ?>
                                                    <div class="col-sm-4">
                                                        <small class="text-success">
                                                            <i class="fas fa-calendar-times me-1"></i>
                                                            Devuelto: <?php echo date('d/m/Y', strtotime($book['return_date'])); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <span class="status-badge status-returned">
                                                <?php echo ucfirst($book['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <a href="book_detail.php?id=<?php echo $book['book_id']; ?>" 
                                               class="btn btn-primary-custom btn-sm">
                                                <i class="fas fa-eye me-1"></i>Ver Libro
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/my_borrows.js"></script>
</body>
</html>
