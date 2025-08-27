<?php
require '../includes/auth.php';
requireLogin();
checkRole('estudiante');
include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$edad = $_SESSION['edad'];
$grupo = $edad <= 13 ? '6-13' : '14-18';

if ($_POST) {
    $respuestas = json_encode($_POST['resp']);
    $urgencia = 'bajo';

    // Lógica simple de urgencia
    if (in_array('triste', $_POST['resp']) || in_array('ansioso', $_POST['resp'])) {
        $urgencia = 'alto';
    }

    $stmt = $pdo->prepare("INSERT INTO cuestionarios (usuario_id, edad_grupo, respuestas, nivel_urgencia) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $grupo, $respuestas, $urgencia]);

    header("Location: dashboard.php?msg=cuestionario_exitoso");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Cuestionario - Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3>Cuestionario de Bienestar</h3>
    <form method="POST">
        <?php if ($grupo === '6-13'): ?>
            <p>¿Cómo te sientes hoy?</p>
            <div class="btn-group" role="group" name="resp[]">
                <button type="button" class="btn btn-lg btn-outline-warning">😊 Feliz</button>
                <button type="button" class="btn btn-lg btn-outline-primary">😐 Normal</button>
                <button type="button" class="btn btn-lg btn-outline-danger">😢 Triste</button>
            </div>
            <input type="hidden" name="resp[]" value="normal">
        <?php else: ?>
            <div class="mb-3">
                <label>¿Te sientes estresado últimamente?</label>
                <select name="resp[]" class="form-control">
                    <option value="no">No</option>
                    <option value="a veces">A veces</option>
                    <option value="sí">Sí</option>
                </select>
            </div>
            <div class="mb-3">
                <label>¿Tienes problemas con amigos o en casa?</label>
                <textarea name="resp[]" class="form-control"></textarea>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-success mt-3">Enviar</button>
    </form>
</div>
</body>
</html>