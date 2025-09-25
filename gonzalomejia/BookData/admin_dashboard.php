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
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="dashboard-container animate-in">
                <!-- Header del Dashboard -->
                <div class="dashboard-header">
                    <h1 class="dashboard-title">
                        <i class="fas fa-chart-line me-3"></i>Panel de Administración
                    </h1>
                    <p class="dashboard-subtitle">Gestiona tu biblioteca de forma eficiente</p>
                </div>

                <!-- Acciones Rápidas -->
                <div class="quick-actions">
                    <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                    <a href="add_book.php" class="quick-action-btn">
                        <i class="fas fa-plus me-2"></i>Agregar Libro
                    </a>
                    <a href="add_user.php" class="quick-action-btn">
                        <i class="fas fa-user-plus me-2"></i>Agregar Usuario
                    </a>
                    <a href="manage_categories.php" class="quick-action-btn">
                        <i class="fas fa-tags me-2"></i>Gestionar Categorías
                    </a>
                    <a href="reports.php" class="quick-action-btn">
                        <i class="fas fa-chart-bar me-2"></i>Reportes
                    </a>
                </div>
                
                <!-- Tarjetas de Estadísticas -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card users animate-in" style="animation-delay: 0.1s">
                            <i class="fas fa-users stat-icon"></i>
                            <div class="stat-label">Usuarios Registrados</div>
                            <div class="stat-value"><?php echo $total_users; ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card books animate-in" style="animation-delay: 0.2s">
                            <i class="fas fa-book stat-icon"></i>
                            <div class="stat-label">Libros Disponibles</div>
                            <div class="stat-value"><?php echo $total_books; ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card active animate-in" style="animation-delay: 0.3s">
                            <i class="fas fa-bookmark stat-icon"></i>
                            <div class="stat-label">Préstamos Activos</div>
                            <div class="stat-value"><?php echo $active_borrows; ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card overdue animate-in" style="animation-delay: 0.4s">
                            <i class="fas fa-exclamation-triangle stat-icon"></i>
                            <div class="stat-label">Préstamos Vencidos</div>
                            <div class="stat-value"><?php echo $overdue_borrows; ?></div>
                        </div>
                    </div>
                </div>
            
                <!-- Libros Populares y Actividad Reciente -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="content-card animate-in" style="animation-delay: 0.5s">
                            <h4 class="section-title">
                                <i class="fas fa-fire me-2"></i>Libros Más Populares
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-book me-2"></i>Título</th>
                                            <th><i class="fas fa-chart-bar me-2"></i>Préstamos</th>
                                            <th><i class="fas fa-eye me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($popular_books as $book): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo $book['borrow_count']; ?> préstamos
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="book_detail.php?id=<?php echo $book['id'] ?? '#'; ?>" class="action-btn btn-view">
                                                        <i class="fas fa-eye me-1"></i>Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($popular_books)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">
                                                    <i class="fas fa-info-circle me-2"></i>No hay datos disponibles
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content-card animate-in" style="animation-delay: 0.6s">
                            <h4 class="section-title">
                                <i class="fas fa-clock me-2"></i>Actividad Reciente
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-2"></i>Usuario</th>
                                            <th><i class="fas fa-book me-2"></i>Libro</th>
                                            <th><i class="fas fa-calendar me-2"></i>Fecha</th>
                                            <th><i class="fas fa-info-circle me-2"></i>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_activity as $activity): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                            <?php echo strtoupper(substr($activity['full_name'], 0, 1)); ?>
                                                        </div>
                                                        <small><?php echo htmlspecialchars($activity['full_name']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-truncate" style="max-width: 150px; display: block;">
                                                        <?php echo htmlspecialchars($activity['title'] ?? 'N/A'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($activity['borrow_date'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="activity-badge <?php echo $activity['status'] === 'active' ? 'activity-active' : ($activity['status'] === 'overdue' ? 'activity-overdue' : 'activity-returned'); ?>">
                                                        <?php 
                                                        $status_icons = [
                                                            'active' => 'fas fa-clock',
                                                            'overdue' => 'fas fa-exclamation-triangle',
                                                            'returned' => 'fas fa-check-circle'
                                                        ];
                                                        $icon = $status_icons[$activity['status']] ?? 'fas fa-question';
                                                        ?>
                                                        <i class="<?php echo $icon; ?> me-1"></i><?php echo ucfirst($activity['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($recent_activity)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    <i class="fas fa-info-circle me-2"></i>No hay actividad reciente
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            
                <!-- Lista de Usuarios -->
                <div class="row">
                    <div class="col-12">
                        <div class="content-card animate-in" style="animation-delay: 0.7s">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="section-title mb-0">
                                    <i class="fas fa-users me-2"></i>Gestión de Usuarios
                                </h4>
                                <div>
                                    <a href="add_user.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Nuevo Usuario
                                    </a>
                                    <button class="btn btn-outline-secondary ms-2" onclick="exportUsers()">
                                        <i class="fas fa-download me-2"></i>Exportar
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filtros -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" placeholder="Buscar usuarios..." id="searchUsers">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterRole">
                                        <option value="">Todos los roles</option>
                                        <option value="admin">Administradores</option>
                                        <option value="student">Estudiantes</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterStatus">
                                        <option value="">Todos los estados</option>
                                        <option value="active">Activos</option>
                                        <option value="inactive">Inactivos</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-2"></i>Usuario</th>
                                            <th><i class="fas fa-envelope me-2"></i>Correo</th>
                                            <th><i class="fas fa-calendar-plus me-2"></i>Registro</th>
                                            <th><i class="fas fa-book-reader me-2"></i>Préstamos</th>
                                            <th><i class="fas fa-user-tag me-2"></i>Rol</th>
                                            <th><i class="fas fa-toggle-on me-2"></i>Estado</th>
                                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT u.*, COUNT(bb.id) as total_borrows FROM users u LEFT JOIN borrowed_books bb ON u.id = bb.user_id AND bb.status = 'active' GROUP BY u.id ORDER BY u.created_at DESC LIMIT 10");
                                        $stmt->execute();
                                        $users = $stmt->fetchAll();
                                        
                                        foreach ($users as $user): ?>
                                            <tr class="user-row">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">
                                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong class="d-block"><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-envelope text-muted me-2"></i>
                                                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-calendar text-muted me-2"></i>
                                                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info rounded-pill">
                                                        <i class="fas fa-book me-1"></i><?php echo $user['total_borrows']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'user-shield' : 'graduation-cap'; ?> me-1"></i>
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               <?php echo ($user['status'] ?? 'active') === 'active' ? 'checked' : ''; ?>
                                                               onchange="toggleUserStatus(<?php echo $user['id']; ?>, this.checked)">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="action-btn btn-view" onclick="viewUser(<?php echo $user['id']; ?>)" title="Ver perfil">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="action-btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    Mostrando <?php echo count($users); ?> de <?php echo $total_users; ?> usuarios
                                </div>
                                <nav aria-label="Paginación de usuarios">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">Anterior</a>
                                        </li>
                                        <li class="page-item active">
                                            <a class="page-link" href="#">1</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">Siguiente</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicialización de animaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de contador para las estadísticas
            animateCounters();
            
            // Configurar filtros de búsqueda
            setupFilters();
            
            // Configurar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Animación de contadores
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current);
                }, 40);
            });
        }

        // Configurar filtros
        function setupFilters() {
            const searchInput = document.getElementById('searchUsers');
            const roleFilter = document.getElementById('filterRole');
            const statusFilter = document.getElementById('filterStatus');

            if (searchInput) {
                searchInput.addEventListener('input', filterUsers);
            }
            if (roleFilter) {
                roleFilter.addEventListener('change', filterUsers);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterUsers);
            }
        }

        // Filtrar usuarios
        function filterUsers() {
            const searchTerm = document.getElementById('searchUsers')?.value.toLowerCase() || '';
            const roleFilter = document.getElementById('filterRole')?.value || '';
            const statusFilter = document.getElementById('filterStatus')?.value || '';
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const name = row.querySelector('strong').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2) span').textContent.toLowerCase();
                const role = row.querySelector('.role-badge').textContent.toLowerCase().trim();
                const statusChecked = row.querySelector('.form-check-input').checked;
                const status = statusChecked ? 'active' : 'inactive';

                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesRole = !roleFilter || role.includes(roleFilter);
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesRole && matchesStatus) {
                    row.style.display = '';
                    row.classList.add('animate-in');
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Funciones de gestión de usuarios
        function viewUser(userId) {
            window.location.href = 'view_user.php?id=' + userId;
        }

        function editUser(userId) {
            BookData.confirm('¿Estás seguro de que quieres editar este usuario?', () => {
                window.location.href = 'edit_user.php?id=' + userId;
            });
        }
        
        function deleteUser(userId, userName) {
            const message = `¿Estás seguro de que quieres eliminar al usuario "${userName}"? Esta acción no se puede deshacer.`;
            BookData.confirm(message, () => {
                const btn = document.querySelector(`button[onclick*="deleteUser(${userId}"]`);
                const hideLoading = BookData.showLoading(btn);
                
                // Simular proceso de eliminación
                setTimeout(() => {
                    window.location.href = 'delete_user.php?id=' + userId;
                }, 1000);
            });
        }

        function toggleUserStatus(userId, isActive) {
            const status = isActive ? 'activo' : 'inactivo';
            const message = `¿Cambiar el estado del usuario a ${status}?`;
            
            BookData.confirm(message, () => {
                // Aquí iría la llamada AJAX para cambiar el estado
                window.location.href = `toggle_user_status.php?id=${userId}&status=${isActive ? 'active' : 'inactive'}`;
            });
        }

        function exportUsers() {
            BookData.confirm('¿Exportar la lista de usuarios a Excel?', () => {
                window.location.href = 'export_users.php';
            });
        }

        // Efecto hover mejorado para las tarjetas de estadísticas
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Efecto de pulso para notificaciones importantes
        if (<?php echo $overdue_borrows; ?> > 0) {
            const overdueCard = document.querySelector('.stat-card.overdue');
            if (overdueCard) {
                setInterval(() => {
                    overdueCard.style.boxShadow = '0 15px 35px rgba(255, 107, 107, 0.5)';
                    setTimeout(() => {
                        overdueCard.style.boxShadow = '0 8px 25px rgba(255, 107, 107, 0.3)';
                    }, 500);
                }, 3000);
            }
        }
    </script>
</body>
</html>