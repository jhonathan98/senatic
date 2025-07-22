<?php
require_once 'functions.php';

// Cerrar sesión
logoutUser();

// Redirigir al login con mensaje
header('Location: login.php?logout=1');
exit();
?>