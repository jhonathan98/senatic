<?php
// pages/profile.php
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

    // Procesar actualización de perfil
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
        $nombre = sanitizeData($_POST['nombre']);
        $email = sanitizeData($_POST['email']);
        
        if (!empty($nombre) && !empty($email) && validarEmail($email)) {
            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?');
            $stmt->execute([$nombre, $email, $usuarioId]);
            
            $_SESSION['nombre'] = $nombre;
            $_SESSION['success'] = "Perfil actualizado correctamente.";
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Por favor, completa todos los campos correctamente.";
        }
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error al cargar el perfil: " . $e->getMessage();
}

$nombre_usuario = isset($usuario) ? htmlspecialchars($usuario['nombre']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1rem;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .achievement-item {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .level-progress {
            height: 10px;
            border-radius: 5px;
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header del perfil -->
        <div class="profile-header text-center">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="col-md-8 text-md-start">
                    <h2 class="mb-2"><?php echo $nombre_usuario; ?></h2>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($usuario['email']); ?></p>
                    <p class="mb-1"><i class="fas fa-user-tag me-2"></i>Estudiante</p>
                    <p class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Miembro desde <?php echo formatearFecha($usuario['fecha_registro']); ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Estadísticas generales -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas Generales</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card stat-card text-center bg-light">
                                    <div class="card-body">
                                        <div class="h2 text-primary mb-2">
                                            <i class="fas fa-clipboard-check"></i>
                                        </div>
                                        <div class="h4 text-primary"><?php echo $estadisticas['total_examenes']; ?></div>
                                        <div class="text-muted">Exámenes Realizados</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card stat-card text-center bg-light">
                                    <div class="card-body">
                                        <div class="h2 text-success mb-2">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                        <div class="h4 text-success"><?php echo $estadisticas['promedio']; ?>%</div>
                                        <div class="text-muted">Promedio General</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card stat-card text-center bg-light">
                                    <div class="card-body">
                                        <div class="h2 text-warning mb-2">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="h4 text-warning"><?php echo $estadisticas['mejor_puntuacion']; ?>%</div>
                                        <div class="text-muted">Mejor Puntuación</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card stat-card text-center bg-light">
                                    <div class="card-body">
                                        <div class="h2 text-info mb-2">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div class="h4 text-info"><?php echo count($logros); ?></div>
                                        <div class="text-muted">Logros Obtenidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progreso por nivel -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-level-up-alt me-2"></i>Nivel de Aprendizaje</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $nivel = min(10, max(1, floor($estadisticas['total_examenes'] / 2) + 1));
                        $progresoNivel = ($estadisticas['total_examenes'] % 2) * 50;
                        $puntosNivel = $estadisticas['total_examenes'] * 10 + $estadisticas['promedio'];
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Nivel <?php echo $nivel; ?></span>
                            <span class="text-muted"><?php echo $puntosNivel; ?> puntos</span>
                        </div>
                        <div class="progress level-progress mb-3">
                            <div class="progress-bar bg-gradient" style="width: <?php echo $progresoNivel; ?>%"></div>
                        </div>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Completa más exámenes para subir de nivel y desbloquear nuevos logros.
                        </p>
                    </div>
                </div>

                <!-- Últimos resultados -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos Resultados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($estadisticas['ultimos_resultados'])): ?>
                            <p class="text-muted text-center">No has realizado exámenes aún.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Examen</th>
                                            <th>Materia</th>
                                            <th>Puntuación</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estadisticas['ultimos_resultados'] as $resultado): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($resultado['examen_titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($resultado['nombre_materia']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $resultado['puntuacion'] >= 80 ? 'success' : ($resultado['puntuacion'] >= 60 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $resultado['puntuacion']; ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo formatearFecha($resultado['fecha_tomado']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-md-4">
                <!-- Editar perfil -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Perfil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            <button type="submit" name="actualizar_perfil" class="btn btn-warning w-100">
                                <i class="fas fa-save me-1"></i>Actualizar Perfil
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Logros -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Mis Logros</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logros)): ?>
                            <p class="text-muted text-center">
                                <i class="fas fa-medal" style="font-size: 2rem; opacity: 0.3;"></i><br>
                                Aún no tienes logros.<br>
                                ¡Completa exámenes para obtener recompensas!
                            </p>
                        <?php else: ?>
                            <?php foreach ($logros as $logro): ?>
                                <div class="achievement-item">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-medal me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($logro['nombre']); ?></div>
                                            <small><?php echo htmlspecialchars($logro['descripcion']); ?></small>
                                            <div class="text-success small">+<?php echo $logro['puntos']; ?> puntos</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="select_grade.php" class="btn btn-primary">
                                <i class="fas fa-play me-1"></i>Hacer Examen
                            </a>
                            <a href="progress.php" class="btn btn-info">
                                <i class="fas fa-chart-line me-1"></i>Ver Progreso
                            </a>
                            <a href="welcome.php" class="btn btn-secondary">
                                <i class="fas fa-home me-1"></i>Ir al Inicio
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

            // Animación del avatar
            const avatar = document.querySelector('.profile-avatar');
            avatar.style.transform = 'scale(0)';
            setTimeout(() => {
                avatar.style.transition = 'transform 0.5s ease';
                avatar.style.transform = 'scale(1)';
            }, 200);
        });
    </script>
</body>
</html>
