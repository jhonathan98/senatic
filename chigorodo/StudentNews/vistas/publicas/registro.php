<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student News - Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow" style="width: 100%; max-width: 500px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">Registrarse</h2>
                <p class="text-muted">Únete al equipo de Student News</p>
            </div>

            <?php
            // Mostrar mensajes de error o éxito
            if (isset($_SESSION['mensaje'])) {
                $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
                echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">';
                echo $_SESSION['mensaje'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
            }

            // Obtener datos del formulario si hay errores
            $form_data = $_SESSION['form_data'] ?? [];
            unset($_SESSION['form_data']);
            ?>

            <form action="../../procesos/registro_proceso.php" method="POST">
                <div class="mb-3">
                    <label for="nombre_completo" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                           value="<?= htmlspecialchars($form_data['nombre_completo'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" 
                           value="<?= htmlspecialchars($form_data['correo'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" 
                           value="<?= htmlspecialchars($form_data['nombre_usuario'] ?? '') ?>" required>
                    <div class="form-text">Solo letras, números y guiones bajos. Mínimo 3 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label for="grado" class="form-label">Grado</label>
                    <input type="text" class="form-control" id="grado" name="grado" 
                           value="<?= htmlspecialchars($form_data['grado'] ?? '') ?>" placeholder="Ej: 10°A">
                </div>
                <div class="mb-3">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" 
                           minlength="6" required>
                    <div class="form-text">Mínimo 6 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label for="contrasena_confirm" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="contrasena_confirm" name="contrasena_confirm" 
                           minlength="6" required>
                    <div id="password-match-message" class="form-text"></div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Registrarse</button>
                </div>
                <div class="text-center mt-3">
                    <p class="mb-0">¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none">Iniciar sesión</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación en tiempo real de contraseñas
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('contrasena');
            const passwordConfirm = document.getElementById('contrasena_confirm');
            const messageDiv = document.getElementById('password-match-message');
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');

            function validatePasswords() {
                if (passwordConfirm.value === '') {
                    messageDiv.textContent = '';
                    messageDiv.className = 'form-text';
                    return;
                }

                if (password.value === passwordConfirm.value) {
                    messageDiv.textContent = '✓ Las contraseñas coinciden';
                    messageDiv.className = 'form-text text-success';
                } else {
                    messageDiv.textContent = '✗ Las contraseñas no coinciden';
                    messageDiv.className = 'form-text text-danger';
                }
            }

            password.addEventListener('input', validatePasswords);
            passwordConfirm.addEventListener('input', validatePasswords);

            // Validación del nombre de usuario
            const username = document.getElementById('nombre_usuario');
            username.addEventListener('input', function() {
                const value = this.value;
                const regex = /^[a-zA-Z0-9_]+$/;
                
                if (value.length > 0 && (!regex.test(value) || value.length < 3)) {
                    this.setCustomValidity('Solo letras, números y guiones bajos. Mínimo 3 caracteres.');
                } else {
                    this.setCustomValidity('');
                }
            });

            // Validación antes de enviar
            form.addEventListener('submit', function(e) {
                if (password.value !== passwordConfirm.value) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    passwordConfirm.focus();
                }
            });
        });
    </script>
</body>
</html>