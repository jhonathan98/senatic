<?php
// views/view_resource.php
// Vista pública para ver detalles de un recurso (usuarios regulares)

session_start();

$page_title = "Ver Recurso - SysPlanner";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../controllers/ResourceController.php';
require_once '../controllers/ReservationController.php';

$resourceController = new ResourceController();
$reservationController = new ReservationController();

$resource_id = $_GET['id'] ?? null;

if (!$resource_id) {
    header("Location: public/schedule_public.php?error=" . urlencode("ID de recurso no válido"));
    exit();
}

// Obtener datos del recurso
$resource = $resourceController->show($resource_id);

if (!$resource || !$resource->activo) {
    header("Location: public/schedule_public.php?error=" . urlencode("Recurso no encontrado o no disponible"));
    exit();
}

// Obtener reservas públicas del recurso (solo fechas futuras y confirmadas)
$today = date('Y-m-d H:i:s');
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$public_reservations_query = "
    SELECT r.fecha_inicio, r.fecha_fin, r.estado, 
           CASE 
               WHEN r.fecha_inicio > NOW() THEN 'Próxima'
               WHEN r.fecha_inicio <= NOW() AND r.fecha_fin >= NOW() THEN 'En curso'
               ELSE 'Finalizada'
           END as status_display
    FROM reservas r
    WHERE r.recurso_id = ? 
    AND r.estado IN ('confirmada', 'pendiente')
    AND r.fecha_fin >= NOW()
    ORDER BY r.fecha_inicio ASC
    LIMIT 10
";

$stmt = $conn->prepare($public_reservations_query);
$stmt->execute([$resource_id]);
$public_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas básicas
$stats_query = "
    SELECT 
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN DATE(fecha_inicio) = CURDATE() THEN 1 END) as reservas_hoy,
        COUNT(CASE WHEN WEEK(fecha_inicio) = WEEK(NOW()) AND YEAR(fecha_inicio) = YEAR(NOW()) THEN 1 END) as reservas_semana
    FROM reservas r
    WHERE r.recurso_id = ? 
    AND r.estado IN ('confirmada', 'pendiente', 'finalizada')
";

