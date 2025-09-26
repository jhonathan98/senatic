<?php
session_start();

// Solo admin puede gestionar categorías
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'admin') {
    header('Location: ../login.php?mensaje=Acceso no autorizado');
    exit();
}

$page_title = "Gestionar Categorías - Panel de Administración";
include_once '../../includes/header.php';
include_once '../../includes/conexion.php';
include_once '../../includes/navbar.php';

$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            
            if (empty($nombre)) {
                $error = 'El nombre de la categoría es obligatorio.';
            } else {
                // Verificar si ya existe una categoría con ese nombre
                $sql_verificar = "SELECT id FROM categorias WHERE nombre = ?";
                $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
                mysqli_stmt_bind_param($stmt_verificar, "s", $nombre);
                mysqli_stmt_execute($stmt_verificar);
                $resultado_verificar = mysqli_stmt_get_result($stmt_verificar);
                
                if (mysqli_num_rows($resultado_verificar) > 0) {
                    $error = 'Ya existe una categoría con ese nombre.';
                } else {
                    // Crear nueva categoría
                    $sql_crear = "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)";
                    $stmt_crear = mysqli_prepare($conexion, $sql_crear);
                    mysqli_stmt_bind_param($stmt_crear, "ss", $nombre, $descripcion);
                    
                    if (mysqli_stmt_execute($stmt_crear)) {
                        $mensaje = 'Categoría creada exitosamente.';
                    } else {
                        $error = 'Error al crear la categoría.';
                    }
                    mysqli_stmt_close($stmt_crear);
                }
                mysqli_stmt_close($stmt_verificar);
            }
            break;
            
        case 'editar':
            $id_categoria = (int)$_POST['id_categoria'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            
            if (empty($nombre)) {
                $error = 'El nombre de la categoría es obligatorio.';
            } else {
                // Verificar si ya existe otra categoría con ese nombre
                $sql_verificar = "SELECT id FROM categorias WHERE nombre = ? AND id != ?";
                $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
                mysqli_stmt_bind_param($stmt_verificar, "si", $nombre, $id_categoria);
                mysqli_stmt_execute($stmt_verificar);
                $resultado_verificar = mysqli_stmt_get_result($stmt_verificar);
                
                if (mysqli_num_rows($resultado_verificar) > 0) {
                    $error = 'Ya existe otra categoría con ese nombre.';
                } else {
                    // Actualizar categoría
                    $sql_editar = "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?";
                    $stmt_editar = mysqli_prepare($conexion, $sql_editar);
                    mysqli_stmt_bind_param($stmt_editar, "ssi", $nombre, $descripcion, $id_categoria);
                    
                    if (mysqli_stmt_execute($stmt_editar)) {
                        $mensaje = 'Categoría actualizada exitosamente.';
                    } else {
                        $error = 'Error al actualizar la categoría.';
                    }
                    mysqli_stmt_close($stmt_editar);
                }
                mysqli_stmt_close($stmt_verificar);
            }
            break;
            
        case 'eliminar':
            $id_categoria = (int)$_POST['id_categoria'];
            
            // Verificar si hay noticias asociadas a esta categoría
            $sql_verificar_noticias = "SELECT COUNT(*) as total FROM noticias WHERE id_categoria = ?";
            $stmt_verificar_noticias = mysqli_prepare($conexion, $sql_verificar_noticias);
            mysqli_stmt_bind_param($stmt_verificar_noticias, "i", $id_categoria);
            mysqli_stmt_execute($stmt_verificar_noticias);
            $resultado_noticias = mysqli_stmt_get_result($stmt_verificar_noticias);
            $total_noticias = mysqli_fetch_assoc($resultado_noticias)['total'];
            
            if ($total_noticias > 0) {
                $error = "No se puede eliminar la categoría porque tiene $total_noticias noticias asociadas.";
            } else {
                // Eliminar categoría
                $sql_eliminar = "DELETE FROM categorias WHERE id = ?";
                $stmt_eliminar = mysqli_prepare($conexion, $sql_eliminar);
                mysqli_stmt_bind_param($stmt_eliminar, "i", $id_categoria);
                
                if (mysqli_stmt_execute($stmt_eliminar)) {
                    $mensaje = 'Categoría eliminada exitosamente.';
                } else {
                    $error = 'Error al eliminar la categoría.';
                }
                mysqli_stmt_close($stmt_eliminar);
            }
            mysqli_stmt_close($stmt_verificar_noticias);
            break;
    }
}

