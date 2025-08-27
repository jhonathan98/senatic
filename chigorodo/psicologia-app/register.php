<?php
include 'includes/db.php';
include 'includes/auth.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $edad = isset($_POST['edad']) ? (int)$_POST['edad'] : null;

    if (empty($nombre) || empty($email) || empty($password) || empty($rol)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!validateEmail($email)) {
        $error = "El correo debe ser institucional (@institucion.edu).";
    } elseif ($rol === 'estudiante' && (!$edad || $edad < 10 || $edad > 30)) {
        $error = "La edad debe estar entre 10 y 30 años.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "Este correo ya está registrado.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nombre, email, password, rol, edad) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $hashed, $rol, $edad])) {
                $success = "Registro exitoso. Ahora puedes iniciar sesión.";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Crear Cuenta</h3>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Nombre completo</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Correo institucional (@institucion.edu)</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Rol</label>
                                <select name="rol" class="form-control" onchange="toggleEdad(this.value)">
                                    <option value="">Seleccionar rol</option>
                                    <option value="estudiante">Estudiante</option>
                                    <option value="psicologo">Psicólogo</option>
                                </select>
                            </div>
                            <div class="mb-3" id="edad-group" style="display:none;">
                                <label>Edad</label>
                                <input type="number" name="edad" class="form-control" min="10" max="30">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Registrarse</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleEdad(rol) {
            document.getElementById('edad-group').style.display = rol === 'estudiante' ? 'block' : 'none';
        }
    </script>
</body>
</html>