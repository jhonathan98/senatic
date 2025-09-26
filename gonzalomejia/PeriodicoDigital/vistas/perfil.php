<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?mensaje=Debe iniciar sesión');
    exit();
}

$page_title = "Mi Perfil - Periódico Digital";
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

$mensaje = '';
$error = '';

// Obtener información del usuario
$sql_usuario = "SELECT * FROM usuarios WHERE id = ?";
$stmt_usuario = mysqli_prepare($conexion, $sql_usuario);
mysqli_stmt_bind_param($stmt_usuario, "i", $_SESSION['usuario_id']);
mysqli_stmt_execute($stmt_usuario);
$resultado_usuario = mysqli_stmt_get_result($stmt_usuario);
$usuario = mysqli_fetch_assoc($resultado_usuario);

if (!$usuario) {
    header('Location: ../index.php');
    exit();
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion == 'actualizar_perfil') {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $correo = trim($_POST['correo']);
        
        if (empty($nombre) || empty($apellido) || empty($correo)) {
            $error = 'Por favor, complete todos los campos.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, ingrese un correo electrónico válido.';
        } else {
            // Verificar si el correo ya existe (excepto el usuario actual)
            $sql_verificar = "SELECT id FROM usuarios WHERE correo_electronico = ? AND id != ?";
            $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
            mysqli_stmt_bind_param($stmt_verificar, "si", $correo, $_SESSION['usuario_id']);
            mysqli_stmt_execute($stmt_verificar);
            $resultado_verificar = mysqli_stmt_get_result($stmt_verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $error = 'Este correo electrónico ya está en uso por otro usuario.';
            } else {
                // Actualizar información
                $sql_actualizar = "UPDATE usuarios SET nombre = ?, apellido = ?, correo_electronico = ? WHERE id = ?";
                $stmt_actualizar = mysqli_prepare($conexion, $sql_actualizar);
                mysqli_stmt_bind_param($stmt_actualizar, "sssi", $nombre, $apellido, $correo, $_SESSION['usuario_id']);
                
                if (mysqli_stmt_execute($stmt_actualizar)) {
                    $mensaje = 'Perfil actualizado exitosamente.';
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['usuario_correo'] = $correo;
                    $usuario['nombre'] = $nombre;
                    $usuario['apellido'] = $apellido;
                    $usuario['correo_electronico'] = $correo;
                } else {
                    $error = 'Error al actualizar el perfil.';
                }
                mysqli_stmt_close($stmt_actualizar);
            }
            mysqli_stmt_close($stmt_verificar);
        }
    }
    
    elseif ($accion == 'cambiar_contrasena') {
        $contrasena_actual = $_POST['contrasena_actual'];
        $nueva_contrasena = $_POST['nueva_contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];
        
        if (empty($contrasena_actual) || empty($nueva_contrasena) || empty($confirmar_contrasena)) {
            $error = 'Por favor, complete todos los campos de contraseña.';
        } elseif (strlen($nueva_contrasena) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } elseif (!password_verify($contrasena_actual, $usuario['contrasena_hash'])) {
            $error = 'La contraseña actual es incorrecta.';
        } else {
            // Actualizar contraseña
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $sql_contrasena = "UPDATE usuarios SET contrasena_hash = ? WHERE id = ?";
            $stmt_contrasena = mysqli_prepare($conexion, $sql_contrasena);
            mysqli_stmt_bind_param($stmt_contrasena, "si", $nueva_contrasena_hash, $_SESSION['usuario_id']);
            
            if (mysqli_stmt_execute($stmt_contrasena)) {
                $mensaje = 'Contraseña cambiada exitosamente.';
                $usuario['contrasena_hash'] = $nueva_contrasena_hash;
            } else {
                $error = 'Error al cambiar la contraseña.';
            }
            mysqli_stmt_close($stmt_contrasena);
        }
    }
}

// Obtener estadísticas del usuario
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM comentarios WHERE id_usuario = ? AND activo = TRUE) as comentarios_realizados,
    (SELECT COUNT(*) FROM noticias WHERE id_autor = ? AND activa = TRUE) as noticias_publicadas";

$stmt_stats = mysqli_prepare($conexion, $sql_stats);
mysqli_stmt_bind_param($stmt_stats, "ii", $_SESSION['usuario_id'], $_SESSION['usuario_id']);
mysqli_stmt_execute($stmt_stats);
$resultado_stats = mysqli_stmt_get_result($stmt_stats);
$stats = mysqli_fetch_assoc($resultado_stats);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Información del perfil -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px; font-size: 2rem;">
                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                        </div>
                    </div>
                    <h4><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></p>
                    
                    <?php
                    $badge_class = [
                        'admin' => 'bg-danger',
                        'redactor' => 'bg-warning',
                        'miembro' => 'bg-success',
                        'invitado' => 'bg-secondary'
                    ];
                    $clase = $badge_class[$usuario['tipo_usuario']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $clase; ?> mb-3">
                        <?php echo ucfirst($usuario['tipo_usuario']); ?>
                    </span>
                    
                    <div class="text-muted">
                        <small><i class="bi bi-calendar"></i> Miembro desde <?php echo date('F Y', strtotime($usuario['fecha_registro'])); ?></small>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Mis Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $stats['comentarios_realizados']; ?></h3>
                            <small class="text-muted">Comentarios</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success"><?php echo $stats['noticias_publicadas']; ?></h3>
                            <small class="text-muted">Noticias</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enlaces rápidos -->
            <?php if ($usuario['tipo_usuario'] == 'admin' || $usuario['tipo_usuario'] == 'redactor'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Acceso Rápido</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin/dashboard.php" class="btn btn-primary">
                                <i class="bi bi-speedometer2"></i> Panel de Admin
                            </a>
                            <a href="admin/crear_noticia.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Nueva Noticia
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Editar información personal -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-gear"></i> Información Personal</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="actualizar_perfil">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" 
                                   value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
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
                        <input type="hidden" name="accion" value="cambiar_contrasena">
                        
                        <div class="mb-3">
                            <label for="contrasena_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" 
                                       minlength="6" required>
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" 
                                       minlength="6" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>