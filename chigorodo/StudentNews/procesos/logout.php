<?php
/**
 * LOGOUT.PHP - Cierra la sesión del usuario
 */

session_start();

// Verificar si hay una sesión activa
if (isset($_SESSION['id_usuario'])) {
    // Guardar el nombre del usuario para el mensaje de despedida
    $nombre_usuario = $_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';
    
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Iniciar nueva sesión para el mensaje
    session_start();
    $_SESSION['mensaje'] = "¡Hasta pronto, " . htmlspecialchars($nombre_usuario) . "! Has cerrado sesión correctamente.";
    $_SESSION['tipo_mensaje'] = 'success';
}

// Redirigir al login
header('Location: ../vistas/publicas/login.php');
exit();
?>
