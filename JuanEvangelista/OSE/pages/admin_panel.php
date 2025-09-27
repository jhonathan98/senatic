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

// Obtener estadísticas
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_examenes FROM examenes");
    $total_examenes = $stmt->fetch()['total_examenes'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_preguntas FROM preguntas");
    $total_preguntas = $stmt->fetch()['total_preguntas'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_materias FROM materias");
    $total_materias = $stmt->fetch()['total_materias'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_usuarios FROM usuarios WHERE rol = 'estudiante'");
    $total_estudiantes = $stmt->fetch()['total_usuarios'];
    
} catch(PDOException $e) {
    $error = "Error al obtener estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Gestión - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Panel de Gestión
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($nombre_usuario); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="welcome.php"><i class="fas fa-home me-2"></i>Área Estudiante</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Gestión</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="admin_panel.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="manage_exams.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-2"></i>Gestionar Exámenes
                        </a>
                        <a href="manage_questions.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-question-circle me-2"></i>Gestionar Preguntas
                        </a>
                        <a href="manage_subjects.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i>Gestionar Materias
                        </a>
                        <?php if ($rol_usuario === 'admin'): ?>
                        <a href="manage_users.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i>Gestionar Usuarios
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Accesos Rápidos -->
                    <div class="card-header bg-success text-white mt-3">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Crear Nuevo</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="create_exam.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-plus me-2 text-primary"></i>Nuevo Examen
                        </a>
                        <a href="create_subject.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book-plus me-2 text-success"></i>Nueva Materia
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard - Panel de Gestión</h2>
                    <span class="badge bg-primary fs-6">Rol: <?php echo ucfirst($rol_usuario); ?></span>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_examenes; ?></h4>
                                        <p class="mb-0">Exámenes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_preguntas; ?></h4>
                                        <p class="mb-0">Preguntas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-question-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_materias; ?></h4>
                                        <p class="mb-0">Materias</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-book fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_estudiantes; ?></h4>
                                        <p class="mb-0">Estudiantes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus-circle me-2"></i>Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="create_exam.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Crear Nuevo Examen
                                    </a>
                                    <a href="create_subject.php" class="btn btn-success">
                                        <i class="fas fa-book me-2"></i>Crear Nueva Materia
                                    </a>
                                    <?php if ($rol_usuario === 'admin'): ?>
                                    <a href="manage_users.php" class="btn btn-warning">
                                        <i class="fas fa-users me-2"></i>Gestionar Usuarios
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#quickSubjectModal">
                                        <i class="fas fa-lightning-bolt me-2"></i>Materia Rápida
                                    </button>
                                    <a href="manage_subjects.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-list me-2"></i>Ver Todas las Materias
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Resumen Reciente</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Últimas actividades del sistema:</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Sistema funcionando correctamente
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-database text-info me-2"></i>
                                        Base de datos conectada
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        <?php echo $total_estudiantes; ?> estudiantes registrados
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para creación rápida de materia -->
    <div class="modal fade" id="quickSubjectModal" tabindex="-1" aria-labelledby="quickSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="quickSubjectModalLabel">
                        <i class="fas fa-lightning-bolt me-2"></i>Crear Materia Rápida
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickSubjectForm" action="create_subject.php" method="POST">
                        <div class="mb-3">
                            <label for="quick_grado_id" class="form-label">Grado *</label>
                            <select class="form-select" id="quick_grado_id" name="grado_id" required>
                                <option value="">Seleccionar grado...</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT * FROM grados ORDER BY id");
                                    $grados_modal = $stmt->fetchAll();
                                    foreach ($grados_modal as $grado) {
                                        echo '<option value="' . $grado['id'] . '">' . htmlspecialchars($grado['nombre_grado']) . '</option>';
                                    }
                                } catch(PDOException $e) {
                                    echo '<option value="">Error al cargar grados</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quick_nombre_materia" class="form-label">Nombre de la Materia *</label>
                            <input type="text" class="form-control" id="quick_nombre_materia" name="nombre_materia" 
                                   placeholder="Ej: Matemáticas" maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acciones después de crear:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="next_action" id="stay_panel" value="stay" checked>
                                <label class="form-check-label" for="stay_panel">
                                    Volver al panel de administración
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="next_action" id="create_exam" value="exam">
                                <label class="form-check-label" for="create_exam">
                                    Crear un examen para esta materia
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="quickSubjectForm" class="btn btn-info">
                        <i class="fas fa-save me-2"></i>Crear Materia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejar el formulario rápido de materia
        document.getElementById('quickSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const nextAction = formData.get('next_action');
            
            // Si el usuario quiere crear un examen después, agregar el parámetro
            if (nextAction === 'exam') {
                formData.append('create_and_exam', '1');
            }
            
            // Enviar el formulario
            fetch('create_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Si fue exitoso y el usuario eligió crear examen, redirigir
                if (nextAction === 'exam' && data.includes('correctamente')) {
                    window.location.href = 'create_exam.php';
                } else {
                    // Recargar la página para actualizar estadísticas
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la materia. Por favor, intenta de nuevo.');
            });
        });
    </script>
</body>
</html>
