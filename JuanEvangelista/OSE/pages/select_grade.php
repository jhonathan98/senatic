<?php
// pages/select_grade.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';

// Obtener grados disponibles
try {
    $grados = getGrados($pdo);
} catch(Exception $e) {
    $grados = [];
    $error = "Error al cargar los grados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Grado - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .grade-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .grade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .grade-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        .progress-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Hola, <?php echo $nombre_usuario; ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="welcome.php"><i class="fas fa-home me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Mi Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php"><i class="fas fa-chart-line me-1"></i>Progreso</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sección de progreso -->
        <div class="progress-section text-center">
            <h2><i class="fas fa-trophy me-2"></i>Tu Progreso</h2>
            <p class="mb-0">Selecciona tu grado para continuar aprendiendo</p>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-school me-2"></i>Selecciona tu Grado</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Elige el grado académico en el que te encuentras para acceder a contenido específico y ejercicios adaptados a tu nivel.</p>
                        
                        <?php if (empty($grados)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay grados disponibles en este momento. Contacta al administrador.
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($grados as $grado): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card grade-card h-100 text-center" 
                                             onclick="selectGrade(<?php echo $grado['id']; ?>)">
                                            <div class="card-body">
                                                <div class="grade-icon">
                                                    <i class="fas fa-book-open"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($grado['nombre_grado']); ?></h5>
                                                <p class="card-text text-muted">
                                                    Accede a materias y ejercicios de <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                                </p>
                                                <div class="mt-3">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-play me-1"></i>Comenzar
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de consejos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Consejos para el Éxito</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-clock text-info" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Practica Regularmente</h6>
                                <p class="small text-muted">Dedica al menos 15 minutos diarios para obtener mejores resultados.</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-target text-success" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Establece Metas</h6>
                                <p class="small text-muted">Define objetivos claros para cada sesión de estudio.</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-medal text-warning" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Celebra tus Logros</h6>
                                <p class="small text-muted">Reconoce tu progreso y mantén la motivación alta.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2025 OSE. Todos los derechos reservados.</p>
            <p><small>Facilitando el aprendizaje, potenciando el conocimiento.</small></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectGrade(gradeId) {
            // Agregar efecto visual
            event.currentTarget.style.transform = 'scale(0.95)';
            setTimeout(() => {
                window.location.href = `select_subject.php?grado_id=${gradeId}`;
            }, 150);
        }

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.grade-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
