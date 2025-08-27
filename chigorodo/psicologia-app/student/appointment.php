<?php include '../includes/auth.php'; requireAuth('estudiante'); 
include '../includes/db.php';

$error = $success = '';

// ✅ Verificar si el estudiante ya tiene una cita activa
// Estados activos: 'pendiente', 'completada' (excluye 'cancelada' y 'no asistió')
$stmt = $pdo->prepare("
    SELECT t.id, t.estado, h.fecha, h.hora 
    FROM turnos t
    JOIN horarios h ON t.horario_id = h.id
    WHERE t.estudiante_id = ? 
    AND t.estado IN ('pendiente', 'completada')
");
$stmt->execute([$_SESSION['user_id']]);
$cita_activa = $stmt->fetch();

if ($cita_activa) {
    // Mostrar mensaje en lugar de permitir agendar
    $fecha_hora = date('d/m/Y H:i', strtotime($cita_activa['fecha'] . ' ' . $cita_activa['hora']));
    $estado = $cita_activa['estado'] === 'pendiente' ? 'pendiente' : 'confirmada';
    $error = "Ya tienes una cita $estado programada para el <strong>$fecha_hora</strong>. 
              No puedes agendar otra hasta que esta finalice o sea cancelada.";
}

// ✅ Obtener horarios disponibles solo si no tiene cita activa
$horarios = [];
if (!$cita_activa) {
    $stmt_horarios = $pdo->query("SELECT h.id, h.fecha, h.hora, u.nombre as psicologo 
                                  FROM horarios h 
                                  JOIN users u ON h.psicologo_id = u.id 
                                  WHERE h.disponible = 1 
                                  ORDER BY h.fecha, h.hora");
    $horarios = $stmt_horarios->fetchAll();
}

// ✅ Reservar turno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$cita_activa) {
    $horario_id = (int)$_POST['horario_id'];
    $stmt = $pdo->prepare("SELECT * FROM horarios WHERE id = ? AND disponible = 1");
    $stmt->execute([$horario_id]);
    $horario = $stmt->fetch();

    if (!$horario) {
        $error = "El horario seleccionado ya no está disponible.";
    } else {
        try {
            $pdo->beginTransaction();

            // Crear turno sin formulario
            $stmt = $pdo->prepare("INSERT INTO turnos (estudiante_id, psicologo_id, horario_id, estado) VALUES (?, ?, ?, 'pendiente')");
            $stmt->execute([$_SESSION['user_id'], $horario['psicologo_id'], $horario_id]);

            // Marcar horario como no disponible
            $pdo->prepare("UPDATE horarios SET disponible = 0 WHERE id = ?")->execute([$horario_id]);

            $pdo->commit();

            // Redirigir al formulario para completar
            header("Location: form.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "Error al reservar el turno: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Turno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <h3>📅 Agendar una Nueva Cita</h3>

        <?php if ($error): ?>
            <div class="alert alert-warning"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Mostrar horarios solo si no tiene cita activa -->
        <?php if (!$cita_activa): ?>
            <p class="text-info">Después de seleccionar un turno, deberás completar un breve formulario para confirmarlo.</p>
            <div class="row">
                <?php if (count($horarios) > 0): ?>
                    <?php foreach ($horarios as $h): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6><?= htmlspecialchars($h['psicologo']) ?></h6>
                                <p>
                                    <strong><?= date('d/m/Y', strtotime($h['fecha'])) ?></strong><br>
                                    <?= date('H:i', strtotime($h['hora'])) ?>
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="horario_id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-100">Reservar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No hay horarios disponibles en este momento.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>