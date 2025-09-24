<?php
/**
 * AUTH.PHP - Gestión de autenticación y permisos para Student News
 * Control de sesiones, login, logout y verificación de permisos
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependencias
require_once __DIR__ . '/db.php';

/**
 * Verificar si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

/**
 * Obtener datos del usuario actual
 */
function obtenerUsuarioActual() {
    if (!estaLogueado()) {
        return null;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre_rol 
            FROM usuarios u 
            LEFT JOIN roles r ON u.id_rol = r.id_rol 
            WHERE u.id_usuario = ?
        ");
        $stmt->execute([$_SESSION['id_usuario']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error al obtener usuario actual: " . $e->getMessage());
        return null;
    }
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function tieneRol($roles_permitidos) {
    if (!estaLogueado()) {
        return false;
    }
    
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }
    
    if (is_string($roles_permitidos)) {
        $roles_permitidos = [$roles_permitidos];
    }
    
    return in_array($usuario['nombre_rol'], $roles_permitidos);
}

/**
 * Verificar rol y redirigir si no tiene permisos
 */
function verificarRol($roles_permitidos) {
    if (!tieneRol($roles_permitidos)) {
        header("Location: ../../vistas/publicas/login.php?mensaje=No tienes permisos para acceder a esta página&tipo=danger");
        exit();
    }
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

/**
 * Verificar si el usuario puede crear artículos
 */
function puedeCrearArticulos() {
    return tieneRol(['redactor', 'usuarioRegular', 'administrador']);
}

/**
 * Verificar si el usuario puede moderar contenido
 */
function puedeModerar() {
    return tieneRol(['usuarioRegular', 'administrador']);
}

/**
 * Verificar si el usuario es administrador
 */
function esAdministrador() {
    return tieneRol(['administrador']);
}
?>
