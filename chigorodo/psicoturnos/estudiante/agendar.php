<?php
require '../includes/auth.php';
requireLogin();
checkRole('estudiante');
include '../includes/db.php';

$psicologos = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'psicologo'")->fetchAll();
$horarios = [];
$selected_psicologo = null;

if (isset($_GET['psicologo_id'])) {
    $selected_psicologo = $_GET['psicologo_id'];
    $stmt = $pdo->prepare("SELECT * FROM horarios WHERE psicologo_id = ? ORDER BY dia_semana, hora_inicio");
    $stmt->execute([$selected_psicologo]);
    $horarios = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Agendar Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-primary">
    <div class="container d-flex justify-content-between align-items-center">
        <span>Psicólogo: <?= $_SESSION['nombre'] ?></span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>
<div class="container mt-4">
    <a href="dashboard.php" class="btn btn-secondary mb-3">
        <span class="me-1">&#8592;</span> Volver al Dashboard
    </a>
    <h3>Agendar Cita con Psicólogo</h3>
    <form method="GET" class="mb-4">
        <label>Selecciona Psicólogo</label>
        <select name="psicologo_id" class="form-control" onchange="this.form.submit()">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($psicologos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selected_psicologo == $p['id'] ? 'selected' : '' ?>>
                    <?= $p['nombre'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($horarios): ?>
        <h5>Horarios Disponibles</h5>
        <form method="POST" action="procesar_cita.php">
            <input type="hidden" name="psicologo_id" value="<?= $selected_psicologo ?>">
            <div class="list-group">
                <?php foreach ($horarios as $h): ?>
                    <label class="list-group-item">
                        <input type="radio" name="fecha" value="2025-04-10 <?= $h['hora_inicio'] ?>" required>
                        <?= ucfirst($h['dia_semana']) ?> - <?= $h['hora_inicio'] ?> a <?= $h['hora_fin'] ?> (<?= $h['tipo'] ?? 'presencial' ?>)
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label>Tipo de cita</label>
                <select name="tipo" class="form-control">
                    <option value="presencial">Presencial</option>
                    <option value="virtual">Virtual (Google Meet)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Confirmar Cita</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>