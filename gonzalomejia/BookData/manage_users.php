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
            case 'toggle_status':
                $user_id = $_POST['user_id'];
                $new_status = $_POST['new_status'];
                
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $user_id])) {
                    $status_text = $new_status === 'active' ? 'activado' : 'desactivado';
                    $message = "Usuario {$status_text} exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = 'Error al cambiar el estado del usuario.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                
                // Check if user has active borrows
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND status IN ('active', 'overdue')");
                $stmt->execute([$user_id]);
                $active_borrows = $stmt->fetchColumn();
                
                if ($active_borrows > 0) {
                    $message = 'No se puede eliminar el usuario porque tiene préstamos activos.';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        $message = 'Usuario eliminado exitosamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al eliminar el usuario.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'update_role':
                $user_id = $_POST['user_id'];
                $new_role = $_POST['new_role'];
                
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$new_role, $user_id])) {
                    $message = 'Rol del usuario actualizado exitosamente.';
                    $message_type = 'success';
                } else {
                    $message = 'Error al actualizar el rol del usuario.';
                    $message_type = 'danger';
                }
                break;
                
            case 'reset_password':
                $user_id = $_POST['user_id'];
                $new_password = password_hash('password', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$new_password, $user_id])) {
                    $message = 'Contraseña restablecida a "password".';
                    $message_type = 'success';
                } else {
                    $message = 'Error al restablecer la contraseña.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users
FROM users");
$stmt->execute();
$stats = $stmt->fetch();

// Get all users with their borrowing statistics
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(bb.id) as total_borrows,
           SUM(CASE WHEN bb.status = 'active' THEN 1 ELSE 0 END) as active_borrows,
           SUM(CASE WHEN bb.status = 'overdue' THEN 1 ELSE 0 END) as overdue_borrows,
           MAX(bb.borrow_date) as last_borrow_date
    FROM users u 
    LEFT JOIN borrowed_books bb ON u.id = bb.user_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

$page_title = 'Gestión de Usuarios';
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
                        <i class="fas fa-users me-3"></i>Gestión de Usuarios
                    </h1>
                    <p class="text-muted">Administra todos los usuarios del sistema</p>
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
                            <i class="fas fa-users" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Usuarios</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card active">
                            <i class="fas fa-user-check" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Usuarios Activos</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card admins">
                            <i class="fas fa-user-shield" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['admins']; ?></div>
                            <div class="stat-label">Administradores</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card users">
                            <i class="fas fa-user" style="font-size: 2rem; opacity: 0.8; margin-bottom: 10px;"></i>
                            <div class="stat-value"><?php echo $stats['regular_users']; ?></div>
                            <div class="stat-label">Usuarios Regulares</div>
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
                            <button class="btn btn-success me-2" onclick="addNewUser()">
                                <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                            </button>
                            <button class="btn btn-info me-2" onclick="exportUsers()">
                                <i class="fas fa-download me-2"></i>Exportar Lista
                            </button>
                            <button class="btn btn-warning me-2" onclick="sendWelcomeEmails()">
                                <i class="fas fa-envelope me-2"></i>Enviar Bienvenida
                            </button>
                            <button class="btn btn-secondary" onclick="bulkActions()">
                                <i class="fas fa-cogs me-2"></i>Acciones Masivas
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
                                <input type="text" class="form-control" placeholder="Buscar usuarios..." id="searchUsers">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterRole">
                                <option value="">Todos los roles</option>
                                <option value="admin">Administradores</option>
                                <option value="user">Usuarios</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterStatus">
                                <option value="">Todos los estados</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
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

                <!-- Users Table -->
                <div class="content-card">
                    <h4 class="section-title">
                        <i class="fas fa-list me-2"></i>Lista de Usuarios
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-user-tag me-2"></i>Rol</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Estado</th>
                                    <th><i class="fas fa-book me-2"></i>Préstamos</th>
                                    <th><i class="fas fa-calendar me-2"></i>Registro</th>
                                    <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="user-row" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                    <br><small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-envelope text-muted me-2"></i>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <i class="fas <?php echo $user['role'] === 'admin' ? 'fa-shield-alt' : 'fa-user'; ?> me-1"></i>
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                                <i class="fas <?php echo $user['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="borrow-stats">
                                                <span class="stat">Total: <?php echo $user['total_borrows']; ?></span>
                                                <?php if ($user['active_borrows'] > 0): ?>
                                                    <span class="stat active">Activos: <?php echo $user['active_borrows']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($user['overdue_borrows'] > 0): ?>
                                                    <span class="stat overdue">Vencidos: <?php echo $user['overdue_borrows']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar text-muted me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="action-btn btn-edit" 
                                                        onclick="editUser(<?php echo $user['id']; ?>)"
                                                        title="Editar usuario">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn btn-toggle" 
                                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                        title="Cambiar estado">
                                                    <i class="fas fa-toggle-<?php echo $user['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                                </button>
                                                <button class="action-btn btn-reset" 
                                                        onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                        title="Restablecer contraseña">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="action-btn btn-delete" 
                                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', <?php echo $user['active_borrows']; ?>)"
                                                            title="Eliminar usuario">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Editar Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="user_role" class="form-label">Rol del Usuario</label>
                            <select class="form-select" name="new_role" id="user_role" required>
                                <option value="user">Usuario Regular</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Los administradores tienen acceso completo al sistema.
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
            const searchInput = document.getElementById('searchUsers');
            const roleFilter = document.getElementById('filterRole');
            const statusFilter = document.getElementById('filterStatus');
            const dateFromFilter = document.getElementById('dateFrom');

            [searchInput, roleFilter, statusFilter, dateFromFilter].forEach(element => {
                if (element) {
                    element.addEventListener('input', filterUsers);
                    element.addEventListener('change', filterUsers);
                }
            });
        }

        // Filter users
        function filterUsers() {
            const searchTerm = document.getElementById('searchUsers')?.value.toLowerCase() || '';
            const roleFilter = document.getElementById('filterRole')?.value || '';
            const statusFilter = document.getElementById('filterStatus')?.value || '';
            
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const userName = row.querySelector('strong').textContent.toLowerCase();
                const userEmail = row.querySelectorAll('td')[1].textContent.toLowerCase();
                const userRole = row.dataset.role;
                const userStatus = row.dataset.status;

                const matchesSearch = userName.includes(searchTerm) || userEmail.includes(searchTerm);
                const matchesRole = !roleFilter || userRole === roleFilter;
                const matchesStatus = !statusFilter || userStatus === statusFilter;

                if (matchesSearch && matchesRole && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchUsers').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('dateFrom').value = '';
            filterUsers();
        }

        // Edit user
        function editUser(userId) {
            document.getElementById('edit_user_id').value = userId;
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        // Toggle user status
        function toggleUserStatus(userId, currentStatus, userName) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activar' : 'desactivar';
            
            if (confirm(`¿Confirmar ${action} al usuario "${userName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Reset password
        function resetPassword(userId, userName) {
            if (confirm(`¿Restablecer la contraseña del usuario "${userName}" a "password"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete user
        function deleteUser(userId, userName, activeBorrows) {
            if (activeBorrows > 0) {
                alert(`No se puede eliminar a "${userName}" porque tiene ${activeBorrows} préstamos activos.`);
                return;
            }
            
            if (confirm(`¿ELIMINAR PERMANENTEMENTE al usuario "${userName}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Add new user
        function addNewUser() {
            window.location.href = 'register.php?admin=1';
        }

        // Export users
        function exportUsers() {
            if (confirm('¿Exportar la lista de usuarios a Excel?')) {
                window.location.href = 'export_users.php';
            }
        }

        // Send welcome emails
        function sendWelcomeEmails() {
            if (confirm('¿Enviar emails de bienvenida a todos los usuarios nuevos?')) {
                alert('Emails de bienvenida enviados exitosamente');
            }
        }

        // Bulk actions
        function bulkActions() {
            alert('Funcionalidad de acciones masivas - En desarrollo');
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
