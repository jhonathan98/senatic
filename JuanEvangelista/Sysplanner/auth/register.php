<?php
// auth/register.php
$page_title = "Registro - SysPlanner";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../controllers/UserController.php';
require_once '../models/Department.php';

// Verificar si el registro está habilitado (solo admins pueden registrar usuarios)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("No tiene permisos para acceder a esta página"));
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userController = new UserController();
    $result = $userController->store($_POST);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Obtener departamentos para el formulario
$department = new Department();
$departments = $department->read();
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Usuario</h3>
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
                                           value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol *</label>
                                    <select class="form-control" id="rol" name="rol" required>
                                        <option value="usuario" <?php echo ($_POST['rol'] ?? '') === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                        <option value="admin" <?php echo ($_POST['rol'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="departamento_id" class="form-label">Departamento</label>
                                    <select class="form-control" id="departamento_id" name="departamento_id">
                                        <option value="">Seleccionar departamento</option>
                                        <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo ($_POST['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                               <?php echo ($_POST['activo'] ?? '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">
                                            Usuario activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../views/admin/manage_users.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Registrar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
