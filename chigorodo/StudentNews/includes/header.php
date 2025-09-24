<?php
// Iniciar sesión solo si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// No incluir auth.php aquí para evitar conflictos
// El usuario debería estar ya logueado cuando se incluye este header
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            STUDENT NEWS
            <small class="d-block text-white" style="font-size: 0.7rem;">Tu voz, nuestra noticia</small>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Categorías</a>
                    <ul class="dropdown-menu">
                        <?php
                        // Obtener categorías desde la BD
                        require_once 'db.php';
                        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria");
                        while ($cat = $stmt->fetch()):
                        ?>
                        <li><a class="dropdown-item" href="categorias.php?id=<?= $cat['id_categoria'] ?>">
                            <i class="<?= $cat['icono'] ?>"></i> <?= $cat['nombre_categoria'] ?>
                        </a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="galeria.php">Multimedia</a></li>
                <li class="nav-item"><a class="nav-link" href="calendario.php">Calendario</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if ($usuario): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($usuario['nombre_usuario']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../vistas/privadas/dashboard.php">Mi Panel</a></li>
                        <li><a class="dropdown-item" href="../vistas/privadas/perfil.php">Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../procesos/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="login.php">Iniciar Sesión</a></li>
                <li class="nav-item"><a class="nav-link" href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>