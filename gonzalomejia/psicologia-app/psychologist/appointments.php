<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/auth.php'; 
requireAuth('psicologo'); 
include '../includes/db.php';

$turnos = $pdo->prepare("SELECT t.*, u.nombre as estudiante, u.edad 
                         FROM turnos t 
                         JOIN users u ON t.estudiante_id = u.id 
                         WHERE t.psicologo_id = ? 
                         ORDER BY t.fecha_creacion DESC");
$turnos->execute([$_SESSION['user_id']]);
$turnos = $turnos->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Psicólogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <a href="dashboard.php" class="btn btn-primary">Volver al inicio</a>
        <h3>Mis Citas</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Edad</th>
                    <th>Fecha/Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turnos as $t): ?>
                <tr>
                    <td><?= $t['estudiante'] ?></td>
                    <td><?= $t['edad'] ?></td>
                    <td><?= $t['fecha_creacion'] ?></td>
                    <td><span class="badge bg-<?= $t['estado'] === 'pendiente' ? 'warning' : 'success' ?>">
                        <?= ucfirst($t['estado']) ?>
                    </span></td>
                    <td>
                        <a href="view_appointment.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>