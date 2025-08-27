<?php
session_start();

function requireAuth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    if ($role && $_SESSION['rol'] !== $role) {
        header("Location: " . ($_SESSION['rol'] === 'estudiante' ? 'student/dashboard.php' : 'psychologist/dashboard.php'));
        exit();
    }
}

function validateEmail($email) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@institucion\.edu$/', $email);
}
?>