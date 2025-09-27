<?php
// pages/exam_list.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se recibió el ID de la materia
if (!isset($_GET['materia_id']) || !is_numeric($_GET['materia_id'])) {
    header("Location: select_grade.php");
    exit();
}

$materiaId = intval($_GET['materia_id']);
$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';

// Obtener información de la materia
try {
    $stmt = $pdo->prepare('SELECT m.*, g.nombre_grado FROM materias m JOIN grados g ON m.grado_id = g.id WHERE m.id = ?');
    $stmt->execute([$materiaId]);
    $materia = $stmt->fetch();

    if (!$materia) {
        header("Location: select_grade.php");
        exit();
    }

    // Obtener exámenes de la materia
    $examenes = getExamenesByMateria($pdo, $materiaId);

    // Guardar materia en sesión
    $_SESSION['materia_id'] = $materiaId;
    $_SESSION['materia_nombre'] = $materia['nombre_materia'];
    
} catch(Exception $e) {
    $error = "Error al cargar la información: " . $e->getMessage();
    $examenes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($materia) ? htmlspecialchars($materia['nombre_materia']) : 'Materia'; ?> - Exámenes - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exam-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #007bff;
        }
        .exam-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-left-color: #28a745;
        }
        .difficulty-badge {
            font-size: 0.8rem;
        }
        .breadcrumb-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .exam-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
        }
        .no-exams {
            text-align: center;
            padding: 3rem 1rem;
        }
        .no-exams i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
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
                        <a class="nav-link" href="select_grade.php"><i class="fas fa-school me-1"></i>Grados</a>
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

        <?php if (isset($materia)): ?>
        <!-- Breadcrumb -->
        <div class="breadcrumb-custom mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="select_grade.php"><i class="fas fa-school me-1"></i>Grados</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="select_subject.php?grado_id=<?php echo $materia['grado_id']; ?>">
                            <?php echo htmlspecialchars($materia['nombre_grado']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($materia['nombre_materia']); ?>
                    </li>
                </ol>
            </nav>
        </div>
        <?php endif; ?>

        <?php if (!isset($materia)): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h4>Materia no encontrada</h4>
                <p>La materia seleccionada no existe o no está disponible.</p>
                <a href="select_grade.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Seleccionar Grado
                </a>
            </div>
        <?php else: ?>
        <!-- Estadísticas rápidas -->
        <div class="exam-stats mb-4">
            <div class="row text-center">
                <div class="col-md-3 col-6">
                    <div class="h3"><?php echo count($examenes); ?></div>
                    <div class="small">Exámenes Disponibles</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="h3" id="completed-exams">0</div>
                    <div class="small">Completados</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="h3" id="avg-score">-</div>
                    <div class="small">Promedio</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="h3"><i class="fas fa-star"></i></div>
                    <div class="small">¡A por todas!</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Exámenes de <?php echo htmlspecialchars($materia['nombre_materia']); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($examenes)): ?>
                            <div class="no-exams">
                                <i class="fas fa-clipboard-question"></i>
                                <h5>No hay exámenes disponibles</h5>
                                <p class="text-muted">
                                    Actualmente no hay exámenes disponibles para esta materia. 
                                    ¡Vuelve pronto para encontrar nuevo contenido!
                                </p>
                                <a href="select_subject.php?grado_id=<?php echo $materia['grado_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-1"></i>Explorar otras materias
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">
                                Selecciona un examen para comenzar. Cada examen está diseñado para evaluar tus conocimientos 
                                y ayudarte a mejorar en <?php echo htmlspecialchars($materia['nombre_materia']); ?>.
                            </p>
                            
                            <div class="row g-3">
                                <?php foreach ($examenes as $index => $examen): ?>
                                    <?php
                                    // Simular dificultad basada en el índice
                                    $dificultades = ['Básico', 'Intermedio', 'Avanzado'];
                                    $colores = ['success', 'warning', 'danger'];
                                    $dificultadIndex = $index % 3;
                                    $dificultad = $dificultades[$dificultadIndex];
                                    $colorDificultad = $colores[$dificultadIndex];
                                    
                                    // Simular número de preguntas (entre 10 y 25)
                                    $numPreguntas = rand(10, 25);
                                    $tiempoEstimado = $numPreguntas * 2; // 2 minutos por pregunta
                                    ?>
                                    <div class="col-12">
                                        <div class="card exam-card" onclick="startExam(<?php echo $examen['id']; ?>)">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h5 class="card-title mb-2">
                                                            <i class="fas fa-file-alt me-2 text-primary"></i>
                                                            <?php echo htmlspecialchars($examen['titulo']); ?>
                                                        </h5>
                                                        <p class="card-text text-muted mb-2">
                                                            <?php echo htmlspecialchars($examen['descripcion'] ?: 'Examen de práctica para evaluar tus conocimientos.'); ?>
                                                        </p>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <span class="badge bg-<?php echo $colorDificultad; ?> difficulty-badge">
                                                                <i class="fas fa-signal me-1"></i><?php echo $dificultad; ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-question-circle me-1"></i><?php echo $numPreguntas; ?> preguntas
                                                            </small>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>~<?php echo $tiempoEstimado; ?> min
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <div class="mb-2">
                                                            <small class="text-muted">Creado: <?php echo date('d/m/Y', strtotime($examen['fecha_creacion'])); ?></small>
                                                        </div>
                                                        <button class="btn btn-primary btn-lg">
                                                            <i class="fas fa-play me-2"></i>Comenzar Examen
                                                        </button>
                                                    </div>
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

        <!-- Consejos para el examen -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Consejos para el Examen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lee cada pregunta cuidadosamente</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No te apresures, tienes tiempo suficiente</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Si no sabes una respuesta, pasa a la siguiente</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Revisa tus respuestas antes de enviar</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mantén la calma y confía en ti mismo</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Aprende de cada resultado</li>
                                </ul>
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
        function startExam(examId) {
            // Confirmación antes de comenzar
            if (confirm('¿Estás listo para comenzar el examen? Una vez iniciado, se registrará tu intento.')) {
                window.location.href = `exam.php?examen_id=${examId}`;
            }
        }

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.exam-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, index * 100);
            });

            // Simular estadísticas del usuario
            setTimeout(() => {
                document.getElementById('completed-exams').textContent = Math.floor(Math.random() * <?php echo count($examenes); ?>);
                document.getElementById('avg-score').textContent = Math.floor(Math.random() * 40 + 60) + '%';
            }, 500);
        });
    </script>
</body>
</html>