$stmt = $conn->prepare($stats_query);
$stmt->execute([$resource_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row">
        <!-- Información del Recurso -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cube me-2"></i>Información del Recurso</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="resource-icon mb-3">
                            <?php
                            $icon_class = 'fas fa-cube';
                            $icon_color = 'text-primary';
                            switch (strtolower($resource->tipo)) {
                                case 'aula':
                                    $icon_class = 'fas fa-chalkboard-teacher';
                                    $icon_color = 'text-success';
                                    break;
                                case 'laboratorio':
                                    $icon_class = 'fas fa-flask';
                                    $icon_color = 'text-warning';
                                    break;
                                case 'sala de sistemas':
                                    $icon_class = 'fas fa-desktop';
                                    $icon_color = 'text-info';
                                    break;
                                case 'auditorio':
                                    $icon_class = 'fas fa-theater-masks';
                                    $icon_color = 'text-purple';
                                    break;
                                case 'sala de reuniones':
                                    $icon_class = 'fas fa-users';
                                    $icon_color = 'text-primary';
                                    break;
                                case 'equipo':
                                    $icon_class = 'fas fa-tools';
                                    $icon_color = 'text-secondary';
                                    break;
                                case 'instalación deportiva':
                                    $icon_class = 'fas fa-futbol';
                                    $icon_color = 'text-success';
                                    break;
                            }
                            ?>
                            <i class="<?php echo $icon_class; ?> fa-4x <?php echo $icon_color; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($resource->nombre); ?></h3>
                        <span class="badge bg-info fs-6 mb-2"><?php echo htmlspecialchars($resource->tipo); ?></span>
                        <div>
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle me-1"></i>Disponible
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($resource->descripcion): ?>
                        <div class="mb-3">
                            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Descripción</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($resource->descripcion); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row text-center mb-3">
                        <?php if ($resource->capacidad): ?>
                            <div class="col-6">
                                <div class="bg-light p-3 rounded">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h5><?php echo $resource->capacidad; ?></h5>
                                    <small class="text-muted">Capacidad</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-<?php echo $resource->capacidad ? '6' : '12'; ?>">
                            <div class="bg-light p-3 rounded">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h5><?php echo $stats['reservas_hoy']; ?></h5>
                                <small class="text-muted">Usos Hoy</small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($resource->departamento_id): ?>
                        <div class="mb-3">
                            <h6><i class="fas fa-building me-2 text-primary"></i>Departamento</h6>
                            <p class="text-muted mb-0">
                                <?php 
                                require_once '../models/Department.php';
                                $department = new Department();
                                $department->id = $resource->departamento_id;
                                if ($department->readOne()) {
                                    echo htmlspecialchars($department->nombre);
                                } else {
                                    echo 'Sin asignar';
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($resource->caracteristicas): ?>
                        <div class="mb-3">
                            <h6><i class="fas fa-list-ul me-2 text-primary"></i>Características</h6>
                            <div class="bg-light p-3 rounded">
                                <small><?php echo nl2br(htmlspecialchars($resource->caracteristicas)); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Acciones -->
                    <div class="d-grid gap-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="user/make_reservation.php?resource_id=<?php echo $resource->id; ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Reservar Ahora
                            </a>
                        <?php else: ?>
                            <a href="../index.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión para Reservar
                            </a>
                        <?php endif; ?>
                        
                        <a href="public/schedule_public.php?resource_id=<?php echo $resource->id; ?>" 
                           class="btn btn-outline-info">
                            <i class="fas fa-calendar me-1"></i>Ver Disponibilidad Completa
                        </a>
                        
                        <a href="public/schedule_public.php?tipo=<?php echo urlencode($resource->tipo); ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-layer-group me-1"></i>Ver Recursos Similares
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas de Uso -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie me-2"></i>Estadísticas de Uso</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h5 class="text-primary"><?php echo $stats['reservas_hoy']; ?></h5>
                            <small class="text-muted">Hoy</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-success"><?php echo $stats['reservas_semana']; ?></h5>
                            <small class="text-muted">Esta Semana</small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info"><?php echo $stats['total_reservas']; ?></h5>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Disponibilidad y Reservas -->
        <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-calendar-alt me-2"></i>Próximas Reservas</h4>
                <a href="public/schedule_public.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Ver Todos los Recursos
                </a>
            </div>
            
            <!-- Lista de próximas reservas -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($public_reservations)): ?>
                        <div class="timeline">
                            <?php foreach ($public_reservations as $reservation): ?>
                                <?php
                                $start_date = new DateTime($reservation['fecha_inicio']);
                                $end_date = new DateTime($reservation['fecha_fin']);
                                $now = new DateTime();
                                
                                $is_current = $start_date <= $now && $end_date >= $now;
                                $is_today = $start_date->format('Y-m-d') === $now->format('Y-m-d');
                                ?>
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="timeline-marker me-3">
                                            <i class="fas fa-circle <?php echo $is_current ? 'text-danger' : ($is_today ? 'text-warning' : 'text-success'); ?>"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="card <?php echo $is_current ? 'border-danger' : ($is_today ? 'border-warning' : ''); ?>">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <i class="fas fa-clock me-1"></i>
                                                                <?php echo $start_date->format('d/m/Y H:i'); ?> - 
                                                                <?php echo $end_date->format('H:i'); ?>
                                                            </h6>
                                                            <p class="mb-1 text-muted small">
                                                                Duración: <?php 
                                                                $diff = $start_date->diff($end_date);
                                                                echo $diff->format('%h horas %i minutos');
                                                                ?>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <?php if ($is_current): ?>
                                                                <span class="badge bg-danger">En Uso</span>
                                                            <?php elseif ($is_today): ?>
                                                                <span class="badge bg-warning">Hoy</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Programada</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">¡Recurso Disponible!</h5>
                            <p class="text-muted">No hay reservas programadas para los próximos días</p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="user/make_reservation.php?resource_id=<?php echo $resource->id; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Ser el Primero en Reservar
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Vista rápida del calendario de hoy -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-calendar-day me-2"></i>Disponibilidad de Hoy</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Generar horario de 8:00 a 18:00
                        $today_date = date('Y-m-d');
                        for ($hour = 8; $hour <= 17; $hour++):
                            $time_slot = sprintf('%02d:00', $hour);
                            $datetime_start = $today_date . ' ' . $time_slot . ':00';
                            $datetime_end = $today_date . ' ' . sprintf('%02d:59:59', $hour);
                            
                            // Verificar si hay reserva en esta hora
                            $hour_reserved = false;
                            foreach ($public_reservations as $reservation) {
                                $res_start = new DateTime($reservation['fecha_inicio']);
                                $res_end = new DateTime($reservation['fecha_fin']);
                                $slot_time = new DateTime($datetime_start);
                                
                                if ($res_start->format('Y-m-d') === $today_date &&
                                    $res_start <= $slot_time && $res_end > $slot_time) {
                                    $hour_reserved = true;
                                    break;
                                }
                            }
                        ?>
                            <div class="col-2 col-md-1 mb-2">
                                <div class="text-center">
                                    <div class="time-slot <?php echo $hour_reserved ? 'bg-danger' : 'bg-success'; ?> text-white rounded p-1">
                                        <small><?php echo $hour; ?>h</small>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <small>
                                <span class="badge bg-success me-1"></span>
                                Disponible
                            </small>
                        </div>
                        <div class="col-6">
                            <small>
                                <span class="badge bg-danger me-1"></span>
                                Ocupado
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-marker {
    width: 20px;
    text-align: center;
}

.timeline-item:not(:last-child) .timeline-marker::after {
    content: '';
    position: absolute;
    width: 2px;
    height: 40px;
    background: #dee2e6;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 10px;
}

.time-slot {
    min-height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.resource-icon {
    position: relative;
}
</style>

<?php require_once '../includes/footer.php'; ?>
