<?php
// models/Resource.php
require_once __DIR__ . '/../config/database.php';

class Resource {
    private $conn;
    private $table_name = "recursos";

    public $id;
    public $nombre;
    public $descripcion;
    public $tipo;
    public $capacidad;
    public $caracteristicas;
    public $departamento_id;
    public $activo;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear recurso
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, descripcion=:descripcion, tipo=:tipo, 
                      capacidad=:capacidad, caracteristicas=:caracteristicas, 
                      departamento_id=:departamento_id, activo=:activo";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->caracteristicas = htmlspecialchars(strip_tags($this->caracteristicas));
        $this->capacidad = $this->capacidad ?? null;
        $this->departamento_id = $this->departamento_id ?? null;
        $this->activo = $this->activo ?? true;

        // Bind values
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":capacidad", $this->capacidad);
        $stmt->bindParam(":caracteristicas", $this->caracteristicas);
        $stmt->bindParam(":departamento_id", $this->departamento_id);
        $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todos los recursos
    public function read() {
        $query = "SELECT r.id, r.nombre, r.descripcion, r.tipo, r.capacidad, r.caracteristicas, r.activo,
                         d.nombre as departamento_nombre
                  FROM " . $this->table_name . " r
                  LEFT JOIN departamentos d ON r.departamento_id = d.id
                  ORDER BY r.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer recursos activos
    public function readActive() {
        $query = "SELECT r.id, r.nombre, r.descripcion, r.tipo, r.capacidad, r.caracteristicas,
                         d.nombre as departamento_nombre
                  FROM " . $this->table_name . " r
                  LEFT JOIN departamentos d ON r.departamento_id = d.id
                  WHERE r.activo = 1
                  ORDER BY r.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer un recurso por ID
    public function readOne() {
        $query = "SELECT r.id, r.nombre, r.descripcion, r.tipo, r.capacidad, r.caracteristicas, 
                         r.departamento_id, r.activo, d.nombre as departamento_nombre
                  FROM " . $this->table_name . " r
                  LEFT JOIN departamentos d ON r.departamento_id = d.id
                  WHERE r.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->tipo = $row['tipo'];
            $this->capacidad = $row['capacidad'];
            $this->caracteristicas = $row['caracteristicas'];
            $this->departamento_id = $row['departamento_id'];
            $this->activo = $row['activo'];
            return true;
        }
        return false;
    }

    // Actualizar recurso
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      descripcion = :descripcion,
                      tipo = :tipo,
                      capacidad = :capacidad,
                      caracteristicas = :caracteristicas,
                      departamento_id = :departamento_id,
                      activo = :activo
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->caracteristicas = htmlspecialchars(strip_tags($this->caracteristicas));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':capacidad', $this->capacidad);
        $stmt->bindParam(':caracteristicas', $this->caracteristicas);
        $stmt->bindParam(':departamento_id', $this->departamento_id);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar recurso
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar disponibilidad del recurso
    public function checkAvailability($fecha_inicio, $fecha_fin, $exclude_reservation_id = null) {
        $query = "SELECT COUNT(*) as conflicts 
                  FROM reservas 
                  WHERE recurso_id = :recurso_id 
                  AND estado IN ('pendiente', 'confirmada')
                  AND (
                      (fecha_inicio <= :fecha_inicio AND fecha_fin > :fecha_inicio) OR
                      (fecha_inicio < :fecha_fin AND fecha_fin >= :fecha_fin) OR
                      (fecha_inicio >= :fecha_inicio AND fecha_fin <= :fecha_fin)
                  )";
        
        if ($exclude_reservation_id) {
            $query .= " AND id != :exclude_reservation_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':recurso_id', $this->id);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        
        if ($exclude_reservation_id) {
            $stmt->bindParam(':exclude_reservation_id', $exclude_reservation_id);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['conflicts'] == 0;
    }

    // Obtener reservas del recurso
    public function getReservations($fecha_inicio = null, $fecha_fin = null) {
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado,
                         u.nombre_completo as usuario_nombre
                  FROM reservas r
                  JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.recurso_id = :recurso_id";
        
        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND ((r.fecha_inicio <= :fecha_fin AND r.fecha_fin >= :fecha_inicio))";
        }
        
        $query .= " ORDER BY r.fecha_inicio";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':recurso_id', $this->id);
        
        if ($fecha_inicio && $fecha_fin) {
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>
