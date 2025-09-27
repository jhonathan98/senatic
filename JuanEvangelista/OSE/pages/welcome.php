<?php
// pages/welcome.php
// Iniciar la sesión para verificar si el usuario está logueado
session_start();

// Verificar si el usuario NO está logueado
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al login si no está logueado
    header("Location: login.php");
    exit();
}

// Opcional: Obtener el nombre del usuario de la sesión para personalizar el mensaje
$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Ajustar ruta si es necesario -->
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/ose.png" alt="Logo OSE" width="30" height="24" class="d-inline-block align-text-top">
                OSE
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Hola, <?php echo $nombre_usuario; ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Mi Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">Progreso</a>
                    </li>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'docente')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">Panel de Gestión</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger ms-2" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-4">¡Hola, <?php echo $nombre_usuario; ?>!</h2>
                <p class="lead">Estás listo para comenzar a mejorar tu rendimiento académico.</p>
                <p>Selecciona tu grado y materia para empezar a practicar.</p>

                <div class="mt-4">
                    <a href="select_grade.php" class="btn btn-primary btn-lg me-3">Seleccionar Grado y Materia</a>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'docente')): ?>
                        <a href="admin_panel.php" class="btn btn-success btn-lg">
                            <i class="fas fa-cogs me-2"></i>Panel de Gestión
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'docente')): ?>
                <div class="mt-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">
                                        <i class="fas fa-tools me-2"></i>Accesos Rápidos de Gestión
                                    </h6>
                                    <div class="btn-group" role="group">
                                        <a href="create_exam.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Nuevo Examen
                                        </a>
                                        <a href="create_subject.php" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-book me-1"></i>Nueva Materia
                                        </a>
                                        <a href="manage_exams.php" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-list me-1"></i>Ver Exámenes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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