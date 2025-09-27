<?php
// pages/select_subject.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se recibió el ID del grado
if (!isset($_GET['grado_id']) || !is_numeric($_GET['grado_id'])) {
    header("Location: select_grade.php");
    exit();
}

$gradoId = intval($_GET['grado_id']);
$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';

// Obtener información del grado
try {
    $stmt = $pdo->prepare('SELECT * FROM grados WHERE id = ?');
    $stmt->execute([$gradoId]);
    $grado = $stmt->fetch();

    if (!$grado) {
        header("Location: select_grade.php");
        exit();
    }

    // Obtener materias del grado
    $materias = getMateriasByGrado($pdo, $gradoId);

    // Guardar grado en sesión
    $_SESSION['grado_id'] = $gradoId;
    $_SESSION['grado_nombre'] = $grado['nombre_grado'];
    
} catch(Exception $e) {
    $error = "Error al cargar la información: " . $e->getMessage();
    $materias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Materia - <?php echo isset($grado) ? htmlspecialchars($grado['nombre_grado']) : 'Grado'; ?> - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .subject-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            height: 100%;
        }
        .subject-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #28a745;
        }
        .subject-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        .breadcrumb-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1rem;
        }
        .breadcrumb-custom a {
            color: #ffffff;
            text-decoration: none;
        }
        .breadcrumb-custom a:hover {
            color: #f8f9fa;
        }
        .math { color: #e74c3c; }
        .science { color: #3498db; }
        .language { color: #f39c12; }
        .social { color: #9b59b6; }
        .arts { color: #1abc9c; }
        .tech { color: #34495e; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
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
                        <a class="nav-link" href="select_grade.php"><i class="fas fa-arrow-left me-1"></i>Cambiar Grado</a>
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

        <?php if (isset($grado)): ?>
        <!-- Breadcrumb -->
        <div class="breadcrumb-custom mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="select_grade.php"><i class="fas fa-school me-1"></i>Grados</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                    </li>
                </ol>
            </nav>
        </div>
        <?php endif; ?>

        <?php if (!isset($grado)): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h4>Grado no encontrado</h4>
                <p>El grado seleccionado no existe o no está disponible.</p>
                <a href="select_grade.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Seleccionar Grado
                </a>
            </div>
        <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-book me-2"></i>
                            Materias de <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Selecciona la materia que deseas estudiar. Cada materia contiene ejercicios y exámenes 
                            adaptados al currículo nacional de <?php echo htmlspecialchars($grado['nombre_grado']); ?>.
                        </p>
                        
                        <?php if (empty($materias)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay materias disponibles para este grado en este momento.
                                <br>
                                <a href="select_grade.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-arrow-left me-1"></i>Seleccionar otro grado
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($materias as $materia): ?>
                                    <?php
                                    // Asignar clase de color según el nombre de la materia
                                    $iconClass = 'math';
                                    $icon = 'fas fa-calculator';
                                    
                                    $nombreLower = strtolower($materia['nombre_materia']);
                                    if (strpos($nombreLower, 'matemática') !== false || strpos($nombreLower, 'matemática') !== false) {
                                        $iconClass = 'math';
                                        $icon = 'fas fa-calculator';
                                    } elseif (strpos($nombreLower, 'ciencia') !== false || strpos($nombreLower, 'física') !== false || strpos($nombreLower, 'química') !== false || strpos($nombreLower, 'biología') !== false) {
                                        $iconClass = 'science';
                                        $icon = 'fas fa-flask';
                                    } elseif (strpos($nombreLower, 'lengua') !== false || strpos($nombreLower, 'español') !== false || strpos($nombreLower, 'literatura') !== false) {
                                        $iconClass = 'language';
                                        $icon = 'fas fa-book-open';
                                    } elseif (strpos($nombreLower, 'social') !== false || strpos($nombreLower, 'historia') !== false || strpos($nombreLower, 'geografía') !== false) {
                                        $iconClass = 'social';
                                        $icon = 'fas fa-globe';
                                    } elseif (strpos($nombreLower, 'arte') !== false || strpos($nombreLower, 'música') !== false || strpos($nombreLower, 'educación física') !== false) {
                                        $iconClass = 'arts';
                                        $icon = 'fas fa-palette';
                                    } elseif (strpos($nombreLower, 'tecnología') !== false || strpos($nombreLower, 'informática') !== false || strpos($nombreLower, 'computación') !== false) {
                                        $iconClass = 'tech';
                                        $icon = 'fas fa-laptop-code';
                                    }
                                    ?>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card subject-card" onclick="selectSubject(<?php echo $materia['id']; ?>)">
                                            <div class="card-body text-center">
                                                <div class="subject-icon <?php echo $iconClass; ?>">
                                                    <i class="<?php echo $icon; ?>"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($materia['nombre_materia']); ?></h5>
                                                <p class="card-text text-muted">
                                                    Ejercicios y exámenes de <?php echo htmlspecialchars($materia['nombre_materia']); ?> 
                                                    para <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                                </p>
                                                <div class="mt-3">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-play me-1"></i>Estudiar Ahora
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

        <!-- Sección de estadísticas rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Tu Progreso en <?php echo htmlspecialchars($grado['nombre_grado']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6">
                                <div class="h3 text-primary" id="total-exams">-</div>
                                <div class="small text-muted">Exámenes Realizados</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="h3 text-success" id="avg-score">-</div>
                                <div class="small text-muted">Promedio</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="h3 text-warning" id="best-score">-</div>
                                <div class="small text-muted">Mejor Puntuación</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="h3 text-info" id="subjects-studied">-</div>
                                <div class="small text-muted">Materias Estudiadas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-light text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2025 OSE. Todos los derechos reservados.</p>
            <p><small>Facilitando el aprendizaje, potenciando el conocimiento.</small></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectSubject(subjectId) {
            // Efecto visual
            event.currentTarget.style.transform = 'scale(0.95)';
            setTimeout(() => {
                window.location.href = `exam_list.php?materia_id=${subjectId}`;
            }, 150);
        }

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.subject-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Cargar estadísticas (simulado)
            setTimeout(() => {
                document.getElementById('total-exams').textContent = '<?php echo rand(0, 15); ?>';
                document.getElementById('avg-score').textContent = '<?php echo rand(60, 95); ?>%';
                document.getElementById('best-score').textContent = '<?php echo rand(80, 100); ?>%';
                document.getElementById('subjects-studied').textContent = '<?php echo count($materias); ?>';
            }, 1000);
        });
    </script>
</body>
</html>
