<?php
// pages/exam.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se recibió el ID del examen
if (!isset($_GET['examen_id']) || !is_numeric($_GET['examen_id'])) {
    header("Location: select_grade.php");
    exit();
}

$examenId = intval($_GET['examen_id']);
$usuarioId = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';

try {
    // Obtener información del examen
    $stmt = $pdo->prepare('SELECT e.*, m.nombre_materia, g.nombre_grado 
                FROM examenes e 
                JOIN materias m ON e.materia_id = m.id 
                JOIN grados g ON m.grado_id = g.id 
                WHERE e.id = ?');
    $stmt->execute([$examenId]);
    $examen = $stmt->fetch();

    if (!$examen) {
        header("Location: select_grade.php");
        exit();
    }

    // Obtener preguntas del examen
    $preguntas = getPreguntasByExamen($pdo, $examenId);

    if (empty($preguntas)) {
        $_SESSION['error'] = "Este examen no tiene preguntas disponibles.";
        header("Location: exam_list.php?materia_id=" . $examen['materia_id']);
        exit();
    }

    // Obtener respuestas para cada pregunta
    $preguntasConRespuestas = [];
    foreach ($preguntas as $pregunta) {
        $respuestas = getRespuestasByPregunta($pdo, $pregunta['id']);
        $pregunta['respuestas'] = $respuestas;
        $preguntasConRespuestas[] = $pregunta;
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error al cargar el examen: " . $e->getMessage();
    header("Location: select_grade.php");
    exit();
}


// Procesar envío del examen
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_examen']) && isset($preguntasConRespuestas)) {
    $respuestasUsuario = $_POST['respuestas'] ?? [];
    $puntuacionTotal = 0;
    $totalPreguntas = count($preguntasConRespuestas);
    $respuestasCorrectas = 0;
    
    foreach ($preguntasConRespuestas as $pregunta) {
        $respuestaUsuario = $respuestasUsuario[$pregunta['id']] ?? null;
        
        if ($respuestaUsuario) {
            // Verificar si la respuesta es correcta
            foreach ($pregunta['respuestas'] as $respuesta) {
                if ($respuesta['id'] == $respuestaUsuario && $respuesta['es_correcta']) {
                    $respuestasCorrectas++;
                    break;
                }
            }
        }
    }
    
    // Calcular puntuación (0-100)
    $puntuacionTotal = ($respuestasCorrectas / $totalPreguntas) * 100;
    
    // Guardar resultado
    guardarResultado($usuarioId, $examenId, $puntuacionTotal);
    
    // Redirigir a resultados
    $_SESSION['resultado_examen'] = [
        'puntuacion' => $puntuacionTotal,
        'respuestas_correctas' => $respuestasCorrectas,
        'total_preguntas' => $totalPreguntas,
        'examen_titulo' => $examen['titulo'],
        'materia' => $examen['nombre_materia'],
        'respuestas_usuario' => $respuestasUsuario,
        'preguntas' => $preguntasConRespuestas
    ];
    
    header("Location: results.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($examen) ? htmlspecialchars($examen['titulo']) : 'Examen'; ?> - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exam-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .question-card {
            border-left: 4px solid #007bff;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        .question-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .question-number {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        .answer-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .answer-option:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .answer-option input[type="radio"] {
            margin-right: 0.75rem;
        }
        .answer-option.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .progress-container {
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        .timer {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .submit-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Examen en Curso
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-light">
                    <i class="fas fa-user me-1"></i><?php echo $nombre_usuario; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4 exam-container">
        <?php if (isset($examen) && isset($preguntasConRespuestas)): ?>
        <!-- Header del examen -->
        <div class="exam-header text-center">
            <h2 class="mb-3"><?php echo htmlspecialchars($examen['titulo']); ?></h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="h5">
                        <i class="fas fa-book me-2"></i>
                        <?php echo htmlspecialchars($examen['nombre_materia']); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="h5">
                        <i class="fas fa-question-circle me-2"></i>
                        <?php echo count($preguntasConRespuestas); ?> Preguntas
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="timer" id="timer">
                        <i class="fas fa-clock me-2"></i>
                        <span id="time-display">60:00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-container">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Progreso del examen</small>
                        <small class="text-muted">
                            <span id="answered-count">0</span> de <?php echo count($preguntasConRespuestas); ?> respondidas
                        </small>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: 0%" id="progress-bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario del examen -->
        <form method="POST" id="exam-form">
            <?php foreach ($preguntasConRespuestas as $index => $pregunta): ?>
                <div class="question-card card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="question-number">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="question-text mb-4">
                                    <?php echo htmlspecialchars($pregunta['texto_pregunta']); ?>
                                </h5>
                                
                                <div class="answers-container" data-question="<?php echo $pregunta['id']; ?>">
                                    <?php foreach ($pregunta['respuestas'] as $respuesta): ?>
                                        <label class="answer-option" for="resp_<?php echo $respuesta['id']; ?>">
                                            <input type="radio" 
                                                   name="respuestas[<?php echo $pregunta['id']; ?>]" 
                                                   value="<?php echo $respuesta['id']; ?>"
                                                   id="resp_<?php echo $respuesta['id']; ?>"
                                                   onchange="updateProgress()">
                                            <span class="answer-text">
                                                <?php echo htmlspecialchars($respuesta['texto_respuesta']); ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Sección de envío -->
            <div class="submit-section">
                <h4 class="mb-3">¿Listo para enviar tu examen?</h4>
                <p class="text-muted mb-4">
                    Revisa tus respuestas antes de enviar. Una vez enviado, no podrás modificar tus respuestas.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary btn-lg" onclick="reviewAnswers()">
                        <i class="fas fa-eye me-2"></i>Revisar Respuestas
                    </button>
                    <button type="submit" name="enviar_examen" class="btn btn-success btn-lg" id="submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Examen
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Asegúrate de haber respondido todas las preguntas antes de enviar.
                    </small>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres enviar el examen?</p>
                    <p class="text-muted">Has respondido <span id="modal-answered">0</span> de <?php echo count($preguntasConRespuestas); ?> preguntas.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="submitExam()">Enviar Examen</button>
                </div>
            </div>
        </div>
    </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">
                <h4>Error al cargar el examen</h4>
                <p>No se pudo cargar la información del examen.</p>
                <a href="select_grade.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let timeRemaining = 3600; // 60 minutos en segundos
        let timerInterval;

        // Inicializar timer
        function startTimer() {
            timerInterval = setInterval(() => {
                timeRemaining--;
                updateTimerDisplay();
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    alert('¡Se acabó el tiempo! El examen se enviará automáticamente.');
                    document.getElementById('exam-form').submit();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('time-display').textContent = display;
            
            // Cambiar color cuando quede poco tiempo
            if (timeRemaining <= 300) { // 5 minutos
                document.getElementById('timer').style.color = '#dc3545';
            }
        }

        function updateProgress() {
            const totalQuestions = <?php echo count($preguntasConRespuestas); ?>;
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            const percentage = (answeredQuestions / totalQuestions) * 100;
            
            document.getElementById('answered-count').textContent = answeredQuestions;
            document.getElementById('progress-bar').style.width = percentage + '%';
            
            // Habilitar botón de envío si se han respondido todas las preguntas
            const submitBtn = document.getElementById('submit-btn');
            if (answeredQuestions === totalQuestions) {
                submitBtn.classList.remove('btn-outline-success');
                submitBtn.classList.add('btn-success');
            }
        }

        function reviewAnswers() {
            const unanswered = [];
            const questions = document.querySelectorAll('.question-card');
            
            questions.forEach((question, index) => {
                const radios = question.querySelectorAll('input[type="radio"]');
                const answered = Array.from(radios).some(radio => radio.checked);
                
                if (!answered) {
                    unanswered.push(index + 1);
                    question.style.borderLeftColor = '#dc3545';
                    question.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    question.style.borderLeftColor = '#28a745';
                }
            });
            
            if (unanswered.length > 0) {
                alert(`Preguntas sin responder: ${unanswered.join(', ')}`);
            } else {
                alert('¡Todas las preguntas están respondidas! Puedes enviar el examen.');
            }
        }

        // Manejar envío del formulario
        document.getElementById('submit-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const answeredCount = document.querySelectorAll('input[type="radio"]:checked').length;
            document.getElementById('modal-answered').textContent = answeredCount;
            new bootstrap.Modal(document.getElementById('submitModal')).show();
        });

        function submitExam() {
            console.log('submitExam function called');
            const form = document.getElementById('exam-form');
            
            if (!form) {
                console.error('Form not found!');
                alert('Error: Formulario no encontrado');
                return;
            }
            
            console.log('Form found, submitting...');
            console.log('Form method:', form.method);
            console.log('Form action:', form.action);
            
            // Cerrar el modal antes de enviar
            const modal = bootstrap.Modal.getInstance(document.getElementById('submitModal'));
            if (modal) {
                modal.hide();
            }
            
            // Enviar el formulario
            form.submit();
        }

        // Seleccionar respuestas
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                // Remover selección visual anterior
                const container = e.target.closest('.answers-container');
                container.querySelectorAll('.answer-option').forEach(option => {
                    option.classList.remove('selected');
                });
                
                // Agregar selección visual actual
                e.target.closest('.answer-option').classList.add('selected');
                updateProgress();
            }
        });

        // Prevenir salir accidentalmente
        window.addEventListener('beforeunload', function(e) {
            if (document.querySelectorAll('input[type="radio"]:checked').length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            startTimer();
            updateProgress();
            
            // Animación de entrada para las preguntas
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((question, index) => {
                question.style.opacity = '0';
                question.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    question.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    question.style.opacity = '1';
                    question.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>