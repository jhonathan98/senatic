<?php
// pages/register.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: welcome.php");
    exit();
}

$error = '';
$success = '';

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nombre = sanitizeData($_POST['nombre']);
    $email = sanitizeData($_POST['email']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $rol = sanitizeData($_POST['rol'] ?? 'estudiante');
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($contrasena) || empty($confirmar_contrasena)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!validarEmail($email)) {
        $error = "El formato del email no es válido.";
    } elseif (strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $existeUsuario = $stmt->fetch();
            
            if ($existeUsuario) {
                $error = "Ya existe un usuario registrado con este email.";
            } else {
                // Crear nuevo usuario
                $contrasenaHash = hashPassword($contrasena);
                
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nombre, $email, $contrasenaHash, $rol]);
                
                $success = "¡Registro exitoso! Ya puedes iniciar sesión.";
                // Limpiar campos después del registro exitoso
                $nombre = $email = '';
            }
        } catch(PDOException $e) {
            $error = "Error al crear la cuenta: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Crear Cuenta - OSE
                        </h3>
                        <p class="mb-0 mt-2">Completa el formulario para comenzar tu viaje educativo</p>
                    </div>
                    <div class="card-body p-4">

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo $success; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                        <form method="POST" id="registerForm">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       placeholder="Ingresa tu nombre completo"
                                       value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="tu@email.com"
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="contrasena" 
                                       name="contrasena" 
                                       placeholder="Mínimo 6 caracteres"
                                       required>
                                <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_contrasena" 
                                       name="confirmar_contrasena" 
                                       placeholder="Repite tu contraseña"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="rol" class="form-label">Tipo de Usuario</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="estudiante" selected>Estudiante</option>
                                    <option value="docente">Docente</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terminos" required>
                                    <label class="form-check-label" for="terminos">
                                        Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> 
                                        y la <a href="#" class="text-decoration-none">política de privacidad</a>
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" name="register" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Crear Mi Cuenta
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">¿Ya tienes una cuenta?</p>
                            <a href="login.php" class="btn btn-outline-success">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Al registrarte, aceptas nuestros términos de servicio y política de privacidad.
                                <br>
                                OSE - Facilitando el aprendizaje, potenciando el conocimiento.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('contrasena');
            const confirmPassword = document.getElementById('confirmar_contrasena');

            // Validar coincidencia de contraseñas
            function validatePasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                    confirmPassword.classList.add('is-invalid');
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.classList.remove('is-invalid');
                }
            }

            // Validar longitud de contraseña
            function validatePasswordLength() {
                if (password.value && password.value.length < 6) {
                    password.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
                    password.classList.add('is-invalid');
                } else {
                    password.setCustomValidity('');
                    password.classList.remove('is-invalid');
                }
            }

            password.addEventListener('input', function() {
                validatePasswordLength();
                validatePasswordMatch();
            });

            confirmPassword.addEventListener('input', validatePasswordMatch);

            // Validación del formulario
            form.addEventListener('submit', function(e) {
                validatePasswordLength();
                validatePasswordMatch();
                
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>
