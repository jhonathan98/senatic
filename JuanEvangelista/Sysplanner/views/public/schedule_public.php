<?php
// views/public/schedule_public.php
// Vista pública para consultar disponibilidad de recursos

$page_title = "Consultar Disponibilidad - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ResourceController.php';
require_once '../../controllers/ReservationController.php';

$resourceController = new ResourceController();
$reservationController = new ReservationController();

// Obtener parámetros de filtro
$tipo_filtro = $_GET['tipo'] ?? '';
$departamento_filtro = $_GET['departamento'] ?? '';

// Obtener recursos activos con filtros
if ($tipo_filtro || $departamento_filtro) {
    $filters = [
        'tipo' => $tipo_filtro,
        'departamento_id' => $departamento_filtro,
        'activo' => true
    ];
    $resources = $resourceController->search($filters);
} else {
    $resources = $resourceController->getActive();
}

// Obtener tipos únicos y departamentos para filtros
$resource_types = $resourceController->getResourceTypes();
require_once '../../models/Department.php';
$department = new Department();
$departments = $department->read();

// Procesar filtros
$selected_resource = $_GET['resource_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$view_type = $_GET['view'] ?? 'day';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-eye me-2"></i>Consultar Disponibilidad</h2>
                <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../user/make_reservation.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nueva Reserva
                        </a>
                    <?php else: ?>
                        <a href="../../index.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo de Recurso</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos los tipos</option>
                                <?php while ($type = $resource_types->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($type['tipo']); ?>" 
                                            <?php echo $tipo_filtro === $type['tipo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['tipo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="departamento" name="departamento">
                                <option value="">Todos los departamentos</option>
                                <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo $departamento_filtro == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="resource_id" class="form-label">Recurso Específico</label>
                            <select class="form-select" id="resource_id" name="resource_id">
                                <option value="">Ver todos</option>
                                <?php 
                                // Reset pointer for resources
                                if ($tipo_filtro || $departamento_filtro) {
                                    $filters = [
                                        'tipo' => $tipo_filtro,
                                        'departamento_id' => $departamento_filtro,
                                        'activo' => true
                                    ];
                                    $resources_for_select = $resourceController->search($filters);
                                } else {
                                    $resources_for_select = $resourceController->getActive();
                                }
                                
                                while ($resource = $resources_for_select->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $resource['id']; ?>" 
                                            <?php echo $selected_resource == $resource['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($resource['nombre']); ?> 
                                        (<?php echo htmlspecialchars($resource['tipo']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date" class="form-label">Fecha</label>
                            <input type="date" class="form-select" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($selected_date); ?>">
                        </div>
                        
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="col-md-12 mt-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-filter me-1"></i>Aplicar Filtros
                                </button>
                                <a href="schedule_public.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Limpiar Filtros
                                </a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="../user/make_reservation.php<?php echo $selected_resource ? '?resource_id=' . $selected_resource : ''; ?><?php echo $tipo_filtro ? ($selected_resource ? '&' : '?') . 'tipo=' . urlencode($tipo_filtro) : ''; ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i>Reservar
                                        <?php if ($selected_resource): ?>
                                            Este Recurso
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabs para diferentes vistas -->
            <ul class="nav nav-tabs mb-4" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" 
                            type="button" role="tab" aria-controls="calendar-view" aria-selected="true">
                        <i class="fas fa-calendar me-1"></i>Calendario
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" 
                            type="button" role="tab" aria-controls="list-view" aria-selected="false">
                        <i class="fas fa-list me-1"></i>Lista de Recursos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="grid-tab" data-bs-toggle="tab" data-bs-target="#grid-view" 
                            type="button" role="tab" aria-controls="grid-view" aria-selected="false">
                        <i class="fas fa-th me-1"></i>Vista de Cuadrícula
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="viewTabsContent">
                <!-- Vista de Calendario -->
                <div class="tab-pane fade show active" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
                    <div class="card">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Recursos -->
                <div class="tab-pane fade" id="list-view" role="tabpanel" aria-labelledby="list-tab">
                    <div class="row">
                        <?php
                        // Re-obtener recursos para esta vista
                        $resources_list = $resourceController->getActive();
                        while ($resource = $resources_list->fetch(PDO::FETCH_ASSOC)):
                            // Obtener reservas del día seleccionado para este recurso
                            $date_start = $selected_date . ' 00:00:00';
                            $date_end = $selected_date . ' 23:59:59';
                            $reservations = $resourceController->getReservations($resource['id'], $date_start, $date_end);
                            $reservation_count = $reservations ? $reservations->rowCount() : 0;
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title"><?php echo htmlspecialchars($resource['nombre']); ?></h5>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($resource['tipo']); ?></span>
                                        </div>
                                        
                                        <?php if ($resource['descripcion']): ?>
                                            <p class="card-text text-muted small">
                                                <?php echo htmlspecialchars($resource['descripcion']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="row text-center mb-3">
                                            <?php if ($resource['capacidad']): ?>
                                                <div class="col-6">
                                                    <i class="fas fa-users text-primary"></i>
                                                    <div><small>Capacidad</small></div>
                                                    <div><strong><?php echo $resource['capacidad']; ?></strong></div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="col-<?php echo $resource['capacidad'] ? '6' : '12'; ?>">
                                                <i class="fas fa-calendar-check text-success"></i>
                                                <div><small>Reservas Hoy</small></div>
                                                <div><strong><?php echo $reservation_count; ?></strong></div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($resource['departamento_nombre']): ?>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-building me-1"></i>
                                                <?php echo htmlspecialchars($resource['departamento_nombre']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    onclick="showResourceSchedule(<?php echo $resource['id']; ?>, '<?php echo htmlspecialchars($resource['nombre'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-eye me-1"></i>Ver Horario
                                            </button>
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <a href="../user/make_reservation.php?resource_id=<?php echo $resource['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus me-1"></i>Reservar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Vista de Cuadrícula -->
                <div class="tab-pane fade" id="grid-view" role="tabpanel" aria-labelledby="grid-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Hora</th>
                                            <?php
                                            // Re-obtener recursos para la cuadrícula
                                            $resources_grid = $resourceController->getActive();
                                            $resources_array = [];
                                            while ($resource = $resources_grid->fetch(PDO::FETCH_ASSOC)) {
                                                $resources_array[] = $resource;
                                                echo '<th class="text-center">' . htmlspecialchars($resource['nombre']) . '</th>';
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Generar filas de horario (8:00 AM a 6:00 PM)
                                        for ($hour = 8; $hour <= 18; $hour++):
                                            $time_slot = sprintf('%02d:00', $hour);
                                            $datetime_start = $selected_date . ' ' . $time_slot . ':00';
                                            $datetime_end = $selected_date . ' ' . sprintf('%02d:59:59', $hour);
                                        ?>
                                            <tr>
                                                <td class="text-center fw-bold"><?php echo $time_slot; ?></td>
                                                <?php foreach ($resources_array as $resource): ?>
                                                    <?php
                                                    // Verificar si hay reserva en esta hora
                                                    $reservations = $resourceController->getReservations($resource['id'], $datetime_start, $datetime_end);
                                                    $has_reservation = $reservations && $reservations->rowCount() > 0;
                                                    $reservation_data = $has_reservation ? $reservations->fetch(PDO::FETCH_ASSOC) : null;
                                                    ?>
                                                    <td class="text-center <?php echo $has_reservation ? 'bg-danger text-white' : 'bg-success text-white'; ?>">
                                                        <?php if ($has_reservation): ?>
                                                            <i class="fas fa-times"></i>
                                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                                <br><small><?php echo htmlspecialchars($reservation_data['usuario_nombre']); ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <i class="fas fa-check"></i>
                                                            <br><small>Disponible</small>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-6">
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-check me-1"></i>Disponible
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="badge bg-danger me-2">
                                        <i class="fas fa-times me-1"></i>Ocupado
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar horario de recurso -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">Horario del Recurso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="scheduleModalBody">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar calendario
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: getCalendarView(),
        initialDate: '<?php echo $selected_date; ?>',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(info, successCallback, failureCallback) {
            // Cargar eventos desde el servidor
            fetch('../../api/calendar_events.php?' + new URLSearchParams({
                start: info.startStr,
                end: info.endStr,
                resource_id: '<?php echo $selected_resource; ?>'
            }))
            .then(response => response.json())
            .then(data => successCallback(data))
            .catch(error => {
                console.error('Error loading events:', error);
                failureCallback(error);
            });
        },
        eventClick: function(info) {
            showAlert('info', 'Reserva', `
                Recurso: ${info.event.extendedProps.recurso}<br>
                Usuario: ${info.event.extendedProps.usuario}<br>
                Estado: ${info.event.extendedProps.estado}<br>
                Motivo: ${info.event.extendedProps.motivo}
            `);
        }
    });
    
    calendar.render();
});

function getCalendarView() {
    const viewType = '<?php echo $view_type; ?>';
    switch(viewType) {
        case 'day': return 'timeGridDay';
        case 'week': return 'timeGridWeek';
        case 'month': return 'dayGridMonth';
        default: return 'timeGridDay';
    }
}

function showResourceSchedule(resourceId, resourceName) {
    document.getElementById('scheduleModalTitle').textContent = `Horario - ${resourceName}`;
    
    const modalBody = document.getElementById('scheduleModalBody');
    modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    
    // Cargar horario del recurso
    fetch(`../../api/resource_schedule.php?resource_id=${resourceId}&date=<?php echo $selected_date; ?>`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>Hora</th><th>Estado</th><th>Usuario</th><th>Motivo</th></tr></thead><tbody>';
            
            // Generar horario de 8:00 a 18:00
            for (let hour = 8; hour <= 18; hour++) {
                const timeSlot = hour.toString().padStart(2, '0') + ':00';
                const reservation = data.find(r => {
                    const start = new Date(r.fecha_inicio);
                    return start.getHours() === hour;
                });
                
                html += `<tr>
                    <td>${timeSlot}</td>
                    <td>${reservation ? '<span class="badge bg-danger">Ocupado</span>' : '<span class="badge bg-success">Disponible</span>'}</td>
                    <td>${reservation ? reservation.usuario_nombre : '-'}</td>
                    <td>${reservation ? reservation.motivo : '-'}</td>
                </tr>`;
            }
            
            html += '</tbody></table></div>';
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar el horario</div>';
        });
    
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
