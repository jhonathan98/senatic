<?php
// controllers/ReservationController.php
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Resource.php';
require_once __DIR__ . '/../models/User.php';

class ReservationController {
    private $reservation;
    private $resource;
    private $user;

    public function __construct() {
        $this->reservation = new Reservation();
        $this->resource = new Resource();
        $this->user = new User();
    }

    // Mostrar lista de reservas
    public function index() {
        $stmt = $this->reservation->read();
        return $stmt;
    }

    // Mostrar reservas por usuario
    public function getUserReservations($usuario_id) {
        return $this->reservation->readByUser($usuario_id);
    }

    // Mostrar reservas por recurso
    public function getResourceReservations($recurso_id) {
        return $this->reservation->readByResource($recurso_id);
    }

    // Mostrar formulario de creación
    public function create() {
        $resources = $this->resource->readActive();
        return $resources;
    }

    // Guardar nueva reserva
    public function store($data) {
        // Validar datos
        if (empty($data['recurso_id']) || empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
            return ['success' => false, 'message' => 'Recurso, fecha de inicio y fecha de fin son obligatorios.'];
        }

        // Validar fechas
        $fecha_inicio = new DateTime($data['fecha_inicio']);
        $fecha_fin = new DateTime($data['fecha_fin']);
        $now = new DateTime();

        if ($fecha_inicio < $now) {
            return ['success' => false, 'message' => 'La fecha de inicio no puede ser en el pasado.'];
        }

        if ($fecha_fin <= $fecha_inicio) {
            return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio.'];
        }

        // Verificar disponibilidad del recurso
        $this->resource->id = $data['recurso_id'];
        if (!$this->resource->readOne()) {
            return ['success' => false, 'message' => 'El recurso seleccionado no existe.'];
        }

        if (!$this->resource->activo) {
            return ['success' => false, 'message' => 'El recurso seleccionado no está activo.'];
        }

        if (!$this->resource->checkAvailability($data['fecha_inicio'], $data['fecha_fin'])) {
            return ['success' => false, 'message' => 'El recurso no está disponible en el horario seleccionado.'];
        }

        // Asignar datos
        $this->reservation->recurso_id = $data['recurso_id'];
        $this->reservation->usuario_id = $data['usuario_id'];
        $this->reservation->fecha_inicio = $data['fecha_inicio'];
        $this->reservation->fecha_fin = $data['fecha_fin'];
        $this->reservation->motivo = $data['motivo'] ?? '';
        $this->reservation->estado = $data['estado'] ?? 'confirmada';

        if ($this->reservation->create()) {
            return ['success' => true, 'message' => 'Reserva creada exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al crear la reserva.'];
        }
    }

    // Mostrar una reserva específica
    public function show($id) {
        $this->reservation->id = $id;
        if ($this->reservation->readOne()) {
            return $this->reservation;
        }
        return null;
    }

    // Mostrar formulario de edición
    public function edit($id) {
        $reservation = $this->show($id);
        $resources = $this->resource->readActive();
        
        return ['reservation' => $reservation, 'resources' => $resources];
    }

    // Actualizar reserva
    public function update($id, $data) {
        // Validar datos
        if (empty($data['recurso_id']) || empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
            return ['success' => false, 'message' => 'Recurso, fecha de inicio y fecha de fin son obligatorios.'];
        }

        // Validar fechas
        $fecha_inicio = new DateTime($data['fecha_inicio']);
        $fecha_fin = new DateTime($data['fecha_fin']);

        if ($fecha_fin <= $fecha_inicio) {
            return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio.'];
        }

        // Verificar disponibilidad del recurso (excluyendo la reserva actual)
        $this->resource->id = $data['recurso_id'];
        if (!$this->resource->readOne()) {
            return ['success' => false, 'message' => 'El recurso seleccionado no existe.'];
        }

        if (!$this->resource->checkAvailability($data['fecha_inicio'], $data['fecha_fin'], $id)) {
            return ['success' => false, 'message' => 'El recurso no está disponible en el horario seleccionado.'];
        }

        $this->reservation->id = $id;
        
        // Asignar datos
        $this->reservation->recurso_id = $data['recurso_id'];
        $this->reservation->fecha_inicio = $data['fecha_inicio'];
        $this->reservation->fecha_fin = $data['fecha_fin'];
        $this->reservation->motivo = $data['motivo'] ?? '';
        $this->reservation->estado = $data['estado'] ?? 'confirmada';

        if ($this->reservation->update()) {
            return ['success' => true, 'message' => 'Reserva actualizada exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la reserva.'];
        }
    }

    // Eliminar reserva
    public function destroy($id) {
        $this->reservation->id = $id;
        
        if ($this->reservation->delete()) {
            return ['success' => true, 'message' => 'Reserva eliminada exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar la reserva.'];
        }
    }

    // Cambiar estado de reserva
    public function changeStatus($id, $new_status) {
        $valid_statuses = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        
        if (!in_array($new_status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Estado no válido.'];
        }

        $this->reservation->id = $id;
        
        if ($this->reservation->changeStatus($new_status)) {
            return ['success' => true, 'message' => 'Estado de la reserva actualizado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el estado de la reserva.'];
        }
    }

    // Obtener eventos para calendario
    public function getCalendarEvents($start_date = null, $end_date = null) {
        return $this->reservation->getCalendarEvents($start_date, $end_date);
    }

    // Verificar disponibilidad de recurso en un rango de fechas
    public function checkResourceAvailability($resource_id, $fecha_inicio, $fecha_fin) {
        $this->resource->id = $resource_id;
        
        if (!$this->resource->readOne()) {
            return ['success' => false, 'message' => 'Recurso no encontrado.'];
        }

        if (!$this->resource->activo) {
            return ['success' => false, 'message' => 'El recurso no está activo.'];
        }

        $available = $this->resource->checkAvailability($fecha_inicio, $fecha_fin);
        
        return [
            'success' => true,
            'available' => $available,
            'resource' => [
                'id' => $this->resource->id,
                'nombre' => $this->resource->nombre,
                'tipo' => $this->resource->tipo,
                'capacidad' => $this->resource->capacidad
            ]
        ];
    }

    // Obtener reservas próximas (para dashboard)
    public function getUpcomingReservations($limit = 5) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.estado,
                         rec.nombre as recurso_nombre, u.nombre_completo as usuario_nombre
                  FROM reservas r
                  JOIN recursos rec ON r.recurso_id = rec.id
                  JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.fecha_inicio > NOW() AND r.estado IN ('confirmada', 'pendiente')
                  ORDER BY r.fecha_inicio ASC
                  LIMIT ?";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtener estadísticas de reservas
    public function getStats() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Total de reservas
        $query = "SELECT COUNT(*) as total FROM reservas";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Reservas por estado
        $query = "SELECT estado, COUNT(*) as count FROM reservas GROUP BY estado";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['por_estado'][$row['estado']] = $row['count'];
        }
        
        // Reservas del mes actual
        $query = "SELECT COUNT(*) as count FROM reservas WHERE MONTH(fecha_inicio) = MONTH(NOW()) AND YEAR(fecha_inicio) = YEAR(NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['mes_actual'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
}
?>
