<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=attendance_search");
    exit;
}

require_once 'config.php';
require_once 'includes/functions.php';

// Verificar permisos
requireRole(['admin', 'teacher']);

$user = $_SESSION['user'];
$role = $user['role'];
$teacher_id = $user['id'];
$date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));
$grade_filter = sanitizeInput($_GET['grade'] ?? '');
$students = [];
$stats = [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-event text-primary"></i> Consultar Asistencia</h2>
    <small class="text-muted"><?= formatDate($date) ?></small>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="section" value="attendance_search">
            <div class="col-md-4">
                <label class="form-label">Fecha:</label>
                <input type="date" name="date" class="form-control" value="<?= $date ?>" required>
            </div>
            <?php if ($role === 'admin'): ?>
            <div class="col-md-4">
                <label class="form-label">Filtrar por grado:</label>
                <select name="grade" class="form-select">
                    <option value="">Todos los grados</option>
                    <?php foreach (getGrades() as $key => $value): ?>
                        <option value="<?= $key ?>" <?= $grade_filter === $key ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <a href="?section=attendance_search&date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-calendar-today"></i> Hoy
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Construir query base
$sql = "";
$params = [];

if ($role === 'admin') {
    $sql = "
        SELECT s.id, s.name, s.grade, s.document_number, u.name as parent_name
        FROM students s 
        JOIN users u ON s.parent_id = u.id
    ";
    
    if (!empty($grade_filter)) {
        $sql .= " WHERE s.grade = ?";
        $params[] = $grade_filter;
    }
} else {
    $sql = "
        SELECT s.id, s.name, s.grade, s.document_number, u.name as parent_name
        FROM students s 
        JOIN teacher_students ts ON s.id = ts.student_id 
        JOIN users u ON s.parent_id = u.id
        WHERE ts.teacher_id = ?
    ";
    $params[] = $teacher_id;
    
    if (!empty($grade_filter)) {
        $sql .= " AND s.grade = ?";
        $params[] = $grade_filter;
    }
}

$sql .= " ORDER BY s.grade, s.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Obtener asistencia del día específico
$attendance_sql = "
    SELECT a.student_id, a.status, a.notes, a.created_at, u.name as teacher_name
    FROM attendance a
    JOIN users u ON a.teacher_id = u.id
    WHERE a.date = ?
";
$attendance_params = [$date];

if ($role === 'teacher') {
    $attendance_sql .= " AND a.teacher_id = ?";
    $attendance_params[] = $teacher_id;
}

$stmt = $pdo->prepare($attendance_sql);
$stmt->execute($attendance_params);
$attendance_data = $stmt->fetchAll();

// Indexar por student_id
$attendance = [];
foreach ($attendance_data as $record) {
    $attendance[$record['student_id']] = $record;
}

// Calcular estadísticas
$total_students = count($students);
$present = 0;
$absent = 0;
$late = 0;
$excused = 0;
$not_registered = 0;

foreach ($students as $student) {
    $status = $attendance[$student['id']]['status'] ?? null;
    switch ($status) {
        case 'Asistió': $present++; break;
        case 'No asistió': $absent++; break;
        case 'Tarde': $late++; break;
        case 'Excusado': $excused++; break;
        default: $not_registered++; break;
    }
}

$stats = [
    'total' => $total_students,
    'present' => $present,
    'absent' => $absent,
    'late' => $late,
    'excused' => $excused,
    'not_registered' => $not_registered
];
?>

<!-- Estadísticas de asistencia -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['total'] ?></h4>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['present'] ?></h4>
                <small>Asistieron</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['absent'] ?></h4>
                <small>Ausentes</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['late'] ?></h4>
                <small>Tarde</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['excused'] ?></h4>
                <small>Excusados</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= $stats['not_registered'] ?></h4>
                <small>Sin registro</small>
            </div>
        </div>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="alert alert-info text-center">
        <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
        <h5 class="mt-2">No hay estudiantes disponibles</h5>
        <p class="mb-0">No se encontraron estudiantes para mostrar en esta fecha.</p>
    </div>
<?php else: ?>
    <!-- Tabla de asistencia -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-check"></i> 
                Detalle de Asistencia - <?= formatDate($date) ?>
                <?php if (!empty($grade_filter)): ?>
                    <span class="badge bg-secondary ms-2">Grado: <?= getGrades()[$grade_filter] ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Documento</th>
                            <th>Grado</th>
                            <th>Acudiente</th>
                            <th>Estado</th>
                            <?php if ($role === 'admin'): ?>
                            <th>Registrado por</th>
                            <?php endif; ?>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $record = $attendance[$student['id']] ?? null;
                            $status = $record['status'] ?? 'Sin registro';
                            
                            // Determinar color del badge
                            switch ($status) {
                                case 'Asistió': $badge_class = 'success'; $icon = 'check-circle'; break;
                                case 'No asistió': $badge_class = 'danger'; $icon = 'x-circle'; break;
                                case 'Tarde': $badge_class = 'warning'; $icon = 'clock'; break;
                                case 'Excusado': $badge_class = 'info'; $icon = 'info-circle'; break;
                                default: $badge_class = 'secondary'; $icon = 'question-circle'; break;
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($student['name']) ?></strong>
                                <?php if ($record && !empty($record['notes'])): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-sticky"></i> <?= htmlspecialchars($record['notes']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($student['document_number']) ?></code></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($student['grade']) ?></span></td>
                            <td><?= htmlspecialchars($student['parent_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $badge_class ?>">
                                    <i class="bi bi-<?= $icon ?>"></i> <?= $status ?>
                                </span>
                            </td>
                            <?php if ($role === 'admin'): ?>
                            <td>
                                <?php if ($record): ?>
                                    <small><?= htmlspecialchars($record['teacher_name']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php if ($record): ?>
                                    <small><?= formatDateTime($record['created_at'], 'H:i') ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="mt-3">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i>
            Total de estudiantes: <strong><?= $stats['total'] ?></strong> | 
            Asistencia registrada: <strong><?= $stats['total'] - $stats['not_registered'] ?></strong> | 
            Porcentaje de asistencia: <strong><?= $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0 ?>%</strong>
        </small>
    </div>
<?php endif; ?>