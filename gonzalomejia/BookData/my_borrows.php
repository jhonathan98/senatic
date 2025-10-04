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
    
    // Check if the book is overdue before returning
    $stmt = $pdo->prepare("SELECT due_date, DATEDIFF(NOW(), due_date) as days_overdue FROM borrowed_books WHERE id = ? AND status IN ('active', 'overdue')");
    $stmt->execute([$borrow_id]);
    $book_info = $stmt->fetch();
    
    $is_overdue = $book_info && $book_info['days_overdue'] > 0;
    
    if (return_book($borrow_id)) {
        if ($is_overdue) {
            $success_message = "Libro devuelto exitosamente. Nota: El libro fue devuelto con " . $book_info['days_overdue'] . " día(s) de retraso.";
        } else {
            $success_message = "Libro devuelto exitosamente.";
        }
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

// Include header
include 'includes/header.php';
?>

<!-- Estilos mínimos adicionales para funcionalidad -->
<style>
    .book-cover {
        width: 80px;
        height: 100px;
        object-fit: cover;
    }
    
    .status-overdue {
        background-color: #dc3545;
        color: white;
    }
    
    .status-due-soon {
        background-color: #ffc107;
        color: #212529;
    }
    
    .status-active {
        background-color: #28a745;
        color: white;
    }
    
    .status-returned {
        background-color: #6c757d;
        color: white;
    }
    
    .due-date-warning {
        color: #dc3545;
        font-weight: bold;
    }
    
    .due-date-soon {
        color: #fd7e14;
        font-weight: bold;
    }
    
    .due-date-ok {
        color: #28a745;
        font-weight: bold;
    }
    
    .overdue-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 8px;
        margin-top: 5px;
    }
    
    .btn-overdue {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }
    
    .btn-overdue:hover {
        background-color: #c82333;
        border-color: #bd2130;
        color: white;
    }
</style>

