<?php
require '../includes/auth.php';
requireLogin();
checkRole('estudiante');
include '../includes/db.php';
include '../includes/functions.php';

if ($_POST) {
    $estudiante_id = $_SESSION['user_id'];
    $psicologo_id = $_POST['psicologo_id'];
    $fecha = $_POST['fecha'];
    $tipo = $_POST['tipo'];
    $enlace = $tipo === 'virtual' ? "https://meet.google.com/xyz-" . rand(1000, 9999) : null;

    $stmt = $pdo->prepare("INSERT INTO citas (estudiante_id, psicologo_id, fecha, tipo, estado, enlace) 
                           VALUES (?, ?, ?, ?, 'pendiente', ?)");
    if ($stmt->execute([$estudiante_id, $psicologo_id, $fecha, $tipo, $enlace])) {
        // Notificación al psicólogo
        $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt->execute([$psicologo_id]);
        $psicologo = $stmt->fetch();
        /*
        enviarNotificacion(
            $psicologo['email'],
            "Nueva cita pendiente",
            "El estudiante {$_SESSION['nombre']} ha solicitado una cita para el $fecha."
        );
*/
        header("Location: dashboard.php?msg=cita_agendada");
        exit();
    }
}
?>