<?php
session_start();

// Destruir todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al inicio
header('Location: ../index.php?mensaje=Sesión cerrada correctamente');
exit();
?>