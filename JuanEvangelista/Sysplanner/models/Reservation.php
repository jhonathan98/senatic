<?php
// models/Reservation.php
require_once __DIR__ . '/../config/database.php';

class Reservation {
    private $conn;
    private $table_name = "reservas";

    public $id;
    public $recurso_id;
    public $usuario_id;
    public $fecha_inicio;
    public $fecha_fin;
    public $motivo;
    public $estado;
    public $fecha_creacion;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear reserva
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET recurso_id=:recurso_id, usuario_id=:usuario_id, fecha_inicio=:fecha_inicio, 
                      fecha_fin=:fecha_fin, motivo=:motivo, estado=:estado";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->estado = $this->estado ?? 'confirmada';

        // Bind values
        $stmt->bindParam(":recurso_id", $this->recurso_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":fecha_inicio", $this->fecha_inicio);
        $stmt->bindParam(":fecha_fin", $this->fecha_fin);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":estado", $this->estado);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todas las reservas
    public function read() {
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado, r.fecha_creacion,
                         rec.nombre as recurso_nombre, rec.tipo as recurso_tipo,
                         u.nombre_completo as usuario_nombre, u.email as usuario_email
                  FROM " . $this->table_name . " r
                  JOIN recursos rec ON r.recurso_id = rec.id
                  JOIN usuarios u ON r.usuario_id = u.id
                  ORDER BY r.fecha_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer reservas por usuario
    public function readByUser($usuario_id) {
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado, r.fecha_creacion,
                         rec.nombre as recurso_nombre, rec.tipo as recurso_tipo
                  FROM " . $this->table_name . " r
                  JOIN recursos rec ON r.recurso_id = rec.id
                  WHERE r.usuario_id = ?
                  ORDER BY r.fecha_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario_id);
        $stmt->execute();

        return $stmt;
    }

    // Leer reservas por recurso
    public function readByResource($recurso_id) {
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado, r.fecha_creacion,
                         u.nombre_completo as usuario_nombre, u.email as usuario_email
                  FROM " . $this->table_name . " r
                  JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.recurso_id = ?
                  ORDER BY r.fecha_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $recurso_id);
        $stmt->execute();

        return $stmt;
    }

    // Leer una reserva por ID
    public function readOne() {
        $query = "SELECT r.id, r.recurso_id, r.usuario_id, r.fecha_inicio, r.fecha_fin, r.motivo, 
                         r.estado, r.fecha_creacion,
                         rec.nombre as recurso_nombre, u.nombre_completo as usuario_nombre
                  FROM " . $this->table_name . " r
                  JOIN recursos rec ON r.recurso_id = rec.id
                  JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->recurso_id = $row['recurso_id'];
            $this->usuario_id = $row['usuario_id'];
            $this->fecha_inicio = $row['fecha_inicio'];
            $this->fecha_fin = $row['fecha_fin'];
            $this->motivo = $row['motivo'];
            $this->estado = $row['estado'];
            $this->fecha_creacion = $row['fecha_creacion'];
            return true;
        }
        return false;
    }

    // Actualizar reserva
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET recurso_id = :recurso_id,
                      fecha_inicio = :fecha_inicio,
                      fecha_fin = :fecha_fin,
                      motivo = :motivo,
                      estado = :estado
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':recurso_id', $this->recurso_id);
        $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':motivo', $this->motivo);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar reserva
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Cambiar estado de reserva
    public function changeStatus($new_status) {
        $query = "UPDATE " . $this->table_name . " SET estado = ? WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $new_status);
        $stmt->bindParam(2, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener reservas para calendario (formato JSON)
    public function getCalendarEvents($start_date = null, $end_date = null) {
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado,
                         rec.nombre as recurso_nombre, rec.tipo as recurso_tipo,
                         u.nombre_completo as usuario_nombre
                  FROM " . $this->table_name . " r
                  JOIN recursos rec ON r.recurso_id = rec.id
                  JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.estado IN ('confirmada', 'pendiente')";
        
        if ($start_date && $end_date) {
            $query .= " AND ((r.fecha_inicio <= :end_date AND r.fecha_fin >= :start_date))";
        }
        
        $query .= " ORDER BY r.fecha_inicio";

        $stmt = $this->conn->prepare($query);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        
        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $color = $row['estado'] == 'confirmada' ? '#28a745' : '#ffc107';
            
            $events[] = [
                'id' => $row['id'],
                'title' => $row['recurso_nombre'] . ' - ' . $row['usuario_nombre'],
                'start' => $row['fecha_inicio'],
                'end' => $row['fecha_fin'],
                'description' => $row['motivo'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'recurso' => $row['recurso_nombre'],
                    'tipo' => $row['recurso_tipo'],
                    'usuario' => $row['usuario_nombre'],
                    'estado' => $row['estado'],
                    'motivo' => $row['motivo']
                ]
            ];
        }
        
        return $events;
    }

    // Validar conflictos de horario
    public function hasConflicts() {
        $query = "SELECT COUNT(*) as conflicts 
                  FROM " . $this->table_name . " 
                  WHERE recurso_id = :recurso_id 
                  AND estado IN ('pendiente', 'confirmada')
                  AND id != :id
                  AND (
                      (fecha_inicio <= :fecha_inicio AND fecha_fin > :fecha_inicio) OR
                      (fecha_inicio < :fecha_fin AND fecha_fin >= :fecha_fin) OR
                      (fecha_inicio >= :fecha_inicio AND fecha_fin <= :fecha_fin)
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':recurso_id', $this->recurso_id);
        $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':id', $this->id);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['conflicts'] > 0;
    }
}
?>
