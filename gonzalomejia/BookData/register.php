<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $profile_image = null;
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "Por favor completa todos los campos.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
        $error_message = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error_message = "El nombre de usuario o correo electrónico ya están en uso.";
        } else {
            // Hash password
            $hashed_password = hash_password($password);
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, 'student', NOW())");
            $stmt->execute([$username, $email, $hashed_password, $full_name]);
            
            $success_message = "Registro exitoso. Puedes iniciar sesión ahora.";
            
            // Clear form data
            $full_name = $email = $username = $password = $confirm_password = '';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - Registro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .register-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .register-image {
            flex: 1;
            background-image: url('https://placehold.co/800x600/3b71e7/ffffff?text=Registro+de+Usuarios');
            background-size: cover;
            background-position: center;
            border-right: 1px solid #dee2e6;
        }
        .register-form {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
        }
        .register-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #3b71e7;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 12px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #3b71e7;
            box-shadow: 0 0 0 0.2rem rgba(59, 113, 231, 0.25);
        }
        .btn-primary {
            background-color: #3b71e7;
            border-color: #3b71e7;
            border-radius: 5px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #2c55bb;
            border-color: #2c55bb;
            transform: translateY(-2px);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .login-link a {
            color: #3b71e7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            color: #2c55bb;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .success-message {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }
            .register-image {
                height: 300px;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            .register-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-image"></div>
        <div class="register-form">
            <h2>📚 Book Data</h2>
            <form id="registerForm" action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Nombre completo</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <div class="login-link">
                    ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const full_name = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            
            if (!full_name || !email || !username || !password || !confirm_password) {
                e.preventDefault();
                alert('Por favor completa todos los campos.');
            } else if (password !== confirm_password) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
            } else if (password.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres.');
            }
        });
    </script>
</body>
</html>