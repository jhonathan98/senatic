<?php
session_start();

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] != 'admin' && $_SESSION['tipo_usuario'] != 'redactor')) {
    header('Location: ../login.php?mensaje=Acceso no autorizado');
    exit();
}

$page_title = "Gestionar Noticias - Panel de Administración";
include_once '../../includes/header.php';
include_once '../../includes/conexion.php';
include_once '../../includes/navbar.php';

$mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_noticia = (int)($_POST['id_noticia'] ?? 0);
    
    switch ($accion) {
        case 'eliminar':
            $sql_eliminar = "DELETE FROM noticias WHERE id = ?";
            $stmt_eliminar = mysqli_prepare($conexion, $sql_eliminar);
            mysqli_stmt_bind_param($stmt_eliminar, "i", $id_noticia);
            if (mysqli_stmt_execute($stmt_eliminar)) {
                $mensaje = 'Noticia eliminada exitosamente.';
            } else {
                $mensaje = 'Error al eliminar la noticia.';
            }
            mysqli_stmt_close($stmt_eliminar);
            break;
            
        case 'toggle_activo':
            $sql_toggle = "UPDATE noticias SET activa = NOT activa WHERE id = ?";
            $stmt_toggle = mysqli_prepare($conexion, $sql_toggle);
            mysqli_stmt_bind_param($stmt_toggle, "i", $id_noticia);
            if (mysqli_stmt_execute($stmt_toggle)) {
                $mensaje = 'Estado de la noticia actualizado.';
            } else {
                $mensaje = 'Error al actualizar el estado.';
            }
            mysqli_stmt_close($stmt_toggle);
            break;
            
        case 'toggle_destacada':
            $sql_destacada = "UPDATE noticias SET destacada = NOT destacada WHERE id = ?";
            $stmt_destacada = mysqli_prepare($conexion, $sql_destacada);
            mysqli_stmt_bind_param($stmt_destacada, "i", $id_noticia);
            if (mysqli_stmt_execute($stmt_destacada)) {
                $mensaje = 'Estado destacado actualizado.';
            } else {
                $mensaje = 'Error al actualizar estado destacado.';
            }
            mysqli_stmt_close($stmt_destacada);
            break;
    }
}

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir consulta
$where_conditions = [];
$params = [];
$types = "";

if ($filtro_categoria > 0) {
    $where_conditions[] = "n.id_categoria = ?";
    $params[] = $filtro_categoria;
    $types .= "i";
}

if ($filtro_estado == 'activas') {
    $where_conditions[] = "n.activa = TRUE";
} elseif ($filtro_estado == 'inactivas') {
    $where_conditions[] = "n.activa = FALSE";
} elseif ($filtro_estado == 'destacadas') {
    $where_conditions[] = "n.destacada = TRUE";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(n.titulo LIKE ? OR n.contenido LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "ss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Paginación
$por_pagina = 15;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $por_pagina;

// Contar total
$sql_total = "SELECT COUNT(*) as total 
              FROM noticias n 
              JOIN categorias c ON n.id_categoria = c.id 
              $where_clause";

$stmt_total = mysqli_prepare($conexion, $sql_total);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_total, $types, ...$params);
}
mysqli_stmt_execute($stmt_total);
$resultado_total = mysqli_stmt_get_result($stmt_total);
$total_noticias = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_noticias / $por_pagina);

// Obtener noticias
$sql_noticias = "SELECT n.*, c.nombre AS categoria_nombre, u.nombre AS autor_nombre, u.apellido AS autor_apellido
                 FROM noticias n
                 JOIN categorias c ON n.id_categoria = c.id
                 JOIN usuarios u ON n.id_autor = u.id
                 $where_clause
                 ORDER BY n.fecha_publicacion DESC
                 LIMIT ?, ?";

$stmt_noticias = mysqli_prepare($conexion, $sql_noticias);
$params[] = $inicio;
$params[] = $por_pagina;
$types .= "ii";
mysqli_stmt_bind_param($stmt_noticias, $types, ...$params);
mysqli_stmt_execute($stmt_noticias);
$noticias = mysqli_stmt_get_result($stmt_noticias);

// Obtener categorías para filtro
$sql_categorias = "SELECT * FROM categorias ORDER BY nombre";
$categorias = mysqli_query($conexion, $sql_categorias);
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
                        <a class="nav-link active" href="gestionar_noticias.php">
                            <i class="bi bi-newspaper"></i> Gestionar Noticias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crear_noticia.php">
                            <i class="bi bi-plus-circle"></i> Nueva Noticia
                        </a>
                    </li>
                    <?php if ($_SESSION['tipo_usuario'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_categorias.php">
                                <i class="bi bi-tags"></i> Categorías
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-newspaper"></i> Gestionar Noticias</h1>
                <a href="crear_noticia.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Noticia
                </a>
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
                        <div class="col-md-3">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Título o contenido...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="0">Todas</option>
                                <?php while ($categoria = mysqli_fetch_assoc($categorias)): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                            <?php echo ($filtro_categoria == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="todos" <?php echo ($filtro_estado == 'todos') ? 'selected' : ''; ?>>Todos</option>
                                <option value="activas" <?php echo ($filtro_estado == 'activas') ? 'selected' : ''; ?>>Activas</option>
                                <option value="inactivas" <?php echo ($filtro_estado == 'inactivas') ? 'selected' : ''; ?>>Inactivas</option>
                                <option value="destacadas" <?php echo ($filtro_estado == 'destacadas') ? 'selected' : ''; ?>>Destacadas</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <a href="gestionar_noticias.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultados -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Total: <?php echo $total_noticias; ?> noticias</span>
                    <small class="text-muted">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></small>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Autor</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($noticia = mysqli_fetch_assoc($noticias)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($noticia['imagen_principal']): ?>
                                                <img src="../../assets/images/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" 
                                                     class="rounded me-2" width="50" height="40" style="object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <a href="../noticias_detalle.php?id=<?php echo $noticia['id']; ?>" 
                                                   class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars($noticia['titulo']); ?>
                                                </a>
                                                <?php if ($noticia['destacada']): ?>
                                                    <span class="badge bg-warning ms-1">Destacada</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($noticia['categoria_nombre']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($noticia['autor_nombre'] . ' ' . $noticia['autor_apellido']); ?></td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($noticia['activa']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../noticias_detalle.php?id=<?php echo $noticia['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id_noticia" value="<?php echo $noticia['id']; ?>">
                                                <input type="hidden" name="accion" value="toggle_activo">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                        title="<?php echo $noticia['activa'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="bi bi-<?php echo $noticia['activa'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id_noticia" value="<?php echo $noticia['id']; ?>">
                                                <input type="hidden" name="accion" value="toggle_destacada">
                                                <button type="submit" class="btn btn-sm btn-outline-info" 
                                                        title="<?php echo $noticia['destacada'] ? 'Quitar destacado' : 'Destacar'; ?>">
                                                    <i class="bi bi-star<?php echo $noticia['destacada'] ? '-fill' : ''; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta noticia?')">
                                                <input type="hidden" name="id_noticia" value="<?php echo $noticia['id']; ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if (mysqli_num_rows($noticias) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">No se encontraron noticias</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($pagina_actual > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo ($pagina_actual - 1); ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $filtro_categoria; ?>&estado=<?php echo $filtro_estado; ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $filtro_categoria; ?>&estado=<?php echo $filtro_estado; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo ($pagina_actual + 1); ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $filtro_categoria; ?>&estado=<?php echo $filtro_estado; ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>