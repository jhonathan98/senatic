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

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_materia = trim($_POST['nombre_materia'] ?? '');
    $grado_id = $_POST['grado_id'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    $errors = [];
    
    if (empty($nombre_materia)) {
        $errors[] = "El nombre de la materia es obligatorio";
    }
    
    if (empty($grado_id)) {
        $errors[] = "Debe seleccionar un grado";
    }
    
    // Validar longitud
    if (strlen($nombre_materia) < 3) {
        $errors[] = "El nombre de la materia debe tener al menos 3 caracteres";
    }
    
    if (strlen($nombre_materia) > 100) {
        $errors[] = "El nombre de la materia no puede exceder 100 caracteres";
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
                
                $materia_id = $pdo->lastInsertId();
                $success = "Materia '" . htmlspecialchars($nombre_materia) . "' creada correctamente";
                
                // Opcionalmente redirigir
                if (isset($_POST['create_and_exam'])) {
                    header("Location: create_exam.php?materia_id=" . $materia_id);
                    exit();
                }
                
                // Limpiar el formulario
                $_POST = [];
            }
            
        } catch(PDOException $e) {
            $errors[] = "Error al crear la materia: " . $e->getMessage();
        }
    }
}

// Obtener materias recientes para mostrar
$materias_recientes = [];
try {
    $stmt = $pdo->query("
        SELECT m.*, g.nombre_grado,
               (SELECT COUNT(*) FROM examenes e WHERE e.materia_id = m.id) as total_examenes
        FROM materias m 
        JOIN grados g ON m.grado_id = g.id 
        ORDER BY m.id DESC 
        LIMIT 5
    ");
    $materias_recientes = $stmt->fetchAll();
} catch(PDOException $e) {
    // Error silencioso para la vista
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Materia - OSE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>OSE - Crear Materia
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
                    <li class="nav-item">
                        <a class="nav-link" href="manage_subjects.php">
                            <i class="fas fa-list me-1"></i>Ver Todas las Materias
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
            <!-- Formulario principal -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nueva Materia</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
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

                        <form method="POST" id="createSubjectForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="grado_id" class="form-label">
                                            <i class="fas fa-layer-group me-2 text-primary"></i>Grado *
                                        </label>
                                        <select class="form-select form-select-lg" id="grado_id" name="grado_id" required>
                                            <option value="">Seleccionar grado...</option>
                                            <?php foreach ($grados as $grado): ?>
                                                <option value="<?php echo $grado['id']; ?>" 
                                                        <?php echo (isset($_POST['grado_id']) && $_POST['grado_id'] == $grado['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($grado['nombre_grado']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Selecciona el grado para el cual se creará la materia</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre_materia" class="form-label">
                                            <i class="fas fa-book me-2 text-primary"></i>Nombre de la Materia *
                                        </label>
                                        <input type="text" class="form-control form-control-lg" id="nombre_materia" name="nombre_materia" 
                                               value="<?php echo htmlspecialchars($_POST['nombre_materia'] ?? ''); ?>" 
                                               placeholder="Ej: Matemáticas Avanzadas" 
                                               maxlength="100" required>
                                        <div class="form-text">
                                            <span id="char-count">0</span>/100 caracteres
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="descripcion" class="form-label">
                                    <i class="fas fa-align-left me-2 text-primary"></i>Descripción (Opcional)
                                </label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                          placeholder="Breve descripción de la materia y sus objetivos..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                                <div class="form-text">Esta descripción ayudará a los estudiantes a entender el contenido de la materia</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Crear Materia
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-grid">
                                        <button type="submit" name="create_and_exam" class="btn btn-success btn-lg">
                                            <i class="fas fa-plus me-2"></i>Crear y Agregar Examen
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <a href="manage_subjects.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Guía de ayuda -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Consejos para Crear Materias</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">✓ Buenas Prácticas:</h6>
                                <ul class="small">
                                    <li>Usa nombres descriptivos y claros</li>
                                    <li>Mantén consistencia con otras materias</li>
                                    <li>Considera el nivel del grado</li>
                                    <li>Agrega una descripción útil</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">💡 Ejemplos:</h6>
                                <ul class="small">
                                    <li>Matemáticas Básicas</li>
                                    <li>Lengua Castellana y Literatura</li>
                                    <li>Ciencias Naturales</li>
                                    <li>Historia de Colombia</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-md-4">
                <!-- Materias sugeridas -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-stars me-2"></i>Materias Sugeridas por Nivel</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="subjectsAccordion">
                            <!-- Primaria -->
                            <div class="accordion-item">
                                <h6 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#primaria">
                                        Primaria
                                    </button>
                                </h6>
                                <div id="primaria" class="accordion-collapse collapse" data-bs-parent="#subjectsAccordion">
                                    <div class="accordion-body">
                                        <small>
                                            • Matemáticas<br>
                                            • Lengua Castellana<br>
                                            • Ciencias Naturales<br>
                                            • Ciencias Sociales<br>
                                            • Educación Artística<br>
                                            • Educación Física
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Secundaria -->
                            <div class="accordion-item">
                                <h6 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secundaria">
                                        Secundaria
                                    </button>
                                </h6>
                                <div id="secundaria" class="accordion-collapse collapse" data-bs-parent="#subjectsAccordion">
                                    <div class="accordion-body">
                                        <small>
                                            • Matemáticas<br>
                                            • Física<br>
                                            • Química<br>
                                            • Biología<br>
                                            • Historia<br>
                                            • Geografía<br>
                                            • Inglés<br>
                                            • Filosofía
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materias recientes -->
                <?php if (!empty($materias_recientes)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Materias Creadas Recientemente</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($materias_recientes as $materia): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($materia['nombre_materia']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($materia['nombre_grado']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary"><?php echo $materia['total_examenes']; ?> exámenes</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="mt-3">
                            <a href="manage_subjects.php" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-list me-2"></i>Ver Todas las Materias
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM materias");
                            $total_materias = $stmt->fetch()['total'];
                            
                            $stmt = $pdo->query("SELECT COUNT(DISTINCT grado_id) as total FROM materias");
                            $grados_con_materias = $stmt->fetch()['total'];
                        } catch(PDOException $e) {
                            $total_materias = 0;
                            $grados_con_materias = 0;
                        }
                        ?>
                        <div class="text-center">
                            <h4 class="text-primary"><?php echo $total_materias; ?></h4>
                            <p class="mb-1">Total de materias</p>
                            <h5 class="text-success"><?php echo $grados_con_materias; ?></h5>
                            <p class="mb-0">Grados con materias</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contador de caracteres
            const nombreInput = document.getElementById('nombre_materia');
            const charCount = document.getElementById('char-count');
            
            function updateCharCount() {
                const count = nombreInput.value.length;
                charCount.textContent = count;
                charCount.className = count > 80 ? 'text-warning' : count > 95 ? 'text-danger' : '';
            }
            
            nombreInput.addEventListener('input', updateCharCount);
            updateCharCount(); // Actualizar al cargar
            
            // Auto-completar sugerencias
            const suggestions = [
                'Matemáticas', 'Lengua Castellana', 'Ciencias Naturales', 'Ciencias Sociales',
                'Física', 'Química', 'Biología', 'Historia', 'Geografía', 'Inglés',
                'Educación Física', 'Educación Artística', 'Filosofía', 'Ética y Valores',
                'Tecnología e Informática', 'Religión'
            ];
            
            // Crear datalist para autocompletado
            const datalist = document.createElement('datalist');
            datalist.id = 'materias-suggestions';
            suggestions.forEach(suggestion => {
                const option = document.createElement('option');
                option.value = suggestion;
                datalist.appendChild(option);
            });
            document.body.appendChild(datalist);
            nombreInput.setAttribute('list', 'materias-suggestions');
            
            // Validación en tiempo real
            const form = document.getElementById('createSubjectForm');
            form.addEventListener('submit', function(e) {
                const nombre = nombreInput.value.trim();
                const grado = document.getElementById('grado_id').value;
                
                if (nombre.length < 3) {
                    e.preventDefault();
                    alert('El nombre de la materia debe tener al menos 3 caracteres.');
                    nombreInput.focus();
                    return;
                }
                
                if (!grado) {
                    e.preventDefault();
                    alert('Por favor, selecciona un grado.');
                    document.getElementById('grado_id').focus();
                    return;
                }
                
                // Mostrar indicador de carga
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>
