<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$section = $_GET['section'] ?? 'panel';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>STUDENTGPS - Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #0a2e67;
            padding-top: 20px;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #d1d9f0;
            margin-bottom: 8px;
            padding: 10px 15px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #1a4d9f;
            border-radius: 8px;
            color: white;
        }
        .header {
            background-color: #1a4d9f;
            color: white;
            padding: 1rem 0;
            text-align: center;
            position: fixed;
            width: calc(100% - 250px);
            left: 250px;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-left: 40px;
            margin-top: 135px;
            padding: 2rem;
            min-height: 100vh;
            background: #f8f9fc;
        }
       @media (max-width: 992px) {
        .sidebar {
            width: 250px;
            left: -250px;
            position: fixed;
            top: 0;
            height: 100vh;
            z-index: 2000;
            background: #0a2e67;
            transition: left 0.3s;
        }
        .sidebar.active {
            left: 0;
        }
        .header, .main-content {
            width: 100%;
            left: 0;
        }
        .header {
            width: 100%;
            left: 0;
        }
        .main-content {
            margin-left: 0;
            margin-top: 140px;
        }
        .menu-toggle {
            display: block;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 2100;
            background: #1a4d9f;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 1.5rem;
        }
    }
    @media (min-width: 993px) {
        .menu-toggle {
            display: none;
        }
    }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">
        <i class="bi bi-list"></i>
    </button>
    <!-- Encabezado (Navbar) -->
    <div class="header">
        <h1>STUDENTGPS</h1>
        <p class="mb-0">Bienvenido, <?= htmlspecialchars($user['name']) ?> | Rol: <strong><?= ucfirst($role) ?></strong></p>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Menú Lateral -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <h6 class="text-white px-3 mb-3">Menú Principal</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $section === 'panel' ? 'active' : '' ?>" href="?section=panel">
                            <i class="bi bi-speedometer2 me-2"></i> Panel
                        </a>
                    </li>

                    <!-- Registro de Estudiante -->
                    <?php if ($role === 'admin' || $role === 'parent'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $section === 'register_student' ? 'active' : '' ?>" href="?section=register_student">
                                <i class="bi bi-person-plus me-2"></i> Registrar Estudiante
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Registro de Profesor (solo admin) -->
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $section === 'register_teacher' ? 'active' : '' ?>" href="?section=register_teacher">
                                <i class="bi bi-person-workspace me-2"></i> Registrar Profesor
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Registrar Asistencia -->
                    <?php if ($role === 'teacher' || $role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $section === 'attendance_register' ? 'active' : '' ?>" href="?section=attendance_register">
                                <i class="bi bi-calendar-check me-2"></i> Registrar Asistencia
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Ver Asistencia por Fecha -->
                    <?php if ($role === 'teacher' || $role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $section === 'search_attendance' ? 'active' : '' ?>" href="?section=search_attendance">
                                <i class="bi bi-calendar-event me-2"></i> Ver Asistencia por Fecha
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Buscar Estudiante -->
                    <?php if ($role === 'teacher' || $role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $section === 'search_student' ? 'active' : '' ?>" href="?section=search_student">
                                <i class="bi bi-search me-2"></i> Buscar Estudiante
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Mapa -->
                    <li class="nav-item">
                        <a class="nav-link <?= $section === 'map' ? 'active' : '' ?>" href="?section=map">
                            <i class="bi bi-geo-alt me-2"></i> Ubicación en Tiempo Real
                        </a>
                    </li>

                    <!-- Cerrar sesión -->
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Contenido Principal -->
            <main class="main-content">
                <div class="row">
                    <div class="col-2"></div>
                    <div class="col-10">
                        <?php if ($section !== 'panel'): ?>
                            <a href="dashboard.php" class="btn btn-secondary mb-3">
                                <i class="bi bi-arrow-left"></i> Volver al Dashboard
                            </a>
                        <?php endif; ?>
                        <?php
                        // Rutas permitidas
                        $allowed_sections = [
                            'panel',
                            'register_student',
                            'register_teacher',
                            'attendance_register',
                            'attendance_search',
                            'search_attendance',
                            'search_student',
                            'map'
                        ];

                        // Determinar panel por rol
                        if ($section === 'panel') {
                            $file = "panel_{$role}.php";
                            if (file_exists($file)) {
                                include $file;
                            } else {
                                echo "<div class='alert alert-warning'>Panel no disponible para tu rol.</div>";
                            }
                        } elseif (in_array($section, $allowed_sections)) {
                            $file = "{$section}.php";
                            if (file_exists($file)) {
                                include $file;
                            } else {
                                echo "<div class='alert alert-danger'>Módulo no encontrado: $file</div>";
                            }
                        } else {
                            echo "<div class='alert alert-danger'>Sección no válida.</div>";
                        }
                        ?>
                    </div>
                       
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>