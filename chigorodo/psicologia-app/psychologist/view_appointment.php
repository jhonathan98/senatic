<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/auth.php'; 
requireAuth('psicologo'); 
include '../includes/db.php';

$appointment_id = $_GET['id'] ?? null;
$error = $success = '';

// Verificar que el turno pertenezca al psicólogo
$stmt = $pdo->prepare("SELECT t.*, u.nombre as estudiante, u.edad 
                       FROM turnos t 
                       JOIN users u ON t.estudiante_id = u.id 
                       WHERE t.id = ? AND t.psicologo_id = ?");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$cita = $stmt->fetch();

if (!$cita) {
    die("Cita no encontrada o no tienes permiso.");
}

// Actualizar estado o notas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notas = trim($_POST['notas'] ?? '');
    $estado = $_POST['estado'] ?? 'pendiente';

    $pdo->prepare("UPDATE turnos SET notas_psicologo = ?, estado = ? WHERE id = ?")
        ->execute([$notas, $estado, $appointment_id]);
    $success = "Información actualizada.";
    // Recargar datos
    $cita['notas_psicologo'] = $notas;
    $cita['estado'] = $estado;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <a href="appointments.php" class="btn btn-primary">Volver a las citas</a>
        <h3>Detalle de la Cita</h3>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5><?= $cita['estudiante'] ?> (<?= $cita['edad'] ?> años)</h5>
            </div>
            <div class="card-body">
                <p><strong>Síntomas:</strong> <?= htmlspecialchars($cita['sintomas']) ?></p>
                <p><strong>Urgencia:</strong> <?= $cita['urgencia'] ?>/5</p>
                <p><strong>¿Ayuda previa?:</strong> <?= $cita['ayuda_previa'] ? 'Sí' : 'No' ?></p>
                <p><strong>Detalles:</strong> <?= nl2br(htmlspecialchars($cita['detalles'] ?? 'Ninguno')) ?></p>
                <p><strong>Fecha de solicitud:</strong> <?= $cita['fecha_creacion'] ?></p>
            </div>
        </div>

        <!-- Formulario de gestión -->
        <form method="POST">
            <div class="mb-3">
                <label>Notas privadas (solo tú las ves)</label>
                <textarea name="notas" class="form-control" rows="5"><?= htmlspecialchars($cita['notas_psicologo'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label>Cambiar estado</label>
                <select name="estado" class="form-control">
                    <option value="pendiente" <?= $cita['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="completada" <?= $cita['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                    <option value="cancelada" <?= $cita['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="no asistió" <?= $cita['estado'] === 'no asistió' ? 'selected' : '' ?>>No asistió</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="appointments.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</body>
</html>