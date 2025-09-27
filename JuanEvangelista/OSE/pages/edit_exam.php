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
$examen = null;
try {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nombre_materia, g.nombre_grado, m.grado_id 
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
} catch(PDOException $e) {
    $error = "Error al obtener el examen: " . $e->getMessage();
}

// Obtener grados para el select
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
    $titulo = trim($_POST['titulo'] ?? '');
    $materia_id = $_POST['materia_id'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    $errors = [];
    
    if (empty($titulo)) {
        $errors[] = "El título es obligatorio";
    }
    
    if (empty($materia_id)) {
        $errors[] = "Debe seleccionar una materia";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE examenes SET titulo = ?, materia_id = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$titulo, $materia_id, $descripcion, $exam_id]);
            
            $success = "Examen actualizado correctamente";
            
            // Recargar información del examen
            $stmt = $pdo->prepare("
                SELECT e.*, m.nombre_materia, g.nombre_grado, m.grado_id 
                FROM examenes e 
                JOIN materias m ON e.materia_id = m.id 
                JOIN grados g ON m.grado_id = g.id 
                WHERE e.id = ?
            ");
            $stmt->execute([$exam_id]);
            $examen = $stmt->fetch();
            
        } catch(PDOException $e) {
            $errors[] = "Error al actualizar el examen: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Examen - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Editar Examen
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Examen</h4>
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

                        <form method="POST" id="editExamForm">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">
                                    <i class="fas fa-heading me-2"></i>Título del Examen *
                                </label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($examen['titulo']); ?>" 
                                       placeholder="Ej: Matemáticas - Números Enteros" required>
                            </div>

                            <div class="mb-3">
                                <label for="grado_id" class="form-label">
                                    <i class="fas fa-layer-group me-2"></i>Grado *
                                </label>
                                <select class="form-select" id="grado_id" name="grado_id" required>
                                    <option value="">Seleccionar grado...</option>
                                    <?php foreach ($grados as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>" 
                                                <?php echo ($examen['grado_id'] == $grado['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="materia_id" class="form-label">
                                    <i class="fas fa-book me-2"></i>Materia *
                                </label>
                                <select class="form-select" id="materia_id" name="materia_id" required>
                                    <option value="<?php echo $examen['materia_id']; ?>" selected>
                                        <?php echo htmlspecialchars($examen['nombre_materia']); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Descripción (Opcional)
                                </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                          placeholder="Describe brevemente el contenido del examen..."><?php echo htmlspecialchars($examen['descripcion']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_exams.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Actualizar Examen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Examen</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID del Examen:</strong> <?php echo $examen['id']; ?></p>
                                <p><strong>Fecha de Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($examen['fecha_creacion'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Grado Actual:</strong> <?php echo htmlspecialchars($examen['nombre_grado']); ?></p>
                                <p><strong>Materia Actual:</strong> <?php echo htmlspecialchars($examen['nombre_materia']); ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Acciones Rápidas:</h6>
                            <div class="btn-group" role="group">
                                <a href="add_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-question-circle me-1"></i>Gestionar Preguntas
                                </a>
                                <a href="view_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>Vista Previa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gradoSelect = document.getElementById('grado_id');
            const materiaSelect = document.getElementById('materia_id');
            const currentMateriaId = <?php echo $examen['materia_id']; ?>;
            
            gradoSelect.addEventListener('change', function() {
                const gradoId = this.value;
                
                // Limpiar y deshabilitar select de materias
                materiaSelect.innerHTML = '<option value="">Cargando materias...</option>';
                materiaSelect.disabled = true;
                
                if (gradoId) {
                    // Obtener materias del grado seleccionado
                    fetch(`edit_exam.php?get_materias=1&grado_id=${gradoId}`)
                        .then(response => response.json())
                        .then(data => {
                            materiaSelect.innerHTML = '<option value="">Seleccionar materia...</option>';
                            
                            if (data.length > 0) {
                                data.forEach(materia => {
                                    const option = document.createElement('option');
                                    option.value = materia.id;
                                    option.textContent = materia.nombre_materia;
                                    
                                    // Mantener la materia actual seleccionada si coincide
                                    if (materia.id == currentMateriaId) {
                                        option.selected = true;
                                    }
                                    
                                    materiaSelect.appendChild(option);
                                });
                                materiaSelect.disabled = false;
                            } else {
                                materiaSelect.innerHTML = '<option value="">No hay materias disponibles</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                        });
                } else {
                    materiaSelect.innerHTML = '<option value="">Primero selecciona un grado</option>';
                }
            });
        });
    </script>
</body>
</html>
