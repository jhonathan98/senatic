<?php
require '../includes/auth.php';
requireLogin();
checkRole('admin');
include '../includes/db.php';

$mensaje = '';

// Guardar horario
if ($_POST) {
    $psicologo_id = $_POST['psicologo_id'];
    $dia = $_POST['dia'];
    $inicio = $_POST['hora_inicio'];
    $fin = $_POST['hora_fin'];

    $stmt = $pdo->prepare("INSERT INTO horarios (psicologo_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$psicologo_id, $dia, $inicio, $fin])) {
        $mensaje = '<div class="alert alert-success">Horario agregado.</div>';
    }
}

// Listar psicólogos
$psicologos = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'psicologo'")->fetchAll();
$horarios = $pdo->query("SELECT h.*, u.nombre as psicologo FROM horarios h JOIN usuarios u ON h.psicologo_id = u.id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Horarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">Horarios de Psicólogos</div>
    </nav>

    <div class="container mt-4">
        <h2>⏰ Gestión de Horarios</h2>
        <?= $mensaje ?>

        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label>Psicólogo</label>
                    <select name="psicologo_id" class="form-control" required>
                        <?php foreach ($psicologos as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Día</label>
                    <select name="dia" class="form-control" required>
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miércoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Fin</label>
                    <input type="time" name="hora_fin" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </div>
            </div>
        </form>

        <h5>Horarios Actuales</h5>
        <table class="table table-bordered">
            <tr><th>Psicólogo</th><th>Día</th><th>Hora</th></tr>
            <?php foreach ($horarios as $h): ?>
                <tr>
                    <td><?= $h['psicologo'] ?></td>
                    <td><?= ucfirst($h['dia_semana']) ?></td>
                    <td><?= $h['hora_inicio'] ?> - <?= $h['hora_fin'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>