<?php
session_start();

// Verificar si el usuario está logueado y tiene permisos de admin o redactor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] != 'admin' && $_SESSION['tipo_usuario'] != 'redactor')) {
    header('Location: ../login.php?mensaje=Acceso no autorizado');
    exit();
}

$page_title = "Panel de Administración - Periódico Digital";
include_once '../../includes/header.php';
include_once '../../includes/conexion.php';
include_once '../../includes/navbar.php';

// Obtener estadísticas
$sql_stats = "SELECT 
        (SELECT COUNT(*) FROM noticias WHERE activa = TRUE) as total_noticias,
        (SELECT COUNT(*) FROM categorias) as total_categorias,
        (SELECT COUNT(*) FROM usuarios WHERE activo = TRUE) as total_usuarios,
        (SELECT COUNT(*) FROM comentarios WHERE activo = TRUE) as total_comentarios,
        (SELECT COUNT(*) FROM noticias WHERE DATE(fecha_publicacion) = CURDATE()) as noticias_hoy,
        (SELECT COUNT(*) FROM eventos WHERE activo = TRUE) as total_eventos,
        (SELECT COUNT(*) FROM eventos WHERE activo = TRUE AND fecha_evento >= CURDATE()) as eventos_futuros
";
$resultado_stats = mysqli_query($conexion, $sql_stats);
$stats = mysqli_fetch_assoc($resultado_stats);

// Obtener últimas noticias
$sql_ultimas = "SELECT n.id, n.titulo, n.fecha_publicacion, c.nombre AS categoria_nombre, n.activa
                FROM noticias n
                JOIN categorias c ON n.id_categoria = c.id
                ORDER BY n.fecha_publicacion DESC
                LIMIT 5";
$ultimas_noticias = mysqli_query($conexion, $sql_ultimas);

// Obtener últimos comentarios
$sql_comentarios = "SELECT c.contenido, c.fecha_comentario, u.nombre, u.apellido, n.titulo AS noticia_titulo
                   FROM comentarios c
                   JOIN usuarios u ON c.id_usuario = u.id
                   JOIN noticias n ON c.id_noticia = n.id
                   WHERE c.activo = TRUE
                   ORDER BY c.fecha_comentario DESC
                   LIMIT 5";
$ultimos_comentarios = mysqli_query($conexion, $sql_comentarios);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <h5><i class="bi bi-speedometer2"></i> Panel Admin</h5>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
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
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_eventos.php">
                                <i class="bi bi-calendar-event"></i> Eventos
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
                <div>
                    <span class="badge bg-success">Conectado como: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                </div>
            </div>

            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['total_noticias']; ?></h4>
                                    <p class="mb-0">Total Noticias</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-newspaper" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['noticias_destacadas']; ?></h4>
                                    <p class="mb-0">Destacadas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-star-fill" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['total_usuarios']; ?></h4>
                                    <p class="mb-0">Usuarios</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['total_comentarios']; ?></h4>
                                    <p class="mb-0">Comentarios</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-chat-left-text" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segunda fila de estadísticas: Eventos -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['total_eventos']; ?></h4>
                                    <p class="mb-0">Total Eventos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-event" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['eventos_futuros']; ?></h4>
                                    <p class="mb-0">Próximos Eventos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-plus" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                

                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $stats['total_categorias']; ?></h4>
                                    <p class="mb-0">Categorías</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-tags" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Últimas noticias publicadas -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimas Noticias</h5>
                            <a href="gestionar_noticias.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                        </div>
                        <div class="card-body">
                            <?php while ($noticia = mysqli_fetch_assoc($ultimas_noticias)): ?>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="../noticias_detalle.php?id=<?php echo $noticia['id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($noticia['titulo']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($noticia['categoria_nombre']); ?> • 
                                            <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?>
                                        </small>
                                    </div>
                                    <div class="ms-2">
                                        <?php if ($noticia['activa']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Últimos comentarios -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Comentarios Recientes</h5>
                        </div>
                        <div class="card-body">
                            <?php while ($comentario = mysqli_fetch_assoc($ultimos_comentarios)): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="text-primary">
                                            <?php echo htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellido']); ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($comentario['fecha_comentario'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars(substr($comentario['contenido'], 0, 100)) . (strlen($comentario['contenido']) > 100 ? '...' : ''); ?></p>
                                    <small class="text-muted">En: <?php echo htmlspecialchars($comentario['noticia_titulo']); ?></small>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-lightning"></i> Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="crear_noticia.php" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle"></i> Nueva Noticia
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="gestionar_noticias.php" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-list-ul"></i> Ver Todas las Noticias
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="../../index.php" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-eye"></i> Ver Sitio Público
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>