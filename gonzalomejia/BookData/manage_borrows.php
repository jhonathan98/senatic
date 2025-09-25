<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

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
            case 'return_book':
                $borrow_id = $_POST['borrow_id'];
                if (return_book($borrow_id)) {
                    $message = 'Libro devuelto exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al procesar la devolución.';
                    $message_type = 'danger';
                }
                break;
            
            case 'extend_due_date':
                $borrow_id = $_POST['borrow_id'];
                $new_due_date = $_POST['new_due_date'];
                
                $stmt = $pdo->prepare("UPDATE borrowed_books SET due_date = ? WHERE id = ?");
                if ($stmt->execute([$new_due_date, $borrow_id])) {
                    $message = 'Fecha de devolución extendida exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al extender la fecha.';
                    $message_type = 'danger';
                }
                break;
                
            case 'mark_overdue':
                $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'overdue' WHERE due_date < CURDATE() AND status = 'active'");
                $affected = $stmt->execute();
                $count = $stmt->rowCount();
                
                $message = "Se marcaron {$count} préstamos como vencidos.";
                $message_type = 'info';
                break;
        }
    }
}

// Get borrowing statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned
FROM borrowed_books");
$stmt->execute();
$stats = $stmt->fetch();

// Get all borrowings with user and book information
$stmt = $pdo->prepare("
    SELECT bb.*, u.full_name, u.email, b.title, b.author, b.cover_image,
           DATEDIFF(bb.due_date, CURDATE()) as days_until_due
    FROM borrowed_books bb 
    JOIN users u ON bb.user_id = u.id 
    JOIN books b ON bb.book_id = b.id 
    ORDER BY 
        CASE 
            WHEN bb.status = 'overdue' THEN 1
            WHEN bb.status = 'active' AND bb.due_date < CURDATE() THEN 2
            WHEN bb.status = 'active' THEN 3
            ELSE 4
        END,
        bb.due_date ASC
");
$stmt->execute();
$borrowings = $stmt->fetchAll();

$page_title = 'Gestión de Préstamos';
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
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="manage-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-handshake me-3"></i>Gestión de Préstamos
                    </h1>
                    <p class="text-muted">Administra todos los préstamos de libros del sistema</p>
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
                        <div class="stat-card total">
                            <i class="fas fa-list-ol" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Préstamos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card active">
                            <i class="fas fa-clock" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Préstamos Activos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card overdue">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['overdue']; ?></div>
                            <div class="stat-label">Préstamos Vencidos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card returned">
                            <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['returned']; ?></div>
                            <div class="stat-label">Préstamos Devueltos</div>
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
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="mark_overdue">
                                <button type="submit" class="btn btn-warning me-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Marcar Vencidos
                                </button>
                            </form>
                            <button class="btn btn-info me-2" onclick="exportBorrows()">
                                <i class="fas fa-download me-2"></i>Exportar Lista
                            </button>
                            <button class="btn btn-secondary" onclick="sendReminders()">
                                <i class="fas fa-envelope me-2"></i>Enviar Recordatorios
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Buscar usuario o libro..." id="searchBorrows">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterStatus">
                                <option value="">Todos los estados</option>
                                <option value="active">Activos</option>
                                <option value="overdue">Vencidos</option>
                                <option value="returned">Devueltos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterDueDate">
                                <option value="">Todas las fechas</option>
                                <option value="today">Vence hoy</option>
                                <option value="tomorrow">Vence mañana</option>
                                <option value="week">Vence esta semana</option>
                                <option value="overdue">Ya vencido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">Desde</span>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Borrowings Table -->
                <div class="content-card">
                    <h4 class="section-title">
                        <i class="fas fa-list me-2"></i>Lista de Préstamos
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th><i class="fas fa-book me-2"></i>Libro</th>
                                    <th><i class="fas fa-calendar me-2"></i>Fecha Préstamo</th>
                                    <th><i class="fas fa-calendar-check me-2"></i>Fecha Vencimiento</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Estado</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowings as $borrow): ?>
                                    <tr class="borrow-row" data-status="<?php echo $borrow['status']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($borrow['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($borrow['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="book-info">
                                                <img src="<?php echo $borrow['cover_image'] ?: 'assets/images/book-placeholder.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($borrow['title']); ?>" class="book-cover">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($borrow['author']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar text-muted me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($borrow['borrow_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-check text-muted me-2"></i>
                                                <span class="due-date <?php 
                                                    if ($borrow['days_until_due'] < 0) echo 'due-overdue';
                                                    elseif ($borrow['days_until_due'] <= 2) echo 'due-soon';
                                                ?>">
                                                    <?php echo date('d/m/Y', strtotime($borrow['due_date'])); ?>
                                                    <?php if ($borrow['status'] === 'active'): ?>
                                                        <br><small>
                                                            <?php 
                                                            if ($borrow['days_until_due'] < 0) {
                                                                echo abs($borrow['days_until_due']) . ' días vencido';
                                                            } elseif ($borrow['days_until_due'] == 0) {
                                                                echo 'Vence hoy';
                                                            } else {
                                                                echo $borrow['days_until_due'] . ' días restantes';
                                                            }
                                                            ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $borrow['status']; ?>">
                                                <?php 
                                                $icons = [
                                                    'active' => 'fas fa-clock',
                                                    'overdue' => 'fas fa-exclamation-triangle', 
                                                    'returned' => 'fas fa-check-circle'
                                                ];
                                                $icon = $icons[$borrow['status']] ?? 'fas fa-question';
                                                ?>
                                                <i class="<?php echo $icon; ?> me-1"></i>
                                                <?php echo ucfirst($borrow['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($borrow['status'] === 'active' || $borrow['status'] === 'overdue'): ?>
                                                    <button class="action-btn btn-return" 
                                                            onclick="returnBook(<?php echo $borrow['id']; ?>, '<?php echo htmlspecialchars($borrow['title']); ?>')"
                                                            title="Marcar como devuelto">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    <button class="action-btn btn-extend" 
                                                            onclick="extendDueDate(<?php echo $borrow['id']; ?>)"
                                                            title="Extender fecha">
                                                        <i class="fas fa-calendar-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn btn-view" 
                                                        onclick="viewBorrowDetails(<?php echo $borrow['id']; ?>)"
                                                        title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
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
    </div>

    <!-- Extend Due Date Modal -->
    <div class="modal fade" id="extendDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>Extender Fecha de Vencimiento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="extend_due_date">
                        <input type="hidden" name="borrow_id" id="extend_borrow_id">
                        <div class="mb-3">
                            <label for="new_due_date" class="form-label">Nueva Fecha de Vencimiento</label>
                            <input type="date" class="form-control" name="new_due_date" id="new_due_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize filters and interactions
        document.addEventListener('DOMContentLoaded', function() {
            setupFilters();
            animateCounters();
        });

        // Setup filters
        function setupFilters() {
            const searchInput = document.getElementById('searchBorrows');
            const statusFilter = document.getElementById('filterStatus');
            const dueDateFilter = document.getElementById('filterDueDate');
            const dateFromFilter = document.getElementById('dateFrom');

            [searchInput, statusFilter, dueDateFilter, dateFromFilter].forEach(element => {
                if (element) {
                    element.addEventListener('input', filterBorrows);
                    element.addEventListener('change', filterBorrows);
                }
            });
        }

        // Filter borrowings
        function filterBorrows() {
            const searchTerm = document.getElementById('searchBorrows')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('filterStatus')?.value || '';
            const dueDateFilter = document.getElementById('filterDueDate')?.value || '';
            const dateFromFilter = document.getElementById('dateFrom')?.value || '';
            
            const rows = document.querySelectorAll('.borrow-row');

            rows.forEach(row => {
                const userName = row.querySelector('strong').textContent.toLowerCase();
                const bookTitle = row.querySelectorAll('strong')[1]?.textContent.toLowerCase() || '';
                const status = row.dataset.status;
                const dueDate = row.querySelector('.due-date')?.textContent || '';

                const matchesSearch = userName.includes(searchTerm) || bookTitle.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                // Add more complex filtering logic here for due dates if needed
                const matchesDueDate = true; // Simplified for now

                if (matchesSearch && matchesStatus && matchesDueDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchBorrows').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDueDate').value = '';
            document.getElementById('dateFrom').value = '';
            filterBorrows();
        }

        // Return book
        function returnBook(borrowId, bookTitle) {
            if (confirm(`¿Confirmar la devolución del libro "${bookTitle}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="return_book">
                    <input type="hidden" name="borrow_id" value="${borrowId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Extend due date
        function extendDueDate(borrowId) {
            document.getElementById('extend_borrow_id').value = borrowId;
            new bootstrap.Modal(document.getElementById('extendDateModal')).show();
        }

        // View borrow details
        function viewBorrowDetails(borrowId) {
            // Implement view details functionality
            alert('Ver detalles del préstamo ID: ' + borrowId);
        }

        // Export borrowings
        function exportBorrows() {
            if (confirm('¿Exportar la lista de préstamos a Excel?')) {
                window.location.href = 'export_borrows.php';
            }
        }

        // Send reminders
        function sendReminders() {
            if (confirm('¿Enviar recordatorios a usuarios con préstamos próximos a vencer?')) {
                // Implement send reminders functionality
                alert('Recordatorios enviados exitosamente');
            }
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
    </script>
</body>
</html>
