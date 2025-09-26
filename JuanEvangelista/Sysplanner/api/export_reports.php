<?php
// api/export_reports.php
// API para exportar reportes en formato CSV

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Parámetros de filtro
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$resource_id = $_GET['resource_id'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$export_type = $_GET['type'] ?? 'reservations';

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

// Configurar headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_sysplanner_' . date('Y-m-d') . '.csv"');

// Crear el output stream
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($export_type) {
    case 'reservations':
        exportReservations($conn, $where_clause, $params, $output);
        break;
    case 'resources_usage':
        exportResourcesUsage($conn, $where_clause, $params, $output);
        break;
    case 'users_activity':
        exportUsersActivity($conn, $where_clause, $params, $output);
        break;
    case 'daily_summary':
        exportDailySummary($conn, $where_clause, $params, $output);
        break;
    default:
        exportReservations($conn, $where_clause, $params, $output);
        break;
}

fclose($output);
exit();

function exportReservations($conn, $where_clause, $params, $output) {
    // Headers del CSV
    fputcsv($output, [
        'ID Reserva',
        'Usuario',
        'Email Usuario',
        'Recurso',
        'Tipo Recurso',
        'Departamento',
        'Fecha Inicio',
        'Fecha Fin',
        'Duración (minutos)',
        'Estado',
        'Fecha Creación',
        'Observaciones'
    ]);

    $query = "
        SELECT 
            r.id,
            u.nombre as usuario_nombre,
            u.email as usuario_email,
            rec.nombre as recurso_nombre,
            rec.tipo as recurso_tipo,
            d.nombre as departamento_nombre,
            r.fecha_inicio,
            r.fecha_fin,
            TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin) as duracion_minutos,
            r.estado,
            r.fecha_creacion,
            r.observaciones
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN recursos rec ON r.recurso_id = rec.id
        LEFT JOIN departamentos d ON rec.departamento_id = d.id
        WHERE $where_clause
        ORDER BY r.fecha_inicio DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['usuario_nombre'],
            $row['usuario_email'],
            $row['recurso_nombre'],
            $row['recurso_tipo'],
            $row['departamento_nombre'] ?? 'Sin asignar',
            $row['fecha_inicio'],
            $row['fecha_fin'],
            $row['duracion_minutos'],
            ucfirst($row['estado']),
            $row['fecha_creacion'],
            $row['observaciones'] ?? ''
        ]);
    }
}

function exportResourcesUsage($conn, $where_clause, $params, $output) {
    // Headers del CSV
    fputcsv($output, [
        'Recurso',
        'Tipo',
        'Departamento',
        'Total Reservas',
        'Reservas Confirmadas',
        'Reservas Pendientes',
        'Reservas Canceladas',
        'Reservas Finalizadas',
        'Tasa Confirmación (%)',
        'Horas Totales',
        'Duración Promedio (minutos)',
        'Primer Uso',
        'Último Uso'
    ]);

    $query = "
        SELECT 
            rec.nombre,
            rec.tipo,
            d.nombre as departamento_nombre,
            COUNT(*) as total_reservas,
            COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
            COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN r.estado = 'cancelada' THEN 1 END) as canceladas,
            COUNT(CASE WHEN r.estado = 'finalizada' THEN 1 END) as finalizadas,
            SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales,
            AVG(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as duracion_promedio,
            MIN(r.fecha_inicio) as primer_uso,
            MAX(r.fecha_fin) as ultimo_uso
        FROM reservas r
        JOIN recursos rec ON r.recurso_id = rec.id
        LEFT JOIN departamentos d ON rec.departamento_id = d.id
        WHERE $where_clause
        GROUP BY r.recurso_id, rec.nombre, rec.tipo, d.nombre
        ORDER BY total_reservas DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tasa_confirmacion = $row['total_reservas'] > 0 ? 
            ($row['confirmadas'] / $row['total_reservas']) * 100 : 0;
        
        $horas_totales = round($row['minutos_totales'] / 60, 2);

        fputcsv($output, [
            $row['nombre'],
            $row['tipo'],
            $row['departamento_nombre'] ?? 'Sin asignar',
            $row['total_reservas'],
            $row['confirmadas'],
            $row['pendientes'],
            $row['canceladas'],
            $row['finalizadas'],
            number_format($tasa_confirmacion, 2),
            $horas_totales,
            round($row['duracion_promedio'], 2),
            $row['primer_uso'],
            $row['ultimo_uso']
        ]);
    }
}

