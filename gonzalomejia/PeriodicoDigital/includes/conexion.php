<?php
$servername = "localhost"; // Cambia si tu servidor es diferente
$username = "root";
$password = "";
$dbname = "periodico_digital_db";

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
// Establecer el conjunto de caracteres a UTF-8
$conexion->set_charset("utf8mb4");
?>