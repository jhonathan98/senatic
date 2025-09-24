<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config.php';
$message = '';

// Verificar si hay un mensaje en la URL
if (isset($_GET['message'])) {
    $message = sanitizeInput($_GET['message']);
}

if ($_POST) {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Token de seguridad inválido.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        // Validar campos vacíos
        if (empty($username) || empty($password)) {
            $message = "Por favor, complete todos los campos.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, role, name, document_number FROM users WHERE username = ? AND created_at IS NOT NULL");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    // Guardar información del usuario en la sesión
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'name' => $user['name'],
                        'document_number' => $user['document_number']
                    ];
                    $_SESSION['login_time'] = time();
                    
                    // Actualizar último acceso
                    $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$user['id']]);
                    
                    redirect("dashboard.php");
                } else {
                    $message = "Usuario o contraseña incorrectos.";
                    // Log del intento fallido
                    error_log("Intento de login fallido para usuario: $username desde IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } catch (PDOException $e) {
                error_log("Error en login: " . $e->getMessage());
                $message = "Error interno. Por favor, inténtelo más tarde.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>STUDENTGPS - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            background: white;
        }
        .header {
            background-color: #0a2e67;
            color: white;
            padding: 1.5rem 0;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Encabezado -->
    <div class="header">
        <h1>STUDENTGPS</h1>
        <p>Monitoreo seguro y en tiempo real para estudiantes</p>
    </div>

    <!-- Formulario de Login -->
    <div class="login-container">
        <h3 class="text-center mb-4">Iniciar Sesión</h3>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="mb-3">
                <label>Usuario</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">Usuarios de prueba:<br>
                admin / password<br>
                juanperez / password<br>
                profesor1 / password
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>