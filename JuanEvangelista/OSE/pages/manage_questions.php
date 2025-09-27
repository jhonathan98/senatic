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

// Obtener exámenes para el filtro
$examenes = [];
try {
    $stmt = $pdo->query("
        SELECT e.id, e.titulo, m.nombre_materia, g.nombre_grado,
               (SELECT COUNT(*) FROM preguntas p WHERE p.examen_id = e.id) as total_preguntas
        FROM examenes e 
        JOIN materias m ON e.materia_id = m.id 
        JOIN grados g ON m.grado_id = g.id 
        ORDER BY g.id, m.nombre_materia, e.titulo
    ");
    $examenes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener exámenes: " . $e->getMessage();
}

// Filtrar por examen si se especifica
$exam_filter = $_GET['exam_id'] ?? '';
$preguntas = [];

if ($exam_filter) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, e.titulo as examen_titulo, m.nombre_materia, g.nombre_grado
            FROM preguntas p
            JOIN examenes e ON p.examen_id = e.id
            JOIN materias m ON e.materia_id = m.id
            JOIN grados g ON m.grado_id = g.id
            WHERE p.examen_id = ?
            ORDER BY p.id
        ");
        $stmt->execute([$exam_filter]);
        $preguntas = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error al obtener preguntas: " . $e->getMessage();
    }
} else {
    // Obtener todas las preguntas si no hay filtro
    try {
        $stmt = $pdo->query("
            SELECT p.*, e.titulo as examen_titulo, m.nombre_materia, g.nombre_grado
            FROM preguntas p
            JOIN examenes e ON p.examen_id = e.id
            JOIN materias m ON e.materia_id = m.id
            JOIN grados g ON m.grado_id = g.id
            ORDER BY g.id, m.nombre_materia, e.titulo, p.id
            LIMIT 50
        ");
        $preguntas = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error al obtener preguntas: " . $e->getMessage();
    }
}

// Eliminar pregunta si se solicita
if (isset($_POST['delete_question']) && isset($_POST['question_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id = ?");
        $stmt->execute([$_POST['question_id']]);
        $success = "Pregunta eliminada correctamente";
        
        // Recargar la página
        $redirect_url = "manage_questions.php";
        if ($exam_filter) {
            $redirect_url .= "?exam_id=" . $exam_filter;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch(PDOException $e) {
        $error = "Error al eliminar pregunta: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Preguntas - OSE</title>
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
                        <a class="nav-link" href="admin_panel.php">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Panel
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-question-circle me-2"></i>Gestionar Preguntas</h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="exam_id" class="form-label">Filtrar por Examen:</label>
                        <select class="form-select" id="exam_id" name="exam_id">
                            <option value="">Todos los exámenes</option>
                            <?php foreach ($examenes as $examen): ?>
                                <option value="<?php echo $examen['id']; ?>" 
                                        <?php echo ($exam_filter == $examen['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($examen['nombre_grado'] . ' - ' . $examen['nombre_materia'] . ' - ' . $examen['titulo']); ?>
                                    (<?php echo $examen['total_preguntas']; ?> preguntas)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>Filtrar
                        </button>
                        <a href="manage_questions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de preguntas -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        <?php if ($exam_filter): ?>
                            Preguntas del Examen Seleccionado
                        <?php else: ?>
                            Todas las Preguntas (Últimas 50)
                        <?php endif ?>
                        <span class="badge bg-primary"><?php echo count($preguntas); ?></span>
                    </h5>
                    <?php if ($exam_filter): ?>
                        <a href="add_questions.php?exam_id=<?php echo $exam_filter; ?>" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Agregar Pregunta
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($preguntas)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">
                            <?php if ($exam_filter): ?>
                                No hay preguntas en este examen
                            <?php else: ?>
                                No hay preguntas disponibles
                            <?php endif; ?>
                        </h5>
                        <p class="text-muted">
                            <?php if ($exam_filter): ?>
                                Agrega preguntas para que los estudiantes puedan tomar el examen
                            <?php else: ?>
                                Primero debes crear exámenes y luego agregar preguntas
                            <?php endif; ?>
                        </p>
                        <?php if ($exam_filter): ?>
                            <a href="add_questions.php?exam_id=<?php echo $exam_filter; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Agregar Primera Pregunta
                            </a>
                        <?php else: ?>
                            <a href="create_exam.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Examen
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    Pregunta #<?php echo $pregunta['id']; ?>
                                                    <span class="badge bg-secondary ms-2">
                                                        <?php echo $pregunta['tipo_pregunta'] === 'multiple_choice' ? 'Múltiple' : 'V/F'; ?>
                                                    </span>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($pregunta['nombre_grado']); ?> - 
                                                    <?php echo htmlspecialchars($pregunta['nombre_materia']); ?>
                                                </small>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="add_questions.php?exam_id=<?php echo $pregunta['examen_id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="view_exam.php?id=<?php echo $pregunta['examen_id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>Ver Examen
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" type="button" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?php echo $pregunta['id']; ?>">
                                                            <i class="fas fa-trash me-2"></i>Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-file-alt me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($pregunta['examen_titulo']); ?>
                                        </h6>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($pregunta['texto_pregunta']); ?>
                                        </p>
                                        
                                        <?php
                                        // Obtener respuestas de esta pregunta
                                        $stmt = $pdo->prepare("SELECT * FROM respuestas WHERE pregunta_id = ? ORDER BY id");
                                        $stmt->execute([$pregunta['id']]);
                                        $respuestas_pregunta = $stmt->fetchAll();
                                        ?>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted d-block mb-2"><strong>Opciones:</strong></small>
                                            <?php foreach ($respuestas_pregunta as $resp): ?>
                                                <small class="d-block <?php echo $resp['es_correcta'] ? 'text-success fw-bold' : 'text-muted'; ?>">
                                                    <?php echo $resp['es_correcta'] ? '✓' : '○'; ?> 
                                                    <?php echo htmlspecialchars($resp['texto_respuesta']); ?>
                                                    <?php if ($resp['es_correcta']): ?>
                                                        <span class="badge bg-success ms-1">Correcta</span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de confirmación para eliminar pregunta -->
                                <div class="modal fade" id="deleteModal<?php echo $pregunta['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>¿Estás seguro de que deseas eliminar esta pregunta?</p>
                                                <p class="text-muted">
                                                    <strong>Pregunta:</strong> <?php echo htmlspecialchars(substr($pregunta['texto_pregunta'], 0, 100)); ?>...
                                                </p>
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información adicional -->
        <?php if (!empty($preguntas) && !$exam_filter): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Nota:</strong> Se muestran las últimas 50 preguntas. 
                Usa el filtro por examen para ver todas las preguntas de un examen específico.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
