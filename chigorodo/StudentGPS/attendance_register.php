<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=attendance_register");
    exit;
}

require_once 'config.php';
$teacher_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];
$date = $_GET['date'] ?? date('Y-m-d');
$students = [];

// Obtener estudiantes del profesor (o todos si es admin)
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT s.id, s.name FROM students s ORDER BY s.name");
} else {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM students s 
        JOIN teacher_students ts ON s.id = ts.student_id 
        WHERE ts.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
}

$students = $stmt->fetchAll();

// Guardar asistencia
if ($_POST) {
    $date = $_POST['date'];
    foreach ($_POST['status'] as $student_id => $status) {
        $stmt = $pdo->prepare("REPLACE INTO attendance (student_id, teacher_id, date, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $teacher_id, $date, $status]);
    }
    echo "<div class='alert alert-success'>Asistencia guardada para $date</div>";
}
?>

<h2>Registrar Asistencia - <?= $date ?></h2>
<form method="POST">
    <input type="hidden" name="date" value="<?= $date ?>">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Estudiante</th>
                <th>Asistencia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td>
                        <select name="status[<?= $s['id'] ?>]" class="form-select">
                            <option value="Asistió">Asistió</option>
                            <option value="No asistió">No asistió</option>
                            <option value="Tarde">Tarde</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary">Guardar Asistencia</button>
</form>