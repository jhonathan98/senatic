<?php
/**
 * Archivo de configuración de la base de datos
 * Proporciona la función getConnection() para obtener una conexión PDO
 */

function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_mentorias";
    
    try {
        // Crear conexión usando PDO
        $conn = new PDO(
            "mysql:host=$servername;dbname=$dbname;charset=utf8",
            $username,
            $password,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        
        // Configurar el modo de obtención predeterminado
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $conn;
        
    } catch(PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        throw new PDOException("Error de conexión a la base de datos: " . $e->getMessage());
    }
}
?>
