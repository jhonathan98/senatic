<?php
require_once 'config.php';

// Estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
$students_count = $pdo->query("SELECT COUNT(*) as total FROM students")->fetch();
$teachers_count = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'")->fetch();
$attendance_today = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE date = ?");
$attendance_today->execute([date('Y-m-d')]);
$attendance_today = $attendance_today->fetch();

// Últimos estudiantes
$latest_students = $pdo->query("
    SELECT s.name, s.grade, u.name as parent_name 
    FROM students s 
    JOIN users u ON s.parent_id = u.id 
    ORDER BY s.id DESC LIMIT 5
")->fetchAll();
?>

<h2>Panel de Administración</h2>

<!-- Estadísticas -->
<div class="row text-center mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5><?= $students_count['total'] ?></h5>
                <p class="mb-0">Estudiantes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5><?= $teachers_count['total'] ?></h5>
                <p class="mb-0">Profesores</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5><?= $attendance_today['present'] ?></h5>
                <p class="mb-0">Asistieron Hoy</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5><?= date('d/m') ?></h5>
                <p class="mb-0">Fecha</p>
            </div>
        </div>
    </div>
</div>

<!-- Últimos estudiantes registrados -->
<h4>Últimos Estudiantes Registrados</h4>
<table class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th>Nombre</th>
            <th>Grado</th>
            <th>Acudiente</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($latest_students)): ?>
            <tr>
                <td colspan="3" class="text-center text-muted">No hay estudiantes registrados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($latest_students as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['grade']) ?></td>
                    <td><?= htmlspecialchars($s['parent_name']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Acciones rápidas -->
<div class="mt-4">
    <a href="register_student.php" class="btn btn-primary me-2">
        <i class="bi bi-person-plus"></i> Registrar Estudiante
    </a>
    <a href="register_teacher.php" class="btn btn-success">
        <i class="bi bi-person-workspace"></i> Registrar Profesor
    </a>
</div>