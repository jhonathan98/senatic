<?php
include 'includes/db.php';
include 'includes/functions.php';

$error = '';
$success = '';

if ($_POST) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $grado = $rol === 'estudiante' ? $_POST['grado'] : null;
    $edad = $_POST['edad'];

    // Validar correo institucional
    if (!str_ends_with($email, '@colegio.edu')) {
        $error = "Solo se permiten correos institucionales (@colegio.edu)";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "El correo ya está registrado.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, grado, edad) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $password, $rol, $grado, $edad])) {
                $success = "Registro exitoso. Inicia sesión.";
            } else {
                $error = "Error al registrar.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Registro - EduBienestar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 500px;">
    <h2 class="text-center mb-4">Crear Cuenta</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email (@colegio.edu)</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Edad</label>
            <input type="number" name="edad" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Rol</label>
            <select name="rol" class="form-control" onchange="toggleGrado(this)">
                <option value="estudiante">Estudiante</option>
                <option value="psicologo">Psicólogo</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        <div class="mb-3" id="gradoField">
            <label>Grado</label>
            <input type="text" name="grado" class="form-control">
        </div>
        <button type="submit" class="btn btn-success w-100">Registrar</button>
    </form>
    <p class="text-center mt-3"><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p>
</div>

<script>
function toggleGrado(select) {
    document.getElementById('gradoField').style.display = select.value === 'estudiante' ? 'block' : 'none';
}
toggleGrado(document.querySelector('[name="rol"]'));
</script>
</body>
</html>