<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publicas/login.php");
    exit();
}

// Verificar que no sea administrador (ellos tienen su propio dashboard)
if ($_SESSION['id_rol'] == 4) {
    header("Location: admin/dashboard.php");
    exit();
}

require_once '../../includes/db.php';

$id_usuario = $_SESSION['id_usuario'];
$nombre_rol = $_SESSION['nombre_rol'] ?? 'usuario';
$pdo = getDB();

// Obtener estadísticas del usuario
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_articulos,
    SUM(CASE WHEN estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
    SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) as borradores,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados,
    SUM(vistas) as total_vistas
    FROM articulos WHERE id_autor = ?");
$stmt->execute([$id_usuario]);
$estadisticas = $stmt->fetch();

// Obtener los últimos artículos del usuario
$stmt = $pdo->prepare("SELECT a.*, c.nombre_categoria, c.icono
    FROM articulos a
    LEFT JOIN categorias c ON a.id_categoria = c.id_categoria
    WHERE a.id_autor = ? 
    ORDER BY a.fecha_creacion DESC 
    LIMIT 5");
$stmt->execute([$id_usuario]);
$mis_articulos = $stmt->fetchAll();

// Obtener artículos más vistos del usuario
$stmt = $pdo->prepare("SELECT titulo, vistas, fecha_publicacion
    FROM articulos 
    WHERE id_autor = ? AND estado = 'publicado' AND vistas > 0
    ORDER BY vistas DESC 
    LIMIT 3");
$stmt->execute([$id_usuario]);
$articulos_populares = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">
                STUDENT NEWS<br>
                <small class="text-light" style="font-size: 0.8rem;">Panel de <?= ucfirst($nombre_rol) ?></small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Mi Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="articulo_form.php">Crear Artículo</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../index.php">Ver Sitio</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nombre_completo']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../../procesos/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php
        // Mostrar mensajes de éxito/error
        if (isset($_SESSION['mensaje'])) {
            $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
            echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION['mensaje'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        }
        ?>

        <!-- Saludo personalizado -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <h2 class="mb-1">¡Hola, <?= htmlspecialchars($_SESSION['nombre_completo']) ?>! 👋</h2>
                        <p class="mb-0">Bienvenido a tu panel personal. Aquí puedes gestionar tus artículos y ver tus estadísticas.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del usuario -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-bg-primary h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-text fs-1 mb-2"></i>
                        <h4><?= $estadisticas['total_articulos'] ?? 0 ?></h4>
                        <p class="mb-0">Total Artículos</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-success h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1 mb-2"></i>
                        <h4><?= $estadisticas['publicados'] ?? 0 ?></h4>
                        <p class="mb-0">Publicados</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-warning h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clock fs-1 mb-2"></i>
                        <h4><?= $estadisticas['pendientes'] ?? 0 ?></h4>
                        <p class="mb-0">En Revisión</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-info h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-eye fs-1 mb-2"></i>
                        <h4><?= $estadisticas['total_vistas'] ?? 0 ?></h4>
                        <p class="mb-0">Total Vistas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mis últimos artículos -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-newspaper"></i> Mis Últimos Artículos</h5>
                        <a href="articulo_form.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Nuevo
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($mis_articulos): ?>
                            <?php foreach ($mis_articulos as $articulo): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    <i class="<?= $articulo['icono'] ?? 'bi bi-file-text' ?> fs-4 text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($articulo['titulo']) ?></h6>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($articulo['nombre_categoria'] ?? 'Sin categoría') ?> • 
                                        <?= date('d/m/Y', strtotime($articulo['fecha_creacion'])) ?>
                                        <?php if ($articulo['vistas'] > 0): ?>
                                            • <?= $articulo['vistas'] ?> vistas
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php
                                    $badge_class = match($articulo['estado']) {
                                        'publicado' => 'bg-success',
                                        'pendiente' => 'bg-warning text-dark',
                                        'borrador' => 'bg-secondary',
                                        'rechazado' => 'bg-danger',
                                        default => 'bg-light text-dark'
                                    };
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= ucfirst($articulo['estado']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-file-text text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-2">No has creado artículos aún</h5>
                                <p class="text-muted">¡Es hora de compartir tus ideas con el mundo!</p>
                                <a href="articulo_form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Crear mi primer artículo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Acciones rápidas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="articulo_form.php" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> Crear Artículo
                            </a>
                            <a href="perfil.php" class="btn btn-outline-secondary">
                                <i class="bi bi-person"></i> Editar Perfil
                            </a>
                            <a href="../publicas/participar.php" class="btn btn-outline-info">
                                <i class="bi bi-lightbulb"></i> Enviar Idea
                            </a>
                            <a href="../../index.php" class="btn btn-outline-success">
                                <i class="bi bi-house"></i> Ver Sitio Web
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Artículos más populares del usuario -->
                <?php if ($articulos_populares): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Mis Artículos Más Vistos</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($articulos_populares as $popular): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars(substr($popular['titulo'], 0, 40)) ?>...</h6>
                                <small class="text-muted">
                                    <i class="bi bi-eye"></i> <?= $popular['vistas'] ?> vistas • 
                                    <?= date('d/m/Y', strtotime($popular['fecha_publicacion'])) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>