<div class="main-content">
    <div class="container">
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="h2">
                    <i class="fas fa-bookmark me-2"></i>Mis Préstamos
                </h1>
                <p class="lead">Gestiona todos tus libros prestados y su historial</p>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-book-open text-primary fs-1"></i>
                            <h3 class="mt-2"><?php echo $stats['active_borrows']; ?></h3>
                            <p class="card-text">Préstamos Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-exclamation-triangle text-danger fs-1"></i>
                            <h3 class="mt-2"><?php echo $stats['overdue_borrows']; ?></h3>
                            <p class="card-text">Libros Vencidos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-clock text-warning fs-1"></i>
                            <h3 class="mt-2"><?php echo $stats['due_soon']; ?></h3>
                            <p class="card-text">Vencen Pronto</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-history text-info fs-1"></i>
                            <h3 class="mt-2"><?php echo $stats['total_borrows']; ?></h3>
                            <p class="card-text">Historial Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Borrowed Books -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>
                            <i class="fas fa-book-reader me-2"></i>
                            Préstamos Actuales
                        </h2>
                        
                        <?php if (!empty($borrowed_books)): ?>
                            <div class="mb-3" style="min-width: 300px;">
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
                        <div class="card">
                            <div class="card-body text-center p-5">
                                <i class="fas fa-book-open text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">No tienes préstamos activos</h5>
                                <p class="mb-3">¡Explora nuestro catálogo y encuentra tu próximo libro favorito!</p>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Explorar Catálogo
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrowed_books as $book): ?>
                            <?php
                            $is_overdue = $book['status_detail'] == 'overdue';
                            $is_due_soon = $book['status_detail'] == 'due_soon';
                            $days_until_due = $book['days_until_due'];
                            ?>
                            <div class="card mb-3" data-book-title="<?php echo strtolower(htmlspecialchars($book['title'])); ?>" data-book-author="<?php echo strtolower(htmlspecialchars($book['author'])); ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 text-center mb-3 mb-md-0">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                     class="book-cover img-thumbnail">
                                            <?php else: ?>
                                                <div class="book-cover bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-book fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </p>
                                            
                                            <?php if (!empty($book['category'])): ?>
                                                <span class="badge bg-secondary mb-2">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo htmlspecialchars($book['category']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-calendar-plus me-1"></i>
                                                        <strong>Prestado:</strong> <?php echo date('d/m/Y', strtotime($book['borrow_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="col-sm-6">
                                                    <small class="<?php echo $is_overdue ? 'due-date-warning' : ($is_due_soon ? 'due-date-soon' : 'due-date-ok'); ?> d-block">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        <strong>Vence:</strong> <?php echo date('d/m/Y', strtotime($book['due_date'])); ?>
                                                        <?php if ($is_overdue): ?>
                                                            <br><span class="badge bg-danger">Vencido hace <?php echo abs(floor($days_until_due)); ?> días</span>
                                                        <?php elseif ($is_due_soon): ?>
                                                            <br><span class="badge bg-warning text-dark"><?php echo ceil($days_until_due); ?> días restantes</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($book['renewal_count']) && $book['renewal_count'] > 0): ?>
                                                <div class="alert alert-info py-2 px-3 mt-2 mb-0">
                                                    <small><i class="fas fa-redo me-1"></i>
                                                    <strong>Renovado <?php echo $book['renewal_count']; ?> vez(es)</strong></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <span class="badge fs-6 <?php echo $is_overdue ? 'status-overdue' : ($is_due_soon ? 'status-due-soon' : 'status-active'); ?>">
                                                <i class="fas fa-<?php echo $is_overdue ? 'exclamation-triangle' : ($is_due_soon ? 'clock' : 'check-circle'); ?> me-1"></i>
                                                <?php echo $is_overdue ? 'Vencido' : ($is_due_soon ? 'Vence pronto' : 'Activo'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="d-grid gap-2">
                                                <!-- Return Book Form -->
                                                <form method="POST">
                                                    <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                                    <?php if ($is_overdue): ?>
                                                        <button type="submit" name="return_book" class="btn btn-danger btn-sm w-100" 
                                                                onclick="return confirm('Este libro está vencido hace <?php echo abs(floor($days_until_due)); ?> días. ¿Confirmas la devolución? Pueden aplicarse multas por el retraso.')">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Devolver (Vencido)
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="return_book" class="btn btn-success btn-sm w-100" 
                                                                onclick="return confirm('¿Estás seguro de que quieres devolver este libro?')">
                                                            <i class="fas fa-check me-1"></i>Devolver
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                                
                                                <!-- Renew Book Form -->
                                                <?php if (!$is_overdue && (!isset($book['renewal_count']) || $book['renewal_count'] < 2)): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" name="renew_book" class="btn btn-warning btn-sm w-100">
                                                            <i class="fas fa-redo me-1"></i>Renovar
                                                        </button>
                                                    </form>
                                                <?php elseif ($is_overdue): ?>
                                                    <div class="alert alert-danger py-1 px-2 mb-2">
                                                        <small><strong>¡Libro vencido!</strong><br>
                                                        Vencido hace <?php echo abs(floor($days_until_due)); ?> días</small>
                                                    </div>
                                                    <small class="text-muted d-block mb-1">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Pueden aplicarse multas
                                                    </small>
                                                <?php elseif (isset($book['renewal_count']) && $book['renewal_count'] >= 2): ?>
                                                    <div class="alert alert-warning py-1 px-2 mb-0">
                                                        <small><strong>Límite alcanzado</strong><br>
                                                        No se puede renovar más</small>
                                                    </div>
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
                        <div class="card">
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
                        <h2>
                            <i class="fas fa-history me-2"></i>
                            Historial de Préstamos
                        </h2>
                        
                        <?php if (!empty($borrow_history)): ?>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="historyFilter" id="all" data-filter="all" checked>
                                <label class="btn btn-outline-primary" for="all">Todos</label>
                                
                                <input type="radio" class="btn-check" name="historyFilter" id="devueltos" data-filter="returned">
                                <label class="btn btn-outline-success" for="devueltos">Devueltos</label>
                                
                                <input type="radio" class="btn-check" name="historyFilter" id="vencidos" data-filter="overdue">
                                <label class="btn btn-outline-danger" for="vencidos">Vencidos</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($borrow_history)): ?>
                        <div class="card">
                            <div class="card-body text-center p-5">
                                <i class="fas fa-clock text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">Sin historial de préstamos</h5>
                                <p>Aquí aparecerán los libros que hayas devuelto anteriormente.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrow_history as $book): ?>
                            <div class="card mb-3 <?php echo $book['status']; ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center mb-3 mb-md-0">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                     class="book-cover img-thumbnail">
                                            <?php else: ?>
                                                <div class="book-cover bg-light d-flex align-items-center justify-content-center">
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
                                            
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-plus me-1"></i>
                                                        <strong>Prestado:</strong> <?php echo date('d/m/Y', strtotime($book['borrow_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="col-sm-4">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        <strong>Debía vencer:</strong> <?php echo date('d/m/Y', strtotime($book['due_date'])); ?>
                                                    </small>
                                                </div>
                                                <?php if ($book['return_date']): ?>
                                                    <div class="col-sm-4">
                                                        <small class="text-success">
                                                            <i class="fas fa-calendar-times me-1"></i>
                                                            <strong>Devuelto:</strong> <?php echo date('d/m/Y', strtotime($book['return_date'])); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <span class="badge bg-secondary fs-6">
                                                <?php 
                                                $status_spanish = $book['status'];
                                                if ($book['status'] == 'returned') {
                                                    $status_spanish = 'Devuelto';
                                                } elseif ($book['status'] == 'overdue') {
                                                    $status_spanish = 'Vencido';
                                                }
                                                echo $status_spanish; 
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <a href="book_detail.php?id=<?php echo $book['book_id']; ?>" 
                                               class="btn btn-primary btn-sm">
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
</div>

<!-- JavaScript para funcionalidad básica -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad de búsqueda
    setupSearch();
    
    // Auto-hide alerts
    autoHideAlerts();
    
    // Filtros de historial
    setupHistoryFilters();
});

// Funcionalidad de búsqueda
function setupSearch() {
    const searchInput = document.getElementById('borrowSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const bookCards = document.querySelectorAll('.card[data-book-title]');
            let hasResults = false;
            
            bookCards.forEach(card => {
                const title = card.getAttribute('data-book-title');
                const author = card.getAttribute('data-book-author');
                
                if (title.includes(searchTerm) || author.includes(searchTerm)) {
                    card.style.display = 'block';
                    hasResults = true;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            let noResultsMsg = document.getElementById('noResultsMessage');
            
            if (!hasResults && searchTerm !== '' && bookCards.length > 0) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noResultsMessage';
                    noResultsMsg.className = 'card';
                    noResultsMsg.innerHTML = `
                        <div class="card-body text-center p-4">
                            <i class="fas fa-search text-muted fs-1"></i>
                            <h5 class="mt-3">No se encontraron resultados</h5>
                            <p>No hay libros que coincidan con "${searchTerm}"</p>
                        </div>
                    `;
                    searchInput.closest('.row').querySelector('.col-12').appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        });
    }
}

// Auto-ocultar alerts después de 5 segundos
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
}

// Configurar filtros de historial
function setupHistoryFilters() {
    const filterRadios = document.querySelectorAll('input[name="historyFilter"]');
    
    filterRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const filter = this.getAttribute('data-filter');
            const historyCards = document.querySelectorAll('.card.returned, .card.overdue, .card[class*="returned"], .card[class*="overdue"]');
            
            historyCards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else {
                    if (card.classList.contains(filter)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });
}

// Confirmación para acciones importantes
function confirmAction(message) {
    return confirm(message);
}
</script>

<?php include 'includes/footer.php'; ?>
