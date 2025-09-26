<?php
// views/profile.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=" . urlencode("Debe iniciar sesión para acceder"));
    exit();
}

$page_title = "Mi Perfil - SysPlanner";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../controllers/UserController.php';

$userController = new UserController();
$message = '';
$error = '';

// Procesar cambio de información personal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $result = $userController->update($_SESSION['user_id'], $_POST);
            if ($result['success']) {
                $message = $result['message'];
                // Actualizar datos de sesión
                $_SESSION['user_nombre'] = $_POST['nombre_completo'];
                $_SESSION['user_email'] = $_POST['email'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'change_password':
            $result = $userController->changePassword(
                $_SESSION['user_id'], 
                $_POST['current_password'], 
                $_POST['new_password'], 
                $_POST['confirm_password']
            );
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// Obtener datos del usuario
$user_data = $userController->edit($_SESSION['user_id']);
$user = $user_data['user'];
$departments = $user_data['departments'];

if (!$user) {
    header("Location: ../index.php?error=" . urlencode("Error al cargar datos del usuario"));
    exit();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Card de información del usuario -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    <span class="badge <?php echo $_SESSION['user_rol'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?> mb-2">
                        <?php echo ucfirst($_SESSION['user_rol']); ?>
                    </span>
                    
                    <?php if (isset($_SESSION['user_departamento']) && $_SESSION['user_departamento']): ?>
                        <?php
                        // Obtener nombre del departamento
                        $departments->execute(); // Re-ejecutar para obtener datos frescos
                        while ($dept = $departments->fetch(PDO::FETCH_ASSOC)) {
                            if ($dept['id'] == $_SESSION['user_departamento']) {
                                echo '<p class="text-muted"><i class="fas fa-building me-1"></i>' . htmlspecialchars($dept['nombre']) . '</p>';
                                break;
                            }
                        }
                        ?>
                    <?php endif; ?>
                    
                    <p class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Miembro desde <?php echo date('F Y', strtotime($user->fecha_registro)); ?>
                    </p>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Mis Estadísticas</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener estadísticas del usuario
                    require_once '../controllers/ReservationController.php';
                    $reservationController = new ReservationController();
                    $user_stats = $reservationController->getUserReservations($_SESSION['user_id']);
                    
                    $total_reservas = 0;
                    $reservas_activas = 0;
                    $reservas_completadas = 0;
                    
                    while ($reservation = $user_stats->fetch(PDO::FETCH_ASSOC)) {
                        $total_reservas++;
                        if ($reservation['estado'] === 'confirmada' || $reservation['estado'] === 'pendiente') {
                            $reservas_activas++;
                        } elseif ($reservation['estado'] === 'finalizada') {
                            $reservas_completadas++;
                        }
                    }
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-primary"><?php echo $total_reservas; ?></h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-success"><?php echo $reservas_activas; ?></h4>
                                <small class="text-muted">Activas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info"><?php echo $reservas_completadas; ?></h4>
                            <small class="text-muted">Completadas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs para perfil -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" 
                            type="button" role="tab" aria-controls="info-pane" aria-selected="true">
                        <i class="fas fa-user me-1"></i>Información Personal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" 
                            type="button" role="tab" aria-controls="password-pane" aria-selected="false">
                        <i class="fas fa-lock me-1"></i>Cambiar Contraseña
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Información Personal -->
                <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-edit me-2"></i>Editar Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                                   value="<?php echo htmlspecialchars($user->nombre_completo); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user->email); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rol" class="form-label">Rol</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user->rol); ?>" readonly>
                                            <input type="hidden" name="rol" value="<?php echo $user->rol; ?>">
                                            <div class="form-text">Solo los administradores pueden cambiar roles</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="departamento_id" class="form-label">Departamento</label>
                                            <?php if ($_SESSION['user_rol'] === 'admin'): ?>
                                                <select class="form-control" id="departamento_id" name="departamento_id">
                                                    <option value="">Sin departamento</option>
                                                    <?php 
                                                    $departments->execute(); // Re-ejecutar
                                                    while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): 
                                                    ?>
                                                        <option value="<?php echo $dept['id']; ?>" 
                                                                <?php echo $user->departamento_id == $dept['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($dept['nombre']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            <?php else: ?>
                                                <?php
                                                $departments->execute();
                                                $dept_name = 'Sin asignar';
                                                while ($dept = $departments->fetch(PDO::FETCH_ASSOC)) {
                                                    if ($dept['id'] == $user->departamento_id) {
                                                        $dept_name = $dept['nombre'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($dept_name); ?>" readonly>
                                                <input type="hidden" name="departamento_id" value="<?php echo $user->departamento_id; ?>">
                                                <div class="form-text">Solo los administradores pueden cambiar departamentos</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="activo" value="<?php echo $user->activo ? '1' : '0'; ?>">
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Cambiar Contraseña -->
                <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Contraseña Actual *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Nueva Contraseña *</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   minlength="6" required>
                                            <div class="form-text">Mínimo 6 caracteres</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña *</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   minlength="6" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key me-1"></i>Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value && this.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
    } else {
        confirmPassword.setCustomValidity('');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
