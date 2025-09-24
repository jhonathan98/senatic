<?php
session_start();

// Log de actividad si el usuario estaba logueado
if (isset($_SESSION['user'])) {
    require_once 'config.php';
    require_once 'includes/functions.php';
    
    try {
        logActivity($pdo, $_SESSION['user']['id'], 'logout', 'Usuario cerró sesión');
    } catch (Exception $e) {
        // Ignorar errores de logging
        error_log("Error logging logout: " . $e->getMessage());
    }
}

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir la cookie de sesión si está configurada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redireccionar con mensaje
redirect('index.php?message=Sesión cerrada correctamente');
?>