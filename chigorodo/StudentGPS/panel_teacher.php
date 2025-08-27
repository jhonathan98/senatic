<?php
require_once 'config.php';

$teacher_id = $_SESSION['user']['id'];

// Obtener estudiantes asignados al profesor
$stmt = $pdo->prepare("
    SELECT 
        s.id, 
        s.name, 
        s.grade, 
        u.name AS parent_name,
        s.document_number
    FROM students s
    JOIN teacher_students ts ON s.id = ts.student_id
    JOIN users u ON s.parent_id = u.id
    WHERE ts.teacher_id = ?
    ORDER BY s.grade, s.name
");
$stmt->execute([$teacher_id]);
$students = $stmt->fetchAll();

// Contar estudiantes
$total_students = count($students);

// Asistieron hoy
$today = date('Y-m-d');
$attended = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM attendance a
    JOIN teacher_students ts ON a.student_id = ts.student_id
    WHERE a.date = ? AND ts.teacher_id = ?
");
$attended->execute([$today, $teacher_id]);
$attended = $attended->fetch();

// Asistencia pendiente (no registrada hoy)
$pending = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM teacher_students ts
    WHERE ts.teacher_id = ? 
    AND ts.student_id NOT IN (
        SELECT student_id FROM attendance WHERE date = ?
    )
");
$pending->execute([$teacher_id, $today]);
$pending = $pending->fetch();
?>

<h2>Panel del Profesor</h2>
<p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['user']['name']) ?></strong></p>

<!-- Estadísticas -->
<div class="row text-center mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5><?= $total_students ?></h5>
                <p class="mb-0">Mis Estudiantes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5><?= $attended['count'] ?></h5>
                <p class="mb-0">Asistieron Hoy</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5><?= $pending['count'] ?></h5>
                <p class="mb-0">Pendientes de Registro</p>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="mb-4 d-flex gap-2 flex-wrap">
    <a href="attendance_register.php" class="btn btn-primary">
        <i class="bi bi-calendar-check"></i> Registrar Asistencia
    </a>
    <a href="attendance_search.php" class="btn btn-outline-primary">
        <i class="bi bi-search"></i> Ver Asistencia por Fecha
    </a>
    <a href="search_student.php" class="btn btn-outline-info">
        <i class="bi bi-person-search"></i> Buscar Estudiante
    </a>
</div>

<!-- Lista de estudiantes -->
<h4>Mis Estudiantes (<?= $total_students ?>)</h4>

<?php if (empty($students)): ?>
    <div class="alert alert-info">
        No tienes estudiantes asignados aún. Contacta al administrador.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Grado</th>
                    <th>Acudiente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['document_number']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($s['grade']) ?></span></td>
                    <td><?= htmlspecialchars($s['parent_name']) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="map.php?student_id=<?= $s['id'] ?>" 
                               class="btn btn-outline-info" 
                               title="Ver ubicación">
                                <i class="bi bi-geo-alt"></i> Ubicación
                            </a>
                            <a href="attendance_register.php" 
                               class="btn btn-outline-success" 
                               title="Registrar asistencia">
                                <i class="bi bi-calendar-plus"></i> Asistencia
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Recordatorio -->
<div class="alert alert-light border mt-4">
    <i class="bi bi-info-circle-fill text-primary"></i>
    <strong>Consejo:</strong> Recuerda registrar la asistencia diaria para mantener el control escolar actualizado.
</div>