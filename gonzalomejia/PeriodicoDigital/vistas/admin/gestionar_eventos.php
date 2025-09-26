<?php
session_start();

// Solo admin puede gestionar eventos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'admin') {
    header('Location: ../login.php?mensaje=Acceso no autorizado');
    exit();
}

$page_title = "Gestionar Eventos - Panel de Administración";
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
            $titulo = trim($_POST['titulo']);
            $descripcion = trim($_POST['descripcion']);
            $fecha_evento = $_POST['fecha_evento'];
            $hora_evento = $_POST['hora_evento'] ?? null;
            $lugar = trim($_POST['lugar']);
            $tipo_evento = $_POST['tipo_evento'];
            $cupo_maximo = (int)($_POST['cupo_maximo'] ?? 0);
            $precio = floatval($_POST['precio'] ?? 0);
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            
            // Validaciones
            if (empty($titulo) || empty($fecha_evento)) {
                $error = 'El título y la fecha del evento son obligatorios.';
            } elseif (strtotime($fecha_evento) < strtotime(date('Y-m-d'))) {
                $error = 'La fecha del evento no puede ser anterior a hoy.';
            } else {
                // Procesar imagen si se subió
                $imagen_evento = null;
                if (isset($_FILES['imagen_evento']) && $_FILES['imagen_evento']['error'] == 0) {
                    $directorio_upload = '../../uploads/eventos/';
                    if (!file_exists($directorio_upload)) {
                        mkdir($directorio_upload, 0777, true);
                    }
                    
                    $extension = strtolower(pathinfo($_FILES['imagen_evento']['name'], PATHINFO_EXTENSION));
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extension, $extensiones_permitidas)) {
                        $nombre_archivo = uniqid() . '.' . $extension;
                        $ruta_completa = $directorio_upload . $nombre_archivo;
                        
                        if (move_uploaded_file($_FILES['imagen_evento']['tmp_name'], $ruta_completa)) {
                            $imagen_evento = 'uploads/eventos/' . $nombre_archivo;
                        }
                    }
                }
                
                // Crear evento
                $sql_crear = "INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_evento, lugar, imagen_evento, tipo_evento, cupo_maximo, precio, destacado, id_creador) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_crear = mysqli_prepare($conexion, $sql_crear);
                mysqli_stmt_bind_param($stmt_crear, "sssssssidii", 
                    $titulo, $descripcion, $fecha_evento, $hora_evento, $lugar, $imagen_evento, 
                    $tipo_evento, $cupo_maximo, $precio, $destacado, $_SESSION['usuario_id']);
                
                if (mysqli_stmt_execute($stmt_crear)) {
                    $mensaje = 'Evento creado exitosamente.';
                } else {
                    $error = 'Error al crear el evento.';
                }
                mysqli_stmt_close($stmt_crear);
            }
            break;
            
        case 'editar':
            $id_evento = (int)$_POST['id_evento'];
            $titulo = trim($_POST['titulo']);
            $descripcion = trim($_POST['descripcion']);
            $fecha_evento = $_POST['fecha_evento'];
            $hora_evento = $_POST['hora_evento'] ?? null;
            $lugar = trim($_POST['lugar']);
            $tipo_evento = $_POST['tipo_evento'];
            $cupo_maximo = (int)($_POST['cupo_maximo'] ?? 0);
            $precio = floatval($_POST['precio'] ?? 0);
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            $estado = $_POST['estado'];
            
            if (empty($titulo) || empty($fecha_evento)) {
                $error = 'El título y la fecha del evento son obligatorios.';
            } else {
                // Procesar nueva imagen si se subió
                $imagen_evento = $_POST['imagen_actual'] ?? null;
                if (isset($_FILES['imagen_evento']) && $_FILES['imagen_evento']['error'] == 0) {
                    $directorio_upload = '../../uploads/eventos/';
                    if (!file_exists($directorio_upload)) {
                        mkdir($directorio_upload, 0777, true);
                    }
                    
                    $extension = strtolower(pathinfo($_FILES['imagen_evento']['name'], PATHINFO_EXTENSION));
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extension, $extensiones_permitidas)) {
                        // Eliminar imagen anterior si existe
                        if ($imagen_evento && file_exists('../../' . $imagen_evento)) {
                            unlink('../../' . $imagen_evento);
                        }
                        
                        $nombre_archivo = uniqid() . '.' . $extension;
                        $ruta_completa = $directorio_upload . $nombre_archivo;
                        
                        if (move_uploaded_file($_FILES['imagen_evento']['tmp_name'], $ruta_completa)) {
                            $imagen_evento = 'uploads/eventos/' . $nombre_archivo;
                        }
                    }
                }
                
                // Actualizar evento
                $sql_editar = "UPDATE eventos SET titulo = ?, descripcion = ?, fecha_evento = ?, hora_evento = ?, 
                              lugar = ?, imagen_evento = ?, tipo_evento = ?, cupo_maximo = ?, precio = ?, 
                              destacado = ?, estado = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt_editar = mysqli_prepare($conexion, $sql_editar);
                mysqli_stmt_bind_param($stmt_editar, "sssssssidiisi", 
                    $titulo, $descripcion, $fecha_evento, $hora_evento, $lugar, $imagen_evento, 
                    $tipo_evento, $cupo_maximo, $precio, $destacado, $estado, $id_evento);
                
                if (mysqli_stmt_execute($stmt_editar)) {
                    $mensaje = 'Evento actualizado exitosamente.';
                } else {
                    $error = 'Error al actualizar el evento.';
                }
                mysqli_stmt_close($stmt_editar);
            }
            break;
            
        case 'eliminar':
            $id_evento = (int)$_POST['id_evento'];
            
            // Verificar si hay inscripciones
            $sql_verificar = "SELECT COUNT(*) as total FROM inscripciones_eventos WHERE id_evento = ?";
            $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
            mysqli_stmt_bind_param($stmt_verificar, "i", $id_evento);
            mysqli_stmt_execute($stmt_verificar);
            $resultado = mysqli_stmt_get_result($stmt_verificar);
            $total_inscripciones = mysqli_fetch_assoc($resultado)['total'];
            
            if ($total_inscripciones > 0) {
                $error = "No se puede eliminar el evento porque tiene $total_inscripciones inscripciones registradas.";
            } else {
                // Obtener imagen para eliminarla
                $sql_imagen = "SELECT imagen_evento FROM eventos WHERE id = ?";
                $stmt_imagen = mysqli_prepare($conexion, $sql_imagen);
                mysqli_stmt_bind_param($stmt_imagen, "i", $id_evento);
                mysqli_stmt_execute($stmt_imagen);
                $resultado_imagen = mysqli_stmt_get_result($stmt_imagen);
                $evento_data = mysqli_fetch_assoc($resultado_imagen);
                
                // Eliminar evento
                $sql_eliminar = "DELETE FROM eventos WHERE id = ?";
                $stmt_eliminar = mysqli_prepare($conexion, $sql_eliminar);
                mysqli_stmt_bind_param($stmt_eliminar, "i", $id_evento);
                
                if (mysqli_stmt_execute($stmt_eliminar)) {
                    // Eliminar imagen del servidor
                    if ($evento_data['imagen_evento'] && file_exists('../../' . $evento_data['imagen_evento'])) {
                        unlink('../../' . $evento_data['imagen_evento']);
                    }
                    $mensaje = 'Evento eliminado exitosamente.';
                } else {
                    $error = 'Error al eliminar el evento.';
                }
                mysqli_stmt_close($stmt_eliminar);
                mysqli_stmt_close($stmt_imagen);
            }
            mysqli_stmt_close($stmt_verificar);
            break;
    }
}

