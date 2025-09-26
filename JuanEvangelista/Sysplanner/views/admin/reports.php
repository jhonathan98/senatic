<?php
// views/admin/reports.php
// Vista de reportes y análisis para administradores

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$page_title = "Reportes - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Parámetros de filtro
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$resource_id = $_GET['resource_id'] ?? '';
$department_id = $_GET['department_id'] ?? '';

// Obtener recursos para el filtro
$resources_query = "SELECT id, nombre FROM recursos WHERE activo = 1 ORDER BY nombre";
$resources = $conn->query($resources_query)->fetchAll(PDO::FETCH_ASSOC);

// Obtener departamentos para el filtro
$departments_query = "SELECT id, nombre FROM departamentos ORDER BY nombre";
$departments = $conn->query($departments_query)->fetchAll(PDO::FETCH_ASSOC);

// Construir condiciones WHERE
$where_conditions = ["r.fecha_inicio BETWEEN ? AND ?"];
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

if ($resource_id) {
    $where_conditions[] = "r.recurso_id = ?";
    $params[] = $resource_id;
}

if ($department_id) {
    $where_conditions[] = "rec.departamento_id = ?";
    $params[] = $department_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Estadísticas generales
$stats_query = "
    SELECT 
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
        COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN r.estado = 'cancelada' THEN 1 END) as canceladas,
        COUNT(CASE WHEN r.estado = 'finalizada' THEN 1 END) as finalizadas,
        COUNT(DISTINCT r.usuario_id) as usuarios_activos,
        COUNT(DISTINCT r.recurso_id) as recursos_utilizados,
        AVG(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as duracion_promedio
    FROM reservas r
    LEFT JOIN recursos rec ON r.recurso_id = rec.id
    WHERE $where_clause
";

$stmt = $conn->prepare($stats_query);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Reservas por día
$daily_query = "
    SELECT 
        DATE(r.fecha_inicio) as fecha,
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
        SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales
    FROM reservas r
    LEFT JOIN recursos rec ON r.recurso_id = rec.id
    WHERE $where_clause
    GROUP BY DATE(r.fecha_inicio)
    ORDER BY fecha DESC
    LIMIT 15
";

$stmt = $conn->prepare($daily_query);
$stmt->execute($params);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recursos más utilizados
$resources_usage_query = "
    SELECT 
        rec.nombre,
        rec.tipo,
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
        SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales,
        AVG(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as duracion_promedio
    FROM reservas r
    JOIN recursos rec ON r.recurso_id = rec.id
    WHERE $where_clause
    GROUP BY r.recurso_id, rec.nombre, rec.tipo
    ORDER BY total_reservas DESC
    LIMIT 10
";

$stmt = $conn->prepare($resources_usage_query);
$stmt->execute($params);
$resources_usage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Usuarios más activos
$users_activity_query = "
    SELECT 
        u.nombre,
        u.email,
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
        SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    LEFT JOIN recursos rec ON r.recurso_id = rec.id
    WHERE $where_clause
    GROUP BY r.usuario_id, u.nombre, u.email
    ORDER BY total_reservas DESC
    LIMIT 10
";

$stmt = $conn->prepare($users_activity_query);
$stmt->execute($params);
$users_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distribución por horas
$hourly_query = "
    SELECT 
        HOUR(r.fecha_inicio) as hora,
        COUNT(*) as total_reservas
    FROM reservas r
    LEFT JOIN recursos rec ON r.recurso_id = rec.id
    WHERE $where_clause
    GROUP BY HOUR(r.fecha_inicio)
    ORDER BY hora
";

$stmt = $conn->prepare($hourly_query);
$stmt->execute($params);
$hourly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para gráficos
$hourly_data = array_fill(0, 24, 0);
foreach ($hourly_stats as $hour_stat) {
    $hourly_data[$hour_stat['hora']] = $hour_stat['total_reservas'];
}
?>

<div class="container-fluid mt-4">
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter me-2"></i>Filtros de Reporte</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label for="resource_id" class="form-label">Recurso</label>
                    <select class="form-select" id="resource_id" name="resource_id">
                        <option value="">Todos los recursos</option>
                        <?php foreach ($resources as $resource): ?>
                            <option value="<?php echo $resource['id']; ?>" 
                                    <?php echo $resource_id == $resource['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($resource['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Departamento</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Todos los departamentos</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>" 
                                    <?php echo $department_id == $department['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Aplicar Filtros
                    </button>
                    <a href="reports.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i>Limpiar
                    </a>
                    <button type="button" class="btn btn-success" onclick="exportToCSV()">
                        <i class="fas fa-download me-2"></i>Exportar CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Reservas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_reservas']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tasa de Confirmación
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $confirmation_rate = $stats['total_reservas'] > 0 ? 
                                    ($stats['confirmadas'] / $stats['total_reservas']) * 100 : 0;
                                echo number_format($confirmation_rate, 1); 
                                ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Usuarios Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['usuarios_activos']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Duración Promedio
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $avg_hours = floor($stats['duracion_promedio'] / 60);
                                $avg_minutes = $stats['duracion_promedio'] % 60;
                                echo $avg_hours . 'h ' . round($avg_minutes) . 'm';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Distribución por Horas -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Distribución de Reservas por Hora
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Estados de Reservas -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Estados de Reservas
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="300"></canvas>
                    <div class="mt-3">
                        <div class="small">
                            <span class="badge bg-success me-2">Confirmadas: <?php echo $stats['confirmadas']; ?></span>
                            <span class="badge bg-warning me-2">Pendientes: <?php echo $stats['pendientes']; ?></span>
                            <span class="badge bg-danger me-2">Canceladas: <?php echo $stats['canceladas']; ?></span>
                            <span class="badge bg-secondary">Finalizadas: <?php echo $stats['finalizadas']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recursos Más Utilizados -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy me-2"></i>Recursos Más Utilizados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Recurso</th>
                                    <th>Tipo</th>
                                    <th>Reservas</th>
                                    <th>Horas Totales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources_usage as $resource): ?>
                                    <tr>
                                        <td>
                                            <a href="view_resource.php?id=<?php echo $resource['id'] ?? ''; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($resource['nombre']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($resource['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $resource['total_reservas']; ?></td>
                                        <td>
                                            <?php 
                                            $hours = floor($resource['minutos_totales'] / 60);
                                            $minutes = $resource['minutos_totales'] % 60;
                                            echo $hours . 'h ' . $minutes . 'm';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios Más Activos -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-friends me-2"></i>Usuarios Más Activos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Reservas</th>
                                    <th>Confirmadas</th>
                                    <th>Horas Totales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users_activity as $user): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td><?php echo $user['total_reservas']; ?></td>
                                        <td><?php echo $user['confirmadas']; ?></td>
                                        <td>
                                            <?php 
                                            $hours = floor($user['minutos_totales'] / 60);
                                            $minutes = $user['minutos_totales'] % 60;
                                            echo $hours . 'h ' . $minutes . 'm';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Diaria -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar-alt me-2"></i>Actividad Diaria (Últimos 15 días)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Total Reservas</th>
                            <th>Confirmadas</th>
                            <th>Tasa Confirmación</th>
                            <th>Horas Totales</th>
                            <th>Promedio por Reserva</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_stats as $day): ?>
                            <?php
                            $confirmation_rate = $day['total_reservas'] > 0 ? 
                                ($day['confirmadas'] / $day['total_reservas']) * 100 : 0;
                            $avg_minutes = $day['total_reservas'] > 0 ? 
                                $day['minutos_totales'] / $day['total_reservas'] : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    $date = new DateTime($day['fecha']);
                                    echo $date->format('d/m/Y');
                                    ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $date->format('l'); ?>
                                    </small>
                                </td>
                                <td><strong><?php echo $day['total_reservas']; ?></strong></td>
                                <td><?php echo $day['confirmadas']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $confirmation_rate; ?>%">
                                            <?php echo number_format($confirmation_rate, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $hours = floor($day['minutos_totales'] / 60);
                                    $minutes = $day['minutos_totales'] % 60;
                                    echo $hours . 'h ' . $minutes . 'm';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $avg_hours = floor($avg_minutes / 60);
                                    $avg_mins = $avg_minutes % 60;
                                    echo $avg_hours . 'h ' . round($avg_mins) . 'm';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Gráfico de distribución por horas
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(range(0, 23)); ?>,
        datasets: [{
            label: 'Reservas por Hora',
            data: <?php echo json_encode($hourly_data); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Hora del Día'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Número de Reservas'
                }
            }
        }
    }
});

// Gráfico de estados
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Confirmadas', 'Pendientes', 'Canceladas', 'Finalizadas'],
        datasets: [{
            data: [
                <?php echo $stats['confirmadas']; ?>,
                <?php echo $stats['pendientes']; ?>,
                <?php echo $stats['canceladas']; ?>,
                <?php echo $stats['finalizadas']; ?>
            ],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#dc3545',
                '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Función para exportar a CSV
function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '../../api/export_reports.php?' + params.toString();
}
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
