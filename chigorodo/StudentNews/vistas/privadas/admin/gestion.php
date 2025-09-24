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

try {
    $pdo = getDB();
    
    // Estadísticas generales del sistema
    $stats = [];
    
    // Usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'administrador'");
    $stats['total_administradores'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'redactor'");
    $stats['total_redactores'] = $stmt->fetch()['total'];
    
    // Artículos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM articulos");
    $stats['total_articulos'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM articulos WHERE estado = 'pendiente'");
    $stats['articulos_pendientes'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM articulos WHERE estado = 'publicado'");
    $stats['articulos_publicados'] = $stmt->fetch()['total'];
    
    // Categorías
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $stats['total_categorias'] = $stmt->fetch()['total'];
    
    // Eventos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos");
    $stats['total_eventos'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()");
    $stats['eventos_futuros'] = $stmt->fetch()['total'];
    
    // Participaciones
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM participaciones");
    $stats['total_participaciones'] = $stmt->fetch()['total'];
    
    // Contactos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contactos");
    $stats['total_contactos'] = $stmt->fetch()['total'];
    
    // Actividad reciente
    $stmt = $pdo->query("
        SELECT nombre_usuario, email, fecha_registro 
        FROM usuarios 
        ORDER BY fecha_registro DESC 
        LIMIT 5
    ");
    $usuarios_recientes = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT a.titulo, a.fecha_creacion, u.nombre_usuario 
        FROM articulos a 
        JOIN usuarios u ON a.id_usuario = u.id_usuario 
        ORDER BY a.fecha_creacion DESC 
        LIMIT 5
    ");
    $articulos_recientes = $stmt->fetchAll();
    
    // Obtener contactos pendientes
    $stmt = $pdo->query("
        SELECT * FROM contactos 
        WHERE estado = 'pendiente' 
        ORDER BY fecha_envio DESC
    ");
    $contactos_pendientes = $stmt->fetchAll();
    
    // Estadísticas
    $stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM participaciones WHERE estado = 'pendiente'");
    $stats['participaciones_pendientes'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contactos WHERE estado = 'pendiente'");
    $stats['contactos_pendientes'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM participaciones WHERE estado = 'aprobada'");
    $stats['participaciones_aprobadas'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Error en gestion.php: " . $e->getMessage());
    $stats = array_fill_keys([
        'total_usuarios', 'total_administradores', 'total_redactores',
        'total_articulos', 'articulos_pendientes', 'articulos_publicados',
        'total_categorias', 'total_eventos', 'eventos_futuros',
        'total_participaciones', 'total_contactos'
    ], 0);
    $usuarios_recientes = [];
    $articulos_recientes = [];
}

// Función para formatear fecha
function formatearFechaRelativa($fecha) {
    $timestamp = strtotime($fecha);
    $ahora = time();
    $diferencia = $ahora - $timestamp;
    
    if ($diferencia < 60) {
        return 'Hace menos de un minuto';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return "Hace $minutos minuto" . ($minutos > 1 ? 's' : '');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return "Hace $horas hora" . ($horas > 1 ? 's' : '');
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return "Hace $dias día" . ($dias > 1 ? 's' : '');
    } else {
        return date('j/n/Y', $timestamp);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Gestión - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
    <style>
        .management-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: none;
            height: 100%;
        }
        
        .management-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .activity-item:last-child {
            margin-bottom: 0;
        }
    </style>
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
                            <a class="nav-link active" href="gestion.php">
                                <i class="bi bi-collection"></i> Centro de Gestión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestionar_usuarios.php">
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
                    <h1 class="h2">
                        <i class="bi bi-collection"></i> Centro de Gestión
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="bi bi-people fs-1"></i>
                                <h3 class="mt-2"><?= $stats['total_usuarios'] ?></h3>
                                <p class="mb-0">Usuarios Registrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card warning">
                            <div class="card-body text-center">
                                <i class="bi bi-clock fs-1"></i>
                                <h3 class="mt-2"><?= $stats['articulos_pendientes'] ?></h3>
                                <p class="mb-0">Artículos Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card success">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle fs-1"></i>
                                <h3 class="mt-2"><?= $stats['articulos_publicados'] ?></h3>
                                <p class="mb-0">Artículos Publicados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card info">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-event fs-1"></i>
                                <h3 class="mt-2"><?= $stats['eventos_futuros'] ?></h3>
                                <p class="mb-0">Eventos Futuros</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulos de Gestión -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">
                            <i class="bi bi-grid-3x3-gap"></i> Módulos de Gestión
                        </h4>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-people-fill fs-1 text-primary"></i>
                                </div>
                                <h5 class="card-title">Usuarios</h5>
                                <p class="card-text">Gestiona usuarios, roles y permisos del sistema.</p>
                                <div class="row text-center mb-3">
                                    <div class="col">
                                        <small class="text-muted">Total: <?= $stats['total_usuarios'] ?></small>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Admins: <?= $stats['total_administradores'] ?></small>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Redactores: <?= $stats['total_redactores'] ?></small>
                                    </div>
                                </div>
                                <a href="gestionar_usuarios.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-right"></i> Gestionar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-tags-fill fs-1 text-success"></i>
                                </div>
                                <h5 class="card-title">Categorías</h5>
                                <p class="card-text">Administra las categorías de artículos y sus iconos.</p>
                                <div class="text-center mb-3">
                                    <span class="badge bg-success fs-6">
                                        <?= $stats['total_categorias'] ?> categorías disponibles
                                    </span>
                                </div>
                                <a href="gestionar_categorias.php" class="btn btn-success">
                                    <i class="bi bi-arrow-right"></i> Gestionar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-calendar-event-fill fs-1 text-info"></i>
                                </div>
                                <h5 class="card-title">Eventos</h5>
                                <p class="card-text">Crea y administra eventos estudiantiles.</p>
                                <div class="row text-center mb-3">
                                    <div class="col">
                                        <small class="text-muted">Total: <?= $stats['total_eventos'] ?></small>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Futuros: <?= $stats['eventos_futuros'] ?></small>
                                    </div>
                                </div>
                                <a href="gestionar_eventos.php" class="btn btn-info">
                                    <i class="bi bi-arrow-right"></i> Gestionar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-newspaper fs-1 text-warning"></i>
                                </div>
                                <h5 class="card-title">Artículos</h5>
                                <p class="card-text">Revisa y administra los artículos del sitio.</p>
                                <div class="row text-center mb-3">
                                    <div class="col">
                                        <small class="text-muted">Total: <?= $stats['total_articulos'] ?></small>
                                    </div>
                                    <div class="col">
                                        <small class="text-danger">Pendientes: <?= $stats['articulos_pendientes'] ?></small>
                                    </div>
                                </div>
                                <a href="revisar_articulo.php" class="btn btn-warning">
                                    <i class="bi bi-arrow-right"></i> Revisar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-chat-dots-fill fs-1 text-secondary"></i>
                                </div>
                                <h5 class="card-title">Mensajes</h5>
                                <p class="card-text">Revisa mensajes de contacto y participaciones.</p>
                                <div class="row text-center mb-3">
                                    <div class="col">
                                        <small class="text-muted">Contactos: <?= $stats['total_contactos'] ?></small>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Participaciones: <?= $stats['total_participaciones'] ?></small>
                                    </div>
                                </div>
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-arrow-right"></i> Próximamente
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card management-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-bar-chart-fill fs-1 text-dark"></i>
                                </div>
                                <h5 class="card-title">Reportes</h5>
                                <p class="card-text">Genera reportes y estadísticas del sistema.</p>
                                <div class="text-center mb-3">
                                    <span class="badge bg-dark fs-6">
                                        Sistema completo de análisis
                                    </span>
                                </div>
                                <button class="btn btn-dark" disabled>
                                    <i class="bi bi-arrow-right"></i> Próximamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-plus"></i> Usuarios Recientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($usuarios_recientes)): ?>
                                <p class="text-muted text-center py-3">No hay usuarios registrados recientemente.</p>
                                <?php else: ?>
                                <?php foreach ($usuarios_recientes as $usuario): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($usuario['nombre_usuario']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                                        </div>
                                        <small class="text-muted"><?= formatearFechaRelativa($usuario['fecha_registro']) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-file-earmark-text"></i> Artículos Recientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($articulos_recientes)): ?>
                                <p class="text-muted text-center py-3">No hay artículos creados recientemente.</p>
                                <?php else: ?>
                                <?php foreach ($articulos_recientes as $articulo): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars(substr($articulo['titulo'], 0, 40)) ?><?= strlen($articulo['titulo']) > 40 ? '...' : '' ?></h6>
                                            <small class="text-muted">por <?= htmlspecialchars($articulo['nombre_usuario']) ?></small>
                                        </div>
                                        <small class="text-muted"><?= formatearFechaRelativa($articulo['fecha_creacion']) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="participaciones-tab" data-bs-toggle="tab" 
                                data-bs-target="#participaciones" type="button" role="tab">
                            <i class="bi bi-lightbulb"></i> Participaciones (<?= count($participaciones_pendientes) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contactos-tab" data-bs-toggle="tab" 
                                data-bs-target="#contactos" type="button" role="tab">
                            <i class="bi bi-envelope"></i> Contactos (<?= count($contactos_pendientes) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="gestionTabsContent">
                    <!-- Tab de Participaciones -->
                    <div class="tab-pane fade show active" id="participaciones" role="tabpanel">
                        <div class="py-3">
                            <?php if ($participaciones_pendientes): ?>
                            <div class="row">
                                <?php foreach ($participaciones_pendientes as $participacion): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span class="badge bg-<?= $participacion['tipo_participacion'] === 'noticia' ? 'primary' : 
                                                ($participacion['tipo_participacion'] === 'evento' ? 'success' : 
                                                ($participacion['tipo_participacion'] === 'denuncia' ? 'danger' : 'secondary')) ?>">
                                                <?= ucfirst($participacion['tipo_participacion']) ?>
                                            </span>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($participacion['fecha_envio'])) ?>
                                            </small>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($participacion['titulo']) ?></h6>
                                            <p class="card-text">
                                                <strong>De:</strong> <?= htmlspecialchars($participacion['nombre']) ?><br>
                                                <strong>Email:</strong> <?= htmlspecialchars($participacion['email']) ?><br>
                                                <?php if ($participacion['telefono']): ?>
                                                <strong>Teléfono:</strong> <?= htmlspecialchars($participacion['telefono']) ?><br>
                                                <?php endif; ?>
                                            </p>
                                            <p class="card-text">
                                                <?= nl2br(htmlspecialchars(substr($participacion['descripcion'], 0, 200))) ?>
                                                <?= strlen($participacion['descripcion']) > 200 ? '...' : '' ?>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="aprobar_participacion">
                                                    <input type="hidden" name="id" value="<?= $participacion['id_participacion'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('¿Aprobar esta participación?')">
                                                        <i class="bi bi-check-circle"></i> Aprobar
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="rechazar_participacion">
                                                    <input type="hidden" name="id" value="<?= $participacion['id_participacion'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('¿Rechazar esta participación?')">
                                                        <i class="bi bi-x-circle"></i> Rechazar
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-info btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#modalParticipacion<?= $participacion['id_participacion'] ?>">
                                                    <i class="bi bi-eye"></i> Ver Completo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para ver participación completa -->
                                <div class="modal fade" id="modalParticipacion<?= $participacion['id_participacion'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?= htmlspecialchars($participacion['titulo']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Descripción completa:</strong></p>
                                                <div class="bg-light p-3 rounded">
                                                    <?= nl2br(htmlspecialchars($participacion['descripcion'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-check-all text-success" style="font-size: 5rem;"></i>
                                <h4 class="text-muted">No hay participaciones pendientes</h4>
                                <p class="text-muted">Todas las participaciones han sido procesadas.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab de Contactos -->
                    <div class="tab-pane fade" id="contactos" role="tabpanel">
                        <div class="py-3">
                            <?php if ($contactos_pendientes): ?>
                            <div class="row">
                                <?php foreach ($contactos_pendientes as $contacto): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span class="fw-bold"><?= htmlspecialchars($contacto['asunto']) ?></span>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($contacto['fecha_envio'])) ?>
                                            </small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>De:</strong> <?= htmlspecialchars($contacto['nombre']) ?><br>
                                                <strong>Email:</strong> <?= htmlspecialchars($contacto['email']) ?>
                                            </p>
                                            <p class="card-text">
                                                <?= nl2br(htmlspecialchars(substr($contacto['mensaje'], 0, 150))) ?>
                                                <?= strlen($contacto['mensaje']) > 150 ? '...' : '' ?>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <a href="mailto:<?= htmlspecialchars($contacto['email']) ?>?subject=Re: <?= htmlspecialchars($contacto['asunto']) ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="bi bi-reply"></i> Responder
                                                </a>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="marcar_respondido">
                                                    <input type="hidden" name="id" value="<?= $contacto['id_contacto'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check"></i> Marcar Respondido
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-info btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#modalContacto<?= $contacto['id_contacto'] ?>">
                                                    <i class="bi bi-eye"></i> Ver Completo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para ver contacto completo -->
                                <div class="modal fade" id="modalContacto<?= $contacto['id_contacto'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?= htmlspecialchars($contacto['asunto']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Mensaje completo:</strong></p>
                                                <div class="bg-light p-3 rounded">
                                                    <?= nl2br(htmlspecialchars($contacto['mensaje'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-success" style="font-size: 5rem;"></i>
                                <h4 class="text-muted">No hay contactos pendientes</h4>
                                <p class="text-muted">Todos los mensajes han sido respondidos.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
