<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Aquí iría la lógica para verificar si el usuario está logueado y obtener su rol
// Por ahora, se simulan variables de sesión
$esta_logueado = isset($_SESSION['user_id']); // Simulación
$rol_usuario = $_SESSION['rol'] ?? null; // Simulación
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario'; // Simulación
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InsumoTrack - <?php echo $pagina_titulo ?? 'Dashboard'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            padding-top: 56px; /* Espacio para el navbar fijo */
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Mejoras para el dropdown del usuario */
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
        }
        
        .dropdown-header {
            background-color: #f8f9fa;
            border-radius: 0.5rem 0.5rem 0 0;
            padding: 0.75rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item.text-danger:hover {
            background-color: #f8d7da;
            color: #721c24 !important;
        }
        
        /* Asegurar que el toggle funcione */
        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        /* Responsive para el nombre de usuario */
        @media (max-width: 768px) {
            .navbar-nav .badge {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">InsumoTrack</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($esta_logueado): ?>
                <ul class="navbar-nav me-auto">
                    <?php if ($rol_usuario === 'user'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/dashboard.php"><i class="bi bi-house me-1"></i>Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/profile.php"><i class="bi bi-person me-1"></i>Mi Perfil</a>
                        </li>
                    <?php elseif ($rol_usuario === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/inventory.php"><i class="bi bi-archive me-1"></i>Inventario</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/loans.php"><i class="bi bi-list-check me-1"></i>Préstamos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/reports.php"><i class="bi bi-file-earmark-bar-graph me-1"></i>Reportes</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                            <i class="bi bi-person-circle me-2" style="font-size: 1.2rem;"></i>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                            <span class="badge bg-secondary ms-2 d-none d-md-inline"><?php echo ucfirst($rol_usuario); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <i class="bi bi-person-circle me-2"></i>
                                <?php echo htmlspecialchars($nombre_usuario); ?>
                                <br>
                                <small class="text-muted"><?php echo ucfirst($rol_usuario); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($rol_usuario === 'user'): ?>
                                <li><a class="dropdown-item" href="../user/profile.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <form method="POST" action="../../functions/auth.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="logout">
                                    <button type="submit" class="dropdown-item text-danger" style="border: none; background: none; width: 100%; text-align: left;">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../views/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Acceder</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">