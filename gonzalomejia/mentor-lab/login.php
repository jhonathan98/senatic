<?php
// login.php - Página de inicio de sesión

// Configuración de seguridad para la sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Tiempo de expiración de la sesión (2 horas)
$session_timeout = 7200; // 2 horas en segundos

// Verificar si la sesión ha expirado
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    
    if ($inactive >= $session_timeout) {
        // Limpiar todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir con mensaje de sesión expirada
        header("Location: login.php?mensaje=sesion_expirada");
        exit();
    }
}

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Manejar el formulario de inicio de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y limpiar datos de entrada
    $correo = filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL);
    $contrasena = trim($_POST['contrasena']);
    
    if (!$correo) {
        $error = "Por favor, ingrese un correo electrónico válido.";
    } elseif (empty($contrasena)) {
        $error = "Por favor, ingrese su contraseña.";
    } else {
        try {
            error_log("Intentando iniciar sesión para: " . $correo);
            // Incluir y usar la conexión centralizada
            require_once __DIR__ . '/config/db.php';
            $conn = getConnection();
        
            // Preparar y ejecutar la consulta con parámetros seguros
            $stmt = $conn->prepare("SELECT id, nombre_completo, correo_electronico, contrasena, rol, status FROM usuarios WHERE correo_electronico = :correo");
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = "Correo electrónico o contraseña incorrectos.";
            } elseif ($user['status'] !== 'activo') {
                $error = "Esta cuenta no está activa. Por favor, contacte al administrador.";
            } elseif (password_verify($contrasena, $user['contrasena'])) {
                // Iniciar sesión de manera segura
                session_regenerate_id(true); // Previene session fixation
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre_completo'];
                $_SESSION['correo'] = $user['correo_electronico'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['last_activity'] = time();
                
                // Registrar el inicio de sesión exitoso
                error_log("Inicio de sesión exitoso para el usuario: " . $user['correo_electronico']);
                
                // Redirigir al dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Correo electrónico o contraseña incorrectos.";
                error_log("Intento de inicio de sesión fallido para: " . $correo);
            }
        } catch(PDOException $e) {
            error_log("Error de base de datos en login: " . $e->getMessage());
            $error = "Error del sistema. Por favor, intente más tarde.";
        } catch(Exception $e) {
            error_log("Error general en login: " . $e->getMessage());
            $error = "Error del sistema. Por favor, intente más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #8a2be2;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-login {
            background-color: #8a2be2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #7b1fa2;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #8a2be2;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Sistema de Mentorías</h2>
            <p>Accede a tu cuenta para comenzar</p>
        </div>
        
        <?php
        // Manejo de mensajes del sistema
        if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'sesion_expirada'): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit" class="btn btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="register-link">
            <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>