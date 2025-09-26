<?php
// auth/logout.php
session_start();

require_once '../controllers/UserController.php';

$userController = new UserController();
$userController->logout();

header("Location: ../index.php?message=" . urlencode("Sesión cerrada exitosamente"));
exit();
?>
