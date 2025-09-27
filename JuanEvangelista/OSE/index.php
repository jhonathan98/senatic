<?php
// index.php
// Iniciar la sesión para verificar si el usuario ya está logueado
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['usuario_id'])) { // Asumiendo que guardas el ID del usuario en la sesión
    // Redirigir a la página de bienvenida si ya está logueado
    header("Location: pages/welcome.php");
    exit(); // Es importante usar exit() después de header(Location: ...) para detener la ejecución del script actual
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/ose.png" alt="Logo OSE" width="30" height="24" class="d-inline-block align-text-top">
                OSE
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Acerca de</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contacto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary ms-2" href="pages/login.php">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-2" href="pages/register.php">Regístrate</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">¡Bienvenido a OSE!</h1>
                <p class="lead">Plataforma para el mejoramiento educativo. Refuerza tus habilidades y conquista tus metas académicas.</p>
                <a href="pages/login.php" class="btn btn-primary btn-lg me-2">Iniciar Sesión</a>
                <a href="pages/register.php" class="btn btn-outline-primary btn-lg">Regístrate</a>
            </div>
            <div class="col-md-6 text-center">
                <!-- Imagen representativa -->
                <img src="https://st2.depositphotos.com/1763191/9720/v/950/depositphotos_97201940-stock-illustration-students-learning-and-reading.jpg" alt="Estudiantes Aprendiendo" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>

    <footer class="bg-light text-center text-muted py-4 mt-5">
        <div class="container">
            <p>&copy; 2025 OSE. Todos los derechos reservados.</p>
            <p>Facilitando el aprendizaje, potenciando el conocimiento.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>