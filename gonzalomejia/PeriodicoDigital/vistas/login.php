<?php
$page_title = "Iniciar Sesión - Periódico Digital";
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);
    
    if (empty($correo) || empty($contrasena)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $sql = "SELECT id, nombre, apellido, correo_electronico, contrasena_hash, tipo_usuario 
                FROM usuarios 
                WHERE correo_electronico = ? AND activo = TRUE";
        
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "s", $correo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($usuario = mysqli_fetch_assoc($resultado)) {
            if (password_verify($contrasena, $usuario['contrasena_hash'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_correo'] = $usuario['correo_electronico'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                
                // Redirigir según el tipo de usuario
                if ($usuario['tipo_usuario'] == 'admin' || $usuario['tipo_usuario'] == 'redactor') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: ../index.php');
                }
                exit();
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="correo" name="correo" 
                                       value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>