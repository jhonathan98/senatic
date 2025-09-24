<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=reports");
    exit;
}

require_once 'config.php';
require_once 'includes/functions.php';

// Verificar permisos
requireRole(['admin', 'teacher']);

$user = $_SESSION['user'];
$role = $user['role'];

// Parámetros de filtro
$start_date = sanitizeInput($_GET['start_date'] ?? date('Y-m-01')); // Primer día del mes
$end_date = sanitizeInput($_GET['end_date'] ?? date('Y-m-d')); // Hoy
$grade_filter = sanitizeInput($_GET['grade'] ?? '');
$report_type = sanitizeInput($_GET['report_type'] ?? 'summary');

// Validar fechas
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up text-primary"></i> Reportes de Asistencia</h2>
    <div>
        <button class="btn btn-outline-success" onclick="exportReport()">
            <i class="bi bi-download"></i> Exportar
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="section" value="reports">
            <div class="col-md-3">
                <label class="form-label">Fecha inicio:</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha fin:</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
            </div>
            <?php if ($role === 'admin'): ?>
            <div class="col-md-2">
                <label class="form-label">Grado:</label>
                <select name="grade" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (getGrades() as $key => $value): ?>
                        <option value="<?= $key ?>" <?= $grade_filter === $key ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label">Tipo:</label>
                <select name="report_type" class="form-select">
                    <option value="summary" <?= $report_type === 'summary' ? 'selected' : '' ?>>Resumen</option>
                    <option value="detailed" <?= $report_type === 'detailed' ? 'selected' : '' ?>>Detallado</option>
                    <option value="by_student" <?= $report_type === 'by_student' ? 'selected' : '' ?>>Por Estudiante</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Generar
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Obtener datos según el tipo de reporte
$report_data = [];
$stats = [];

