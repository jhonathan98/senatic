<?php
session_start();

// Solo admin puede gestionar usuarios
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'admin') {
    header('Location: ../login.php?mensaje=Acceso no autorizado');
    exit();
}

$page_title = "Gestionar Usuarios - Panel de Administración";
include_once '../../includes/header.php';
include_once '../../includes/conexion.php';
include_once '../../includes/navbar.php';

$mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    
    switch ($accion) {
        case 'cambiar_estado':
            $sql_toggle = "UPDATE usuarios SET activo = NOT activo WHERE id = ?";
            $stmt_toggle = mysqli_prepare($conexion, $sql_toggle);
            mysqli_stmt_bind_param($stmt_toggle, "i", $id_usuario);
            if (mysqli_stmt_execute($stmt_toggle)) {
                $mensaje = 'Estado del usuario actualizado.';
            } else {
                $mensaje = 'Error al actualizar el estado.';
            }
            mysqli_stmt_close($stmt_toggle);
            break;
            
        case 'cambiar_tipo':
            $nuevo_tipo = $_POST['nuevo_tipo'] ?? '';
            $tipos_validos = ['invitado', 'miembro', 'redactor', 'admin'];
            
            if (in_array($nuevo_tipo, $tipos_validos)) {
                $sql_tipo = "UPDATE usuarios SET tipo_usuario = ? WHERE id = ?";
                $stmt_tipo = mysqli_prepare($conexion, $sql_tipo);
                mysqli_stmt_bind_param($stmt_tipo, "si", $nuevo_tipo, $id_usuario);
                
                if (mysqli_stmt_execute($stmt_tipo)) {
                    $mensaje = 'Tipo de usuario actualizado.';
                } else {
                    $mensaje = 'Error al actualizar el tipo de usuario.';
                }
                mysqli_stmt_close($stmt_tipo);
            } else {
                $mensaje = 'Tipo de usuario no válido.';
            }
            break;
    }
}

// Filtros
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir consulta
$where_conditions = [];
$params = [];
$types = "";

if ($filtro_tipo != 'todos') {
    $where_conditions[] = "tipo_usuario = ?";
    $params[] = $filtro_tipo;
    $types .= "s";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(nombre LIKE ? OR apellido LIKE ? OR correo_electronico LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "sss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener usuarios
$sql_usuarios = "SELECT * FROM usuarios $where_clause ORDER BY fecha_registro DESC";

$stmt_usuarios = mysqli_prepare($conexion, $sql_usuarios);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_usuarios, $types, ...$params);
}
mysqli_stmt_execute($stmt_usuarios);
$usuarios = mysqli_stmt_get_result($stmt_usuarios);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <h5><i class="bi bi-speedometer2"></i> Panel Admin</h5>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestionar_noticias.php">
                            <i class="bi bi-newspaper"></i> Gestionar Noticias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crear_noticia.php">
                            <i class="bi bi-plus-circle"></i> Nueva Noticia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gestionar_usuarios.php">
                            <i class="bi bi-people"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestionar_categorias.php">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-people"></i> Gestionar Usuarios</h1>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i> <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label for="busqueda" class="form-label">Buscar Usuario</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Nombre, apellido o correo...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo de Usuario</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="todos" <?php echo ($filtro_tipo == 'todos') ? 'selected' : ''; ?>>Todos</option>
                                <option value="admin" <?php echo ($filtro_tipo == 'admin') ? 'selected' : ''; ?>>Administradores</option>
                                <option value="redactor" <?php echo ($filtro_tipo == 'redactor') ? 'selected' : ''; ?>>Redactores</option>
                                <option value="miembro" <?php echo ($filtro_tipo == 'miembro') ? 'selected' : ''; ?>>Miembros</option>
                                <option value="invitado" <?php echo ($filtro_tipo == 'invitado') ? 'selected' : ''; ?>>Invitados</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <a href="gestionar_usuarios.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de usuarios -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Tipo</th>
                                <th>Registro</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($usuario = mysqli_fetch_assoc($usuarios)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'admin' => 'bg-danger',
                                            'redactor' => 'bg-warning',
                                            'miembro' => 'bg-success',
                                            'invitado' => 'bg-secondary'
                                        ];
                                        $clase = $badge_class[$usuario['tipo_usuario']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $clase; ?>">
                                            <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <div class="btn-group" role="group">
                                                <!-- Cambiar estado -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="accion" value="cambiar_estado">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                            title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="bi bi-<?php echo $usuario['activo'] ? 'person-x' : 'person-check'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Cambiar tipo -->
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" 
                                                            data-bs-toggle="dropdown" title="Cambiar tipo">
                                                        <i class="bi bi-person-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php 
                                                        $tipos = ['invitado', 'miembro', 'redactor', 'admin'];
                                                        foreach ($tipos as $tipo):
                                                            if ($tipo != $usuario['tipo_usuario']):
                                                        ?>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                                    <input type="hidden" name="accion" value="cambiar_tipo">
                                                                    <input type="hidden" name="nuevo_tipo" value="<?php echo $tipo; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        Hacer <?php echo ucfirst($tipo); ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted"><small>(Tu cuenta)</small></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if (mysqli_num_rows($usuarios) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-person-x text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">No se encontraron usuarios</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
