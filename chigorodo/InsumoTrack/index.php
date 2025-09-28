<?php
// index.php - Página principal de InsumoTrack

// Incluir funciones de autenticación
require_once 'functions/auth.php';

// Verificar si el usuario está logueado
if (estaLogueado()) {
    // Redirigir según el rol del usuario
    $rol = obtenerRolUsuario();
    if ($rol === 'admin') {
        header("Location: views/admin/dashboard.php");
        exit;
    } else {
        header("Location: views/user/dashboard.php");
        exit;
    }
} else {
    // Si no está logueado, redirigir al login
    header("Location: views/login.php");
    exit;
    exit; // Termina aquí para no mostrar el código de abajo si no está logueado
}
?>

}
?>
