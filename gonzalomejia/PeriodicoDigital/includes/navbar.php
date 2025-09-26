<?php
// Determinar la página actual para resaltar en el menú
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Función para determinar si el enlace está activo
function isActive($page) {
    global $current_page, $current_dir;
    if ($page == 'index.php' && ($current_page == 'index.php' || $current_page == 'inicio.php')) {
        return 'active';
    }
    if ($page == $current_page) {
        return 'active';
    }
    return '';
}

// Ajustar rutas según la ubicación del archivo
$base_path = '';
if ($current_dir == 'vistas') {
    $base_path = '../';
} elseif ($current_dir == 'admin') {
    $base_path = '../../';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $base_path; ?>index.php">
            <i class="bi bi-newspaper"></i> Periódico Digital
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('index.php'); ?>" href="<?php echo $base_path; ?>index.php">Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        Categorías
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/noticias_categoria.php?categoria=1">Cultura</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/noticias_categoria.php?categoria=2">Deportes</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/noticias_categoria.php?categoria=3">Opinión</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/noticias_categoria.php?categoria=4">Eventos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/noticias_categoria.php?categoria=5">Académico</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('noticias_todas.php'); ?>" href="<?php echo $base_path; ?>vistas/noticias_todas.php">Todas las Noticias</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('calendario.php'); ?>" href="<?php echo $base_path; ?>vistas/calendario.php">
                        <i class="bi bi-calendar-event"></i> Calendario de Eventos
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/perfil.php">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a></li>

                            <?php if ($_SESSION['tipo_usuario'] == 'admin' || $_SESSION['tipo_usuario'] == 'redactor'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>vistas/admin/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Panel de Administración
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>includes/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>vistas/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>vistas/registro.php">
                            <i class="bi bi-person-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>