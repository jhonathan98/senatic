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
        
        if ($accion === 'crear_evento') {
            $nombre = trim($_POST['nombre_evento'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fecha = $_POST['fecha'] ?? '';
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            
            if (empty($nombre) || empty($fecha)) {
                throw new Exception("El nombre del evento y la fecha son obligatorios.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descripcion, fecha_evento, lugar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $fecha, $ubicacion]);
            $mensaje = "Evento creado exitosamente.";
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'editar_evento') {
            $id_evento = (int)($_POST['id_evento'] ?? 0);
            $nombre = trim($_POST['nombre_evento'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fecha = $_POST['fecha'] ?? '';
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            
            if (empty($nombre) || empty($fecha)) {
                throw new Exception("El nombre del evento y la fecha son obligatorios.");
            }
            
            $stmt = $pdo->prepare("UPDATE eventos SET titulo = ?, descripcion = ?, fecha_evento = ?, lugar = ? WHERE id_evento = ?");
            $stmt->execute([$nombre, $descripcion, $fecha, $ubicacion, $id_evento]);
            $mensaje = "Evento actualizado exitosamente.";
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'eliminar_evento') {
            $id_evento = (int)($_POST['id_evento'] ?? 0);
            
            // Verificar si hay participaciones en este evento
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM participaciones WHERE id_evento = ?");
            $stmt->execute([$id_evento]);
            $total_participaciones = $stmt->fetch()['total'];
            
            if ($total_participaciones > 0) {
                throw new Exception("No se puede eliminar el evento porque tiene $total_participaciones participaciones registradas.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $stmt->execute([$id_evento]);
            $mensaje = "Evento eliminado exitosamente.";
            $tipo_mensaje = 'success';
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

try {
    $pdo = getDB();
    
    // Obtener todos los eventos con estadísticas
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(p.id_participacion) as total_participaciones
        FROM eventos e
        LEFT JOIN participaciones p ON e.id_evento = p.id_evento
        GROUP BY e.id_evento
        ORDER BY e.fecha_evento DESC
    ");
    $eventos = $stmt->fetchAll();
    
    // Estadísticas generales
    $stats = [];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos");
    $stats['total_eventos'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()");
    $stats['eventos_futuros'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM participaciones");
    $stats['total_participaciones'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Error en gestionar_eventos.php: " . $e->getMessage());
    $eventos = [];
    $stats = ['total_eventos' => 0, 'eventos_futuros' => 0, 'total_participaciones' => 0];
}

// Función para formatear fecha
function formatearFecha($fecha) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('j', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)];
    $anio = date('Y', $timestamp);
    
    return "$dia de $mes de $anio";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Eventos - Student News</title>
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
                            <a class="nav-link" href="gestionar_categorias.php">
                                <i class="bi bi-tags"></i> Gestionar Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="gestionar_eventos.php">
                                <i class="bi bi-calendar-event"></i> Gestionar Eventos
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestionar Eventos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEvento">
                                <i class="bi bi-plus-circle"></i> Nuevo Evento
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
                    <div class="col-md-4">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-calendar-event fs-1"></i>
                                <h3><?= $stats['total_eventos'] ?></h3>
                                <p class="card-text">Total Eventos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-calendar-plus fs-1"></i>
                                <h3><?= $stats['eventos_futuros'] ?></h3>
                                <p class="card-text">Eventos Futuros</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-people fs-1"></i>
                                <h3><?= $stats['total_participaciones'] ?></h3>
                                <p class="card-text">Total Participaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de eventos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list"></i> Eventos Existentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($eventos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x fs-1 text-muted"></i>
                            <h5 class="text-muted mt-3">No hay eventos registrados</h5>
                            <p class="text-muted">Crea el primer evento usando el botón "Nuevo Evento".</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($eventos as $evento): ?>
                            <?php 
                                $es_futuro = strtotime($evento['fecha_evento']) >= strtotime(date('Y-m-d'));
                                $es_hoy = date('Y-m-d') === $evento['fecha_evento'];
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 <?= $es_hoy ? 'border-warning' : ($es_futuro ? '' : 'border-secondary') ?>">
                                    <?php if ($es_hoy): ?>
                                    <div class="card-header bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> ¡Evento Hoy!
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($evento['titulo']) ?></h6>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= formatearFecha($evento['fecha_evento']) ?>
                                            </small>
                                            <?php if (!empty($evento['lugar'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($evento['lugar']) ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($evento['descripcion'])): ?>
                                        <p class="card-text">
                                            <?= nl2br(htmlspecialchars(substr($evento['descripcion'], 0, 100))) ?>
                                            <?= strlen($evento['descripcion']) > 100 ? '...' : '' ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="text-center mb-3">
                                            <span class="badge bg-info fs-6">
                                                <i class="bi bi-people"></i> <?= $evento['total_participaciones'] ?> participaciones
                                            </span>
                                        </div>
                                        
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarEvento<?= $evento['id_evento'] ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEliminarEvento<?= $evento['id_evento'] ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$es_futuro && !$es_hoy): ?>
                                    <div class="card-footer text-muted">
                                        <small><i class="bi bi-clock-history"></i> Evento pasado</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Modal para editar evento -->
                            <div class="modal fade" id="modalEditarEvento<?= $evento['id_evento'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Evento</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="accion" value="editar_evento">
                                                <input type="hidden" name="id_evento" value="<?= $evento['id_evento'] ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="mb-3">
                                                            <label for="nombre_evento_edit<?= $evento['id_evento'] ?>" class="form-label">Nombre del Evento:</label>
                                                            <input type="text" class="form-control" 
                                                                   name="nombre_evento" 
                                                                   id="nombre_evento_edit<?= $evento['id_evento'] ?>" 
                                                                   value="<?= htmlspecialchars($evento['titulo']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="fecha_edit<?= $evento['id_evento'] ?>" class="form-label">Fecha:</label>
                                                            <input type="date" class="form-control" 
                                                                   name="fecha" 
                                                                   id="fecha_edit<?= $evento['id_evento'] ?>" 
                                                                   value="<?= $evento['fecha_evento'] ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="ubicacion_edit<?= $evento['id_evento'] ?>" class="form-label">Ubicación:</label>
                                                    <input type="text" class="form-control" 
                                                           name="ubicacion" 
                                                           id="ubicacion_edit<?= $evento['id_evento'] ?>" 
                                                           value="<?= htmlspecialchars($evento['lugar']) ?>"
                                                           placeholder="Ej: Auditorio Principal, Aula 204, etc.">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="descripcion_edit<?= $evento['id_evento'] ?>" class="form-label">Descripción:</label>
                                                    <textarea class="form-control" 
                                                              name="descripcion" 
                                                              id="descripcion_edit<?= $evento['id_evento'] ?>" 
                                                              rows="4" 
                                                              placeholder="Describe los detalles del evento..."><?= htmlspecialchars($evento['descripcion']) ?></textarea>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i>
                                                    <strong>Participaciones:</strong> Este evento tiene <?= $evento['total_participaciones'] ?> participaciones registradas.
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

                            <!-- Modal para eliminar evento -->
                            <div class="modal fade" id="modalEliminarEvento<?= $evento['id_evento'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Eliminar Evento</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="accion" value="eliminar_evento">
                                                <input type="hidden" name="id_evento" value="<?= $evento['id_evento'] ?>">
                                                
                                                <?php if ($evento['total_participaciones'] > 0): ?>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>¡No se puede eliminar!</strong><br>
                                                    Este evento tiene <?= $evento['total_participaciones'] ?> participaciones registradas.
                                                    Debes contactar a los participantes antes de eliminarlo.
                                                </div>
                                                <?php else: ?>
                                                <div class="alert alert-danger">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                                                </div>
                                                
                                                <p>¿Estás seguro de que deseas eliminar el evento 
                                                   <strong><?= htmlspecialchars($evento['titulo']) ?></strong>?</p>
                                                
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6><?= htmlspecialchars($evento['titulo']) ?></h6>
                                                        <p class="mb-1"><i class="bi bi-calendar"></i> <?= formatearFecha($evento['fecha_evento']) ?></p>
                                                        <?php if (!empty($evento['lugar'])): ?>
                                                        <p class="mb-0"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($evento['lugar']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <?php if ($evento['total_participaciones'] == 0): ?>
                                                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear nuevo evento -->
    <div class="modal fade" id="modalCrearEvento" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="crear_evento">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nombre_evento_new" class="form-label">Nombre del Evento:</label>
                                    <input type="text" class="form-control" name="nombre_evento" id="nombre_evento_new" 
                                           placeholder="Ej: Conferencia de Tecnología 2024" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fecha_new" class="form-label">Fecha:</label>
                                    <input type="date" class="form-control" name="fecha" id="fecha_new" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion_new" class="form-label">Ubicación:</label>
                            <input type="text" class="form-control" name="ubicacion" id="ubicacion_new" 
                                   placeholder="Ej: Auditorio Principal, Aula 204, etc.">
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion_new" class="form-label">Descripción:</label>
                            <textarea class="form-control" name="descripcion" id="descripcion_new" rows="4" 
                                      placeholder="Describe los detalles del evento, agenda, ponentes, etc."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Consejo:</strong> Una buena descripción ayuda a los estudiantes a entender de qué trata el evento y los motiva a participar.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Establecer fecha mínima como hoy
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInputs = document.querySelectorAll('input[type="date"]');
            const hoy = new Date().toISOString().split('T')[0];
            fechaInputs.forEach(input => {
                if (input.id.includes('_new')) {
                    input.min = hoy;
                }
            });
        });
    </script>
</body>
</html>
