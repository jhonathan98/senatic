<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InsumoTrack - Login / Registro</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
</head>
<body>

<div class="container login-container">
    <div class="form-container">
        <div class="logo-section">
            <i class="bi bi-box-seam"></i>
            <h2 class="mt-2">InsumoTrack</h2>
            <p class="text-muted">Sistema de Gestión de Préstamos</p>
        </div>

        <!-- Mostrar mensajes de error/éxito -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pestañas para Login y Registro -->
        <ul class="nav nav-tabs mb-3" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                    <i class="bi bi-person-plus me-2"></i>Crear Cuenta
                </button>
            </li>
        </ul>

        <div class="tab-content" id="authTabsContent">
            <!-- Tab de Login -->
            <div class="tab-pane fade show active" id="login" role="tabpanel">
                <form id="loginForm" action="../functions/auth.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label for="email_login" class="form-label">
                            <i class="bi bi-envelope me-2"></i>Email:
                        </label>
                        <input type="email" class="form-control" id="email_login" name="email" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="mb-3">
                        <label for="password_login" class="form-label">
                            <i class="bi bi-lock me-2"></i>Contraseña:
                        </label>
                        <input type="password" class="form-control" id="password_login" name="password" required placeholder="Ingresa tu contraseña">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                    </button>
                </form>
            </div>

            <!-- Tab de Registro -->
            <div class="tab-pane fade" id="register" role="tabpanel">
                <form id="registerForm" action="../functions/auth.php" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="mb-3">
                <label for="nombre_completo" class="form-label">Nombre Completo:</label>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
            </div>
            <div class="mb-3">
                <label for="tipo_documento" class="form-label">Tipo de Documento:</label>
                <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                    <option value="CC">Cédula de Ciudadanía</option>
                    <option value="TI">Tarjeta de Identidad</option>
                    <option value="CE">Cédula de Extranjería</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="numero_documento" class="form-label">Número de Documento:</label>
                <input type="text" class="form-control" id="numero_documento" name="numero_documento" required>
            </div>
            <div class="mb-3">
                <label for="institucion_educativa" class="form-label">Institución Educativa:</label>
                <input type="text" class="form-control" id="institucion_educativa" name="institucion_educativa" required>
            </div>
            <div class="mb-3">
                <label for="email_register" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email_register" name="email" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Número de Teléfono:</label>
                <input type="tel" class="form-control" id="telefono" name="telefono">
            </div>
            <div class="mb-3">
                <label for="password_register" class="form-label">Contraseña:</label>
                <input type="password" class="form-control" id="password_register" name="password" required>
            </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-person-plus me-2"></i>Registrarse
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check me-1"></i>
                Tus datos están protegidos y seguros
            </small>
        </div>
    </div>
</div>

<!-- Bootstrap JS (Opcional, para componentes como modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>