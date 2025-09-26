<?php
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$mensaje = '';
$error = '';

// Manejar el formulario de registro de mentor
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = $_POST['nombre_completo'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $telefono = $_POST['telefono'];
    $telefono_recuperacion = $_POST['telefono_recuperacion'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $especialidad = $_POST['especialidad'];
    $experiencia_anios = $_POST['experiencia_anios'];
    $descripcion = $_POST['descripcion'];
    $nivel_educativo = $_POST['nivel_educativo'];
    
    // Validar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Validar formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor ingrese un correo electrónico válido.";
        } else {
            try {
                $conn = getConnection();
                $conn->beginTransaction();
                
                // Verificar si el correo ya existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo_electronico = ?");
                $stmt->execute([$correo]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Este correo electrónico ya está registrado.";
                } else {
                    // Hash de la contraseña
                    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                    
                    // Insertar nuevo usuario con rol de mentor
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, correo_electronico, contrasena, telefono, telefono_recuperacion, fecha_nacimiento, rol) VALUES (?, ?, ?, ?, ?, ?, 'mentor')");
                    $stmt->execute([$nombre_completo, $correo, $hashed_password, $telefono, $telefono_recuperacion, $fecha_nacimiento]);
                    
                    $usuario_id = $conn->lastInsertId();
                    
                    // Insertar información del mentor
                    $stmt = $conn->prepare("INSERT INTO mentores (usuario_id, nombre_completo, especialidad, experiencia_anios, descripcion, nivel_educativo) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$usuario_id, $nombre_completo, $especialidad, $experiencia_anios, $descripcion, $nivel_educativo]);
                    
                    $conn->commit();
                    $mensaje = "Mentor registrado exitosamente.";
                }
                
            } catch(PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollback();
                }
                $error = "Error de conexión: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Mentor - Sistema de Mentorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #8a2be2 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e6e6fa !important;
        }
        .register-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #8a2be2;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #8a2be2;
            box-shadow: 0 0 0 0.2rem rgba(138, 43, 226, 0.25);
        }
        .btn-register {
            background: linear-gradient(45deg, #8a2be2, #6a5acd);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.4);
        }
        .section-title {
            color: #8a2be2;
            border-bottom: 2px solid #8a2be2;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Sistema de Mentorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="admin_mentores.php">Gestionar Mentores</a>
                <a class="nav-link" href="gestionar_suscripciones.php">Gestionar Suscripciones</a>
                <a class="nav-link" href="../logout.php">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
            <h2>Registrar Nuevo Mentor</h2>
            <p class="text-muted">Complete la información del mentor para agregarlo al sistema</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Información Personal -->
            <h5 class="section-title">
                <i class="fas fa-user me-2"></i>Información Personal
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="correo" class="form-label">Correo Electrónico *</label>
                    <input type="email" class="form-control" id="correo" name="correo" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono *</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono_recuperacion" class="form-label">Teléfono de Recuperación</label>
                    <input type="tel" class="form-control" id="telefono_recuperacion" name="telefono_recuperacion">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                </div>
            </div>

            <!-- Credenciales -->
            <h5 class="section-title mt-4">
                <i class="fas fa-lock me-2"></i>Credenciales de Acceso
            </h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contrasena" class="form-label">Contraseña *</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                    <small class="form-text text-muted">Mínimo 8 caracteres</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña *</label>
                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                </div>
            </div>

            <!-- Información Profesional -->
            <h5 class="section-title mt-4">
                <i class="fas fa-graduation-cap me-2"></i>Información Profesional
            </h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="especialidad" class="form-label">Especialidad *</label>
                    <select class="form-select" id="especialidad" name="especialidad" required>
                        <option value="">Seleccione una especialidad</option>
                        <option value="Matemáticas">Matemáticas</option>
                        <option value="Idiomas">Idiomas</option>
                        <option value="Tecnología">Tecnología</option>
                        <option value="Ciencias">Ciencias</option>
                        <option value="Historia">Historia</option>
                        <option value="Literatura">Literatura</option>
                        <option value="Física">Física</option>
                        <option value="Química">Química</option>
                        <option value="Biología">Biología</option>
                        <option value="Arte">Arte</option>
                        <option value="Música">Música</option>
                        <option value="Programación">Programación</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="experiencia_anios" class="form-label">Años de Experiencia *</label>
                    <input type="number" class="form-control" id="experiencia_anios" name="experiencia_anios" min="0" max="50" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nivel_educativo" class="form-label">Nivel Educativo Principal *</label>
                    <select class="form-select" id="nivel_educativo" name="nivel_educativo" required>
                        <option value="">Seleccione un nivel</option>
                        <option value="primaria">Primaria</option>
                        <option value="secundaria">Secundaria</option>
                        <option value="preparatoria">Preparatoria</option>
                        <option value="tecnica">Técnica</option>
                        <option value="universidad">Universidad</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción del Mentor *</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" placeholder="Describe la experiencia, metodología y enfoque del mentor..." required></textarea>
                <small class="form-text text-muted">Esta descripción será visible para los estudiantes</small>
            </div>

            <!-- Botones -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="admin_mentores.php" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>Registrar Mentor
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de contraseñas en tiempo real
        document.getElementById('confirmar_contrasena').addEventListener('input', function() {
            const password = document.getElementById('contrasena').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Validación de longitud de contraseña
        document.getElementById('contrasena').addEventListener('input', function() {
            if (this.value.length < 8 && this.value.length > 0) {
                this.setCustomValidity('La contraseña debe tener al menos 8 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
