<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol_usuario = $_SESSION['rol'];

// Obtener todos los usuarios
$usuarios = [];
$filtro_rol = $_GET['rol'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

try {
    $sql = "SELECT * FROM usuarios WHERE 1=1";
    $params = [];
    
    if (!empty($filtro_rol)) {
        $sql .= " AND rol = ?";
        $params[] = $filtro_rol;
    }
    
    if (!empty($busqueda)) {
        $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $sql .= " ORDER BY fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error al obtener usuarios: " . $e->getMessage();
}

// Procesar acciones (crear, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Crear nuevo usuario
    if (isset($_POST['create_user'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $rol = $_POST['rol'] ?? 'estudiante';
        
        $errors = [];
        
        if (empty($nombre)) {
            $errors[] = "El nombre es obligatorio";
        }
        
        if (empty($email) || !validarEmail($email)) {
            $errors[] = "El email es obligatorio y debe ser válido";
        }
        
        if (empty($contrasena) || strlen($contrasena) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if (!in_array($rol, ['estudiante', 'docente', 'admin'])) {
            $errors[] = "Rol no válido";
        }
        
        if (empty($errors)) {
            $resultado = registrarUsuario($pdo, $nombre, $email, $contrasena, $rol);
            
            if (isset($resultado['success'])) {
                $success = "Usuario creado correctamente";
                // Recargar usuarios
                header("Location: manage_users.php");
                exit();
            } else {
                $errors[] = $resultado['error'] ?? 'Error desconocido';
            }
        }
    }
    
    // Eliminar usuario
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        
        // No permitir que el admin se elimine a sí mismo
        if ($user_id == $usuario_id) {
            $error = "No puedes eliminar tu propia cuenta";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "Usuario eliminado correctamente";
                
                // Recargar la página
                header("Location: manage_users.php");
                exit();
            } catch(PDOException $e) {
                $error = "Error al eliminar usuario: " . $e->getMessage();
            }
        }
    }
    
    // Cambiar rol de usuario
    if (isset($_POST['change_role']) && isset($_POST['user_id']) && isset($_POST['new_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        // No permitir que el admin cambie su propio rol
        if ($user_id == $usuario_id) {
            $error = "No puedes cambiar tu propio rol";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $success = "Rol actualizado correctamente";
                
                // Recargar la página
                header("Location: manage_users.php");
                exit();
            } catch(PDOException $e) {
                $error = "Error al cambiar rol: " . $e->getMessage();
            }
        }
    }
}

// Obtener estadísticas de usuarios
$estadisticas = [];
try {
    $stmt = $pdo->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
    $stats = $stmt->fetchAll();
    foreach ($stats as $stat) {
        $estadisticas[$stat['rol']] = $stat['total'];
    }
} catch(PDOException $e) {
    // Error silencioso
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Gestión de Usuarios
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($nombre_usuario); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Gestionar Usuarios</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus me-2"></i>Crear Nuevo Usuario
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $estadisticas['admin'] ?? 0; ?></h4>
                                <p class="mb-0">Administradores</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-shield fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $estadisticas['docente'] ?? 0; ?></h4>
                                <p class="mb-0">Docentes</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $estadisticas['estudiante'] ?? 0; ?></h4>
                                <p class="mb-0">Estudiantes</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-graduate fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo count($usuarios); ?></h4>
                                <p class="mb-0">Total</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar:</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Nombre o email...">
                    </div>
                    <div class="col-md-4">
                        <label for="rol" class="form-label">Filtrar por rol:</label>
                        <select class="form-select" id="rol" name="rol">
                            <option value="">Todos los roles</option>
                            <option value="admin" <?php echo $filtro_rol === 'admin' ? 'selected' : ''; ?>>Administradores</option>
                            <option value="docente" <?php echo $filtro_rol === 'docente' ? 'selected' : ''; ?>>Docentes</option>
                            <option value="estudiante" <?php echo $filtro_rol === 'estudiante' ? 'selected' : ''; ?>>Estudiantes</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de usuarios -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Lista de Usuarios 
                    <span class="badge bg-primary"><?php echo count($usuarios); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron usuarios</h5>
                        <p class="text-muted">Ajusta los filtros o crea un nuevo usuario</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <?php
                                                $icon = 'fa-user';
                                                $color = 'text-secondary';
                                                if ($usuario['rol'] === 'admin') {
                                                    $icon = 'fa-user-shield';
                                                    $color = 'text-primary';
                                                } elseif ($usuario['rol'] === 'docente') {
                                                    $icon = 'fa-chalkboard-teacher';
                                                    $color = 'text-success';
                                                } elseif ($usuario['rol'] === 'estudiante') {
                                                    $icon = 'fa-user-graduate';
                                                    $color = 'text-info';
                                                }
                                                ?>
                                                <i class="fas <?php echo $icon; ?> <?php echo $color; ?>"></i>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                                <?php if ($usuario['id'] == $usuario_id): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Tú</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = 'bg-secondary';
                                        $rolText = ucfirst($usuario['rol']);
                                        if ($usuario['rol'] === 'admin') {
                                            $badgeClass = 'bg-primary';
                                        } elseif ($usuario['rol'] === 'docente') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($usuario['rol'] === 'estudiante') {
                                            $badgeClass = 'bg-info';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $rolText; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($usuario['id'] != $usuario_id): ?>
                                                <!-- Cambiar rol -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown" title="Cambiar rol">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($usuario['rol'] !== 'estudiante'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <input type="hidden" name="new_role" value="estudiante">
                                                                    <button type="submit" name="change_role" class="dropdown-item">
                                                                        <i class="fas fa-user-graduate me-2"></i>Estudiante
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($usuario['rol'] !== 'docente'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <input type="hidden" name="new_role" value="docente">
                                                                    <button type="submit" name="change_role" class="dropdown-item">
                                                                        <i class="fas fa-chalkboard-teacher me-2"></i>Docente
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($usuario['rol'] !== 'admin'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                                    <input type="hidden" name="new_role" value="admin">
                                                                    <button type="submit" name="change_role" class="dropdown-item">
                                                                        <i class="fas fa-user-shield me-2"></i>Admin
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                
                                                <!-- Eliminar usuario -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?php echo $usuario['id']; ?>"
                                                        title="Eliminar usuario">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">
                                                    <i class="fas fa-lock me-1"></i>Tu cuenta
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Modal de confirmación para eliminar -->
                                        <?php if ($usuario['id'] != $usuario_id): ?>
                                        <div class="modal fade" id="deleteModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Estás seguro de que deseas eliminar el usuario "<strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>"?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Advertencia:</strong> Esta acción también eliminará todos los resultados de exámenes asociados a este usuario y no se puede deshacer.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-danger">
                                                                <i class="fas fa-trash me-2"></i>Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createUserModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($errors) && isset($_POST['create_user'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="createUserForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                                           placeholder="Ej: Juan Pérez" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="usuario@ejemplo.com" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contrasena" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                           placeholder="Mínimo 6 caracteres" required>
                                    <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol *</label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="estudiante" <?php echo (($_POST['rol'] ?? 'estudiante') === 'estudiante') ? 'selected' : ''; ?>>
                                            Estudiante
                                        </option>
                                        <option value="docente" <?php echo (($_POST['rol'] ?? '') === 'docente') ? 'selected' : ''; ?>>
                                            Docente
                                        </option>
                                        <option value="admin" <?php echo (($_POST['rol'] ?? '') === 'admin') ? 'selected' : ''; ?>>
                                            Administrador
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Roles:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Estudiante:</strong> Puede tomar exámenes y ver su progreso</li>
                                <li><strong>Docente:</strong> Puede crear exámenes, materias y gestionar preguntas</li>
                                <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="createUserForm" name="create_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (!empty($errors) && isset($_POST['create_user'])): ?>
        // Mostrar el modal si hay errores
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('createUserModal'));
            modal.show();
        });
        <?php endif; ?>

        // Validación del formulario
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('contrasena').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                return;
            }
        });
    </script>
</body>
</html>
