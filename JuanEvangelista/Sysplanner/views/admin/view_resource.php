<?php
// views/admin/view_resource.php
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php?error=" . urlencode("No tiene permisos para acceder a esta página"));
    exit();
}

$page_title = "Ver Recurso - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ResourceController.php';
require_once '../../controllers/ReservationController.php';

$resourceController = new ResourceController();
$reservationController = new ReservationController();

$resource_id = $_GET['id'] ?? null;

if (!$resource_id) {
    header("Location: manage_resources.php?error=" . urlencode("ID de recurso no válido"));
    exit();
}

// Obtener datos del recurso
$resource = $resourceController->show($resource_id);

if (!$resource) {
    header("Location: manage_resources.php?error=" . urlencode("Recurso no encontrado"));
    exit();
}

// Obtener reservas del recurso
$resource_reservations = $reservationController->getResourceReservations($resource_id);

// Obtener estadísticas del recurso
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Contar reservas por período
$reservations_today = 0;
$reservations_week = 0;
$reservations_month = 0;
$total_reservations = 0;

// Re-ejecutar la consulta para estadísticas
$all_reservations = $reservationController->getResourceReservations($resource_id);
while ($reservation = $all_reservations->fetch(PDO::FETCH_ASSOC)) {
    $total_reservations++;
    $reservation_date = date('Y-m-d', strtotime($reservation['fecha_inicio']));
    
    if ($reservation_date === $today) {
        $reservations_today++;
    }
    
    if ($reservation_date >= $week_start && $reservation_date <= $week_end) {
        $reservations_week++;
    }
    
    if ($reservation_date >= $month_start && $reservation_date <= $month_end) {
        $reservations_month++;
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Información del Recurso -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-cube me-2"></i>Información del Recurso</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="edit_resource.php?id=<?php echo $resource->id; ?>">
                                <i class="fas fa-edit me-2"></i>Editar
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" 
                                   onclick="confirmDelete(<?php echo $resource->id; ?>, '<?php echo htmlspecialchars($resource->nombre, ENT_QUOTES); ?>')">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="resource-icon mb-3">
                            <?php
                            $icon_class = 'fas fa-cube';
                            switch (strtolower($resource->tipo)) {
                                case 'aula':
                                    $icon_class = 'fas fa-chalkboard-teacher';
                                    break;
                                case 'laboratorio':
                                    $icon_class = 'fas fa-flask';
                                    break;
                                case 'sala de sistemas':
                                    $icon_class = 'fas fa-desktop';
                                    break;
                                case 'auditorio':
                                    $icon_class = 'fas fa-theater-masks';
                                    break;
                                case 'sala de reuniones':
                                    $icon_class = 'fas fa-users';
                                    break;
                                case 'equipo':
                                    $icon_class = 'fas fa-tools';
                                    break;
                                case 'vehículo':
                                    $icon_class = 'fas fa-car';
                                    break;
                                case 'instalación deportiva':
                                    $icon_class = 'fas fa-futbol';
                                    break;
                            }
                            ?>
                            <i class="<?php echo $icon_class; ?> fa-4x text-primary"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($resource->nombre); ?></h4>
                        <span class="badge bg-info fs-6"><?php echo htmlspecialchars($resource->tipo); ?></span>
                        <div class="mt-2">
                            <span class="badge <?php echo $resource->activo ? 'bg-success' : 'bg-secondary'; ?> fs-6">
                                <?php echo $resource->activo ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="resource-details">
                        <?php if ($resource->descripcion): ?>
                            <div class="mb-3">
                                <strong>Descripción:</strong>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($resource->descripcion); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($resource->capacidad): ?>
                            <div class="mb-3">
                                <strong>Capacidad:</strong>
                                <p class="mb-0">
                                    <i class="fas fa-users text-primary me-1"></i>
                                    <?php echo $resource->capacidad; ?> personas
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <strong>Departamento:</strong>
                            <p class="mb-0">
                                <i class="fas fa-building text-primary me-1"></i>
                                <?php 
                                // Obtener nombre del departamento si existe
                                if ($resource->departamento_id) {
                                    require_once '../../models/Department.php';
                                    $department = new Department();
                                    $department->id = $resource->departamento_id;
                                    if ($department->readOne()) {
                                        echo htmlspecialchars($department->nombre);
                                    } else {
                                        echo 'Sin asignar';
                                    }
                                } else {
                                    echo 'Sin asignar';
                                }
                                ?>
                            </p>
                        </div>
                        
                        <?php if ($resource->caracteristicas): ?>
                            <div class="mb-3">
                                <strong>Características:</strong>
                                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($resource->caracteristicas)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas del Recurso -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Estadísticas de Uso</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border-end">
                                <h4 class="text-primary"><?php echo $reservations_today; ?></h4>
                                <small class="text-muted">Hoy</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="text-success"><?php echo $reservations_week; ?></h4>
                            <small class="text-muted">Esta Semana</small>
                        </div>
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-info"><?php echo $reservations_month; ?></h4>
                                <small class="text-muted">Este Mes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning"><?php echo $total_reservations; ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones Rápidas -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../user/make_reservation.php?resource_id=<?php echo $resource->id; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nueva Reserva
                        </a>
                        <a href="../public/schedule_public.php?resource_id=<?php echo $resource->id; ?>" 
                           class="btn btn-info">
                            <i class="fas fa-calendar me-1"></i>Ver Disponibilidad
                        </a>
                        <a href="edit_resource.php?id=<?php echo $resource->id; ?>" 
                           class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Editar Recurso
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Reservas y Calendario -->
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-calendar-alt me-2"></i>Reservas del Recurso</h3>
                <a href="manage_resources.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </div>
            
            <!-- Tabs para diferentes vistas -->
            <ul class="nav nav-tabs mb-4" id="resourceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations-view" 
                            type="button" role="tab" aria-controls="reservations-view" aria-selected="true">
                        <i class="fas fa-list me-1"></i>Lista de Reservas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" 
                            type="button" role="tab" aria-controls="calendar-view" aria-selected="false">
                        <i class="fas fa-calendar me-1"></i>Calendario
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-view" 
                            type="button" role="tab" aria-controls="timeline-view" aria-selected="false">
                        <i class="fas fa-clock me-1"></i>Línea de Tiempo
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="resourceTabsContent">
                <!-- Lista de Reservas -->
                <div class="tab-pane fade show active" id="reservations-view" role="tabpanel" aria-labelledby="reservations-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Fecha/Hora Inicio</th>
                                            <th>Fecha/Hora Fin</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resource_reservations->rowCount() > 0): ?>
                                            <?php while ($reservation = $resource_reservations->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-user text-primary me-1"></i>
                                                        <strong><?php echo htmlspecialchars($reservation['usuario_nombre']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($reservation['usuario_email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-play text-success me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($reservation['fecha_inicio'])); ?>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-stop text-danger me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($reservation['fecha_fin'])); ?>
                                                    </td>
                                                    <td>
                                                        <span title="<?php echo htmlspecialchars($reservation['motivo']); ?>">
                                                            <?php echo strlen($reservation['motivo']) > 50 
                                                                ? htmlspecialchars(substr($reservation['motivo'], 0, 50) . '...') 
                                                                : htmlspecialchars($reservation['motivo']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $reservation['estado']; ?>">
                                                            <?php echo ucfirst($reservation['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-info" 
                                                                    onclick="showReservationDetails(<?php echo htmlspecialchars(json_encode($reservation)); ?>)"
                                                                    title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if ($reservation['estado'] === 'pendiente'): ?>
                                                                <button type="button" class="btn btn-outline-success" 
                                                                        onclick="changeReservationStatus(<?php echo $reservation['id']; ?>, 'confirmada')"
                                                                        title="Confirmar">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if (in_array($reservation['estado'], ['pendiente', 'confirmada'])): ?>
                                                                <button type="button" class="btn btn-outline-warning" 
                                                                        onclick="changeReservationStatus(<?php echo $reservation['id']; ?>, 'cancelada')"
                                                                        title="Cancelar">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No hay reservas para este recurso</p>
                                                    <a href="../user/make_reservation.php?resource_id=<?php echo $resource->id; ?>" 
                                                       class="btn btn-primary">
                                                        <i class="fas fa-plus me-1"></i>Crear Primera Reserva
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista de Calendario -->
                <div class="tab-pane fade" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
                    <div class="card">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Línea de Tiempo -->
                <div class="tab-pane fade" id="timeline-view" role="tabpanel" aria-labelledby="timeline-tab">
                    <div class="card">
                        <div class="card-body">
                            <div id="timeline-container">
                                <p class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Cargando línea de tiempo...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles de reserva -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reservationDetails">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmación de eliminación del recurso -->
<div class="modal fade" id="deleteResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar el recurso <strong id="deleteResourceName"></strong>?
                <br><small class="text-muted">Esta acción eliminará también todas las reservas asociadas y no se puede deshacer.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="manage_resources.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="resource_id" id="deleteResourceId">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showReservationDetails(reservation) {
    const details = `
        <div class="row">
            <div class="col-sm-4"><strong>ID:</strong></div>
            <div class="col-sm-8">#${reservation.id}</div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Usuario:</strong></div>
            <div class="col-sm-8">${reservation.usuario_nombre}<br><small class="text-muted">${reservation.usuario_email}</small></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Inicio:</strong></div>
            <div class="col-sm-8">${new Date(reservation.fecha_inicio).toLocaleString('es-ES')}</div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Fin:</strong></div>
            <div class="col-sm-8">${new Date(reservation.fecha_fin).toLocaleString('es-ES')}</div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Duración:</strong></div>
            <div class="col-sm-8">${calculateDuration(reservation.fecha_inicio, reservation.fecha_fin)}</div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Estado:</strong></div>
            <div class="col-sm-8"><span class="status-badge status-${reservation.estado}">${reservation.estado.charAt(0).toUpperCase() + reservation.estado.slice(1)}</span></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Motivo:</strong></div>
            <div class="col-sm-8">${reservation.motivo}</div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-4"><strong>Creada:</strong></div>
            <div class="col-sm-8">${new Date(reservation.fecha_creacion).toLocaleString('es-ES')}</div>
        </div>
    `;
    
    document.getElementById('reservationDetails').innerHTML = details;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function calculateDuration(start, end) {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffMs = endDate - startDate;
    const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    if (diffHrs > 0) {
        return `${diffHrs}h ${diffMins}m`;
    } else {
        return `${diffMins}m`;
    }
}

function confirmDelete(resourceId, resourceName) {
    document.getElementById('deleteResourceId').value = resourceId;
    document.getElementById('deleteResourceName').textContent = resourceName;
    new bootstrap.Modal(document.getElementById('deleteResourceModal')).show();
}

function changeReservationStatus(reservationId, newStatus) {
    const statusText = {
        'confirmada': 'confirmar',
        'cancelada': 'cancelar',
        'finalizada': 'finalizar'
    };
    
    if (confirm(`¿Está seguro de que desea ${statusText[newStatus]} esta reserva?`)) {
        // Aquí harías una llamada AJAX para cambiar el estado
        fetch('../../api/change_reservation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reservation_id: reservationId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Éxito', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', 'Error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'Error', 'Ocurrió un error al procesar la solicitud');
        });
    }
}

// Inicializar calendario cuando se active la pestaña
document.getElementById('calendar-tab').addEventListener('shown.bs.tab', function() {
    if (!window.calendarInitialized) {
        initializeCalendar();
        window.calendarInitialized = true;
    }
});

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(info, successCallback, failureCallback) {
            fetch(`../../api/calendar_events.php?resource_id=<?php echo $resource->id; ?>&start=${info.startStr}&end=${info.endStr}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        eventClick: function(info) {
            showAlert('info', 'Reserva', `
                Usuario: ${info.event.extendedProps.usuario}<br>
                Estado: ${info.event.extendedProps.estado}<br>
                Motivo: ${info.event.extendedProps.motivo}
            `);
        }
    });
    
    calendar.render();
}

// Cargar línea de tiempo cuando se active la pestaña
document.getElementById('timeline-tab').addEventListener('shown.bs.tab', function() {
    loadTimeline();
});

function loadTimeline() {
    const container = document.getElementById('timeline-container');
    
    // Simular carga de datos para timeline
    setTimeout(() => {
        container.innerHTML = `
            <div class="timeline">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    La vista de línea de tiempo mostrará el historial de uso del recurso.
                    Esta funcionalidad estará disponible próximamente.
                </div>
            </div>
        `;
    }, 1000);
}
</script>

<?php require_once '../../includes/footer.php'; ?>
