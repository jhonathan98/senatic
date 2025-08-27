<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkRole($role) {
    if ($_SESSION['rol'] !== $role) {
        header("Location: index.php");
        exit();
    }
}
?>