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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Navbar moderno con glassmorphism */
        .navbar {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 0.8rem 0;
        }
        
        .navbar.scrolled {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95), rgba(118, 75, 162, 0.95));
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }
        
        .navbar-brand .brand-icon {
            margin-right: 10px;
            font-size: 2rem;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
            position: relative;
            overflow: hidden;
        }
        
        .navbar-nav .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            font-weight: 600;
        }
        
        /* Dropdown mejorado */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            animation: dropdownFadeIn 0.3s ease;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            color: #495057;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin: 0.25rem 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-divider {
            margin: 0.5rem 1rem;
            border-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Botón de perfil mejorado */
        .profile-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .profile-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .profile-img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
            margin-right: 8px;
            transition: all 0.3s ease;
        }
        
        .profile-btn:hover .profile-img {
            border-color: rgba(255, 255, 255, 0.6);
            transform: scale(1.1);
        }
        
        .profile-placeholder {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
            margin-right: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Sidebar moderno */
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: -280px;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .sidebar.show {
            left: 0;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 600;
            color: #ecf0f1;
            font-size: 1.3rem;
        }
        
        .sidebar-header .admin-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 0.25rem 1rem;
        }
        
        .sidebar-menu li a {
            color: #bdc3c7;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-menu li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-menu li a:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-menu li.active a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        /* Botón de login moderno */
        .login-btn {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .login-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        /* Notificaciones */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ffa726);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .profile-btn span {
                display: none;
            }
        }
        
        /* Animaciones */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Scrollbar para sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $user_role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'; ?>">
                <i class="fas fa-book-open brand-icon"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <!-- Botones de navegación móvil -->
            <div class="d-flex align-items-center d-lg-none">
                <?php if ($user_role === 'admin'): ?>
                    <button class="sidebar-toggle me-2" type="button" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                <?php endif; ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user_role === 'admin'): ?>
                        <li class="nav-item d-lg-none d-xl-block">
                            <button class="nav-link sidebar-toggle" onclick="toggleSidebar()" style="background: none; border: none;">
                                <i class="fas fa-bars me-2"></i>Panel Admin
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                                <i class="fas fa-chart-line me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-books me-2"></i>Catálogo
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_borrows.php">
                                <i class="fas fa-bookmark me-2"></i>Mis Préstamos
                                <?php 
                                if (isset($_SESSION['user_id'])) {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as active_borrows FROM borrowed_books WHERE user_id = ? AND status = 'active'");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $active_count = $stmt->fetch()['active_borrows'];
                                    if ($active_count > 0) {
                                        echo '<span class="notification-badge">' . $active_count . '</span>';
                                    }
                                }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags me-2"></i>Categorías
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Usuario logueado -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown ms-3">
                        <button class="profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($user['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="profile-img">
                            <?php else: ?>
                                <div class="profile-placeholder">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="d-none d-md-inline">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                                <?php if ($user_role === 'admin'): ?>
                                    <small class="d-block text-light opacity-75">Administrador</small>
                                <?php endif; ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i>Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="my_borrows.php">
                                    <i class="fas fa-book-reader"></i>Mis Préstamos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="change_password.php">
                                    <i class="fas fa-key"></i>Cambiar Contraseña
                                </a>
                            </li>
                            <?php if ($user_role === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="settings.php">
                                        <i class="fas fa-cog"></i>Configuración
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="reports.php">
                                        <i class="fas fa-chart-bar"></i>Reportes
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="login-btn" href="login.php">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Overlay para sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <?php if ($user_role === 'admin'): ?>
        <div class="sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <h4>
                    <i class="fas fa-user-shield me-2"></i>
                    Panel Admin
                </h4>
                <div class="admin-badge">
                    <i class="fas fa-crown me-1"></i>Administrador
                </div>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <a href="admin_dashboard.php">
                        <i class="fas fa-chart-line"></i>Dashboard Principal
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_books.php' ? 'active' : ''; ?>">
                    <a href="manage_books.php">
                        <i class="fas fa-books"></i>Gestión de Libros
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>">
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>Gestión de Usuarios
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_borrows.php' ? 'active' : ''; ?>">
                    <a href="manage_borrows.php">
                        <i class="fas fa-handshake"></i>Gestión de Préstamos
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'pending_returns.php' ? 'active' : ''; ?>">
                    <a href="pending_returns.php">
                        <i class="fas fa-clock"></i>Devoluciones Pendientes
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM borrowed_books WHERE status = 'overdue' OR (status = 'active' AND due_date < CURDATE())");
                        $stmt->execute();
                        $overdue_count = $stmt->fetch()['overdue'];
                        if ($overdue_count > 0) {
                            echo '<span class="notification-badge" style="position: relative; margin-left: auto;">' . $overdue_count . '</span>';
                        }
                        ?>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_categories.php' ? 'active' : ''; ?>">
                    <a href="manage_categories.php">
                        <i class="fas fa-tags"></i>Gestión de Categorías
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>Reportes
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>Configuración
                    </a>
                </li>
                <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="dashboard.php">
                        <i class="fas fa-eye"></i>Ver como Usuario
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="main-content">
    
    <script>
        // Funcionalidad del header mejorado
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto scroll en navbar
            window.addEventListener('scroll', function() {
                const navbar = document.getElementById('mainNavbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        });
        
        // Función para toggle del sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                
                // Prevenir scroll del body cuando el sidebar está abierto
                if (sidebar.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }
        
        // Cerrar sidebar al cambiar el tamaño de pantalla
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                if (sidebar && overlay) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            }
        });
    </script>
    
    <!-- Bootstrap JS debe cargarse antes de otros scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>