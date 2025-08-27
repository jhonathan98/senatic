<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Mentorías</title>
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
            height: 100vh;
            position: fixed;
            width: 250px;
            top: 0;
            left: 0;
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .main-content.shifted {
            margin-left: 0;
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
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .main-content.shifted {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-purple">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistema de Mentorías</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Paquetes y promociones</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Categorías
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">Matemáticas</a></li>
                            <li><a class="dropdown-item" href="#">Idiomas</a></li>
                            <li><a class="dropdown-item" href="#">Tecnología</a></li>
                            <li><a class="dropdown-item" href="#">Ciencias</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Filtros</a>
                    </li>
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2 search-box" type="search" placeholder="Buscar mentor" aria-label="Search">
                    <button class="btn btn-outline-light btn-search" type="submit">Buscar</button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar Menu -->
    <div class="sidebar" id="sidebar">
        <div class="p-4">
            <h4 class="text-center mb-4">Menú</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Paquetes y promociones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Categorías</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Filtros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="mb-4">Mentores Disponibles</h1>
                    
                    <!-- Filters Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex flex-wrap">
                                    <button class="filter-btn active" data-filter="all">Todos</button>
                                    <button class="filter-btn" data-filter="math">Matemáticas</button>
                                    <button class="filter-btn" data-filter="language">Idiomas</button>
                                    <button class="filter-btn" data-filter="tech">Tecnología</button>
                                    <button class="filter-btn" data-filter="science">Ciencias</button>
                                </div>
                                <div class="d-flex align-items-center">
                                    <select class="form-select me-2" style="width: 150px;">
                                        <option selected>Nivel educativo</option>
                                        <option value="primaria">Primaria</option>
                                        <option value="secundaria">Secundaria</option>
                                        <option value="preparatoria">Preparatoria</option>
                                        <option value="tecnica">Técnica</option>
                                        <option value="universidad">Universidad</option>
                                    </select>
                                    <button class="btn btn-primary">Aplicar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mentors List -->
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="https://via.placeholder.com/120" class="img-fluid profile-img" alt="Licenciado Luis">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title">Licenciado Luis</h5>
                                            <p class="card-text">Especialista en enseñar conceptos numéricos y lógicos, guiando a los estudiantes en la resolución de problemas y el pensamiento crítico.</p>
                                            <p class="card-text"><small class="text-muted">Experiencia: 8 años</small></p>
                                            <button class="btn btn-solicitar" onclick="solicitarMentor('Licenciado Luis')">Solicitar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="https://via.placeholder.com/120" class="img-fluid profile-img" alt="Licenciada Amanda">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title">Licenciada Amanda</h5>
                                            <p class="card-text">Facilita el aprendizaje de nuevas lenguas, enfocándose en la comunicación oral, escrita y la comprensión cultural.</p>
                                            <p class="card-text"><small class="text-muted">Experiencia: 5 años</small></p>
                                            <button class="btn btn-solicitar" onclick="solicitarMentor('Licenciada Amanda')">Solicitar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="https://via.placeholder.com/120" class="img-fluid profile-img" alt="Licenciado Carlos">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title">Licenciado Carlos</h5>
                                            <p class="card-text">Enseña el uso y aplicación de herramientas digitales, programación y soluciones tecnológicas para el mundo actual.</p>
                                            <p class="card-text"><small class="text-muted">Experiencia: 4 años</small></p>
                                            <button class="btn btn-solicitar" onclick="solicitarMentor('Licenciado Carlos')">Solicitar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for sidebar toggle -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.add('shifted');
            } else {
                sidebar.classList.add('collapsed');
                mainContent.classList.remove('shifted');
            }
        }

        function solicitarMentor(mentorName) {
            alert(`Has solicitado a ${mentorName}. El mentor se contactará con usted por correo electrónico para finalizar el proceso de agendamiento.`);
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>