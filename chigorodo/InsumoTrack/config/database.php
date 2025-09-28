<?php
// config/database.php

$host = 'localhost'; // Cambia si tu servidor está en otro lugar
$dbname = 'insumo_track_db';
$username = 'root'; // Cambia por tu usuario
$password = ''; // Cambia por tu contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa a la base de datos.";
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>