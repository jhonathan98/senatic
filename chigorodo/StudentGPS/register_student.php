<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=register_student");
    exit;
}
if ($_SESSION['user']['role'] !== 'parent' && $_SESSION['user']['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    return;
}

require_once 'config.php';
$message = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $doc = trim($_POST['document_number']);
    $grade = $_POST['grade'];
    $parent_id = $_SESSION['user']['id'];

    if (empty($name) || empty($doc) || empty($grade)) {
        $message = "Todos los campos son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, document_number, grade, parent_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $doc, $grade, $parent_id]);
            $message = "✅ Estudiante registrado con éxito.";
        } catch (PDOException $e) {
            $message = "Error: " . ($e->getCode() == 23000 ? "Documento ya registrado." : $e->getMessage());
        }
    }
}
?>

<h2>Registrar Estudiante</h2>
<?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') ? 'danger' : 'success'; ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" class="card p-4">
    <div class="mb-3">
        <label>Nombre del estudiante *</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Documento del estudiante *</label>
        <input type="text" name="document_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Grado *</label>
        <select name="grade" class="form-select" required>
            <option value="">-- Selecciona --</option>
            <?php foreach (['1°','2°','3°','4°','5°','6°','7°','8°','9°','10°','11°'] as $g): ?>
                <option value="<?= $g ?>"><?= $g ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Registrar</button>
</form>