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
$exam_id = $_GET['exam_id'] ?? '';
$examen = null;

if ($exam_id) {
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
        
        // Obtener preguntas existentes
        $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id");
        $stmt->execute([$exam_id]);
        $preguntas_existentes = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = "Error al obtener el examen: " . $e->getMessage();
    }
} else {
    header("Location: manage_exams.php");
    exit();
}

// Procesar el formulario de agregar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $texto_pregunta = trim($_POST['texto_pregunta'] ?? '');
    $tipo_pregunta = $_POST['tipo_pregunta'] ?? 'multiple_choice';
    $respuestas = $_POST['respuestas'] ?? [];
    $respuesta_correcta = $_POST['respuesta_correcta'] ?? '';
    
    $errors = [];
    
    if (empty($texto_pregunta)) {
        $errors[] = "El texto de la pregunta es obligatorio";
    }
    
    if (!in_array($tipo_pregunta, ['multiple_choice', 'true_false'])) {
        $errors[] = "Tipo de pregunta no válido";
    }
    
    if ($tipo_pregunta === 'multiple_choice') {
        // Filtrar respuestas vacías y validar
        $respuestas_validas = [];
        foreach ($respuestas as $index => $respuesta) {
            if (!empty(trim($respuesta))) {
                $respuestas_validas[$index] = trim($respuesta);
            }
        }
        
        if (count($respuestas_validas) < 2) {
            $errors[] = "Debe proporcionar al menos 2 opciones de respuesta";
        }
        
        // Validar que la respuesta correcta sea válida
        if ($respuesta_correcta === '' || !isset($respuestas_validas[$respuesta_correcta])) {
            $errors[] = "Debe seleccionar una respuesta correcta válida";
        }
    } elseif ($tipo_pregunta === 'true_false') {
        if ($respuesta_correcta === '' || !in_array($respuesta_correcta, ['true', 'false'])) {
            $errors[] = "Debe seleccionar si la respuesta es verdadera o falsa";
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insertar pregunta
            $stmt = $pdo->prepare("INSERT INTO preguntas (examen_id, texto_pregunta, tipo_pregunta) VALUES (?, ?, ?)");
            $stmt->execute([$exam_id, $texto_pregunta, $tipo_pregunta]);
            $pregunta_id = $pdo->lastInsertId();
            
            // Insertar respuestas
            if ($tipo_pregunta === 'multiple_choice') {
                foreach ($respuestas as $index => $respuesta) {
                    if (!empty(trim($respuesta))) {
                        $es_correcta = ($respuesta_correcta == $index) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES (?, ?, ?)");
                        $stmt->execute([$pregunta_id, trim($respuesta), $es_correcta]);
                    }
                }
            } elseif ($tipo_pregunta === 'true_false') {
                $stmt = $pdo->prepare("INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES (?, ?, ?)");
                $stmt->execute([$pregunta_id, 'Verdadero', ($respuesta_correcta === 'true') ? 1 : 0]);
                $stmt->execute([$pregunta_id, 'Falso', ($respuesta_correcta === 'false') ? 1 : 0]);
            }
            
            $pdo->commit();
            $success = "Pregunta agregada correctamente";
            
            // Recargar preguntas existentes
            $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id");
            $stmt->execute([$exam_id]);
            $preguntas_existentes = $stmt->fetchAll();
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error al agregar la pregunta: " . $e->getMessage();
        }
    }
}