if ($report_type === 'summary') {
    // Reporte resumen por fecha
    $sql = "
        SELECT 
            a.date,
            COUNT(*) as total_records,
            SUM(CASE WHEN a.status = 'Asistió' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'No asistió' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'Tarde' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN a.status = 'Excusado' THEN 1 ELSE 0 END) as excused
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.date BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($role === 'teacher') {
        $sql .= " AND a.teacher_id = ?";
        $params[] = $user['id'];
    }
    
    if (!empty($grade_filter)) {
        $sql .= " AND s.grade = ?";
        $params[] = $grade_filter;
    }
    
    $sql .= " GROUP BY a.date ORDER BY a.date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll();
    
} elseif ($report_type === 'detailed') {
    // Reporte detallado
    $sql = "
        SELECT 
            a.date,
            s.name as student_name,
            s.grade,
            s.document_number,
            u.name as parent_name,
            a.status,
            a.notes,
            t.name as teacher_name
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.parent_id = u.id
        JOIN users t ON a.teacher_id = t.id
        WHERE a.date BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($role === 'teacher') {
        $sql .= " AND a.teacher_id = ?";
        $params[] = $user['id'];
    }
    
    if (!empty($grade_filter)) {
        $sql .= " AND s.grade = ?";
        $params[] = $grade_filter;
    }
    
    $sql .= " ORDER BY a.date DESC, s.grade, s.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll();
    
} elseif ($report_type === 'by_student') {
    // Reporte por estudiante
    $sql = "
        SELECT 
            s.id,
            s.name as student_name,
            s.grade,
            s.document_number,
            u.name as parent_name,
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status = 'Asistió' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN a.status = 'No asistió' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN a.status = 'Tarde' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN a.status = 'Excusado' THEN 1 ELSE 0 END) as excused_days,
            ROUND((SUM(CASE WHEN a.status = 'Asistió' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as attendance_rate
        FROM students s
        JOIN users u ON s.parent_id = u.id
        LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    if ($role === 'teacher') {
        $sql .= " JOIN teacher_students ts ON s.id = ts.student_id AND ts.teacher_id = ?";
        $params[] = $user['id'];
    }
    
    if (!empty($grade_filter)) {
        $sql .= " WHERE s.grade = ?";
        $params[] = $grade_filter;
    }
    
    $sql .= " GROUP BY s.id, s.name, s.grade, s.document_number, u.name
              HAVING total_days > 0
              ORDER BY s.grade, attendance_rate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll();
}

// Calcular estadísticas generales
$total_records = 0;
$total_present = 0;
$total_absent = 0;
$total_late = 0;
$total_excused = 0;

if ($report_type === 'summary') {
    foreach ($report_data as $row) {
        $total_records += $row['total_records'];
        $total_present += $row['present'];
        $total_absent += $row['absent'];
        $total_late += $row['late'];
        $total_excused += $row['excused'];
    }
} elseif ($report_type === 'by_student') {
    foreach ($report_data as $row) {
        $total_records += $row['total_days'];
        $total_present += $row['present_days'];
        $total_absent += $row['absent_days'];
        $total_late += $row['late_days'];
        $total_excused += $row['excused_days'];
    }
}

$attendance_percentage = $total_records > 0 ? round(($total_present / $total_records) * 100, 1) : 0;
?>

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $total_records ?></h4>
                <small>Total registros</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $total_present ?></h4>
                <small>Asistencias</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $total_absent ?></h4>
                <small>Ausencias</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $total_late ?></h4>
                <small>Tardanzas</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $total_excused ?></h4>
                <small>Excusados</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $attendance_percentage ?>%</h4>
                <small>% Asistencia</small>
            </div>
        </div>
    </div>
</div>

<!-- Datos del reporte -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-table"></i> 
            <?php
            $report_titles = [
                'summary' => 'Reporte Resumen por Fecha',
                'detailed' => 'Reporte Detallado',
                'by_student' => 'Reporte por Estudiante'
            ];
            echo $report_titles[$report_type];
            ?>
            <small class="text-muted ms-2">
                (<?= formatDate($start_date) ?> - <?= formatDate($end_date) ?>)
            </small>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($report_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <h5 class="mt-3 text-muted">No hay datos disponibles</h5>
                <p class="text-muted">No se encontraron registros para el período seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="reportTable">
                    <thead class="table-light">
                        <tr>
                            <?php if ($report_type === 'summary'): ?>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Asistieron</th>
                                <th>Ausentes</th>
                                <th>Tarde</th>
                                <th>Excusados</th>
                                <th>% Asistencia</th>
                            <?php elseif ($report_type === 'detailed'): ?>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th>Grado</th>
                                <th>Estado</th>
                                <th>Profesor</th>
                                <th>Notas</th>
                            <?php elseif ($report_type === 'by_student'): ?>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th>Grado</th>
                                <th>Acudiente</th>
                                <th>Días Total</th>
                                <th>Asistió</th>
                                <th>Ausente</th>
                                <th>Tarde</th>
                                <th>% Asistencia</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'summary'): ?>
                                    <td><?= formatDate($row['date']) ?></td>
                                    <td><?= $row['total_records'] ?></td>
                                    <td><span class="badge bg-success"><?= $row['present'] ?></span></td>
                                    <td><span class="badge bg-danger"><?= $row['absent'] ?></span></td>
                                    <td><span class="badge bg-warning"><?= $row['late'] ?></span></td>
                                    <td><span class="badge bg-info"><?= $row['excused'] ?></span></td>
                                    <td>
                                        <?php $daily_percentage = $row['total_records'] > 0 ? round(($row['present'] / $row['total_records']) * 100, 1) : 0; ?>
                                        <strong><?= $daily_percentage ?>%</strong>
                                    </td>
                                <?php elseif ($report_type === 'detailed'): ?>
                                    <td><?= formatDate($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><code><?= htmlspecialchars($row['document_number']) ?></code></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['grade']) ?></span></td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'Asistió' => 'success',
                                            'No asistió' => 'danger',
                                            'Tarde' => 'warning',
                                            'Excusado' => 'info'
                                        ];
                                        $color = $status_colors[$row['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td><small><?= htmlspecialchars($row['teacher_name']) ?></small></td>
                                    <td>
                                        <?php if (!empty($row['notes'])): ?>
                                            <small><?= htmlspecialchars($row['notes']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($report_type === 'by_student'): ?>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><code><?= htmlspecialchars($row['document_number']) ?></code></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['grade']) ?></span></td>
                                    <td><?= htmlspecialchars($row['parent_name']) ?></td>
                                    <td><?= $row['total_days'] ?></td>
                                    <td><span class="badge bg-success"><?= $row['present_days'] ?></span></td>
                                    <td><span class="badge bg-danger"><?= $row['absent_days'] ?></span></td>
                                    <td><span class="badge bg-warning"><?= $row['late_days'] ?></span></td>
                                    <td>
                                        <strong class="<?= $row['attendance_rate'] >= 80 ? 'text-success' : ($row['attendance_rate'] >= 60 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $row['attendance_rate'] ?>%
                                        </strong>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportReport() {
    // Crear CSV simple del reporte
    const table = document.getElementById('reportTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'reporte_asistencia_<?= date("Y-m-d") ?>_<?= $report_type ?>.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
</script>
