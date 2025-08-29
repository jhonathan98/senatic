<?php
require_once 'functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user = get_user_by_id($_SESSION['user_id']);
    $user_role = $user['role'];
} else {
    $user_role = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .navbar {
            background-color: #1a1a1a;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: white !important;
        }
        .navbar-nav .nav-link {
            color: #adb5bd !important;
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #fff !important;
        }
        .dropdown-menu {
            background-color: #212529;
            border: none;
            border-radius: 5px;
        }
        .dropdown-item {
            color: #adb5bd;
            transition: color 0.3s ease;
        }
        .dropdown-item:hover {
            color: white;
            background-color: #343a40;
        }
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar {
            background-color: #212529;
            color: white;
            min-height: 100vh;
            padding: 20px 0;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #343a40;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            padding: 10px 20px;
            border-bottom: 1px solid #343a40;
        }
        .sidebar-menu li:hover {
            background-color: #343a40;
        }
        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 0;
        }
        .sidebar-menu li.active a {
            color: #3b71e7;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .menu-toggle {
                display: block;
                cursor: pointer;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">📚 <?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user_role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">Panel Administrador</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown ms-3">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $user['profile_image'] ?: 'assets/images/profile-placeholder.jpg'; ?>" alt="Profile" class="profile-img me-2">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Mi perfil</a></li>
                            <li><a class="dropdown-item" href="my_borrows.php">Mis pedidos</a></li>
                            <li><a class="dropdown-item" href="change_password.php">Cambiar contraseña</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-light" href="login.php">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($user_role === 'admin'): ?>
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>Administración</h4>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="manage_books.php">Gestión de libros</a></li>
                <li><a href="manage_users.php">Gestión de usuarios</a></li>
                <li><a href="manage_borrows.php">Gestión de préstamos</a></li>
                <li><a href="pending_returns.php">Devoluciones pendientes</a></li>
                <li><a href="settings.php">Configuración</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="main-content">