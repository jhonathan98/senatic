<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=profile");
    exit;
}

require_once 'config.php';
require_once 'includes/functions.php';

$user = $_SESSION['user'];
$message = '';
$success = '';

// Obtener información completa del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$user_data = $stmt->fetch();

// Procesar actualización de perfil
if ($_POST && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Token de seguridad inválido.";
    } else {
        if ($_POST['action'] === 'update_profile') {
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            
            // Validaciones
            $errors = [];
            
            if (empty($name) || strlen($name) < 2) {
                $errors[] = "El nombre es obligatorio y debe tener al menos 2 caracteres.";
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "El email no es válido.";
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $email ?: null, $phone ?: null, $user['id']]);
                    
                    // Actualizar sesión
                    $_SESSION['user']['name'] = $name;
                    
                    logActivity($pdo, $user['id'], 'profile_updated', 'Actualizó información de perfil');
                    $success = "Perfil actualizado correctamente.";
                    
                    // Recargar datos
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user_data = $stmt->fetch();
                    
                } catch (PDOException $e) {
                    error_log("Error actualizando perfil: " . $e->getMessage());
                    $message = "Error interno. Por favor, inténtelo más tarde.";
                }
            } else {
                $message = "Errores encontrados:<br>• " . implode("<br>• ", $errors);
            }
            
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validaciones
            $errors = [];
            
            if (empty($current_password)) {
                $errors[] = "La contraseña actual es obligatoria.";
            } elseif (!password_verify($current_password, $user_data['password'])) {
                $errors[] = "La contraseña actual es incorrecta.";
            }
            
            if (empty($new_password)) {
                $errors[] = "La nueva contraseña es obligatoria.";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "La nueva contraseña debe tener al menos 6 caracteres.";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "Las contraseñas no coinciden.";
            }
            
            if (empty($errors)) {
                try {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$password_hash, $user['id']]);
                    
                    logActivity($pdo, $user['id'], 'password_changed', 'Cambió su contraseña');
                    $success = "Contraseña cambiada correctamente.";
                    
                } catch (PDOException $e) {
                    error_log("Error cambiando contraseña: " . $e->getMessage());
                    $message = "Error interno. Por favor, inténtelo más tarde.";
                }
            } else {
                $message = "Errores encontrados:<br>• " . implode("<br>• ", $errors);
            }
        }
    }
}

// Obtener estadísticas del usuario
$stats = [];
if ($user['role'] === 'teacher') {
    $stats['students'] = $pdo->prepare("SELECT COUNT(*) as count FROM teacher_students WHERE teacher_id = ?");
    $stats['students']->execute([$user['id']]);
    $stats['students'] = $stats['students']->fetch()['count'];
    
    $stats['attendance_records'] = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE teacher_id = ?");
    $stats['attendance_records']->execute([$user['id']]);
    $stats['attendance_records'] = $stats['attendance_records']->fetch()['count'];
    
} elseif ($user['role'] === 'parent') {
    $stats['children'] = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE parent_id = ?");
    $stats['children']->execute([$user['id']]);
    $stats['children'] = $stats['children']->fetch()['count'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-circle text-primary"></i> Mi Perfil</h2>
    <small class="text-muted">Gestiona tu información personal</small>
</div>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= $message ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="row">
    <!-- Información del perfil -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle" style="font-size: 5rem; color: #0a2e67;"></i>
                </div>
                <h4><?= htmlspecialchars($user_data['name']) ?></h4>
                <p class="text-muted mb-2"><?= ucfirst($user_data['role']) ?></p>
                <p class="text-muted"><strong>Usuario:</strong> <?= htmlspecialchars($user_data['username']) ?></p>
                <p class="text-muted"><strong>Documento:</strong> <?= htmlspecialchars($user_data['document_number']) ?></p>
                
                <?php if (!empty($user_data['email'])): ?>
                    <p class="text-muted"><strong>Email:</strong> <?= htmlspecialchars($user_data['email']) ?></p>
                <?php endif; ?>
                
                <?php if (!empty($user_data['phone'])): ?>
                    <p class="text-muted"><strong>Teléfono:</strong> <?= htmlspecialchars($user_data['phone']) ?></p>
                <?php endif; ?>
                
                <small class="text-muted">
                    <strong>Miembro desde:</strong> <?= formatDate($user_data['created_at']) ?>
                </small>
            </div>
        </div>
        
        <!-- Estadísticas del usuario -->
        <?php if (!empty($stats)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Estadísticas</h6>
            </div>
            <div class="card-body">
                <?php if ($user['role'] === 'teacher'): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Estudiantes asignados:</span>
                        <span class="badge bg-primary"><?= $stats['students'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Registros de asistencia:</span>
                        <span class="badge bg-success"><?= $stats['attendance_records'] ?></span>
                    </div>
                <?php elseif ($user['role'] === 'parent'): ?>
                    <div class="d-flex justify-content-between">
                        <span>Hijos registrados:</span>
                        <span class="badge bg-primary"><?= $stats['children'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Formularios de edición -->
    <div class="col-lg-8">
        <!-- Actualizar información personal -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Actualizar Información Personal</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($user_data['name']) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user_data['email'] ?? '') ?>">
                            <div class="form-text">Opcional - Para notificaciones</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                            <div class="form-text">Opcional - Para contacto</div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Actualizar Información
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cambiar contraseña -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Cambiar Contraseña</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña actual *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nueva contraseña *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar contraseña *</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Actividad reciente -->
<?php
try {
    $recent_activity = $pdo->prepare("
        SELECT action, description, created_at 
        FROM activity_log 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_activity->execute([$user['id']]);
    $activities = $recent_activity->fetchAll();
    
    if (!empty($activities)):
?>
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Actividad Reciente</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span></td>
                        <td><?= htmlspecialchars($activity['description']) ?></td>
                        <td><small><?= formatDateTime($activity['created_at']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php 
    endif;
} catch (PDOException $e) {
    // La tabla activity_log no existe, ignorar
}
?>
