<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'includes/auth.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Psicología Educativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center">
            <h1>Plataforma de Atención Psicológica</h1>
            <p class="lead">Bienvenido a la gestión de turnos para estudiantes y psicólogos</p>
            <a href="login.php" class="btn btn-primary btn-lg m-2">Iniciar sesión</a>
            <a href="register.php" class="btn btn-outline-primary btn-lg m-2">Registrarse</a>
        </div>
    </div>
</body>
</html>