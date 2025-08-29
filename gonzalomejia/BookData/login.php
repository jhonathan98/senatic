<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Get user by username or email
    $user = get_user_by_username_or_email($username_or_email);
    
    if ($user && verify_password($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error_message = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookData - Inicio de sesión</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .login-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-image {
            flex: 1;
            background-image: url('https://placehold.co/800x600/3b71e7/ffffff?text=Biblioteca+Moderna');
            background-size: cover;
            background-position: center;
            border-right: 1px solid #dee2e6;
        }
        .login-form {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
        }
        .login-form h2 {
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
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .signup-link a {
            color: #3b71e7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .signup-link a:hover {
            color: #2c55bb;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-image {
                height: 300px;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            .login-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image"></div>
        <div class="login-form">
            <h2>📚 Book Data</h2>
            <form id="loginForm" action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Nombre de usuario o correo electrónico</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Iniciar sesión</button>
                <div class="signup-link">
                    ¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Por favor completa todos los campos.');
            }
        });
    </script>
</body>
</html>