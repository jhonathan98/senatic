<?php
// Redireccionar a attendance_search.php
// Este archivo existe para mantener compatibilidad con las referencias del dashboard

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: dashboard.php?section=attendance_search" . ($query_string ? '&' . str_replace('section=search_attendance', '', $_SERVER['QUERY_STRING']) : ''));
    exit;
}

// Si se incluye desde el dashboard, incluir el archivo correcto
require_once 'attendance_search.php';
?>
