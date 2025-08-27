<?php require_once __DIR__ . '/auth.php'; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="./index.php">Psicología Educativa</a>
        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-link text-white">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
                <a class="nav-link" href="../logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a class="nav-link" href="login.php">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>