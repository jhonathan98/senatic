<?php
// views/user/view_schedule.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=" . urlencode("Debe iniciar sesión para acceder"));
    exit();
}

$page_title = "Mi Horario - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ReservationController.php';

$reservationController = new ReservationController();

// Obtener reservas del usuario
$user_reservations = $reservationController->getUserReservations($_SESSION['user_id']);

// Procesar cambios de estado si es necesario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'cancel':
            $result = $reservationController->changeStatus($_POST['reservation_id'], 'cancelada');
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            // Recargar reservas
            $user_reservations = $reservationController->getUserReservations($_SESSION['user_id']);
            break;
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-user me-2"></i>Mi Horario</h2>
                <a href="make_reservation.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nueva Reserva
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs para diferentes vistas -->
            <ul class="nav nav-tabs mb-4" id="scheduleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" 
                            type="button" role="tab" aria-controls="list-view" aria-selected="true">
                        <i class="fas fa-list me-1"></i>Lista
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" 
                            type="button" role="tab" aria-controls="calendar-view" aria-selected="false">
                        <i class="fas fa-calendar me-1"></i>Calendario
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="scheduleTabsContent">
                <!-- Vista de Lista -->
                <div class="tab-pane fade show active" id="list-view" role="tabpanel" aria-labelledby="list-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Recurso</th>
                                            <th>Fecha/Hora Inicio</th>
                                            <th>Fecha/Hora Fin</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($user_reservations->rowCount() > 0): ?>
                                            <?php while ($reservation = $user_reservations->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($reservation['recurso_nombre']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($reservation['recurso_tipo']); ?></small>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-play text-success me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($reservation['fecha_inicio'])); ?>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-stop text-danger me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($reservation['fecha_fin'])); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($reservation['motivo']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $reservation['estado']; ?>">
                                                            <?php echo ucfirst($reservation['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($reservation['estado'] === 'confirmada' || $reservation['estado'] === 'pendiente'): ?>
                                                                <?php 
                                                                $can_cancel = strtotime($reservation['fecha_inicio']) > time() + (2 * 3600); // 2 horas antes
                                                                ?>
                                                                <?php if ($can_cancel): ?>
                                                                    <button type="button" class="btn btn-outline-danger" 
                                                                            onclick="confirmCancel(<?php echo $reservation['id']; ?>, '<?php echo htmlspecialchars($reservation['recurso_nombre']); ?>')"
                                                                            title="Cancelar reserva">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-outline-secondary" disabled 
                                                                            title="No se puede cancelar (menos de 2 horas)">
                                                                        <i class="fas fa-lock"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            
                                                            <button type="button" class="btn btn-outline-info" 
                                                                    onclick="showReservationDetails(<?php echo htmlspecialchars(json_encode($reservation)); ?>)"
                                                                    title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No tiene reservas registradas</p>
                                                    <a href="make_reservation.php" class="btn btn-primary">
                                                        <i class="fas fa-plus me-1"></i>Hacer Primera Reserva
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
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmación de cancelación -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea cancelar la reserva del recurso <strong id="cancelResourceName"></strong>?
                <br><small class="text-muted">Esta acción no se puede deshacer.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="reservation_id" id="cancelReservationId">
                    <button type="submit" class="btn btn-danger">Sí, cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles de reserva -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
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

<script>
function confirmCancel(reservationId, resourceName) {
    document.getElementById('cancelReservationId').value = reservationId;
    document.getElementById('cancelResourceName').textContent = resourceName;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function showReservationDetails(reservation) {
    const details = `
        <div class="row">
            <div class="col-sm-4"><strong>Recurso:</strong></div>
            <div class="col-sm-8">${reservation.recurso_nombre} (${reservation.recurso_tipo})</div>
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
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(info, successCallback, failureCallback) {
            // Aquí cargarías los eventos del usuario desde el servidor
            // Por ahora, convertimos las reservas PHP a eventos de calendario
            const events = [];
            // Esta sería una llamada AJAX en un caso real
            successCallback(events);
        },
        eventClick: function(info) {
            // Manejar click en evento
            showAlert('info', 'Reserva', `Detalles: ${info.event.title}`);
        }
    });
    
    calendar.render();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
