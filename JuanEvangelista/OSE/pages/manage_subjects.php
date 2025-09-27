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

// Obtener grados para el formulario
$grados = [];
try {
    $stmt = $pdo->query("SELECT * FROM grados ORDER BY id");
    $grados = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener grados: " . $e->getMessage();
}

// Obtener materias basadas en el grado seleccionado (AJAX)
if (isset($_GET['get_materias']) && isset($_GET['grado_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM materias WHERE grado_id = ? ORDER BY nombre_materia");
        $stmt->execute([$_GET['grado_id']]);
        $materias = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($materias);
        exit();
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_materia = trim($_POST['nombre_materia'] ?? '');
    $grado_id = $_POST['grado_id'] ?? '';
    
    $errors = [];
    
    if (empty($nombre_materia)) {
        $errors[] = "El nombre de la materia es obligatorio";
    }
    
    if (empty($grado_id)) {
        $errors[] = "Debe seleccionar un grado";
    }
    
    if (empty($errors)) {
        try {
            // Verificar si la materia ya existe para ese grado
            $stmt = $pdo->prepare("SELECT id FROM materias WHERE nombre_materia = ? AND grado_id = ?");
            $stmt->execute([$nombre_materia, $grado_id]);
            
            if ($stmt->fetch()) {
                $errors[] = "Ya existe una materia con ese nombre para el grado seleccionado";
            } else {
                $stmt = $pdo->prepare("INSERT INTO materias (nombre_materia, grado_id) VALUES (?, ?)");
                $stmt->execute([$nombre_materia, $grado_id]);
                
                $success = "Materia creada correctamente";
                
                // Limpiar el formulario
                $_POST = [];
            }
            
        } catch(PDOException $e) {
            $errors[] = "Error al crear la materia: " . $e->getMessage();
        }
    }
}

// Obtener todas las materias existientes
$materias_existentes = [];
try {
    $stmt = $pdo->query("
        SELECT m.*, g.nombre_grado,
               (SELECT COUNT(*) FROM examenes e WHERE e.materia_id = m.id) as total_examenes
        FROM materias m 
        JOIN grados g ON m.grado_id = g.id 
        ORDER BY g.id, m.nombre_materia
    ");
    $materias_existentes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener materias: " . $e->getMessage();
}

// Eliminar materia si se solicita
if (isset($_POST['delete_subject']) && isset($_POST['subject_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM materias WHERE id = ?");
        $stmt->execute([$_POST['subject_id']]);
        $success = "Materia eliminada correctamente";
        header("Location: manage_subjects.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error al eliminar materia: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Materias - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Gestión de Materias
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
        <div class="row">
            <!-- Formulario para crear materia -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nueva Materia</h5>
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

                        <form method="POST" id="createSubjectForm">
                            <div class="mb-3">
                                <label for="grado_id" class="form-label">
                                    <i class="fas fa-layer-group me-2"></i>Grado *
                                </label>
                                <select class="form-select" id="grado_id" name="grado_id" required>
                                    <option value="">Seleccionar grado...</option>
                                    <?php foreach ($grados as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>" 
                                                <?php echo (isset($_POST['grado_id']) && $_POST['grado_id'] == $grado['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_materia" class="form-label">
                                    <i class="fas fa-book me-2"></i>Nombre de la Materia *
                                </label>
                                <input type="text" class="form-control" id="nombre_materia" name="nombre_materia" 
                                       value="<?php echo htmlspecialchars($_POST['nombre_materia'] ?? ''); ?>" 
                                       placeholder="Ej: Matemáticas Avanzadas" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Materia
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Materias sugeridas -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Materias Sugeridas</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Algunas ideas de materias:</small>
                        <ul class="list-unstyled mt-2">
                            <li><small>• Matemáticas</small></li>
                            <li><small>• Lengua Castellana</small></li>
                            <li><small>• Ciencias Naturales</small></li>
                            <li><small>• Ciencias Sociales</small></li>
                            <li><small>• Inglés</small></li>
                            <li><small>• Física</small></li>
                            <li><small>• Química</small></li>
                            <li><small>• Biología</small></li>
                            <li><small>• Historia</small></li>
                            <li><small>• Geografía</small></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Lista de materias existentes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Materias Existentes 
                            <span class="badge bg-primary"><?php echo count($materias_existentes); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materias_existentes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay materias disponibles</h5>
                                <p class="text-muted">Crea tu primera materia para comenzar</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Materia</th>
                                            <th>Grado</th>
                                            <th>Exámenes</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $current_grade = '';
                                        foreach ($materias_existentes as $materia): 
                                            $grade_changed = ($current_grade !== $materia['nombre_grado']);
                                            $current_grade = $materia['nombre_grado'];
                                        ?>
                                            <?php if ($grade_changed): ?>
                                                <tr class="table-secondary">
                                                    <td colspan="5">
                                                        <strong><i class="fas fa-layer-group me-2"></i><?php echo htmlspecialchars($materia['nombre_grado']); ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td><?php echo $materia['id']; ?></td>
                                                <td>
                                                    <i class="fas fa-book me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($materia['nombre_materia']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($materia['nombre_grado']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?php echo $materia['total_examenes']; ?> exámenes
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="create_exam.php?materia_id=<?php echo $materia['id']; ?>" 
                                                           class="btn btn-sm btn-outline-success" title="Crear examen para esta materia">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?php echo $materia['id']; ?>"
                                                                title="Eliminar materia">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Modal de confirmación para eliminar -->
                                                    <div class="modal fade" id="deleteModal<?php echo $materia['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirmar Eliminación</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>¿Estás seguro de que deseas eliminar la materia "<strong><?php echo htmlspecialchars($materia['nombre_materia']); ?></strong>"?</p>
                                                                    <?php if ($materia['total_examenes'] > 0): ?>
                                                                        <p class="text-warning">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                                            Esta materia tiene <?php echo $materia['total_examenes']; ?> exámenes asociados que también serán eliminados.
                                                                        </p>
                                                                    <?php endif; ?>
                                                                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="subject_id" value="<?php echo $materia['id']; ?>">
                                                                        <button type="submit" name="delete_subject" class="btn btn-danger">
                                                                            <i class="fas fa-trash me-2"></i>Eliminar
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
