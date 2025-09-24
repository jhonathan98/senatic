<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../includes/db.php';
require_once '../../../includes/auth.php';

// Verificar que sea administrador
verificarRol(['administrador']);

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        $accion = $_POST['accion'] ?? '';
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        
        if ($accion === 'cambiar_rol') {
            $nuevo_rol = (int)($_POST['nuevo_rol'] ?? 1);
            $stmt = $pdo->prepare("UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?");
            $stmt->execute([$nuevo_rol, $id_usuario]);
            $mensaje = "Rol de usuario actualizado exitosamente.";
            $tipo_mensaje = 'success';
        } elseif ($accion === 'eliminar_usuario') {
            if ($id_usuario != $_SESSION['id_usuario']) {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                $mensaje = "Usuario eliminado exitosamente.";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "No puedes eliminar tu propia cuenta.";
                $tipo_mensaje = 'danger';
            }
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

try {
    $pdo = getDB();
    
    // Obtener todos los usuarios con sus roles y estadísticas
    $stmt = $pdo->query("
        SELECT u.*, r.nombre_rol,
               COUNT(DISTINCT a.id_articulo) as total_articulos,
               COUNT(DISTINCT CASE WHEN a.estado = 'publicado' THEN a.id_articulo END) as articulos_publicados
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN articulos a ON u.id_usuario = a.id_autor
        GROUP BY u.id_usuario
        ORDER BY u.fecha_registro DESC
    ");
    $usuarios = $stmt->fetchAll();
    
    // Obtener todos los roles disponibles
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id_rol");
    $roles = $stmt->fetchAll();
    
    // Estadísticas
    $stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 1");
    $stats['lectores'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 2");
    $stats['redactores'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4");
    $stats['administradores'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Error en gestionar_usuarios.php: " . $e->getMessage());
    $usuarios = [];
    $roles = [];
    $stats = ['total_usuarios' => 0, 'lectores' => 0, 'redactores' => 0, 'administradores' => 0];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
</head>
<body>
    <?php include '../../../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-subheading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Panel de Administración</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion.php">
                                <i class="bi bi-collection"></i> Gestión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="gestionar_usuarios.php">
                                <i class="bi bi-people"></i> Gestionar Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_categorias.php">
                                <i class="bi bi-tags"></i> Gestionar Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_eventos.php">
                                <i class="bi bi-calendar-event"></i> Gestionar Eventos
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestionar Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-people fs-1"></i>
                                <h3><?= $stats['total_usuarios'] ?></h3>
                                <p class="card-text">Total Usuarios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-eye fs-1"></i>
                                <h3><?= $stats['lectores'] ?></h3>
                                <p class="card-text">Lectores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-pencil fs-1"></i>
                                <h3><?= $stats['redactores'] ?></h3>
                                <p class="card-text">Redactores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body">
                                <i class="bi bi-shield fs-1"></i>
                                <h3><?= $stats['administradores'] ?></h3>
                                <p class="card-text">Administradores</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list"></i> Lista de Usuarios Registrados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Foto</th>
                                        <th>Información</th>
                                        <th>Rol Actual</th>
                                        <th>Artículos</th>
                                        <th>Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?= $usuario['id_usuario'] ?></td>
                                        <td>
                                            <img src="../../../assets/images/uploads/<?= htmlspecialchars($usuario['foto_perfil']) ?>" 
                                                 class="rounded-circle" width="40" height="40" 
                                                 alt="<?= htmlspecialchars($usuario['nombre_completo']) ?>">
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong><br>
                                                <small class="text-muted">@<?= htmlspecialchars($usuario['nombre_usuario']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($usuario['correo']) ?></small>
                                                <?php if ($usuario['grado']): ?>
                                                <br><span class="badge bg-light text-dark"><?= htmlspecialchars($usuario['grado']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $usuario['nombre_rol'] === 'administrador' ? 'danger' : 
                                                ($usuario['nombre_rol'] === 'redactor' ? 'success' : 
                                                ($usuario['nombre_rol'] === 'usuarioRegular' ? 'warning' : 'info')) ?>">
                                                <?= ucfirst($usuario['nombre_rol'] ?? 'Sin rol') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $usuario['total_articulos'] ?></span>
                                            <?php if ($usuario['articulos_publicados'] > 0): ?>
                                            <br><small class="text-success"><?= $usuario['articulos_publicados'] ?> publicados</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($usuario['id_usuario'] != $_SESSION['id_usuario']): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#modalCambiarRol<?= $usuario['id_usuario'] ?>">
                                                    <i class="bi bi-person-gear"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#modalVerUsuario<?= $usuario['id_usuario'] ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#modalEliminarUsuario<?= $usuario['id_usuario'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <span class="badge bg-warning">Tú mismo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal para cambiar rol -->
                                    <div class="modal fade" id="modalCambiarRol<?= $usuario['id_usuario'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cambiar Rol - <?= htmlspecialchars($usuario['nombre_completo']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="accion" value="cambiar_rol">
                                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="nuevo_rol<?= $usuario['id_usuario'] ?>" class="form-label">Nuevo Rol:</label>
                                                            <select class="form-select" name="nuevo_rol" id="nuevo_rol<?= $usuario['id_usuario'] ?>" required>
                                                                <?php foreach ($roles as $rol): ?>
                                                                <option value="<?= $rol['id_rol'] ?>" 
                                                                        <?= $rol['id_rol'] == $usuario['id_rol'] ? 'selected' : '' ?>>
                                                                    <?= ucfirst($rol['nombre_rol']) ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            <strong>Advertencia:</strong> Cambiar el rol de un usuario afectará sus permisos inmediatamente.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Cambiar Rol</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal para ver detalles del usuario -->
                                    <div class="modal fade" id="modalVerUsuario<?= $usuario['id_usuario'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles del Usuario</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-3 text-center">
                                                            <img src="../../../assets/images/uploads/<?= htmlspecialchars($usuario['foto_perfil']) ?>" 
                                                                 class="rounded-circle mb-3" width="100" height="100"
                                                                 alt="<?= htmlspecialchars($usuario['nombre_completo']) ?>">
                                                        </div>
                                                        <div class="col-md-9">
                                                            <h5><?= htmlspecialchars($usuario['nombre_completo']) ?></h5>
                                                            <p class="mb-2"><strong>Usuario:</strong> @<?= htmlspecialchars($usuario['nombre_usuario']) ?></p>
                                                            <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($usuario['correo']) ?></p>
                                                            <p class="mb-2"><strong>Grado:</strong> <?= htmlspecialchars($usuario['grado'] ?: 'No especificado') ?></p>
                                                            <p class="mb-2"><strong>Rol:</strong> 
                                                                <span class="badge bg-primary"><?= ucfirst($usuario['nombre_rol'] ?? 'Sin rol') ?></span>
                                                            </p>
                                                            <p class="mb-2"><strong>Registrado:</strong> <?= date('d \d\e F \d\e Y, H:i', strtotime($usuario['fecha_registro'])) ?></p>
                                                            <p class="mb-2"><strong>Artículos publicados:</strong> <?= $usuario['articulos_publicados'] ?></p>
                                                            <p class="mb-2"><strong>Total artículos:</strong> <?= $usuario['total_articulos'] ?></p>
                                                        </div>
                                                    </div>
                                                    <?php if ($usuario['descripcion']): ?>
                                                    <hr>
                                                    <h6>Descripción:</h6>
                                                    <p class="text-muted"><?= nl2br(htmlspecialchars($usuario['descripcion'])) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal para eliminar usuario -->
                                    <div class="modal fade" id="modalEliminarUsuario<?= $usuario['id_usuario'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Eliminar Usuario</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="accion" value="eliminar_usuario">
                                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                                        
                                                        <div class="alert alert-danger">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                                                        </div>
                                                        
                                                        <p>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong>?</p>
                                                        <p>Se eliminarán:</p>
                                                        <ul>
                                                            <li>Todos sus datos personales</li>
                                                            <li>Todos sus artículos (<?= $usuario['total_articulos'] ?>)</li>
                                                            <li>Su cuenta de acceso</li>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Sí, Eliminar Usuario</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
