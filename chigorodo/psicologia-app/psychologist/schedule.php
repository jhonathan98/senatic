<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/auth.php'; 
requireAuth('psicologo'); 
include '../includes/db.php';

$error = $success = '';

// Registrar nuevo horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha'], $_POST['hora'])) {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    // Validar que no exista ya
    $stmt = $pdo->prepare("SELECT id FROM horarios WHERE psicologo_id = ? AND fecha = ? AND hora = ?");
    $stmt->execute([$_SESSION['user_id'], $fecha, $hora]);

    if ($stmt->rowCount() > 0) {
        $error = "Este horario ya está registrado.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO horarios (psicologo_id, fecha, hora, disponible) VALUES (?, ?, ?, 1)");
        if ($stmt->execute([$_SESSION['user_id'], $fecha, $hora])) {
            $success = "Horario agregado correctamente.";
        } else {
            $error = "Error al registrar el horario.";
        }
    }
}

// Cambiar disponibilidad
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT disponible FROM horarios WHERE id = ? AND psicologo_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $horario = $stmt->fetch();

    if ($horario) {
        $nuevo = $horario['disponible'] ? 0 : 1;
        $pdo->prepare("UPDATE horarios SET disponible = ? WHERE id = ?")->execute([$nuevo, $id]);
        $success = "Disponibilidad actualizada.";
    } else {
        $error = "No tienes permiso para modificar este horario.";
    }
}

// Obtener horarios
$horarios = $pdo->prepare("SELECT * FROM horarios WHERE psicologo_id = ? ORDER BY fecha DESC, hora");
$horarios->execute([$_SESSION['user_id']]);
$horarios = $horarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Horarios - Psicólogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <a href="dashboard.php" class="btn btn-primary">Volver al inicio</a>
        <h3>📅 Gestión de Horarios Disponibles</h3>
        <p>Registra tus bloques de atención y gestiona su disponibilidad.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Formulario para agregar horario -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Agregar nuevo horario</h5>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label>Hora</label>
                            <input type="time" name="hora" class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Agregar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de horarios -->
        <h5>Tus horarios registrados</h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horarios as $h): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                    <td><?= date('H:i', strtotime($h['hora'])) ?></td>
                    <td>
                        <span class="badge bg-<?= $h['disponible'] ? 'success' : 'secondary' ?>">
                            <?= $h['disponible'] ? 'Disponible' : 'No disponible' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle=<?= $h['id'] ?>" class="btn btn-sm <?= $h['disponible'] ? 'btn-warning' : 'btn-success' ?>">
                            <?= $h['disponible'] ? 'Desactivar' : 'Activar' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>