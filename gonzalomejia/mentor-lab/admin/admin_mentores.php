<?php
// admin_mentores.php
session_start();

// Verificar si hay una sesión activa y si es admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistema_mentorias";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener filtros
    $nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
    $especialidad = isset($_GET['especialidad']) ? $_GET['especialidad'] : '';
    $nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
    
    // Construir la consulta base
    $query = "SELECT * FROM mentores WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (!empty($nombre)) {
        $query .= " AND nombre_completo LIKE ?";
        $params[] = "%$nombre%";
    }
    
    if (!empty($especialidad)) {
        $query .= " AND especialidad = ?";
        $params[] = $especialidad;
    }
    
    if (!empty($nivel)) {
        $query .= " AND nivel_educativo = ?";
        $params[] = $nivel;
    }
    
    $query .= " ORDER BY nombre_completo";
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $mentores = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Mentores - Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background-color: #8a2be2 !important;
        }
        .nav-link {
            color: white !important;
        }
        .btn-primary {
            background-color: #8a2be2;
            border-color: #8a2be2;
        }
        .btn-primary:hover {
            background-color: #7b1fa2;
            border-color: #7b1fa2;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Sistema de Mentorías</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_mentores.php">Gestionar Mentores</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Gestión de Mentores</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarMentorModal">
                        <i class="fas fa-plus"></i> Agregar Mentor
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Buscar por nombre</label>
                                <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" placeholder="Nombre del mentor">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Especialidad</label>
                                <select class="form-select" name="especialidad">
                                    <option value="">Todas las especialidades</option>
                                    <option value="matematicas" <?php echo $especialidad === 'matematicas' ? 'selected' : ''; ?>>Matemáticas</option>
                                    <option value="idiomas" <?php echo $especialidad === 'idiomas' ? 'selected' : ''; ?>>Idiomas</option>
                                    <option value="tecnologia" <?php echo $especialidad === 'tecnologia' ? 'selected' : ''; ?>>Tecnología</option>
                                    <option value="ciencias" <?php echo $especialidad === 'ciencias' ? 'selected' : ''; ?>>Ciencias</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nivel Educativo</label>
                                <select class="form-select" name="nivel">
                                    <option value="">Todos los niveles</option>
                                    <option value="primaria" <?php echo $nivel === 'primaria' ? 'selected' : ''; ?>>Primaria</option>
                                    <option value="secundaria" <?php echo $nivel === 'secundaria' ? 'selected' : ''; ?>>Secundaria</option>
                                    <option value="preparatoria" <?php echo $nivel === 'preparatoria' ? 'selected' : ''; ?>>Preparatoria</option>
                                    <option value="tecnica" <?php echo $nivel === 'tecnica' ? 'selected' : ''; ?>>Técnica</option>
                                    <option value="universidad" <?php echo $nivel === 'universidad' ? 'selected' : ''; ?>>Universidad</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Mentores -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nombre</th>
                                        <th>Especialidad</th>
                                        <th>Nivel</th>
                                        <th>Experiencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mentores as $mentor): ?>
                                    <tr>
                                        <td><img src="<?php echo empty($mentor['foto_perfil']) ? 'https://cdn-icons-png.flaticon.com/512/3135/3135768.png' : htmlspecialchars($mentor['foto_perfil']); ?>" alt="<?php echo htmlspecialchars($mentor['nombre_completo']); ?>" class="rounded-circle" width="50" height="50"></td>
                                        <td><?php echo htmlspecialchars($mentor['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($mentor['especialidad']); ?></td>
                                        <td><?php echo htmlspecialchars($mentor['nivel_educativo']); ?></td>
                                        <td><?php echo htmlspecialchars($mentor['experiencia_anios']); ?> años</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                    <?php echo $mentor['status'] === 'activo' ? 'checked' : ''; ?>
                                                    onchange="cambiarEstado(<?php echo $mentor['id']; ?>, this.checked)">
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editarMentor(<?php echo $mentor['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarMentor(<?php echo $mentor['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Mentor -->
    <div class="modal fade" id="agregarMentorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nuevo Mentor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAgregarMentor" action="agregar_mentor.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre_completo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" name="correo" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especialidad</label>
                                <select class="form-select" name="especialidad" required>
                                    <option value="">Seleccione una especialidad</option>
                                    <option value="matematicas">Matemáticas</option>
                                    <option value="idiomas">Idiomas</option>
                                    <option value="tecnologia">Tecnología</option>
                                    <option value="ciencias">Ciencias</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nivel Educativo</label>
                                <select class="form-select" name="nivel_educativo" required>
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
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experiencia (años)</label>
                                <input type="number" class="form-control" name="experiencia_anios" required min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formAgregarMentor" class="btn btn-primary">Guardar Mentor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Mentor -->
    <div class="modal fade" id="editarMentorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Mentor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarMentor" action="actualizar_mentor.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mentor_id" id="edit_mentor_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre_completo" id="edit_nombre_completo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" name="correo" id="edit_correo" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especialidad</label>
                                <select class="form-select" name="especialidad" id="edit_especialidad" required>
                                    <option value="">Seleccione una especialidad</option>
                                    <option value="matematicas">Matemáticas</option>
                                    <option value="idiomas">Idiomas</option>
                                    <option value="tecnologia">Tecnología</option>
                                    <option value="ciencias">Ciencias</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nivel Educativo</label>
                                <select class="form-select" name="nivel_educativo" id="edit_nivel_educativo" required>
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
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experiencia (años)</label>
                                <input type="number" class="form-control" name="experiencia_anios" id="edit_experiencia_anios" required min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                <small class="form-text text-muted">Dejar en blanco para mantener la foto actual</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formEditarMentor" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function cambiarEstado(mentorId, estado) {
        fetch('cambiar_estado_mentor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mentor_id: mentorId,
                status: estado ? 'activo' : 'inactivo'
            })
        });
    }

    function eliminarMentor(mentorId) {
        if (confirm('¿Está seguro de que desea eliminar este mentor?')) {
            fetch('eliminar_mentor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mentor_id: mentorId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }

    function editarMentor(mentorId) {
        fetch(`obtener_mentor.php?id=${mentorId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error);
                }
                
                document.getElementById('edit_mentor_id').value = response.id;
                document.getElementById('edit_nombre_completo').value = response.nombre_completo;
                document.getElementById('edit_correo').value = response.correo;
                document.getElementById('edit_especialidad').value = response.especialidad;
                document.getElementById('edit_nivel_educativo').value = response.nivel_educativo;
                document.getElementById('edit_descripcion').value = response.descripcion;
                document.getElementById('edit_experiencia_anios').value = response.experiencia_anios;
                
                new bootstrap.Modal(document.getElementById('editarMentorModal')).show();
            })
            .catch(error => {
                console.error('Error al cargar datos del mentor:', error);
                alert('Error al cargar los datos del mentor. Por favor, intente nuevamente.');
            });
    }
    </script>
</body>
</html>
