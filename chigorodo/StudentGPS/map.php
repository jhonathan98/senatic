<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';
$user = $_SESSION['user'];
$students = [];
$selected_id = null;

// Si es admin o profesor, pueden buscar
if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
    if (isset($_GET['student_id'])) {
        $selected_id = (int)$_GET['student_id'];
    }

    $stmt = $pdo->prepare("SELECT s.id, s.name FROM students s ORDER BY s.name");
    $stmt->execute();
    $students = $stmt->fetchAll();
} else {
    // Acudiente: solo su estudiante
    $stmt = $pdo->prepare("SELECT id, name FROM students WHERE parent_id = ?");
    $stmt->execute([$user['id']]);
    $student = $stmt->fetch();
    if ($student) {
        $selected_id = $student['id'];
    }
}
?>

<h2>Ubicación del Estudiante</h2>

<?php if ($user['role'] !== 'parent'): ?>
    <form method="GET" class="mb-3">
        <input type="hidden" name="section" value="map">
        <label>Seleccionar estudiante:</label>
        <select name="student_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Selecciona --</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $selected_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<?php if ($selected_id): ?>
    <div style="height: 400px; background: #eee; display: flex; align-items: center; justify-content: center;">
        <img src="https://via.placeholder.com/600x400?text=Mapa+Estudiante+ID+<?= $selected_id ?>" 
             alt="Mapa" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
<?php else: ?>
    <div class="alert alert-info">No hay estudiantes disponibles.</div>
<?php endif; ?>