// Eliminar pregunta
if (isset($_POST['delete_question']) && isset($_POST['question_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id = ? AND examen_id = ?");
        $stmt->execute([$_POST['question_id'], $exam_id]);
        $success = "Pregunta eliminada correctamente";
        
        // Recargar preguntas existentes
        $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id");
        $stmt->execute([$exam_id]);
        $preguntas_existentes = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error = "Error al eliminar la pregunta: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Preguntas - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Gestión de Preguntas
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
        <!-- Información del examen -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($examen['titulo']); ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Grado:</strong> <?php echo htmlspecialchars($examen['nombre_grado']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Materia:</strong> <?php echo htmlspecialchars($examen['nombre_materia']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Preguntas:</strong> <?php echo count($preguntas_existentes); ?>
                    </div>
                </div>
                <?php if ($examen['descripcion']): ?>
                    <div class="mt-2">
                        <strong>Descripción:</strong> <?php echo htmlspecialchars($examen['descripcion']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Formulario para agregar preguntas -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nueva Pregunta</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="addQuestionForm">
                            <div class="mb-3">
                                <label for="texto_pregunta" class="form-label">
                                    <i class="fas fa-question me-2"></i>Texto de la Pregunta *
                                </label>
                                <textarea class="form-control" id="texto_pregunta" name="texto_pregunta" rows="3" 
                                          placeholder="Escribe aquí la pregunta..." required><?php echo htmlspecialchars($_POST['texto_pregunta'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_pregunta" class="form-label">
                                    <i class="fas fa-list me-2"></i>Tipo de Pregunta *
                                </label>
                                <select class="form-select" id="tipo_pregunta" name="tipo_pregunta" required>
                                    <option value="multiple_choice" <?php echo (($_POST['tipo_pregunta'] ?? 'multiple_choice') === 'multiple_choice') ? 'selected' : ''; ?>>
                                        Selección Múltiple
                                    </option>
                                    <option value="true_false" <?php echo (($_POST['tipo_pregunta'] ?? '') === 'true_false') ? 'selected' : ''; ?>>
                                        Verdadero/Falso
                                    </option>
                                </select>
                            </div>

                            <!-- Opciones para selección múltiple -->
                            <div id="multiple_choice_options" class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-check-circle me-2"></i>Opciones de Respuesta *
                                </label>
                                
                                <div id="respuestas_container">
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="radio" name="respuesta_correcta" 
                                                   value="<?php echo $i; ?>" <?php echo (($_POST['respuesta_correcta'] ?? '') == $i) ? 'checked' : ''; ?>>
                                        </div>
                                        <input type="text" class="form-control" name="respuestas[<?php echo $i; ?>]" 
                                               placeholder="Opción <?php echo $i + 1; ?>" 
                                               value="<?php echo htmlspecialchars($_POST['respuestas'][$i] ?? ''); ?>">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">Selecciona el círculo junto a la respuesta correcta</small>
                            </div>

                            <!-- Opciones para verdadero/falso -->
                            <div id="true_false_options" class="mb-3" style="display: none;">
                                <label class="form-label">
                                    <i class="fas fa-check-circle me-2"></i>Respuesta Correcta *
                                </label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="respuesta_correcta" 
                                           id="true_option" value="true" <?php echo (($_POST['respuesta_correcta'] ?? '') === 'true') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="true_option">
                                        Verdadero
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="respuesta_correcta" 
                                           id="false_option" value="false" <?php echo (($_POST['respuesta_correcta'] ?? '') === 'false') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="false_option">
                                        Falso
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="add_question" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Agregar Pregunta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de preguntas existentes -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Preguntas del Examen 
                            <span class="badge bg-primary"><?php echo count($preguntas_existentes); ?></span>
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($preguntas_existentes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No hay preguntas agregadas</h6>
                                <p class="text-muted">Agrega la primera pregunta usando el formulario</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($preguntas_existentes as $index => $pregunta): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title">
                                                    Pregunta <?php echo $index + 1; ?>
                                                    <span class="badge bg-secondary ms-2">
                                                        <?php echo $pregunta['tipo_pregunta'] === 'multiple_choice' ? 'Múltiple' : 'V/F'; ?>
                                                    </span>
                                                </h6>
                                                <p class="card-text"><?php echo htmlspecialchars($pregunta['texto_pregunta']); ?></p>
                                                
                                                <?php
                                                // Obtener respuestas de esta pregunta
                                                $stmt = $pdo->prepare("SELECT * FROM respuestas WHERE pregunta_id = ? ORDER BY id");
                                                $stmt->execute([$pregunta['id']]);
                                                $respuestas_pregunta = $stmt->fetchAll();
                                                ?>
                                                
                                                <small class="text-muted">
                                                    <strong>Opciones:</strong><br>
                                                    <?php foreach ($respuestas_pregunta as $resp): ?>
                                                        <span class="<?php echo $resp['es_correcta'] ? 'text-success fw-bold' : ''; ?>">
                                                            <?php echo $resp['es_correcta'] ? '✓' : '○'; ?> 
                                                            <?php echo htmlspecialchars($resp['texto_respuesta']); ?>
                                                        </span><br>
                                                    <?php endforeach; ?>
                                                </small>
                                            </div>
                                            <div class="ms-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteQuestionModal<?php echo $pregunta['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de confirmación para eliminar pregunta -->
                                <div class="modal fade" id="deleteQuestionModal<?php echo $pregunta['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>¿Estás seguro de que deseas eliminar esta pregunta?</p>
                                                <p class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Esta acción no se puede deshacer.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="question_id" value="<?php echo $pregunta['id']; ?>">
                                                    <button type="submit" name="delete_question" class="btn btn-danger">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones finales -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <h6>¿Terminaste de agregar preguntas?</h6>
                <a href="manage_exams.php" class="btn btn-success me-2">
                    <i class="fas fa-check me-2"></i>Finalizar y Volver a Exámenes
                </a>
                <a href="view_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>Vista Previa del Examen
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoPreguntaSelect = document.getElementById('tipo_pregunta');
            const multipleChoiceOptions = document.getElementById('multiple_choice_options');
            const trueFalseOptions = document.getElementById('true_false_options');
            
            function toggleQuestionType() {
                if (tipoPreguntaSelect.value === 'multiple_choice') {
                    multipleChoiceOptions.style.display = 'block';
                    trueFalseOptions.style.display = 'none';
                } else {
                    multipleChoiceOptions.style.display = 'none';
                    trueFalseOptions.style.display = 'block';
                }
            }
            
            tipoPreguntaSelect.addEventListener('change', toggleQuestionType);
            toggleQuestionType(); // Ejecutar al cargar la página
        });
    </script>
</body>
</html>
