<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student News - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow" style="width: 100%; max-width: 400px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold">STUDENT NEWS</h2>
                <p class="text-muted">Tu voz, nuestra noticia</p>
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
            ?>

            <form action="../../procesos/login_procesos.php" method="POST">
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico o usuario</label>
                    <input type="text" class="form-control" id="correo" name="correo" required>
                </div>
                <div class="mb-3">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Iniciar sesión</button>
                </div>
                <div class="text-center mt-3">
                    <a href="#" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
                </div>
                <div class="text-center mt-2">
                    <p class="mb-0">¿No tienes cuenta? <a href="registro.php" class="text-decoration-none">Registrarse</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                const correo = document.getElementById('correo').value.trim();
                const contrasena = document.getElementById('contrasena').value;
                
                if (!correo || !contrasena) {
                    e.preventDefault();
                    alert('Por favor, complete todos los campos');
                    return;
                }
                
                // Deshabilitar botón para evitar doble envío
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Iniciando...';
            });
        });
    </script>
</body>
</html>