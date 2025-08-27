<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/auth.php'; 
requireAuth('psicologo'); 
include '../includes/db.php';

// Estadísticas básicas
$total_citas = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE psicologo_id = ?");
$total_citas->execute([$_SESSION['user_id']]);
$total_citas = $total_citas->fetchColumn();

$citas_pendientes = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE psicologo_id = ? AND estado = 'pendiente'");
$citas_pendientes->execute([$_SESSION['user_id']]);
$citas_pendientes = $citas_pendientes->fetchColumn();

$horarios_activos = $pdo->prepare("SELECT COUNT(*) FROM horarios WHERE psicologo_id = ? AND disponible = 1");
$horarios_activos->execute([$_SESSION['user_id']]);
$horarios_activos = $horarios_activos->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Psicólogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <h2>Bienvenido, Dr. <?= htmlspecialchars($_SESSION['nombre']) ?></h2>
        <p class="text-muted">Panel de control de atención psicológica</p>

        <!-- Tarjetas de resumen -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5><i class="fas fa-calendar-check"></i> Total Citas</h5>
                        <p class="fs-3"><?= $total_citas ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5><i class="fas fa-clock"></i> Pendientes</h5>
                        <p class="fs-3"><?= $citas_pendientes ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5><i class="fas fa-bullseye"></i> Horarios Activos</h5>
                        <p class="fs-3"><?= $horarios_activos ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-calendar-plus"></i> Gestionar Horarios</h5>
                        <p>Define tus bloques de atención disponibles.</p>
                        <a href="schedule.php" class="btn btn-outline-primary">Ir a horarios</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-user-clock"></i> Ver Citas</h5>
                        <p>Revisa y gestiona las citas asignadas.</p>
                        <a href="appointments.php" class="btn btn-outline-info">Ver citas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>