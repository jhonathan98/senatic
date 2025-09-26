<?php
// registro.php - Página de registro

session_start();

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Manejar el formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = $_POST['nombre_completo'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $telefono = $_POST['telefono'];
    $telefono_recuperacion = $_POST['telefono_recuperacion'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    
    // Validar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Validar formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor ingrese un correo electrónico válido.";
        } else {
            // Conexión a la base de datos
            $servername = "localhost";
            $username = "root"; // Cambiar según tu configuración
            $password = ""; // Cambiar según tu configuración
            $dbname = "sistema_mentorias";
            
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Verificar si el correo ya existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo_electronico = ?");
                $stmt->execute([$correo]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Este correo electrónico ya está registrado.";
                } else {
                    // Hash de la contraseña
                    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                    
                    // Insertar nuevo usuario con rol de usuario (estudiante)
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, correo_electronico, contrasena, telefono, telefono_recuperacion, fecha_nacimiento, rol) VALUES (?, ?, ?, ?, ?, ?, 'usuario')");
                    $stmt->execute([$nombre_completo, $correo, $hashed_password, $telefono, $telefono_recuperacion, $fecha_nacimiento]);
                    
                    // Redirigir a la página de inicio de sesión
                    header("Location: login.php");
                    exit();
                }
                
            } catch(PDOException $e) {
                $error = "Error de conexión: " . $e->getMessage();
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
    <title>Registro - Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #8a2be2;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-register {
            background-color: #8a2be2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-register:hover {
            background-color: #7b1fa2;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #8a2be2;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Sistema de Mentorías</h2>
            <p>Crea tu cuenta para acceder a nuestros servicios</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>
            <div class="mb-3">
                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" required>
            </div>
            <div class="mb-3">
                <label for="telefono_recuperacion" class="form-label">Teléfono de Recuperación</label>
                <input type="tel" class="form-control" id="telefono_recuperacion" name="telefono_recuperacion" required>
            </div>
            <div class="mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
            </div>
            <button type="submit" class="btn btn-register">Registrar</button>
        </form>
        
        <div class="login-link">
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>