// Obtener estadísticas
$sql_stats = "SELECT 
                COUNT(*) as total_eventos,
                SUM(CASE WHEN fecha_evento >= CURDATE() THEN 1 ELSE 0 END) as eventos_futuros,
                SUM(CASE WHEN fecha_evento < CURDATE() THEN 1 ELSE 0 END) as eventos_pasados,
                SUM(CASE WHEN destacado = 1 THEN 1 ELSE 0 END) as eventos_destacados
              FROM eventos WHERE activo = 1";
$resultado_stats = mysqli_query($conexion, $sql_stats);
$stats = mysqli_fetch_assoc($resultado_stats);

// Obtener total de inscripciones
$sql_inscripciones = "SELECT COUNT(*) as total_inscripciones FROM inscripciones_eventos";
$resultado_inscripciones = mysqli_query($conexion, $sql_inscripciones);
$stats['total_inscripciones'] = mysqli_fetch_assoc($resultado_inscripciones)['total_inscripciones'];

// Obtener todos los eventos con información adicional
$sql_eventos = "SELECT e.*, u.nombre as creador_nombre,
                (SELECT COUNT(*) FROM inscripciones_eventos WHERE id_evento = e.id) as total_inscripciones
                FROM eventos e 
                LEFT JOIN usuarios u ON e.id_creador = u.id 
                WHERE e.activo = 1
                ORDER BY e.fecha_evento DESC";
