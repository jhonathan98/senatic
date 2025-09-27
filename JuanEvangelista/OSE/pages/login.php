<?php
// pages/login.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: welcome.php");
    exit();
}

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeData($_POST['email']);
    $contrasena = $_POST['contrasena'];
    
    if (empty($email) || empty($contrasena)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!validarEmail($email)) {
        $error = "El formato del email no es válido.";
    } else {
        // Verificar credenciales
        $usuario = verificarCredenciales($pdo, $email, $contrasena);
        
        if ($usuario) {
            // Login exitoso - inicializar sesión
            iniciarSesion($usuario);
            
            // Redirigir al dashboard
            header("Location: welcome.php");
            exit();
        } else {
            $error = "Email o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Iniciar Sesión - OSE
                        </h3>
                        <p class="mb-0 mt-2">Ingresa tus credenciales para continuar</p>
                    </div>
                    <div class="card-body p-4">

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="tu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="contrasena" 
                                       name="contrasena" 
                                       placeholder="Tu contraseña"
                                       required>
                            </div>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember">
                                            <label class="form-check-label" for="remember">
                                                Recordarme
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <a href="#" class="text-decoration-none">
                                            ¿Olvidaste tu contraseña?
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" name="login" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Iniciar Sesión
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">¿No tienes cuenta? 
                                <a href="register.php" class="text-decoration-none">
                                    Regístrate aquí
                                </a>
                            </p>
                        </div>

                        <!-- Demo credentials info -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Credenciales de prueba:
                            </h6>
                            <small class="text-muted">
                                <strong>Email:</strong> estudiante@ose.com<br>
                                <strong>Contraseña:</strong> 123456
                            </small>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-rellenar credenciales de demo (solo para desarrollo)
        document.addEventListener('DOMContentLoaded', function() {
            const demoInfo = document.querySelector('.bg-light');
            if (demoInfo) {
                demoInfo.addEventListener('click', function() {
                    document.getElementById('email').value = 'estudiante@ose.com';
                    document.getElementById('contrasena').value = '123456';
                });
                demoInfo.style.cursor = 'pointer';
                demoInfo.title = 'Haz clic para auto-completar';
            }
        });
    </script>
</body>
</html>