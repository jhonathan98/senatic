<?php
require '../includes/auth.php';
requireLogin();
checkRole('psicologo');
include '../includes/db.php';

$estudiante_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$estudiante_id]);
$estudiante = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->execute([$estudiante_id]);
$cuestionarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Ver Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3>Perfil: <?= $estudiante['nombre'] ?></h3>
    <p><strong>Grado:</strong> <?= $estudiante['grado'] ?> | <strong>Edad:</strong> <?= $estudiante['edad'] ?></p>

    <h5>Cuestionarios</h5>
    <?php foreach ($cuestionarios as $c): ?>
        <div class="card mb-3">
            <div class="card-body">
                <p><?= $c['respuestas'] ?></p>
                <small class="text-muted"><?= $c['fecha'] ?> - <strong><?= ucfirst($c['nivel_urgencia']) ?></strong></small>
            </div>
        </div>
    <?php endforeach; ?>

    <a href="notas.php?id=<?= $estudiante['id'] ?>" class="btn btn-primary">Añadir Notas</a>
</div>
</body>
</html>