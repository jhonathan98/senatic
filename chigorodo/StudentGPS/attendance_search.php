<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=attendance_search");
    exit;
}

require_once 'config.php';
$user = $_SESSION['user'];
$role = $user['role'];
$teacher_id = $user['id'];
$date = $_GET['date'] ?? date('Y-m-d');
$students = [];
?>

<h2>Asistencia del Día: <?= $date ?></h2>

<!-- Selector de fecha -->
<form method="GET" class="mb-4">
    <input type="hidden" name="section" value="attendance_search">
    <label>Seleccionar fecha:</label>
    <div class="d-flex gap-2">
        <input type="date" name="date" class="form-control w-auto" value="<?= $date ?>" required>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </div>
</form>

<?php
// Obtener estudiantes (todos si admin, solo asignados si profesor)
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT s.id, s.name FROM students s ORDER BY s.name");
} else {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM students s 
        JOIN teacher_students ts ON s.id = ts.student_id 
        WHERE ts.teacher_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$teacher_id]);
}

$students = $stmt->fetchAll();

// Obtener asistencia
$stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date = ?");
$stmt->execute([$date]);
$attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php if (empty($students)): ?>
    <div class="alert alert-info">No hay estudiantes asignados.</div>
<?php else: ?>
    <table class="table table-bordered table-striped">
        <thead class="table-primary">
            <tr>
                <th>Estudiante</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): 
                $status = $attendance[$s['id']] ?? 'No registrada';
                $color = $status === 'Asistió' ? 'success' : ($status === 'No asistió' ? 'danger' : ($status === 'Tarde' ? 'warning' : 'secondary'));
            ?>
            <tr>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td>
                    <span class="badge bg-<?= $color ?>">
                        <?= $status ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>