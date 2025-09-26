<?php
// views/admin/edit_user.php
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php?error=" . urlencode("No tiene permisos para acceder a esta página"));
    exit();
}

$page_title = "Editar Usuario - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/UserController.php';

$userController = new UserController();
$message = '';
$error = '';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: manage_users.php?error=" . urlencode("ID de usuario no válido"));
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userController->update($user_id, $_POST);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Obtener datos del usuario y departamentos
$data = $userController->edit($user_id);
$user = $data['user'];
$departments = $data['departments'];

if (!$user) {
    header("Location: manage_users.php?error=" . urlencode("Usuario no encontrado"));
    exit();
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-edit me-2"></i>Editar Usuario</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                           value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? $user->nombre_completo); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $user->email); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol *</label>
                                    <select class="form-control" id="rol" name="rol" required>
                                        <option value="usuario" <?php echo ($_POST['rol'] ?? $user->rol) === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                        <option value="admin" <?php echo ($_POST['rol'] ?? $user->rol) === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="departamento_id" class="form-label">Departamento</label>
                                    <select class="form-control" id="departamento_id" name="departamento_id">
                                        <option value="">Seleccionar departamento</option>
                                        <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo ($_POST['departamento_id'] ?? $user->departamento_id) == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                           <?php echo ($_POST['activo'] ?? $user->activo) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        Usuario activo
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="manage_users.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Cambio de contraseña -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="change_password.php">
                        <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i>Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