function exportUsersActivity($conn, $where_clause, $params, $output) {
    // Headers del CSV
    fputcsv($output, [
        'Usuario',
        'Email',
        'Rol',
        'Total Reservas',
        'Reservas Confirmadas',
        'Reservas Pendientes',
        'Reservas Canceladas',
        'Reservas Finalizadas',
        'Tasa Confirmación (%)',
        'Horas Totales',
        'Duración Promedio (minutos)',
        'Primera Reserva',
        'Última Reserva',
        'Recursos Únicos Utilizados',
        'Usuario Activo'
    ]);

    $query = "
        SELECT 
            u.nombre,
            u.email,
            u.rol,
            u.activo,
            COUNT(*) as total_reservas,
            COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
            COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN r.estado = 'cancelada' THEN 1 END) as canceladas,
            COUNT(CASE WHEN r.estado = 'finalizada' THEN 1 END) as finalizadas,
            SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales,
            AVG(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as duracion_promedio,
            MIN(r.fecha_inicio) as primera_reserva,
            MAX(r.fecha_fin) as ultima_reserva,
            COUNT(DISTINCT r.recurso_id) as recursos_unicos
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN recursos rec ON r.recurso_id = rec.id
        WHERE $where_clause
        GROUP BY r.usuario_id, u.nombre, u.email, u.rol, u.activo
        ORDER BY total_reservas DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tasa_confirmacion = $row['total_reservas'] > 0 ? 
            ($row['confirmadas'] / $row['total_reservas']) * 100 : 0;
        
        $horas_totales = round($row['minutos_totales'] / 60, 2);

        fputcsv($output, [
            $row['nombre'],
            $row['email'],
            ucfirst($row['rol']),
            $row['total_reservas'],
            $row['confirmadas'],
            $row['pendientes'],
            $row['canceladas'],
            $row['finalizadas'],
            number_format($tasa_confirmacion, 2),
            $horas_totales,
            round($row['duracion_promedio'], 2),
            $row['primera_reserva'],
            $row['ultima_reserva'],
            $row['recursos_unicos'],
            $row['activo'] ? 'Sí' : 'No'
        ]);
    }
}

function exportDailySummary($conn, $where_clause, $params, $output) {
    // Headers del CSV
    fputcsv($output, [
        'Fecha',
        'Día de la Semana',
        'Total Reservas',
        'Reservas Confirmadas',
        'Reservas Pendientes',
        'Reservas Canceladas',
        'Reservas Finalizadas',
        'Tasa Confirmación (%)',
        'Horas Totales',
        'Duración Promedio (minutos)',
        'Usuarios Únicos',
        'Recursos Únicos Utilizados'
    ]);

    $query = "
        SELECT 
            DATE(r.fecha_inicio) as fecha,
            DAYNAME(r.fecha_inicio) as dia_semana,
            COUNT(*) as total_reservas,
            COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as confirmadas,
            COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN r.estado = 'cancelada' THEN 1 END) as canceladas,
            COUNT(CASE WHEN r.estado = 'finalizada' THEN 1 END) as finalizadas,
            SUM(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as minutos_totales,
            AVG(TIMESTAMPDIFF(MINUTE, r.fecha_inicio, r.fecha_fin)) as duracion_promedio,
            COUNT(DISTINCT r.usuario_id) as usuarios_unicos,
            COUNT(DISTINCT r.recurso_id) as recursos_unicos
        FROM reservas r
        LEFT JOIN recursos rec ON r.recurso_id = rec.id
        WHERE $where_clause
        GROUP BY DATE(r.fecha_inicio)
        ORDER BY fecha DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tasa_confirmacion = $row['total_reservas'] > 0 ? 
            ($row['confirmadas'] / $row['total_reservas']) * 100 : 0;
        
        $horas_totales = round($row['minutos_totales'] / 60, 2);

        fputcsv($output, [
            $row['fecha'],
            $row['dia_semana'],
            $row['total_reservas'],
            $row['confirmadas'],
            $row['pendientes'],
            $row['canceladas'],
            $row['finalizadas'],
            number_format($tasa_confirmacion, 2),
            $horas_totales,
            round($row['duracion_promedio'], 2),
            $row['usuarios_unicos'],
            $row['recursos_unicos']
        ]);
    }
}
?>
