<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/returns_functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_reminder':
                $borrow_id = $_POST['borrow_id'];
                // In a real application, you would send an email or notification here
                $message = 'Recordatorio enviado exitosamente.';
                $message_type = 'success';
                break;
                
            case 'extend_due_date':
                $borrow_id = $_POST['borrow_id'];
                $new_due_date = $_POST['new_due_date'];
                
                $stmt = $pdo->prepare("UPDATE borrowed_books SET due_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
                if ($stmt->execute([$new_due_date, $borrow_id])) {
                    $message = 'Fecha de devolución extendida exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al extender la fecha de devolución.';
                    $message_type = 'danger';
                }
                break;
                
            case 'mark_returned':
                $borrow_id = $_POST['borrow_id'];
                $book_id = $_POST['book_id'];
                
                // Start transaction
                $pdo->beginTransaction();
                try {
                    // Update borrow record
                    $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'returned', return_date = CURDATE() WHERE id = ?");
                    $stmt->execute([$borrow_id]);
                    
                    // Update book availability
                    $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?");
                    $stmt->execute([$book_id]);
                    
                    $pdo->commit();
                    $message = 'Libro marcado como devuelto exitosamente.';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Error al procesar la devolución.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
        COUNT(CASE WHEN status = 'active' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1 END) as due_soon_count,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
        AVG(DATEDIFF(CURDATE(), borrow_date)) as avg_borrow_days
    FROM borrowed_books 
    WHERE status IN ('active', 'overdue')
");
$stmt->execute();
$stats = $stmt->fetch();

// Get overdue books
$stmt = $pdo->prepare("
    SELECT bb.*, b.title, b.author, b.cover_image, u.full_name, u.email, u.username,
           DATEDIFF(CURDATE(), bb.due_date) as days_overdue
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.user_id = u.id
    WHERE bb.status = 'overdue'
    ORDER BY days_overdue DESC, bb.due_date ASC
");
$stmt->execute();
$overdue_books = $stmt->fetchAll();

// Get books due soon (within 3 days)
$stmt = $pdo->prepare("
    SELECT bb.*, b.title, b.author, b.cover_image, u.full_name, u.email, u.username,
           DATEDIFF(bb.due_date, CURDATE()) as days_until_due
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.user_id = u.id
    WHERE bb.status = 'active' AND bb.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY bb.due_date ASC
");
$stmt->execute();
$due_soon_books = $stmt->fetchAll();

// Get all active borrows for management
$stmt = $pdo->prepare("
    SELECT bb.*, b.title, b.author, b.cover_image, u.full_name, u.email, u.username,
           DATEDIFF(bb.due_date, CURDATE()) as days_until_due,
           DATEDIFF(CURDATE(), bb.borrow_date) as days_borrowed
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.user_id = u.id
    WHERE bb.status = 'active'
    ORDER BY bb.due_date ASC
");
$stmt->execute();
$active_borrows = $stmt->fetchAll();

$page_title = 'Devoluciones Pendientes';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - <?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
        <div class="container-fluid">
            <div class="manage-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-clock me-3"></i>Devoluciones Pendientes
                    </h1>
                    <p class="text-muted">Gestiona los préstamos vencidos y próximos a vencer</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row stats-row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card overdue">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['overdue_count']; ?></div>
                            <div class="stat-label">Préstamos Vencidos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <i class="fas fa-clock" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['due_soon_count']; ?></div>
                            <div class="stat-label">Vencen Pronto</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card active">
                            <i class="fas fa-book-open" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['active_count']; ?></div>
                            <div class="stat-label">Préstamos Activos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card info">
                            <i class="fas fa-calendar-alt" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo round($stats['avg_borrow_days'] ?? 0); ?></div>
                            <div class="stat-label">Días Promedio</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="section-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                        </h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-danger me-2" onclick="sendBulkReminders('overdue')">
                                <i class="fas fa-bell me-2"></i>Recordar Vencidos
                            </button>
                            <button class="btn btn-warning me-2" onclick="sendBulkReminders('due_soon')">
                                <i class="fas fa-clock me-2"></i>Recordar Próximos
                            </button>
                            <button class="btn btn-info me-2" onclick="exportOverdue()">
                                <i class="fas fa-download me-2"></i>Exportar Vencidos
                            </button>
                            <button class="btn btn-secondary" onclick="generateReturnReport()">
                                <i class="fas fa-chart-line me-2"></i>Reporte de Devoluciones
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Overdue Books Section -->
                <?php if (!empty($overdue_books)): ?>
                <div class="content-card">
                    <h4 class="section-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Préstamos Vencidos (<?php echo count($overdue_books); ?>)
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Libro</th>
                                    <th><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th><i class="fas fa-calendar me-2"></i>Fecha Límite</th>
                                    <th><i class="fas fa-clock me-2"></i>Días Vencido</th>
                                    <th><i class="fas fa-redo me-2"></i>Renovaciones</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_books as $borrow): ?>
                                    <tr class="table-danger">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $borrow['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($borrow['title']); ?>" 
                                                     style="width: 40px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                                                    <br><small class="text-muted">por <?php echo htmlspecialchars($borrow['author']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($borrow['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-danger">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($borrow['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger fs-6">
                                                <?php echo $borrow['days_overdue']; ?> días
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $borrow['renewal_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="sendReminder(<?php echo $borrow['id']; ?>)" 
                                                        title="Enviar recordatorio">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="extendDueDate(<?php echo $borrow['id']; ?>, '<?php echo $borrow['due_date']; ?>')" 
                                                        title="Extender fecha">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="markReturned(<?php echo $borrow['id']; ?>, <?php echo $borrow['book_id']; ?>, '<?php echo htmlspecialchars($borrow['title']); ?>')" 
                                                        title="Marcar como devuelto">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Due Soon Books Section -->
                <?php if (!empty($due_soon_books)): ?>
                <div class="content-card">
                    <h4 class="section-title text-warning">
                        <i class="fas fa-clock me-2"></i>Próximos a Vencer (<?php echo count($due_soon_books); ?>)
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Libro</th>
                                    <th><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th><i class="fas fa-calendar me-2"></i>Fecha Límite</th>
                                    <th><i class="fas fa-clock me-2"></i>Días Restantes</th>
                                    <th><i class="fas fa-redo me-2"></i>Renovaciones</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($due_soon_books as $borrow): ?>
                                    <tr class="table-warning">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $borrow['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($borrow['title']); ?>" 
                                                     style="width: 40px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                                                    <br><small class="text-muted">por <?php echo htmlspecialchars($borrow['author']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($borrow['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-warning">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($borrow['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark fs-6">
                                                <?php echo $borrow['days_until_due']; ?> días
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $borrow['renewal_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="sendReminder(<?php echo $borrow['id']; ?>)" 
                                                        title="Enviar recordatorio">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="extendDueDate(<?php echo $borrow['id']; ?>, '<?php echo $borrow['due_date']; ?>')" 
                                                        title="Extender fecha">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="markReturned(<?php echo $borrow['id']; ?>, <?php echo $borrow['book_id']; ?>, '<?php echo htmlspecialchars($borrow['title']); ?>')" 
                                                        title="Marcar como devuelto">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Active Borrows Section -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="section-title mb-0">
                            <i class="fas fa-list me-2"></i>Todos los Préstamos Activos (<?php echo count($active_borrows); ?>)
                        </h4>
                        <div>
                            <input type="text" class="form-control" placeholder="Buscar préstamos..." id="searchBorrows" style="width: 250px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Libro</th>
                                    <th><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th><i class="fas fa-calendar me-2"></i>Prestado</th>
                                    <th><i class="fas fa-calendar-check me-2"></i>Vence</th>
                                    <th><i class="fas fa-hourglass me-2"></i>Tiempo</th>
                                    <th><i class="fas fa-redo me-2"></i>Renovaciones</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_borrows as $borrow): ?>
                                    <tr class="borrow-row" 
                                        data-title="<?php echo strtolower($borrow['title']); ?>"
                                        data-user="<?php echo strtolower($borrow['full_name']); ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $borrow['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($borrow['title']); ?>" 
                                                     style="width: 40px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                                                    <br><small class="text-muted">por <?php echo htmlspecialchars($borrow['author']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($borrow['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($borrow['borrow_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?php echo $borrow['days_until_due'] <= 0 ? 'text-danger' : ($borrow['days_until_due'] <= 3 ? 'text-warning' : 'text-success'); ?>">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($borrow['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($borrow['days_until_due'] <= 0): ?>
                                                <span class="badge bg-danger">Vencido</span>
                                            <?php elseif ($borrow['days_until_due'] <= 3): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $borrow['days_until_due']; ?> días</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $borrow['days_until_due']; ?> días</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $borrow['renewal_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="sendReminder(<?php echo $borrow['id']; ?>)" 
                                                        title="Enviar recordatorio">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="extendDueDate(<?php echo $borrow['id']; ?>, '<?php echo $borrow['due_date']; ?>')" 
                                                        title="Extender fecha">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="markReturned(<?php echo $borrow['id']; ?>, <?php echo $borrow['book_id']; ?>, '<?php echo htmlspecialchars($borrow['title']); ?>')" 
                                                        title="Marcar como devuelto">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Extend Due Date Modal -->
    <div class="modal fade" id="extendDueDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>Extender Fecha de Devolución
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="extend_due_date">
                        <input type="hidden" name="borrow_id" id="extend_borrow_id">
                        
                        <div class="mb-3">
                            <label for="current_due_date" class="form-label">Fecha Actual de Vencimiento</label>
                            <input type="date" class="form-control" id="current_due_date" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_due_date" class="form-label">Nueva Fecha de Vencimiento *</label>
                            <input type="date" class="form-control" name="new_due_date" id="new_due_date" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Esta acción incrementará el contador de renovaciones del préstamo.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Extender Fecha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setupSearch();
            animateCounters();
            
            // Update overdue status automatically
            updateOverdueStatus();
        });

        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('searchBorrows');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.borrow-row');
                    
                    rows.forEach(row => {
                        const title = row.dataset.title || '';
                        const user = row.dataset.user || '';
                        
                        if (title.includes(searchTerm) || user.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        }

        // Send reminder
        function sendReminder(borrowId) {
            if (confirm('¿Enviar recordatorio de devolución al usuario?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="send_reminder">
                    <input type="hidden" name="borrow_id" value="${borrowId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Extend due date
        function extendDueDate(borrowId, currentDueDate) {
            document.getElementById('extend_borrow_id').value = borrowId;
            document.getElementById('current_due_date').value = currentDueDate;
            
            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('new_due_date').min = tomorrow.toISOString().split('T')[0];
            
            // Set default new date to 7 days from current due date
            const defaultDate = new Date(currentDueDate);
            defaultDate.setDate(defaultDate.getDate() + 7);
            document.getElementById('new_due_date').value = defaultDate.toISOString().split('T')[0];
            
            new bootstrap.Modal(document.getElementById('extendDueDateModal')).show();
        }

        // Mark as returned
        function markReturned(borrowId, bookId, bookTitle) {
            if (confirm(`¿Marcar "${bookTitle}" como devuelto?\n\nEsta acción actualizará el inventario del libro.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="mark_returned">
                    <input type="hidden" name="borrow_id" value="${borrowId}">
                    <input type="hidden" name="book_id" value="${bookId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Send bulk reminders
        function sendBulkReminders(type) {
            const message = type === 'overdue' ? 
                '¿Enviar recordatorios a todos los usuarios con préstamos vencidos?' :
                '¿Enviar recordatorios a todos los usuarios con préstamos próximos a vencer?';
                
            if (confirm(message)) {
                alert('Funcionalidad de recordatorios masivos - En desarrollo');
            }
        }

        // Export overdue
        function exportOverdue() {
            if (confirm('¿Exportar lista de préstamos vencidos a Excel?')) {
                alert('Funcionalidad de exportación - En desarrollo');
            }
        }

        // Generate return report
        function generateReturnReport() {
            window.location.href = 'reports.php#returns';
        }

        // Update overdue status
        function updateOverdueStatus() {
            // This would typically be done server-side, but we can add visual indicators
            const today = new Date();
            const rows = document.querySelectorAll('.borrow-row');
            
            rows.forEach(row => {
                // This is just for visual enhancement
                // The actual status update should be handled server-side
            });
        }

        // Animate counters
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current);
                }, 50);
            });
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize animations
            animateCounters();
            updateOverdueStatus();
        });
    </script>
</div>
</body>
</html>
