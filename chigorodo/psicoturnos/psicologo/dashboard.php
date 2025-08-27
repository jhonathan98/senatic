<?php
require '../includes/auth.php';
requireLogin();
checkRole('psicologo');
include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Estudiantes con alta urgencia
$stmt = $pdo->prepare("SELECT u.*, c.nivel_urgencia, c.fecha 
                       FROM cuestionarios c 
                       JOIN usuarios u ON c.usuario_id = u.id 
                       WHERE c.nivel_urgencia IN ('alto', 'crítico')
                       ORDER BY c.fecha DESC");
$stmt->execute();
$urgentes = $stmt->fetchAll();

// Citas pendientes
$stmt = $pdo->prepare("SELECT * FROM citas WHERE psicologo_id = ? AND estado = 'pendiente'");
$stmt->execute([$user_id]);
$citas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Psicólogo - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-primary">
    <div class="container">Psicólogo: <?= $_SESSION['nombre'] ?></div>
    <a href="../logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h4>Estudiantes Prioritarios</h4>
            <table class="table table-striped">
                <tr><th>Nombre</th><th>Grado</th><th>Urgencia</th><th>Fecha</th><th>Acción</th></tr>
                <?php foreach ($urgentes as $u): ?>
                <tr>
                    <td><?= $u['nombre'] ?></td>
                    <td><?= $u['grado'] ?></td>
                    <td><span class="badge bg-<?= $u['nivel_urgencia'] === 'crítico' ? 'danger' : 'warning' ?>">
                        <?= ucfirst($u['nivel_urgencia']) ?>
                    </span></td>
                    <td><?= date('d/m H:i', strtotime($u['fecha'])) ?></td>
                    <td><a href="ver-estudiante.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">Ver</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="col-md-4">
            <h4>Citas Pendientes</h4>
            <?php foreach ($citas as $c): ?>
                <div class="alert alert-warning">
                    <?= $c['fecha'] ?> - <?= $c['tipo'] ?>
                    <div>
                        <a href="gestion-citas.php?accion=confirmar&id=<?= $c['id'] ?>" class="btn btn-sm btn-success">Aceptar</a>
                        <a href="gestion-citas.php?accion=reprogramar&id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Reprogramar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>