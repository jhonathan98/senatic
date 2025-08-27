<?php
require '../includes/auth.php';
requireLogin();
checkRole('admin');
include '../includes/db.php';

// Estadísticas
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'")->fetchColumn();
$total_psicologos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'psicologo'")->fetchColumn();
$citas_pendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'pendiente'")->fetchColumn();

// Nivel de urgencia
$stmt = $pdo->prepare("SELECT nivel_urgencia, COUNT(*) as total FROM cuestionarios GROUP BY nivel_urgencia");
$stmt->execute();
$urgencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Citas por tipo
$stmt = $pdo->prepare("SELECT tipo, COUNT(*) as total FROM citas GROUP BY tipo");
$stmt->execute();
$citas_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reportes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">Panel de Administración</div>
    </nav>

    <div class="container mt-4">
        <h2>📊 Reportes de Bienestar Escolar</h2>

        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5><?= $total_estudiantes ?></h5>
                        <p>Estudiantes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5><?= $total_psicologos ?></h5>
                        <p>Psicólogos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5><?= $citas_pendientes ?></h5>
                        <p>Citas Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5>Niveles de Urgencia Detectados</h5>
                <ul class="list-group">
                    <?php foreach ($urgencias as $u): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= ucfirst($u['nivel_urgencia']) ?></span>
                            <strong><?= $u['total'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Tipo de Citas</h5>
                <ul class="list-group">
                    <?php foreach ($citas_tipo as $c): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= ucfirst($c['tipo']) ?></span>
                            <strong><?= $c['total'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>