$eventos = mysqli_query($conexion, $sql_eventos);
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
                        <a class="nav-link active" href="gestionar_eventos.php">
                            <i class="bi bi-calendar-event"></i> Eventos
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-calendar-event"></i> Gestionar Eventos</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoEvento">
                    <i class="bi bi-plus-circle"></i> Nuevo Evento
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

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-event fs-1"></i>
                            <h3><?php echo $stats['total_eventos']; ?></h3>
                            <p class="mb-0">Total Eventos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-plus fs-1"></i>
                            <h3><?php echo $stats['eventos_futuros']; ?></h3>
                            <p class="mb-0">Próximos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-star fs-1"></i>
                            <h3><?php echo $stats['eventos_destacados']; ?></h3>
                            <p class="mb-0">Destacados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-info">
                        <div class="card-body text-center">
                            <i class="bi bi-people fs-1"></i>
                            <h3><?php echo $stats['total_inscripciones']; ?></h3>
                            <p class="mb-0">Inscripciones</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de eventos -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list"></i> Lista de Eventos</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($eventos) == 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                            <h3 class="mt-3 text-muted">No hay eventos</h3>
                            <p class="text-muted">Crea el primer evento para comenzar.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoEvento">
                                <i class="bi bi-plus-circle"></i> Crear Primer Evento
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php while ($evento = mysqli_fetch_assoc($eventos)): ?>
                                <?php 
                                $es_futuro = strtotime($evento['fecha_evento']) >= strtotime(date('Y-m-d'));
                                $es_hoy = date('Y-m-d') === $evento['fecha_evento'];
                                ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 <?php echo $es_hoy ? 'border-warning' : ($es_futuro ? 'border-success' : 'border-secondary'); ?>">
                                        <?php if ($evento['imagen_evento']): ?>
                                            <img src="../../<?php echo $evento['imagen_evento']; ?>" 
                                                 class="card-img-top" 
                                                 style="height: 200px; object-fit: cover;" 
                                                 alt="<?php echo htmlspecialchars($evento['titulo']); ?>">
                                        <?php endif; ?>
                                        
                                        <?php if ($evento['destacado']): ?>
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-star-fill"></i> Destacado
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> 
                                                    <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?>
                                                    <?php if ($evento['hora_evento']): ?>
                                                        - <?php echo date('H:i', strtotime($evento['hora_evento'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <?php if ($evento['lugar']): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($evento['lugar']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-2">
                                                <span class="badge bg-<?php 
                                                    switch($evento['tipo_evento']) {
                                                        case 'academico': echo 'primary'; break;
                                                        case 'cultural': echo 'success'; break;
                                                        case 'deportivo': echo 'warning'; break;
                                                        case 'institucional': echo 'info'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($evento['tipo_evento']); ?>
                                                </span>
                                                
                                                <span class="badge bg-<?php 
                                                    switch($evento['estado']) {
                                                        case 'programado': echo 'primary'; break;
                                                        case 'en_curso': echo 'success'; break;
                                                        case 'finalizado': echo 'secondary'; break;
                                                        case 'cancelado': echo 'danger'; break;
                                                    }
                                                ?> ms-1">
                                                    <?php echo ucfirst(str_replace('_', ' ', $evento['estado'])); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($evento['descripcion']): ?>
                                                <p class="card-text small">
                                                    <?php echo htmlspecialchars(substr($evento['descripcion'], 0, 100)); ?>
                                                    <?php echo strlen($evento['descripcion']) > 100 ? '...' : ''; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> 
                                                        <?php echo $evento['total_inscripciones']; ?> inscritos
                                                    </small>
                                                </div>
                                                <div class="col-6">
                                                    <?php if ($evento['precio'] > 0): ?>
                                                        <small class="text-success">
                                                            <i class="bi bi-currency-dollar"></i> 
                                                            $<?php echo number_format($evento['precio'], 0); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">
                                                            <i class="bi bi-gift"></i> Gratuito
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="editarEvento(<?php echo htmlspecialchars(json_encode($evento)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-info btn-sm" 
                                                        onclick="verInscritos(<?php echo $evento['id']; ?>)">
                                                    <i class="bi bi-people"></i>
                                                </button>
                                                <?php if ($evento['total_inscripciones'] == 0): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('¿Está seguro de eliminar este evento?')">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_evento" value="<?php echo $evento['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-danger btn-sm" disabled 
                                                            title="No se puede eliminar: tiene inscripciones">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Evento -->
<div class="modal fade" id="modalNuevoEvento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del Evento *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo_evento" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo_evento" name="tipo_evento" required>
                                    <option value="academico">Académico</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="deportivo">Deportivo</option>
                                    <option value="institucional">Institucional</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_evento" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha_evento" name="fecha_evento" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hora_evento" class="form-label">Hora</label>
                                <input type="time" class="form-control" id="hora_evento" name="hora_evento">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lugar" class="form-label">Lugar</label>
                        <input type="text" class="form-control" id="lugar" name="lugar" 
                               placeholder="Ej: Auditorio Principal, Patio Central...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                  placeholder="Describe los detalles del evento..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cupo_maximo" class="form-label">Cupo Máximo</label>
                                <input type="number" class="form-control" id="cupo_maximo" name="cupo_maximo" 
                                       min="0" placeholder="0 = Sin límite">
                                <div class="form-text">Deja en 0 si no hay límite de cupo</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio</label>
                                <input type="number" class="form-control" id="precio" name="precio" 
                                       min="0" step="0.01" placeholder="0.00">
                                <div class="form-text">Deja en 0 si es gratuito</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagen_evento" class="form-label">Imagen del Evento</label>
                        <input type="file" class="form-control" id="imagen_evento" name="imagen_evento" 
                               accept="image/*">
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                        <label class="form-check-label" for="destacado">
                            <i class="bi bi-star"></i> Marcar como evento destacado
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Crear Evento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Evento -->
<div class="modal fade" id="modalEditarEvento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_evento" id="edit_id_evento">
                    <input type="hidden" name="imagen_actual" id="edit_imagen_actual">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_titulo" class="form-label">Título del Evento *</label>
                                <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_tipo_evento" class="form-label">Tipo *</label>
                                <select class="form-select" id="edit_tipo_evento" name="tipo_evento" required>
                                    <option value="academico">Académico</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="deportivo">Deportivo</option>
                                    <option value="institucional">Institucional</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_fecha_evento" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="edit_fecha_evento" name="fecha_evento" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_hora_evento" class="form-label">Hora</label>
                                <input type="time" class="form-control" id="edit_hora_evento" name="hora_evento">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_estado" class="form-label">Estado</label>
                                <select class="form-select" id="edit_estado" name="estado">
                                    <option value="programado">Programado</option>
                                    <option value="en_curso">En Curso</option>
                                    <option value="finalizado">Finalizado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_lugar" class="form-label">Lugar</label>
                        <input type="text" class="form-control" id="edit_lugar" name="lugar">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="4"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cupo_maximo" class="form-label">Cupo Máximo</label>
                                <input type="number" class="form-control" id="edit_cupo_maximo" name="cupo_maximo" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_precio" class="form-label">Precio</label>
                                <input type="number" class="form-control" id="edit_precio" name="precio" 
                                       min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_imagen_evento" class="form-label">Imagen del Evento</label>
                        <input type="file" class="form-control" id="edit_imagen_evento" name="imagen_evento" 
                               accept="image/*">
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                        <div id="imagen_preview" class="mt-2"></div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_destacado" name="destacado">
                        <label class="form-check-label" for="edit_destacado">
                            <i class="bi bi-star"></i> Marcar como evento destacado
                        </label>
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
function editarEvento(evento) {
    document.getElementById('edit_id_evento').value = evento.id;
    document.getElementById('edit_titulo').value = evento.titulo;
    document.getElementById('edit_tipo_evento').value = evento.tipo_evento;
    document.getElementById('edit_fecha_evento').value = evento.fecha_evento;
    document.getElementById('edit_hora_evento').value = evento.hora_evento || '';
    document.getElementById('edit_lugar').value = evento.lugar || '';
    document.getElementById('edit_descripcion').value = evento.descripcion || '';
    document.getElementById('edit_cupo_maximo').value = evento.cupo_maximo;
    document.getElementById('edit_precio').value = evento.precio;
    document.getElementById('edit_estado').value = evento.estado;
    document.getElementById('edit_destacado').checked = evento.destacado == 1;
    document.getElementById('edit_imagen_actual').value = evento.imagen_evento || '';
    
    // Mostrar imagen actual si existe
    const preview = document.getElementById('imagen_preview');
    if (evento.imagen_evento) {
        preview.innerHTML = `<img src="../../${evento.imagen_evento}" class="img-thumbnail" style="max-width: 200px;">`;
    } else {
        preview.innerHTML = '';
    }
    
    new bootstrap.Modal(document.getElementById('modalEditarEvento')).show();
}

function verInscritos(idEvento) {
    // Implementar modal para ver lista de inscritos
    alert('Funcionalidad de ver inscritos - ID: ' + idEvento);
}
</script>

<?php include_once '../../includes/footer.php'; ?>
