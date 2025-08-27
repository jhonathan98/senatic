<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=search_student");
    exit;
}

require_once 'config.php';
$user = $_SESSION['user'];
$role = $user['role'];
$teacher_id = $user['id'];

// Filtros
$search = $_GET['search'] ?? '';
$grade = $_GET['grade'] ?? '';
$students = [];

// Consulta base
$sql = "SELECT s.id, s.name, s.document_number, s.grade, u.name as parent_name FROM students s JOIN users u ON s.parent_id = u.id";
$params = [];

// Filtros
if ($role === 'teacher') {
    $sql .= " JOIN teacher_students ts ON s.id = ts.student_id WHERE ts.teacher_id = ?";
    $params[] = $teacher_id;
} else {
    $sql .= " WHERE 1=1";
}

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR s.document_number LIKE ? OR u.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($grade)) {
    $sql .= " AND s.grade = ?";
    $params[] = $grade;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Grados para filtro
$grades = ['1°', '2°', '3°', '4°', '5°', '6°', '7°', '8°', '9°', '10°', '11°'];
?>

<h2>Buscar Estudiantes</h2>

<!-- Formulario de búsqueda -->
<form method="GET" class="mb-4">
    <input type="hidden" name="section" value="search_student">
    <div class="row g-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Nombre, documento o acudiente..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="grade" class="form-select">
                <option value="">Todos los grados</option>
                <?php foreach ($grades as $g): ?>
                    <option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Buscar</button>
        </div>
    </div>
</form>

<!-- Resultados -->
<?php if (empty($students)): ?>
    <div class="alert alert-info">No se encontraron estudiantes.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
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
                    <td><?= htmlspecialchars($s['grade']) ?></td>
                    <td><?= htmlspecialchars($s['parent_name']) ?></td>
                    <td>
                        <a href="map.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-geo-alt"></i> Ubicación
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>