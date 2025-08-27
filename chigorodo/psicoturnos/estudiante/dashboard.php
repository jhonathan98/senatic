<?php
include '../includes/auth.php';
requireLogin();
checkRole('estudiante');
include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 1");
$stmt->execute([$user_id]);
$cuestionario = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Estudiante - EduBienestar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">EduBienestar (Estudiante)</span>
        <a href="../logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<div class="container mt-4">
    <h3>Hola, <?= $_SESSION['nombre'] ?> 👋</h3>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Cuestionario</h5>
                    <p>Evalúa tu bienestar emocional</p>
                    <a href="cuestionario.php" class="btn btn-primary">Comenzar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Agendar Cita</h5>
                    <p>Con psicólogo escolar</p>
                    <a href="agendar.php" class="btn btn-success">Agendar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Estado</h5>
                    <p>Último cuestionario: 
                        <?= $cuestionario ? ucfirst($cuestionario['nivel_urgencia']) : 'No completado' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>