<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es admin o docente
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'docente')) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol_usuario = $_SESSION['rol'];

// Obtener ID del examen
$exam_id = $_GET['id'] ?? '';
if (!$exam_id) {
    header("Location: manage_exams.php");
    exit();
}

// Obtener información del examen
try {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nombre_materia, g.nombre_grado 
        FROM examenes e 
        JOIN materias m ON e.materia_id = m.id 
        JOIN grados g ON m.grado_id = g.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $examen = $stmt->fetch();
    
    if (!$examen) {
        header("Location: manage_exams.php");
        exit();
    }
    
    // Obtener preguntas del examen
    $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id");
    $stmt->execute([$exam_id]);
    $preguntas = $stmt->fetchAll();
    
    // Obtener respuestas para cada pregunta
    $preguntas_con_respuestas = [];
    foreach ($preguntas as $pregunta) {
        $stmt = $pdo->prepare("SELECT * FROM respuestas WHERE pregunta_id = ? ORDER BY id");
        $stmt->execute([$pregunta['id']]);
        $respuestas = $stmt->fetchAll();
        
        $pregunta['respuestas'] = $respuestas;
        $preguntas_con_respuestas[] = $pregunta;
    }
    
} catch(PDOException $e) {
    $error = "Error al obtener el examen: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - <?php echo htmlspecialchars($examen['titulo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Vista Previa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="manage_exams.php">
                            <i class="fas fa-arrow-left me-1"></i>Volver a Exámenes
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($nombre_usuario); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header del examen -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Vista Previa del Examen
                        </h3>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-dark">MODO VISTA PREVIA</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4><?php echo htmlspecialchars($examen['titulo']); ?></h4>
                        <?php if ($examen['descripcion']): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($examen['descripcion']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="text-md-end">
                            <p class="mb-1">
                                <strong>Grado:</strong> 
                                <span class="badge bg-info"><?php echo htmlspecialchars($examen['nombre_grado']); ?></span>
                            </p>
                            <p class="mb-1">
                                <strong>Materia:</strong> 
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($examen['nombre_materia']); ?></span>
                            </p>
                            <p class="mb-0">
                                <strong>Total de preguntas:</strong> 
                                <span class="badge bg-warning text-dark"><?php echo count($preguntas_con_respuestas); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="row mb-4">
            <div class="col">
                <div class="btn-group" role="group">
                    <a href="edit_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar Examen
                    </a>
                    <a href="add_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-success">
                        <i class="fas fa-question-circle me-2"></i>Gestionar Preguntas
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>

        <!-- Preguntas del examen -->
        <?php if (empty($preguntas_con_respuestas)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Este examen no tiene preguntas</h5>
                    <p class="text-muted">Agrega preguntas para que los estudiantes puedan tomarlo</p>
                    <a href="add_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Agregar Preguntas
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Preguntas del Examen
                        <span class="badge bg-primary ms-2"><?php echo count($preguntas_con_respuestas); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($preguntas_con_respuestas as $index => $pregunta): ?>
                        <div class="question-card <?php echo $index < count($preguntas_con_respuestas) - 1 ? 'border-bottom pb-4 mb-4' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="mb-0">
                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                    Pregunta <?php echo $index + 1; ?>
                                    <span class="badge bg-secondary ms-2">
                                        <?php echo $pregunta['tipo_pregunta'] === 'multiple_choice' ? 'Opción Múltiple' : 'Verdadero/Falso'; ?>
                                    </span>
                                </h6>
                            </div>
                            
                            <div class="question-text mb-3">
                                <p class="h6"><?php echo htmlspecialchars($pregunta['texto_pregunta']); ?></p>
                            </div>
                            
                            <div class="answers">
                                <?php if ($pregunta['tipo_pregunta'] === 'multiple_choice'): ?>
                                    <div class="row">
                                        <?php foreach ($pregunta['respuestas'] as $resp_index => $respuesta): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" disabled>
                                                    <label class="form-check-label <?php echo $respuesta['es_correcta'] ? 'text-success fw-bold' : ''; ?>">
                                                        <?php if ($respuesta['es_correcta']): ?>
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($respuesta['texto_respuesta']); ?>
                                                        <?php if ($respuesta['es_correcta']): ?>
                                                            <small class="text-success">(Correcta)</small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: // true_false ?>
                                    <div class="row">
                                        <?php foreach ($pregunta['respuestas'] as $respuesta): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" disabled>
                                                    <label class="form-check-label <?php echo $respuesta['es_correcta'] ? 'text-success fw-bold' : ''; ?>">
                                                        <?php if ($respuesta['es_correcta']): ?>
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($respuesta['texto_respuesta']); ?>
                                                        <?php if ($respuesta['es_correcta']): ?>
                                                            <small class="text-success">(Correcta)</small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas del examen -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas del Examen</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-question-circle fa-2x text-primary"></i>
                        </div>
                        <h5><?php echo count($preguntas_con_respuestas); ?></h5>
                        <p class="text-muted mb-0">Preguntas</p>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-list fa-2x text-success"></i>
                        </div>
                        <h5>
                            <?php 
                            $multiple_choice = array_filter($preguntas_con_respuestas, function($p) { 
                                return $p['tipo_pregunta'] === 'multiple_choice'; 
                            });
                            echo count($multiple_choice);
                            ?>
                        </h5>
                        <p class="text-muted mb-0">Opción Múltiple</p>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-toggle-on fa-2x text-info"></i>
                        </div>
                        <h5>
                            <?php 
                            $true_false = array_filter($preguntas_con_respuestas, function($p) { 
                                return $p['tipo_pregunta'] === 'true_false'; 
                            });
                            echo count($true_false);
                            ?>
                        </h5>
                        <p class="text-muted mb-0">Verdadero/Falso</p>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <h5><?php echo count($preguntas_con_respuestas) * 2; ?> min</h5>
                        <p class="text-muted mb-0">Tiempo estimado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        @media print {
            .navbar, .btn-group, .card-header .badge {
                display: none !important;
            }
            
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            
            .question-card {
                page-break-inside: avoid;
            }
        }
    </style>
</body>
</html>
