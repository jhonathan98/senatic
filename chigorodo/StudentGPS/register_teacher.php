<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=register_teacher");
    exit;
}

require_once 'config.php';
$message = '';

if ($_POST) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $doc = $_POST['document_number'];
    $password = password_hash('password', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role, document_number) VALUES (?, ?, ?, 'teacher', ?)");
        $stmt->execute([$name, $username, $password, $doc]);
        $message = "✅ Profesor creado. Contraseña: <strong>password</strong>";
    } catch (PDOException $e) {
        $message = "Error: Documento o usuario ya existe.";
    }
}
?>

<h2>Registrar Profesor</h2>
<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<form method="POST" class="card p-4">
    <div class="mb-3">
        <label>Nombre completo *</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Usuario *</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Documento *</label>
        <input type="text" name="document_number" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Registrar Profesor</button>
</form>