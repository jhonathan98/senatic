<?php include '../includes/auth.php'; requireAuth('estudiante'); 
include '../includes/db.php';

$success = $error = '';

// Verificar si ya completó el formulario
$stmt = $pdo->prepare("SELECT id, estado FROM turnos WHERE estudiante_id = ? AND sintomas IS NOT NULL AND sintomas != '' ORDER BY fecha_creacion DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$completado = $stmt->fetch();

if ($completado) {
    $success = "Ya has completado el formulario para tu cita.";
}

// Verificar si tiene un turno pendiente de formulario
$stmt = $pdo->prepare("SELECT t.id, t.horario_id FROM turnos t WHERE t.estudiante_id = ? AND (t.sintomas IS NULL OR t.sintomas = '') AND t.estado = 'pendiente'");
$stmt->execute([$_SESSION['user_id']]);
$turno_pendiente = $stmt->fetch();

if (!$turno_pendiente && !$completado) {
    header("Location: appointment.php");
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $turno_pendiente) {
    $sintomas = implode(", ", $_POST['sintomas'] ?? []);
    $urgencia = (int)$_POST['urgencia'];
    $ayuda_previa = $_POST['ayuda_previa'] === 'si' ? 1 : 0;
    $detalles = trim($_POST['detalles']);

    if (empty($sintomas) || $urgencia < 1 || $urgencia > 5) {
        $error = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $pdo->prepare("UPDATE turnos 
                           SET sintomas = ?, urgencia = ?, ayuda_previa = ?, detalles = ?
                           WHERE id = ?")->execute([
                $sintomas, $urgencia, $ayuda_previa, $detalles, $turno_pendiente['id']
            ]);
            $success = "✅ Formulario completado. Tu cita ha sido confirmada.";
        } catch (Exception $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Evaluación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <h3>📝 Formulario de Evaluación Inicial</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php if (!$completado): ?>
                <a href="dashboard.php" class="btn btn-primary">Volver al inicio</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($turno_pendiente && !$completado): ?>
            <p><strong>Por favor, completa este formulario para confirmar tu cita.</strong></p>
            <form method="POST">
                <div class="mb-3">
                    <label>Síntomas (seleccione uno o más):</label><br>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="ansiedad" id="ansiedad">
                        <label class="form-check-label" for="ansiedad">Ansiedad</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="depresión" id="depresion">
                        <label class="form-check-label" for="depresion">Depresión</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="estrés académico" id="estres">
                        <label class="form-check-label" for="estres">Estrés académico</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Nivel de urgencia (1-5):</label>
                    <input type="range" name="urgencia" min="1" max="5" class="form-range" value="3" id="urgenciaRange">
                    <output id="urgenciaValue">3</output>
                </div>

                <div class="mb-3">
                    <label>¿Ha recibido ayuda psicológica antes?</label><br>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="ayuda_previa" value="si" required>
                        <label class="form-check-label">Sí</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="ayuda_previa" value="no">
                        <label class="form-check-label">No</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Detalles adicionales:</label>
                    <textarea name="detalles" class="form-control" rows="4" placeholder="Describe cómo te sientes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Confirmar cita</button>
            </form>
        <?php elseif ($completado): ?>
            <p>Tu cita ya está confirmada. Gracias por completar el formulario.</p>
            <a href="dashboard.php" class="btn btn-primary">Volver al inicio</a>
        <?php endif; ?>
    </div>

    <script>
        const range = document.getElementById('urgenciaRange');
        const output = document.getElementById('urgenciaValue');
        output.textContent = range.value;
        range.oninput = () => output.textContent = range.value;
    </script>
</body>
</html>