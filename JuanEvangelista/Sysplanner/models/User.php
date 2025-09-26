<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre_completo;
    public $email;
    public $password_hash;
    public $rol;
    public $departamento_id;
    public $activo;
    public $fecha_registro;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_completo=:nombre_completo, email=:email, password_hash=:password_hash, 
                      rol=:rol, departamento_id=:departamento_id, activo=:activo";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre_completo = htmlspecialchars(strip_tags($this->nombre_completo));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);
        $this->rol = htmlspecialchars(strip_tags($this->rol));
        $this->departamento_id = $this->departamento_id ?? null;
        $this->activo = $this->activo ?? true;

        // Bind values
        $stmt->bindParam(":nombre_completo", $this->nombre_completo);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":departamento_id", $this->departamento_id);
        $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todos los usuarios
    public function read() {
        $query = "SELECT u.id, u.nombre_completo, u.email, u.rol, u.activo, u.fecha_registro,
                         d.nombre as departamento_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN departamentos d ON u.departamento_id = d.id
                  ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer un usuario por ID
    public function readOne() {
        $query = "SELECT u.id, u.nombre_completo, u.email, u.rol, u.departamento_id, u.activo, u.fecha_registro,
                         d.nombre as departamento_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN departamentos d ON u.departamento_id = d.id
                  WHERE u.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nombre_completo = $row['nombre_completo'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->departamento_id = $row['departamento_id'];
            $this->activo = $row['activo'];
            $this->fecha_registro = $row['fecha_registro'];
            return true;
        }
        return false;
    }

    // Actualizar usuario
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre_completo = :nombre_completo,
                      email = :email,
                      rol = :rol,
                      departamento_id = :departamento_id,
                      activo = :activo
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre_completo = htmlspecialchars(strip_tags($this->nombre_completo));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->rol = htmlspecialchars(strip_tags($this->rol));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':nombre_completo', $this->nombre_completo);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':departamento_id', $this->departamento_id);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar usuario
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Autenticar usuario
    public function authenticate($email, $password) {
        $query = "SELECT id, nombre_completo, email, password_hash, rol, departamento_id, activo
                  FROM " . $this->table_name . "
                  WHERE email = ? AND activo = 1
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($password, $row['password_hash'])) {
            $this->id = $row['id'];
            $this->nombre_completo = $row['nombre_completo'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->departamento_id = $row['departamento_id'];
            return true;
        }
        return false;
    }

    // Verificar si email existe
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Cambiar contraseña
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " SET password_hash = ? WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(1, $new_password_hash);
        $stmt->bindParam(2, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
