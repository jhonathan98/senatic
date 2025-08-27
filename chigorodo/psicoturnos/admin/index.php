<?php
require '../includes/auth.php';
requireLogin();
checkRole('admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Admin - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">Dashboard Administrador</div>
    </nav>

    <div class="container mt-4">
        <h2>Bienvenido, <?= $_SESSION['nombre'] ?> 👨‍💼</h2>
        <p>Desde aquí puedes gestionar usuarios, horarios y ver reportes del bienestar estudiantil.</p>

        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Usuarios</h5>
                        <p>Gestiona estudiantes, psicólogos y administradores</p>
                        <a href="usuarios.php" class="btn btn-light btn-sm">Ir</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Reportes</h5>
                        <p>Estadísticas de bienestar y uso del sistema</p>
                        <a href="reportes.php" class="btn btn-light btn-sm">Ir</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5>Horarios</h5>
                        <p>Configura disponibilidad de psicólogos</p>
                        <a href="horarios.php" class="btn btn-light btn-sm">Ir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>