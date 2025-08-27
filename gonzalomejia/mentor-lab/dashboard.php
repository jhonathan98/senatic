<?php
// dashboard.php - Página principal después de iniciar sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener el nombre del usuario y su rol
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$es_admin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambiar según tu configuración
$password = ""; // Cambiar según tu configuración
$dbname = "sistema_mentorias";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener filtros aplicados
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'all';
    $nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
    
    // Construir la consulta base
    $query = "SELECT * FROM mentores WHERE status = 'activo'";
    $params = [];
    
    // Aplicar filtros
    if ($categoria !== 'all') {
        $query .= " AND especialidad = ?";
        $params[] = $categoria;
    }
    
    if (!empty($nivel)) {
        $query .= " AND nivel_educativo = ?";
        $params[] = $nivel;
    }
    
    $query .= " ORDER BY nombre_completo";
    
    // Preparar y ejecutar la consulta
    $mentores_stmt = $conn->prepare($query);
    $mentores_stmt->execute($params);
    $mentores = $mentores_stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #8a2be2 !important;
        }
        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }
        .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e6e6fa !important;
        }
        .sidebar {
            display: none;
            height: 100vh;
            position: fixed;
            width: 250px;
            top: 0;
            left: 0;
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
            padding-top: 60px;
        }
        .sidebar .nav-link {
            color: #8a2be2 !important;
            padding: 10px 20px;
        }
        .sidebar .nav-link:hover {
            background-color: #f8f4ff;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }
        .btn-solicitar {
            background-color: #8a2be2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-solicitar:hover {
            background-color: #7b1fa2;
        }
        .filter-btn {
            background-color: #e6e6fa;
            color: #8a2be2;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .filter-btn.active {
            background-color: #8a2be2;
            color: white;
        }
        .search-box {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn-search {
            background-color: #8a2be2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        /* Estilos para paquetes y promociones */
        .card.border-primary {
            border-width: 2px;
        }
        .card.border-success {
            border-width: 2px;
        }
        .card.border-warning {
            border-width: 2px;
        }
        .card .card-header {
            padding: 1rem;
        }
        .card .badge {
            position: absolute;
            top: -10px;
            right: 10px;
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        .bg-gradient {
            transition: transform 0.3s ease;
        }
        .bg-gradient:hover {
            transform: translateY(-5px);
        }
        .card-body ul li {
            margin-bottom: 0.8rem;
        }
        .card-body ul li i {
            width: 20px;
            text-align: center;
        }
        .menu-toggle {
            display: none;
            cursor: pointer;
            padding: 10px;
            background-color: #8a2be2;
            color: white;
            border: none;
            border-radius: 5px;
            margin-left: 10px;
        }
        @media (max-width: 768px) {
            .sidebar {
                display: block;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .menu-toggle {
                display: block;
            }
            .navbar-toggler {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-purple">
        <div class="container-fluid">
            <button class="menu-toggle btn" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars text-white"></i>
            </button>
            <a class="navbar-brand" href="#">Sistema de Mentorías</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="paquetes.php">Paquetes y promociones</a>
                    </li>
                    <?php if ($es_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            Administración
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#agregarMentorModal">Agregar Mentor</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#gestionMentoresModal">Gestionar Mentores</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar Menu -->
    <div class="sidebar" id="sidebar">
        <div class="p-4">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="paquetes.php">Paquetes y promociones</a>
                </li>
                <li class="nav-item border-top mt-3 pt-3">
                    <span class="nav-link text-muted">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <!-- Encabezado de Mentores -->

                    <h1 class="mb-4">Mentores Disponibles</h1>
                    
                    <!-- Filters Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form id="filterForm" method="GET" class="d-flex justify-content-between align-items-center">
                                <div class="d-flex flex-wrap">
                                    <button type="button" class="filter-btn <?php echo (!isset($_GET['categoria']) || $_GET['categoria'] === 'all') ? 'active' : ''; ?>" data-filter="all">Todos</button>
                                    <button type="button" class="filter-btn <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === 'matematicas') ? 'active' : ''; ?>" data-filter="matematicas">Matemáticas</button>
                                    <button type="button" class="filter-btn <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === 'idiomas') ? 'active' : ''; ?>" data-filter="idiomas">Idiomas</button>
                                    <button type="button" class="filter-btn <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === 'tecnologia') ? 'active' : ''; ?>" data-filter="tecnologia">Tecnología</button>
                                    <button type="button" class="filter-btn <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === 'ciencias') ? 'active' : ''; ?>" data-filter="ciencias">Ciencias</button>
                                </div>
                                <input type="hidden" name="categoria" id="categoriaInput" value="<?php echo isset($_GET['categoria']) ? htmlspecialchars($_GET['categoria']) : 'all'; ?>">
                                <div class="d-flex align-items-center">
                                    <select name="nivel" class="form-select me-2" style="width: 150px;">
                                        <option value="">Todos los niveles</option>
                                        <option value="primaria" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === 'primaria') ? 'selected' : ''; ?>>Primaria</option>
                                        <option value="secundaria" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === 'secundaria') ? 'selected' : ''; ?>>Secundaria</option>
                                        <option value="preparatoria" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === 'preparatoria') ? 'selected' : ''; ?>>Preparatoria</option>
                                        <option value="tecnica" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === 'tecnica') ? 'selected' : ''; ?>>Técnica</option>
                                        <option value="universidad" <?php echo (isset($_GET['nivel']) && $_GET['nivel'] === 'universidad') ? 'selected' : ''; ?>>Universidad</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Mentors List -->
                    <div class="row">
                        <?php if (isset($mentores) && count($mentores) > 0): ?>
                            <?php foreach ($mentores as $mentor): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <img src="<?php echo htmlspecialchars($mentor['foto_perfil']); ?>" class="img-fluid profile-img" alt="<?php echo htmlspecialchars($mentor['nombre_completo']); ?>">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($mentor['nombre_completo']); ?></h5>
                                                    <p class="card-text"><?php echo htmlspecialchars($mentor['descripcion']); ?></p>
                                                    <p class="card-text"><small class="text-muted">Especialidad: <?php echo htmlspecialchars($mentor['especialidad']); ?></small></p>
                                                    <p class="card-text"><small class="text-muted">Experiencia: <?php echo htmlspecialchars($mentor['experiencia_anios']); ?> años</small></p>
                                                    <button class="btn btn-solicitar" onclick="solicitarMentor('<?php echo htmlspecialchars($mentor['nombre_completo']); ?>')">Solicitar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">No se encontraron mentores disponibles.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for sidebar toggle -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Cerrar sidebar al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.querySelector('.menu-toggle');
            
            if (!sidebar.contains(event.target) && !toggleButton.contains(event.target) && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        function solicitarMentor(mentorName) {
            alert(`Has solicitado a ${mentorName}. El mentor se contactará con usted por correo electrónico para finalizar el proceso de agendamiento.`);
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Actualizar botones activos
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Actualizar el valor del input oculto
                document.getElementById('categoriaInput').value = this.getAttribute('data-filter');
                
                // Enviar el formulario
                document.getElementById('filterForm').submit();
            });
        });
    </script>

    <?php if ($es_admin): ?>
    <!-- Modal Agregar Mentor -->
    <div class="modal fade" id="agregarMentorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nuevo Mentor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAgregarMentor" action="admin/agregar_mentor.php" method="POST" enctype="multipart/form-data">
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
                                <input type="file" class="form-control" name="foto_perfil" accept="image/*" required>
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

    <!-- Modal Gestionar Mentores -->
    <div class="modal fade" id="gestionMentoresModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestionar Mentores</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                            <tbody id="tablaMentores">
                                <!-- Los mentores se cargarán aquí dinámicamente -->
                            </tbody>
                        </table>
                    </div>
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
                    <form id="formEditarMentor" action="admin/actualizar_mentor.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mentor_id" id="edit_mentor_id">
                        <!-- Los campos serán similares al formulario de agregar -->
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
                                    <option value="Matemáticas">Matemáticas</option>
                                    <option value="Idiomas">Idiomas</option>
                                    <option value="Tecnología">Tecnología</option>
                                    <option value="Ciencias">Ciencias</option>
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
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts específicos del dashboard -->
    
    <?php if ($es_admin): ?>
    <script>
    // Función para cargar la lista de mentores en la tabla
    function cargarMentores() {
        fetch('admin/listar_mentores.php')
            .then(response => response.json())
            .then(mentores => {
                const tabla = document.getElementById('tablaMentores');
                tabla.innerHTML = mentores.map(mentor => `
                    <tr>
                        <td><img src="${mentor.foto_perfil}" alt="${mentor.nombre_completo}" class="rounded-circle" width="50" height="50"></td>
                        <td>${mentor.nombre_completo}</td>
                        <td>${mentor.especialidad}</td>
                        <td>${mentor.nivel_educativo}</td>
                        <td>${mentor.experiencia_anios} años</td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                    ${mentor.status === 'activo' ? 'checked' : ''}
                                    onchange="cambiarEstado(${mentor.id}, this.checked)">
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarMentor(${mentor.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarMentor(${mentor.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            });
    }

    // Cargar mentores cuando se abre el modal de gestión
    document.getElementById('gestionMentoresModal').addEventListener('show.bs.modal', cargarMentores);

    // Función para cambiar el estado de un mentor
    function cambiarEstado(mentorId, estado) {
        fetch('admin/cambiar_estado_mentor.php', {
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

    // Función para eliminar un mentor
    function eliminarMentor(mentorId) {
        if (confirm('¿Está seguro de que desea eliminar este mentor?')) {
            fetch('admin/eliminar_mentor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mentor_id: mentorId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cargarMentores();
                }
            });
        }
    }

    // Función para cargar datos de un mentor en el formulario de edición
    function editarMentor(mentorId) {
        fetch(`admin/obtener_mentor.php?id=${mentorId}`)
            .then(response => {
                if (!response.ok) {
                    console.log('Respuesta del servidor no OK:', response);
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error);
                }
                console.log('Datos del mentor obtenidos:', response);
                document.getElementById('edit_mentor_id').value = response.id;
                document.getElementById('edit_nombre_completo').value = response.nombre_completo;
                document.getElementById('edit_correo').value = response.correo;
                document.getElementById('edit_especialidad').value = response.especialidad;
                document.getElementById('edit_nivel_educativo').value = response.nivel_educativo;
                document.getElementById('edit_descripcion').value = response.descripcion;
                document.getElementById('edit_experiencia_anios').value = response.experiencia_anios;
                
                // Mostrar el modal de edición
                new bootstrap.Modal(document.getElementById('editarMentorModal')).show();
            })
            .catch(error => {
                console.error('Error al cargar datos del mentor:', error);
                alert('Error al cargar los datos del mentor. Por favor, intente nuevamente.');
            });
    }
    </script>
    <?php endif; ?>
</body>
</html>