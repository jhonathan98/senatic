<?php
require '../includes/auth.php';
requireLogin();
checkRole('psicologo');
include '../includes/db.php';

if (isset($_GET['accion']) && isset($_GET['id'])) {
    $accion = $_GET['accion'];
    $id = $_GET['id'];

    $estado = $accion === 'confirmar' ? 'confirmada' : 
              ($accion === 'reprogramar' ? 'reprogramada' : 'cancelada');

    $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    header("Location: dashboard.php?msg=accion_ok");
    exit();
}
?>