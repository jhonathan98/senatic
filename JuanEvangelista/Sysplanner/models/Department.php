<?php
// models/Department.php
require_once __DIR__ . '/../config/database.php';

class Department {
    private $conn;
    private $table_name = "departamentos";

    public $id;
    public $nombre;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear departamento
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nombre=:nombre";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));

        // Bind values
        $stmt->bindParam(":nombre", $this->nombre);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todos los departamentos
    public function read() {
        $query = "SELECT id, nombre FROM " . $this->table_name . " ORDER BY nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer un departamento por ID
    public function readOne() {
        $query = "SELECT id, nombre FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nombre = $row['nombre'];
            return true;
        }
        return false;
    }

    // Actualizar departamento
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar departamento
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si el departamento tiene usuarios o recursos asociados
    public function hasAssociatedRecords() {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM usuarios WHERE departamento_id = ?) +
                    (SELECT COUNT(*) FROM recursos WHERE departamento_id = ?) as total";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] > 0;
    }
}
?>
