<?php
// Determinar la ruta base dependiendo del archivo actual
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_path = '';

if (strpos($current_dir, '/views/admin') !== false) {
    $base_path = '../../';
} elseif (strpos($current_dir, '/views/user') !== false) {
    $base_path = '../../';
} elseif (strpos($current_dir, '/views/public') !== false) {
    $base_path = '../../';
} elseif (strpos($current_dir, '/views') !== false) {
    $base_path = '../';
} elseif (strpos($current_dir, '/auth') !== false) {
    $base_path = '../';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_path; ?>views/dashboard.php">
            <i class="fas fa-calendar-alt me-2"></i>SysPlanner
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>views/dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if ($_SESSION['user_rol'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>Administración
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/admin/manage_resources.php">
                                    <i class="fas fa-cube me-1"></i>Gestionar Recursos
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/admin/manage_users.php">
                                    <i class="fas fa-users me-1"></i>Gestionar Usuarios
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/admin/reports.php">
                                    <i class="fas fa-chart-bar me-1"></i>Reportes
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>views/user/make_reservation.php">
                                <i class="fas fa-plus-circle me-1"></i>Nueva Reserva
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>views/user/view_schedule.php">
                                <i class="fas fa-calendar me-1"></i>Mi Horario
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cube me-1"></i>Recursos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php">
                                <i class="fas fa-eye me-1"></i>Ver Todos
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Por Tipo:</h6></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php?tipo=Aula">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Aulas
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php?tipo=Laboratorio">
                                <i class="fas fa-flask me-1"></i>Laboratorios
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php?tipo=Sala de Sistemas">
                                <i class="fas fa-desktop me-1"></i>Salas de Sistemas
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php?tipo=Auditorio">
                                <i class="fas fa-theater-masks me-1"></i>Auditorios
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/public/schedule_public.php?tipo=Equipo">
                                <i class="fas fa-tools me-1"></i>Equipos
                            </a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>views/profile.php">
                                <i class="fas fa-user-edit me-1"></i>Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>index.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
