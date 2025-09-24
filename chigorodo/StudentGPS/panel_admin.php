<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Obtener estadísticas generales
$stats = getDashboardStats($pdo, $_SESSION['user']);

// Estadísticas adicionales
$parents_count = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent'")->fetch()['count'];

// Últimos estudiantes registrados
$latest_students = $pdo->query("
    SELECT s.name, s.grade, s.created_at, u.name as parent_name 
    FROM students s 
    JOIN users u ON s.parent_id = u.id 
    ORDER BY s.created_at DESC LIMIT 5
")->fetchAll();

// Asistencia por grado hoy
$attendance_by_grade = $pdo->prepare("
    SELECT s.grade, COUNT(*) as total,
           SUM(CASE WHEN a.status = 'Asistió' THEN 1 ELSE 0 END) as present
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    GROUP BY s.grade
    ORDER BY s.grade
");
$attendance_by_grade->execute([date('Y-m-d')]);
$attendance_by_grade = $attendance_by_grade->fetchAll();

// Actividad reciente (si existe la tabla)
$recent_activity = [];
try {
    $activity_stmt = $pdo->query("
        SELECT al.action, al.description, al.created_at, u.name as user_name
        FROM activity_log al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $recent_activity = $activity_stmt->fetchAll();
} catch (PDOException $e) {
    // La tabla no existe, ignorar
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2 text-primary"></i> Panel de Administración</h2>
    <small class="text-muted"><?= getGreeting() ?>, <?= htmlspecialchars($_SESSION['user']['name']) ?></small>
</div>

<!-- Estadísticas principales -->
<div class="row text-center mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= $stats['students'] ?></h3>
                        <p class="mb-0">Estudiantes</p>
                    </div>
                    <i class="bi bi-people-fill fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= $stats['teachers'] ?></h3>
                        <p class="mb-0">Profesores</p>
                    </div>
                    <i class="bi bi-person-workspace fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= $parents_count ?></h3>
                        <p class="mb-0">Acudientes</p>
                    </div>
                    <i class="bi bi-person-hearts fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= $stats['attendance_today'] ?></h3>
                        <p class="mb-0">Asistieron Hoy</p>
                    </div>
                    <i class="bi bi-calendar-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <!-- Últimos estudiantes registrados -->
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus-fill"></i> Últimos Estudiantes Registrados</h5>
            </div>
            <div class="card-body">
                <?php if (empty($latest_students)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2">No hay estudiantes registrados.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Grado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_students as $s): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                                            <small class="text-muted">Acudiente: <?= htmlspecialchars($s['parent_name']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($s['grade']) ?></span></td>
                                        <td><small><?= formatDate($s['created_at']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <!-- Asistencia por grado -->
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Asistencia por Grado (Hoy)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($attendance_by_grade)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                        <p class="mt-2">No hay datos de asistencia.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($attendance_by_grade as $grade_data): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong><?= htmlspecialchars($grade_data['grade']) ?></strong></span>
                                <span><?= $grade_data['present'] ?>/<?= $grade_data['total'] ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <?php 
                                $percentage = $grade_data['total'] > 0 ? ($grade_data['present'] / $grade_data['total']) * 100 : 0;
                                $color = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                ?>
                                <div class="progress-bar bg-<?= $color ?>" role="progressbar" 
                                     style="width: <?= $percentage ?>%" 
                                     aria-valuenow="<?= $percentage ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recent_activity)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-activity"></i> Actividad Reciente</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><?= htmlspecialchars($activity['user_name']) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span></td>
                                    <td><?= htmlspecialchars($activity['description']) ?></td>
                                    <td><small><?= formatDateTime($activity['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Acciones rápidas -->
<div class="mt-4">
    <h5><i class="bi bi-lightning-fill"></i> Acciones Rápidas</h5>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?section=register_student" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Registrar Estudiante
        </a>
        <a href="?section=register_teacher" class="btn btn-success">
            <i class="bi bi-person-workspace"></i> Registrar Profesor
        </a>
        <a href="?section=attendance_register" class="btn btn-info">
            <i class="bi bi-calendar-check"></i> Registrar Asistencia
        </a>
        <a href="?section=search_student" class="btn btn-outline-primary">
            <i class="bi bi-search"></i> Buscar Estudiante
        </a>
        <a href="?section=map" class="btn btn-outline-success">
            <i class="bi bi-geo-alt"></i> Ver Ubicaciones
        </a>
    </div>
</div>