<?php
try {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_mentorias";

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit();
}
?>
