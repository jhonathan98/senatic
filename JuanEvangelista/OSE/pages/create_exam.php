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
            $stmt = $pdo->prepare("INSERT INTO examenes (titulo, materia_id, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $materia_id, $descripcion]);
            
            $examen_id = $pdo->lastInsertId();
            $success = "Examen creado correctamente";
            
            // Redirigir a la página de agregar preguntas
            header("Location: add_questions.php?exam_id=" . $examen_id);
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Error al crear el examen: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Examen - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Crear Examen
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
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Examen</h4>
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

                        <form method="POST" id="createExamForm">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">
                                    <i class="fas fa-heading me-2"></i>Título del Examen *
                                </label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" 
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
                                                <?php echo (isset($_POST['grado_id']) && $_POST['grado_id'] == $grado['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="materia_id" class="form-label">
                                    <i class="fas fa-book me-2"></i>Materia *
                                </label>
                                <select class="form-select" id="materia_id" name="materia_id" required disabled>
                                    <option value="">Primero selecciona un grado</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Descripción (Opcional)
                                </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                          placeholder="Describe brevemente el contenido del examen..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nota:</strong> Después de crear el examen, podrás agregar preguntas y respuestas.
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_exams.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Examen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Guía rápida -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Guía Rápida</h6>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>Elige un título descriptivo para tu examen</li>
                            <li>Selecciona el grado correspondiente</li>
                            <li>Elige la materia específica</li>
                            <li>Agrega una descripción opcional</li>
                            <li>Haz clic en "Crear Examen"</li>
                            <li>Serás redirigido para agregar preguntas</li>
                        </ol>
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
            
            gradoSelect.addEventListener('change', function() {
                const gradoId = this.value;
                
                // Limpiar y deshabilitar select de materias
                materiaSelect.innerHTML = '<option value="">Cargando materias...</option>';
                materiaSelect.disabled = true;
                
                if (gradoId) {
                    // Obtener materias del grado seleccionado
                    fetch(`create_exam.php?get_materias=1&grado_id=${gradoId}`)
                        .then(response => response.json())
                        .then(data => {
                            materiaSelect.innerHTML = '<option value="">Seleccionar materia...</option>';
                            
                            if (data.length > 0) {
                                data.forEach(materia => {
                                    const option = document.createElement('option');
                                    option.value = materia.id;
                                    option.textContent = materia.nombre_materia;
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
