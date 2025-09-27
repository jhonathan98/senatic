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

// Obtener todos los exámenes con información de materia y grado
try {
    $stmt = $pdo->query("
        SELECT e.*, m.nombre_materia, g.nombre_grado,
               (SELECT COUNT(*) FROM preguntas p WHERE p.examen_id = e.id) as total_preguntas
        FROM examenes e 
        JOIN materias m ON e.materia_id = m.id 
        JOIN grados g ON m.grado_id = g.id 
        ORDER BY e.fecha_creacion DESC
    ");
    $examenes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener exámenes: " . $e->getMessage();
}

// Eliminar examen si se solicita
if (isset($_POST['delete_exam']) && isset($_POST['exam_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM examenes WHERE id = ?");
        $stmt->execute([$_POST['exam_id']]);
        $success = "Examen eliminado correctamente";
        header("Location: manage_exams.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error al eliminar examen: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Exámenes - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Gestión de Exámenes
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
            <h2><i class="fas fa-file-alt me-2"></i>Gestionar Exámenes</h2>
            <a href="create_exam.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Crear Nuevo Examen
            </a>
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

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Exámenes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($examenes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay exámenes disponibles</h5>
                        <p class="text-muted">Crea tu primer examen para comenzar</p>
                        <a href="create_exam.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Examen
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Grado</th>
                                    <th>Materia</th>
                                    <th>Preguntas</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examenes as $examen): ?>
                                <tr>
                                    <td><?php echo $examen['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($examen['titulo']); ?></strong>
                                        <?php if ($examen['descripcion']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($examen['descripcion'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($examen['nombre_grado']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($examen['nombre_materia']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $examen['total_preguntas']; ?> preguntas
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($examen['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_exam.php?id=<?php echo $examen['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Ver examen">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_exam.php?id=<?php echo $examen['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="Editar examen">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_questions.php?exam_id=<?php echo $examen['id']; ?>" 
                                               class="btn btn-sm btn-outline-success" title="Gestionar preguntas">
                                                <i class="fas fa-question-circle"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $examen['id']; ?>"
                                                    title="Eliminar examen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal de confirmación para eliminar -->
                                        <div class="modal fade" id="deleteModal<?php echo $examen['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Estás seguro de que deseas eliminar el examen "<strong><?php echo htmlspecialchars($examen['titulo']); ?></strong>"?</p>
                                                        <p class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Esta acción también eliminará todas las preguntas asociadas y no se puede deshacer.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="exam_id" value="<?php echo $examen['id']; ?>">
                                                            <button type="submit" name="delete_exam" class="btn btn-danger">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
