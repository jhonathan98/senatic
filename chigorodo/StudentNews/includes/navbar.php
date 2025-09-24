<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
$usuario = obtenerUsuarioActual();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= estaLogueado() ? '../../index.php' : '../publicas/../../index.php' ?>">
            STUDENT NEWS<br>
            <small class="text-light" style="font-size: 0.8rem;">Tu voz, nuestra noticia</small>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (estaLogueado()): ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Panel
                    </a>
                </li>
                <?php if (puedeCrearArticulos()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="articulo_form.php">
                        <i class="bi bi-plus-circle"></i> Crear Artículo
                    </a>
                </li>
                <?php endif; ?>
                <?php if (esAdministrador()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> Administración
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin/dashboard.php">Dashboard Admin</a></li>
                        <li><a class="dropdown-item" href="admin/usuarios.php">Usuarios</a></li>
                        <li><a class="dropdown-item" href="admin/gestion.php">Gestión</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="../../assets/images/uploads/<?= htmlspecialchars($usuario['foto_perfil'] ?? 'default.jpg') ?>" 
                             class="rounded-circle me-1" width="25" height="25" alt="Perfil">
                        <?= htmlspecialchars($usuario['nombre_usuario'] ?? 'Usuario') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="perfil.php">
                            <i class="bi bi-person"></i> Mi Perfil
                        </a></li>
                        <li><a class="dropdown-item" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Mi Panel
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../procesos/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </li>
            </ul>
            <?php else: ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../publicas/login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
