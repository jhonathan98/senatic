<?php
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Procesar logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php?logout=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Autenticación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Tarjeta de Bienvenida -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card welcome-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-wave fa-3x mb-3"></i>
                        <h1 class="card-title">¡Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                        <p class="card-text lead">Has iniciado sesión exitosamente en el sistema</p>
                        <small class="text-light">
                            <i class="fas fa-envelope me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['email']); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Perfil</h5>
                        <p class="card-text">Gestiona tu información personal</p>
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>
                            Editar
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Seguridad</h5>
                        <p class="card-text">Configuración de seguridad</p>
                        <a href="#" class="btn btn-success">
                            <i class="fas fa-cog me-1"></i>
                            Configurar
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Actividad</h5>
                        <p class="card-text">Revisa tu actividad reciente</p>
                        <a href="#" class="btn btn-warning">
                            <i class="fas fa-eye me-1"></i>
                            Ver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la Sesión -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Información de la Sesión
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID de Usuario:</strong> <?php echo $_SESSION['user_id']; ?></p>
                                <p><strong>Nombre de Usuario:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                                <p><strong>Sesión iniciada:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="?logout=1" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4">
        <div class="container text-center">
            <div class="text-white">
                <p>&copy; 2025 Sistema de Autenticación. Desarrollado con PHP, Bootstrap y MySQL.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>