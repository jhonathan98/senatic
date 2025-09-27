<?php
// pages/progress.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuarioId = $_SESSION['usuario_id'];

try {
    $usuario = getUserById($pdo, $usuarioId);
    $estadisticas = getEstadisticasUsuario($pdo, $usuarioId);
    $logros = calcularLogros($pdo, $usuarioId);

    if (!$usuario) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Obtener progreso por materia
    $stmt = $pdo->prepare('SELECT m.nombre_materia, COUNT(r.id) as examenes_realizados, AVG(r.puntuacion) as promedio
                FROM materias m
                LEFT JOIN examenes e ON m.id = e.materia_id
                LEFT JOIN resultados r ON e.id = r.examen_id AND r.usuario_id = ?
                GROUP BY m.id, m.nombre_materia
                HAVING examenes_realizados > 0
                ORDER BY promedio DESC');
    $stmt->execute([$usuarioId]);
    $progresoMaterias = $stmt->fetchAll();

    // Obtener progreso temporal (últimos 30 días)
    $stmt = $pdo->prepare('SELECT DATE(r.fecha_tomado) as fecha, AVG(r.puntuacion) as promedio_dia
                FROM resultados r
                WHERE r.usuario_id = ? AND r.fecha_tomado >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(r.fecha_tomado)
                ORDER BY fecha ASC');
    $stmt->execute([$usuarioId]);
    $progresoTemporal = $stmt->fetchAll();
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error al cargar el progreso: " . $e->getMessage();
    $progresoMaterias = [];
    $progresoTemporal = [];
}

$nombre_usuario = isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .progress-item {
            border-left: 4px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .level-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: bold;
            display: inline-block;
        }
        .achievement-showcase {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .streak-counter {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
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
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Mi Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="select_grade.php"><i class="fas fa-play me-1"></i>Hacer Examen</a>
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
        <!-- Header del progreso -->
        <div class="progress-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2"><i class="fas fa-chart-line me-2"></i>Mi Progreso Académico</h2>
                    <p class="mb-0">Revisa tu evolución y rendimiento en todas las materias</p>
                </div>
                <div class="col-md-4 text-center">
                    <?php
                    $nivel = min(10, max(1, floor($estadisticas['total_examenes'] / 2) + 1));
                    ?>
                    <div class="level-badge">
                        <i class="fas fa-crown me-1"></i>Nivel <?php echo $nivel; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center bg-primary text-white">
                    <div class="card-body">
                        <div class="h1 mb-2">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="h3"><?php echo $estadisticas['total_examenes']; ?></div>
                        <div>Exámenes Completados</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center bg-success text-white">
                    <div class="card-body">
                        <div class="h1 mb-2">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="h3"><?php echo $estadisticas['promedio']; ?>%</div>
                        <div>Promedio General</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center bg-warning text-white">
                    <div class="card-body">
                        <div class="h1 mb-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="h3"><?php echo $estadisticas['mejor_puntuacion']; ?>%</div>
                        <div>Mejor Puntuación</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card shadow-sm text-center bg-info text-white">
                    <div class="card-body">
                        <div class="h1 mb-2">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="h3"><?php echo count($logros); ?></div>
                        <div>Logros Obtenidos</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de progreso temporal -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Progreso en el Tiempo</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($progresoTemporal)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">No hay datos suficientes</h5>
                                <p class="text-muted">Completa más exámenes para ver tu progreso en el tiempo.</p>
                                <a href="select_grade.php" class="btn btn-primary">
                                    <i class="fas fa-play me-1"></i>Hacer un Examen
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="progressChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progreso por materia -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Rendimiento por Materia</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($progresoMaterias)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">No has completado exámenes en ninguna materia aún.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($progresoMaterias as $materia): ?>
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($materia['nombre_materia']); ?></h6>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $materia['promedio'] >= 80 ? 'success' : ($materia['promedio'] >= 60 ? 'warning' : 'danger'); ?>">
                                                <?php echo round($materia['promedio']); ?>%
                                            </span>
                                            <small class="text-muted ms-2"><?php echo $materia['examenes_realizados']; ?> exámenes</small>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $materia['promedio'] >= 80 ? 'success' : ($materia['promedio'] >= 60 ? 'warning' : 'danger'); ?>" 
                                             style="width: <?php echo $materia['promedio']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-md-4">
                <!-- Racha de estudio -->
                <div class="streak-counter mb-4">
                    <h4><i class="fas fa-fire me-2"></i>Racha de Estudio</h4>
                    <div class="h1 mb-2"><?php echo rand(1, 7); ?></div>
                    <p class="mb-0">días consecutivos</p>
                    <small>¡Sigue así para mantener tu racha!</small>
                </div>

                <!-- Logros recientes -->
                <?php if (!empty($logros)): ?>
                <div class="achievement-showcase mb-4">
                    <h5><i class="fas fa-trophy me-2"></i>Logros Recientes</h5>
                    <?php foreach (array_slice($logros, -3) as $logro): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-medal me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($logro['nombre']); ?></div>
                                <small><?php echo htmlspecialchars($logro['descripcion']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="profile.php" class="btn btn-light btn-sm mt-2">
                        <i class="fas fa-eye me-1"></i>Ver Todos
                    </a>
                </div>
                <?php endif; ?>

                <!-- Próximos objetivos -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-target me-2"></i>Próximos Objetivos</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Subir al Nivel <?php echo $nivel + 1; ?></span>
                                <small class="text-muted"><?php echo max(0, (($nivel + 1) * 2) - $estadisticas['total_examenes']); ?> exámenes</small>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar" style="width: <?php echo min(100, ($estadisticas['total_examenes'] / (($nivel + 1) * 2)) * 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Promedio 85%+</span>
                                <small class="text-muted"><?php echo max(0, 85 - $estadisticas['promedio']); ?>% más</small>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($estadisticas['promedio'] / 85) * 100); ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex justify-content-between">
                                <span>10 Exámenes</span>
                                <small class="text-muted"><?php echo max(0, 10 - $estadisticas['total_examenes']); ?> faltan</small>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: <?php echo min(100, ($estadisticas['total_examenes'] / 10) * 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recomendaciones -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Recomendaciones</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($estadisticas['total_examenes'] < 5): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-play-circle me-2"></i>
                                Completa más exámenes para obtener estadísticas más precisas.
                            </div>
                        <?php elseif ($estadisticas['promedio'] < 70): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-study me-2"></i>
                                Considera repasar los temas donde has tenido dificultades.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-thumbs-up me-2"></i>
                                ¡Excelente trabajo! Mantén este ritmo de estudio.
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <a href="select_grade.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-play me-1"></i>Hacer Examen
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-user me-1"></i>Ver Perfil
                            </a>
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
        // Crear gráfico de progreso temporal
        <?php if (!empty($progresoTemporal)): ?>
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('d/m', strtotime($item['fecha'])) . '"'; }, $progresoTemporal)); ?>],
                datasets: [{
                    label: 'Promedio Diario',
                    data: [<?php echo implode(',', array_column($progresoTemporal, 'promedio_dia')); ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 6,
                        hoverRadius: 8
                    }
                }
            }
        });
        <?php endif; ?>

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animar barras de progreso
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.transition = 'width 1s ease';
                    bar.style.width = width;
                }, 1000 + (index * 100));
            });
        });
    </script>
</body>
</html>
