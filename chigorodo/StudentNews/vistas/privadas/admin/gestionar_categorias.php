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
        
        if ($accion === 'crear_categoria') {
            $nombre = trim($_POST['nombre_categoria'] ?? '');
            $icono = trim($_POST['icono'] ?? '');
            
            if (empty($nombre)) {
                throw new Exception("El nombre de la categoría es obligatorio.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre_categoria, icono) VALUES (?, ?)");
            $stmt->execute([$nombre, $icono]);
            $mensaje = "Categoría creada exitosamente.";
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'editar_categoria') {
            $id_categoria = (int)($_POST['id_categoria'] ?? 0);
            $nombre = trim($_POST['nombre_categoria'] ?? '');
            $icono = trim($_POST['icono'] ?? '');
            
            if (empty($nombre)) {
                throw new Exception("El nombre de la categoría es obligatorio.");
            }
            
            $stmt = $pdo->prepare("UPDATE categorias SET nombre_categoria = ?, icono = ? WHERE id_categoria = ?");
            $stmt->execute([$nombre, $icono, $id_categoria]);
            $mensaje = "Categoría actualizada exitosamente.";
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'eliminar_categoria') {
            $id_categoria = (int)($_POST['id_categoria'] ?? 0);
            
            // Verificar si hay artículos usando esta categoría
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM articulos WHERE id_categoria = ?");
            $stmt->execute([$id_categoria]);
            $total_articulos = $stmt->fetch()['total'];
            
            if ($total_articulos > 0) {
                throw new Exception("No se puede eliminar la categoría porque tiene $total_articulos artículos asociados.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?");
            $stmt->execute([$id_categoria]);
            $mensaje = "Categoría eliminada exitosamente.";
            $tipo_mensaje = 'success';
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

try {
    $pdo = getDB();
    
    // Obtener todas las categorías con estadísticas
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(a.id_articulo) as total_articulos,
               COUNT(CASE WHEN a.estado = 'publicado' THEN a.id_articulo END) as articulos_publicados
        FROM categorias c
        LEFT JOIN articulos a ON c.id_categoria = a.id_categoria
        GROUP BY c.id_categoria
        ORDER BY c.nombre_categoria
    ");
    $categorias = $stmt->fetchAll();
    
    // Estadísticas generales
    $stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $stats['total_categorias'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_categoria) as total FROM articulos");
    $stats['categorias_con_articulos'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Error en gestionar_categorias.php: " . $e->getMessage());
    $categorias = [];
    $stats = ['total_categorias' => 0, 'categorias_con_articulos' => 0];
}

// Lista de iconos disponibles de Bootstrap Icons
$iconos_disponibles = [
    'bi bi-newspaper' => 'Periódico (Noticias)',
    'bi bi-chat-quote' => 'Burbuja de chat (Opinión)',
    'bi bi-palette' => 'Paleta (Arte/Cultura)',
    'bi bi-trophy' => 'Trofeo (Deportes)',
    'bi bi-camera-reels' => 'Cámara (Multimedia)',
    'bi bi-mic' => 'Micrófono (Entrevistas)',
    'bi bi-lightbulb' => 'Bombilla (Tips/Ideas)',
    'bi bi-people' => 'Personas (Vida estudiantil)',
    'bi bi-book' => 'Libro (Académico)',
    'bi bi-calendar-event' => 'Calendario (Eventos)',
    'bi bi-heart' => 'Corazón (Salud/Bienestar)',
    'bi bi-globe' => 'Mundo (Internacional)',
    'bi bi-laptop' => 'Laptop (Tecnología)',
    'bi bi-music-note' => 'Nota musical (Música)',
    'bi bi-camera' => 'Cámara (Fotografía)',
    'bi bi-star' => 'Estrella (Destacados)'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Categorías - Student News</title>
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
                            <a class="nav-link" href="gestionar_usuarios.php">
                                <i class="bi bi-people"></i> Gestionar Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="gestionar_categorias.php">
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
                    <h1 class="h2">Gestionar Categorías</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCategoria">
                                <i class="bi bi-plus-circle"></i> Nueva Categoría
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
                    <div class="col-md-6">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-tags fs-1"></i>
                                <h3><?= $stats['total_categorias'] ?></h3>
                                <p class="card-text">Total Categorías</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-check-circle fs-1"></i>
                                <h3><?= $stats['categorias_con_articulos'] ?></h3>
                                <p class="card-text">Con Artículos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de categorías -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list"></i> Categorías Existentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($categorias as $categoria): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="<?= htmlspecialchars($categoria['icono']) ?> fs-1 text-primary me-3"></i>
                                            <div>
                                                <h6 class="card-title mb-1"><?= htmlspecialchars($categoria['nombre_categoria']) ?></h6>
                                                <small class="text-muted">ID: <?= $categoria['id_categoria'] ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="border-end">
                                                    <h5 class="text-success"><?= $categoria['articulos_publicados'] ?></h5>
                                                    <small class="text-muted">Publicados</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="text-info"><?= $categoria['total_articulos'] ?></h5>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                        
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarCategoria<?= $categoria['id_categoria'] ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEliminarCategoria<?= $categoria['id_categoria'] ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para editar categoría -->
                            <div class="modal fade" id="modalEditarCategoria<?= $categoria['id_categoria'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Categoría</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="accion" value="editar_categoria">
                                                <input type="hidden" name="id_categoria" value="<?= $categoria['id_categoria'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="nombre_categoria_edit<?= $categoria['id_categoria'] ?>" class="form-label">Nombre de la Categoría:</label>
                                                    <input type="text" class="form-control" 
                                                           name="nombre_categoria" 
                                                           id="nombre_categoria_edit<?= $categoria['id_categoria'] ?>" 
                                                           value="<?= htmlspecialchars($categoria['nombre_categoria']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="icono_edit<?= $categoria['id_categoria'] ?>" class="form-label">Icono:</label>
                                                    <select class="form-select" name="icono" id="icono_edit<?= $categoria['id_categoria'] ?>">
                                                        <?php foreach ($iconos_disponibles as $icono => $descripcion): ?>
                                                        <option value="<?= $icono ?>" <?= $categoria['icono'] === $icono ? 'selected' : '' ?>>
                                                            <?= $descripcion ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <strong>Vista previa:</strong>
                                                    <div class="mt-2">
                                                        <i class="<?= htmlspecialchars($categoria['icono']) ?>"></i>
                                                        <?= htmlspecialchars($categoria['nombre_categoria']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para eliminar categoría -->
                            <div class="modal fade" id="modalEliminarCategoria<?= $categoria['id_categoria'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Eliminar Categoría</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="accion" value="eliminar_categoria">
                                                <input type="hidden" name="id_categoria" value="<?= $categoria['id_categoria'] ?>">
                                                
                                                <?php if ($categoria['total_articulos'] > 0): ?>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>¡No se puede eliminar!</strong><br>
                                                    Esta categoría tiene <?= $categoria['total_articulos'] ?> artículos asociados.
                                                    Debes reasignar o eliminar los artículos primero.
                                                </div>
                                                <?php else: ?>
                                                <div class="alert alert-danger">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                                                </div>
                                                
                                                <p>¿Estás seguro de que deseas eliminar la categoría 
                                                   <strong><?= htmlspecialchars($categoria['nombre_categoria']) ?></strong>?</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <?php if ($categoria['total_articulos'] == 0): ?>
                                                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear nueva categoría -->
    <div class="modal fade" id="modalCrearCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="crear_categoria">
                        
                        <div class="mb-3">
                            <label for="nombre_categoria_new" class="form-label">Nombre de la Categoría:</label>
                            <input type="text" class="form-control" name="nombre_categoria" id="nombre_categoria_new" 
                                   placeholder="Ej: Ciencia y Tecnología" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="icono_new" class="form-label">Icono:</label>
                            <select class="form-select" name="icono" id="icono_new" required>
                                <option value="">Selecciona un icono</option>
                                <?php foreach ($iconos_disponibles as $icono => $descripcion): ?>
                                <option value="<?= $icono ?>"><?= $descripcion ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Consejo:</strong> Elige un nombre descriptivo y un icono que represente bien el tipo de contenido.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
