<?php
require_once 'functions.php';

// Si el usuario está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
} else {
    // Si no está logueado, redirigir al login
    header('Location: login.php');
    exit();
}
?>