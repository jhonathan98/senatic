<?php
// pages/results.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si hay resultados del examen
if (!isset($_SESSION['resultado_examen'])) {
    header("Location: select_grade.php");
    exit();
}

$resultado = $_SESSION['resultado_examen'];
$usuarioId = $_SESSION['usuario_id'];
$nombre_usuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';

// Calcular estadísticas
$puntuacion = $resultado['puntuacion'];
$respuestasCorrectas = $resultado['respuestas_correctas'];
$totalPreguntas = $resultado['total_preguntas'];
$respuestasIncorrectas = $totalPreguntas - $respuestasCorrectas;
$porcentajeAcierto = ($respuestasCorrectas / $totalPreguntas) * 100;

// Obtener mensaje motivacional
$mensajeMotivacional = getMensajeMotivacional($puntuacion);

// Calcular logros
$logros = calcularLogros($pdo, $usuarioId);

// Limpiar resultado de la sesión
unset($_SESSION['resultado_examen']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados del Examen - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .results-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            position: relative;
        }
        
        .score-excellent { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .score-good { 
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .score-average { 
            background: linear-gradient(135deg, #fd7e14, #dc3545);
            color: white;
        }
        
        .score-poor { 
            background: linear-gradient(135deg, #dc3545, #6f42c1);
            color: white;
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .achievement-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            display: inline-block;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .question-review {
            border-left: 4px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .question-correct {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        
        .question-incorrect {
            border-left-color: #dc3545;
            background-color: #fff8f8;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #ffd700;
            animation: confetti 3s ease-out forwards;
            z-index: 9999;
        }
        
        @keyframes confetti {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        .motivational-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-light">
                    <i class="fas fa-user me-1"></i><?php echo $nombre_usuario; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header de resultados -->
        <div class="results-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <?php
                    $scoreClass = 'score-poor';
                    $icon = 'fas fa-frown';
                    
                    if ($puntuacion >= 90) {
                        $scoreClass = 'score-excellent';
                        $icon = 'fas fa-star';
                    } elseif ($puntuacion >= 80) {
                        $scoreClass = 'score-good';
                        $icon = 'fas fa-smile';
                    } elseif ($puntuacion >= 70) {
                        $scoreClass = 'score-average';
                        $icon = 'fas fa-meh';
                    }
                    ?>
                    <div class="score-circle <?php echo $scoreClass; ?>">
                        <?php echo round($puntuacion); ?>%
                    </div>
                </div>
                <div class="col-md-8 text-md-start text-center">
                    <h2 class="mb-3">
                        <i class="<?php echo $icon; ?> me-2"></i>
                        ¡Examen Completado!
                    </h2>
                    <h4 class="mb-2"><?php echo htmlspecialchars($resultado['examen_titulo']); ?></h4>
                    <p class="mb-0 h5"><?php echo htmlspecialchars($resultado['materia']); ?></p>
                </div>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center">
                    <div class="card-body">
                        <div class="h2 text-success mb-2">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="h4 text-success"><?php echo $respuestasCorrectas; ?></div>
                        <div class="text-muted">Correctas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center">
                    <div class="card-body">
                        <div class="h2 text-danger mb-2">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="h4 text-danger"><?php echo $respuestasIncorrectas; ?></div>
                        <div class="text-muted">Incorrectas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center">
                    <div class="card-body">
                        <div class="h2 text-primary mb-2">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="h4 text-primary"><?php echo $totalPreguntas; ?></div>
                        <div class="text-muted">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center">
                    <div class="card-body">
                        <div class="h2 text-info mb-2">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="h4 text-info"><?php echo round($porcentajeAcierto); ?>%</div>
                        <div class="text-muted">Acierto</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje motivacional -->
        <div class="motivational-message">
            <h4><i class="fas fa-heart me-2"></i><?php echo $mensajeMotivacional; ?></h4>
            <p class="mb-0">Sigue practicando para mejorar tus habilidades y alcanzar tus metas académicas.</p>
        </div>

        <!-- Logros desbloqueados -->
        <?php if (!empty($logros)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2"></i>¡Logros Desbloqueados!
                </h5>
            </div>
            <div class="card-body text-center">
                <?php foreach ($logros as $logro): ?>
                    <div class="achievement-badge">
                        <i class="fas fa-medal me-1"></i>
                        <?php echo htmlspecialchars($logro['nombre']); ?>
                        <small>(+<?php echo $logro['puntos']; ?> pts)</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Revisión de respuestas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-eye me-2"></i>Revisión de Respuestas
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($resultado['preguntas'] as $index => $pregunta): ?>
                    <?php
                    $respuestaUsuario = $resultado['respuestas_usuario'][$pregunta['id']] ?? null;
                    $esCorrecta = false;
                    $respuestaCorrectaTexto = '';
                    $respuestaUsuarioTexto = '';
                    
                    foreach ($pregunta['respuestas'] as $respuesta) {
                        if ($respuesta['es_correcta']) {
                            $respuestaCorrectaTexto = $respuesta['texto_respuesta'];
                        }
                        if ($respuesta['id'] == $respuestaUsuario) {
                            $respuestaUsuarioTexto = $respuesta['texto_respuesta'];
                            $esCorrecta = $respuesta['es_correcta'];
                        }
                    }
                    
                    $cardClass = $esCorrecta ? 'question-correct' : 'question-incorrect';
                    $iconClass = $esCorrecta ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
                    ?>
                    
                    <div class="question-review card <?php echo $cardClass; ?> mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="<?php echo $iconClass; ?>" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        Pregunta <?php echo $index + 1; ?>: <?php echo htmlspecialchars($pregunta['texto_pregunta']); ?>
                                    </h6>
                                    
                                    <?php if ($respuestaUsuario): ?>
                                        <p class="mb-1">
                                            <strong>Tu respuesta:</strong> 
                                            <span class="<?php echo $esCorrecta ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo htmlspecialchars($respuestaUsuarioTexto); ?>
                                            </span>
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-1">
                                            <strong>Tu respuesta:</strong> 
                                            <span class="text-muted">No respondida</span>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!$esCorrecta): ?>
                                        <p class="mb-0">
                                            <strong>Respuesta correcta:</strong> 
                                            <span class="text-success"><?php echo htmlspecialchars($respuestaCorrectaTexto); ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a href="select_grade.php" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-redo me-2"></i>Hacer Otro Examen
                </a>
            </div>
            <div class="col-md-4">
                <a href="progress.php" class="btn btn-info btn-lg w-100">
                    <i class="fas fa-chart-line me-2"></i>Ver Mi Progreso
                </a>
            </div>
            <div class="col-md-4">
                <button onclick="shareResults()" class="btn btn-success btn-lg w-100">
                    <i class="fas fa-share me-2"></i>Compartir Resultado
                </a>
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
        // Crear confetti si la puntuación es excelente
        <?php if ($puntuacion >= 90): ?>
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = ['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'][Math.floor(Math.random() * 6)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }, i * 50);
            }
        }
        
        // Activar confetti después de un pequeño delay
        setTimeout(createConfetti, 500);
        <?php endif; ?>

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animar cartas de estadísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animar revisión de preguntas
            const questionCards = document.querySelectorAll('.question-review');
            questionCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, 1000 + (index * 50));
            });
        });

        function shareResults() {
            const text = `¡Acabo de completar un examen en OSE y obtuve ${<?php echo round($puntuacion); ?>}%! 🎉`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Mi resultado en OSE',
                    text: text,
                    url: window.location.origin
                });
            } else {
                // Fallback para navegadores que no soportan Web Share API
                navigator.clipboard.writeText(text + ' ' + window.location.origin).then(() => {
                    alert('¡Resultado copiado al portapapeles!');
                });
            }
        }

        // Reproducir sonido según el resultado
        <?php if ($puntuacion >= 90): ?>
            // Sonido de éxito (simulado con beep)
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const successSound = () => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            };
            setTimeout(successSound, 1000);
        <?php endif; ?>
    </script>
</body>
</html>