// Obtener todas las categorías con estadísticas
$sql_categorias = "SELECT c.*, 
                   (SELECT COUNT(*) FROM noticias WHERE id_categoria = c.id AND activa = TRUE) as total_noticias_activas,
                   (SELECT COUNT(*) FROM noticias WHERE id_categoria = c.id) as total_noticias
                   FROM categorias c 
                   ORDER BY c.nombre";

$categorias = mysqli_query($conexion, $sql_categorias);

// Para el modal de edición
$categoria_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $sql_categoria = "SELECT * FROM categorias WHERE id = ?";
    $stmt_categoria = mysqli_prepare($conexion, $sql_categoria);
    mysqli_stmt_bind_param($stmt_categoria, "i", $id_editar);
    mysqli_stmt_execute($stmt_categoria);
    $resultado_categoria = mysqli_stmt_get_result($stmt_categoria);
    $categoria_editar = mysqli_fetch_assoc($resultado_categoria);
    mysqli_stmt_close($stmt_categoria);
}
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
                        <a class="nav-link" href="gestionar_usuarios.php">
                            <i class="bi bi-people"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gestionar_categorias.php">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-tags"></i> Gestionar Categorías</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                    <i class="bi bi-plus-circle"></i> Nueva Categoría
                </button>
            </div>

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

            <!-- Lista de categorías -->
            <div class="row">
                <?php while ($categoria = mysqli_fetch_assoc($categorias)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-truncate">
                                    <i class="bi bi-tag-fill text-primary"></i> 
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="?editar=<?php echo $categoria['id']; ?>" 
                                               data-bs-toggle="modal" data-bs-target="#modalEditarCategoria">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                        </li>
                                        <?php if ($categoria['total_noticias'] == 0): ?>
                                            <li>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('¿Está seguro de eliminar esta categoría?')">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id_categoria" value="<?php echo $categoria['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="../noticias_categoria.php?categoria=<?php echo $categoria['id']; ?>">
                                                <i class="bi bi-eye"></i> Ver Noticias
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($categoria['descripcion']): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                                <?php else: ?>
                                    <p class="card-text text-muted"><em>Sin descripción</em></p>
                                <?php endif; ?>
                                
                                <div class="row text-center mt-3">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <h4 class="text-primary mb-0"><?php echo $categoria['total_noticias_activas']; ?></h4>
                                            <small class="text-muted">Activas</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-secondary mb-0"><?php echo $categoria['total_noticias']; ?></h4>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="../noticias_categoria.php?categoria=<?php echo $categoria['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver Noticias
                                    </a>
                                    <div>
                                        <a href="?editar=<?php echo $categoria['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           data-bs-toggle="modal" data-bs-target="#modalEditarCategoria">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($categoria['total_noticias'] == 0): ?>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta categoría?')">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id_categoria" value="<?php echo $categoria['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-danger" disabled 
                                                    title="No se puede eliminar: tiene noticias asociadas">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if (mysqli_num_rows($categorias) == 0): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-tags text-muted" style="font-size: 4rem;"></i>
                            <h3 class="mt-3 text-muted">No hay categorías</h3>
                            <p class="text-muted">Crea la primera categoría para organizar las noticias.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                                <i class="bi bi-plus-circle"></i> Crear Primera Categoría
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               placeholder="Ej: Deportes, Cultura, Académico..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Descripción breve de qué tipo de noticias incluye esta categoría..."></textarea>
                        <div class="form-text">La descripción es opcional pero recomendada.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Crear Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoría -->
<?php if ($categoria_editar): ?>
<div class="modal fade" id="modalEditarCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_categoria" value="<?php echo $categoria_editar['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="nombre_editar" class="form-label">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre_editar" name="nombre" 
                               value="<?php echo htmlspecialchars($categoria_editar['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion_editar" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion_editar" name="descripcion" rows="3"><?php echo htmlspecialchars($categoria_editar['descripcion']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mostrar modal de edición si se pasa el parámetro
<?php if (isset($_GET['editar'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));
        modal.show();
    });
<?php endif; ?>
</script>
<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>
