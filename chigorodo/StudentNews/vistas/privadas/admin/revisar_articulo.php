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

// Verificar que se proporcionó un ID de artículo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id_articulo = (int)$_GET['id'];

// Procesar acciones de revisión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        $accion = $_POST['accion'] ?? '';
        $comentario = trim($_POST['comentario'] ?? '');
        
        if ($accion === 'aprobar') {
            $stmt = $pdo->prepare("
                UPDATE articulos 
                SET estado = 'publicado', fecha_publicacion = NOW() 
                WHERE id_articulo = ?
            ");
            $stmt->execute([$id_articulo]);
            $mensaje = "Artículo aprobado y publicado exitosamente.";
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'rechazar') {
            $stmt = $pdo->prepare("UPDATE articulos SET estado = 'rechazado' WHERE id_articulo = ?");
            $stmt->execute([$id_articulo]);
            $mensaje = "Artículo rechazado.";
            $tipo_mensaje = 'warning';
            
        } elseif ($accion === 'solicitar_cambios') {
            $stmt = $pdo->prepare("UPDATE articulos SET estado = 'borrador' WHERE id_articulo = ?");
            $stmt->execute([$id_articulo]);
            $mensaje = "Artículo devuelto al autor para realizar cambios.";
            $tipo_mensaje = 'info';
        }
        
        // Aquí podrías agregar lógica para enviar notificaciones al autor
        
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

try {
    $pdo = getDB();
    
    // Obtener información completa del artículo
    $stmt = $pdo->prepare("
        SELECT a.*, u.nombre_completo, u.correo, u.nombre_usuario, c.nombre_categoria, c.icono
        FROM articulos a
        JOIN usuarios u ON a.id_autor = u.id_usuario
        JOIN categorias c ON a.id_categoria = c.id_categoria
        WHERE a.id_articulo = ?
    ");
    $stmt->execute([$id_articulo]);
    $articulo = $stmt->fetch();
    
    if (!$articulo) {
        header("Location: dashboard.php");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error en revisar_articulo.php: " . $e->getMessage());
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar Artículo - Student News</title>
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
                            <a class="nav-link" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Revisar Artículo</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Contenido del artículo -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Contenido del Artículo</h5>
                                <span class="badge bg-<?= 
                                    $articulo['estado'] === 'pendiente' ? 'warning' : 
                                    ($articulo['estado'] === 'publicado' ? 'success' : 
                                    ($articulo['estado'] === 'rechazado' ? 'danger' : 'secondary')) ?>">
                                    <?= ucfirst($articulo['estado']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <!-- Imagen destacada -->
                                <?php if (!empty($articulo['imagen_destacada'])): ?>
                                <div class="mb-4">
                                    <img src="../../../assets/images/uploads/<?= htmlspecialchars($articulo['imagen_destacada']) ?>" 
                                         class="img-fluid rounded" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                                </div>
                                <?php endif; ?>

                                <!-- Título -->
                                <h1 class="mb-3"><?= htmlspecialchars($articulo['titulo']) ?></h1>

                                <!-- Metadatos -->
                                <div class="mb-4 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Categoría:</strong> 
                                                <i class="<?= $articulo['icono'] ?>"></i>
                                                <?= htmlspecialchars($articulo['nombre_categoria']) ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Autor:</strong> <?= htmlspecialchars($articulo['nombre_completo']) ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Fecha de creación:</strong> 
                                                <?= date('d \d\e F \d\e Y, H:i', strtotime($articulo['fecha_creacion'])) ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Vistas:</strong> <?= $articulo['vistas'] ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenido -->
                                <div class="article-content">
                                    <?= $articulo['contenido'] ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de revisión -->
                    <div class="col-lg-4">
                        <!-- Información del autor -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-person"></i> Información del Autor
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong>Nombre:</strong> <?= htmlspecialchars($articulo['nombre_completo']) ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Usuario:</strong> @<?= htmlspecialchars($articulo['nombre_usuario']) ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Email:</strong> 
                                    <a href="mailto:<?= htmlspecialchars($articulo['correo']) ?>">
                                        <?= htmlspecialchars($articulo['correo']) ?>
                                    </a>
                                </p>
                            </div>
                        </div>

                        <!-- Acciones de revisión -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-check-square"></i> Acciones de Revisión
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if ($articulo['estado'] === 'pendiente'): ?>
                                
                                <!-- Aprobar artículo -->
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <button type="submit" class="btn btn-success w-100" 
                                            onclick="return confirm('¿Aprobar y publicar este artículo?')">
                                        <i class="bi bi-check-lg"></i> Aprobar y Publicar
                                    </button>
                                </form>

                                <!-- Solicitar cambios -->
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="accion" value="solicitar_cambios">
                                    <div class="mb-3">
                                        <label for="comentario" class="form-label">Comentarios para el autor:</label>
                                        <textarea class="form-control" id="comentario" name="comentario" rows="3" 
                                                  placeholder="Indica qué cambios necesita el artículo..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bi bi-arrow-counterclockwise"></i> Solicitar Cambios
                                    </button>
                                </form>

                                <!-- Rechazar artículo -->
                                <form method="POST">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <div class="mb-3">
                                        <label for="comentario_rechazo" class="form-label">Motivo del rechazo:</label>
                                        <textarea class="form-control" id="comentario_rechazo" name="comentario" rows="3" 
                                                  placeholder="Explica por qué se rechaza el artículo..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100" 
                                            onclick="return confirm('¿Rechazar definitivamente este artículo?')">
                                        <i class="bi bi-x-lg"></i> Rechazar Artículo
                                    </button>
                                </form>

                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Estado:</strong> Este artículo ya ha sido <?= $articulo['estado'] ?>.
                                    <?php if ($articulo['estado'] === 'publicado' && $articulo['fecha_publicacion']): ?>
                                    <br><small>Publicado el <?= date('d/m/Y H:i', strtotime($articulo['fecha_publicacion'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estadísticas del artículo -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-graph-up"></i> Estadísticas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <h4 class="text-primary"><?= $articulo['vistas'] ?></h4>
                                            <small class="text-muted">Vistas</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?= str_word_count(strip_tags($articulo['contenido'])) ?></h4>
                                        <small class="text-muted">Palabras</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .article-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }
        
        .article-content h1,
        .article-content h2,
        .article-content h3,
        .article-content h4,
        .article-content h5,
        .article-content h6 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .article-content p {
            margin-bottom: 1.5rem;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
    </style>
</body>
</html>
