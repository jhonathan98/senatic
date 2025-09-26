<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/auth.php'; 
requireAuth('estudiante'); 
include '../includes/db.php';


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiante - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container py-5">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h2>
        <!-- Dentro del dashboard.php, después del título -->
        <?php
            $stmt = $pdo->prepare("SELECT t.id FROM turnos t WHERE t.estudiante_id = ? AND (t.sintomas IS NULL OR t.sintomas = '') AND t.estado = 'pendiente'");
            $stmt->execute([$_SESSION['user_id']]);
            $pendiente = $stmt->fetch();

            if ($pendiente): ?>
                <div class="alert alert-warning">
                    Tienes un turno reservado. <a href="form.php" class="alert-link">Completa el formulario</a> para confirmarlo.
                </div>
        <?php endif; ?>
        <?php
        $stmt = $pdo->prepare("
            SELECT t.estado, h.fecha, h.hora 
            FROM turnos t 
            JOIN horarios h ON t.horario_id = h.id 
            WHERE t.estudiante_id = ? 
            AND t.estado IN ('pendiente', 'completada') 
            ORDER BY h.fecha, h.hora 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cita = $stmt->fetch();

        if ($cita):
            $fecha_hora = date('d/m/Y H:i', strtotime($cita['fecha'] . ' ' . $cita['hora']));
            $estado_texto = $cita['estado'] === 'pendiente' ? 'Pendiente de confirmación' : 'Confirmada';
            $color = $cita['estado'] === 'pendiente' ? 'warning' : 'success';
        ?>
            <div class="alert alert-<?= $color ?>">
                <strong>Tu cita está <?= strtolower($estado_texto) ?>.</strong><br>
                Programada para el <?= $fecha_hora ?>.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No tienes citas activas. Puedes agendar una cuando quieras.
            </div>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Agendar turno</h5>
                            <a href="appointment.php" class="btn btn-success">Seleccionar</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>