<?php
// auth/login.php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../views/dashboard.php");
    exit();
}

require_once '../controllers/UserController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header("Location: ../index.php?error=" . urlencode("Por favor complete todos los campos"));
        exit();
    }
    
    $userController = new UserController();
    $result = $userController->authenticate($email, $password);
    
    if ($result['success']) {
        header("Location: ../views/dashboard.php");
        exit();
    } else {
        header("Location: ../index.php?error=" . urlencode($result['message']));
        exit();
    }
} else {
    // Si no es POST, redirigir al formulario de login
    header("Location: ../index.php");
    exit();
}
?>
