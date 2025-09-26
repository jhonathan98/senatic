<?php
// config/database.php
// Configuración de la base de datos

class Database {
    private $host = 'localhost';
    private $db_name = 'sysplanner_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Función helper para obtener la conexión de forma rápida
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>
