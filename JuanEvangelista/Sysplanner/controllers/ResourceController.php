<?php
// controllers/ResourceController.php
require_once __DIR__ . '/../models/Resource.php';
require_once __DIR__ . '/../models/Department.php';

class ResourceController {
    private $resource;
    private $department;

    public function __construct() {
        $this->resource = new Resource();
        $this->department = new Department();
    }

    // Mostrar lista de recursos
    public function index() {
        $stmt = $this->resource->read();
        return $stmt;
    }

    // Mostrar recursos activos
    public function getActive() {
        $stmt = $this->resource->readActive();
        return $stmt;
    }

    // Mostrar formulario de creación
    public function create() {
        $departments = $this->department->read();
        return $departments;
    }

    // Guardar nuevo recurso
    public function store($data) {
        // Validar datos
        if (empty($data['nombre']) || empty($data['tipo'])) {
            return ['success' => false, 'message' => 'El nombre y tipo del recurso son obligatorios.'];
        }

        // Asignar datos
        $this->resource->nombre = $data['nombre'];
        $this->resource->descripcion = $data['descripcion'] ?? '';
        $this->resource->tipo = $data['tipo'];
        $this->resource->capacidad = !empty($data['capacidad']) ? (int)$data['capacidad'] : null;
        $this->resource->caracteristicas = $data['caracteristicas'] ?? '';
        $this->resource->departamento_id = !empty($data['departamento_id']) ? $data['departamento_id'] : null;
        $this->resource->activo = isset($data['activo']) ? (bool)$data['activo'] : true;

        if ($this->resource->create()) {
            return ['success' => true, 'message' => 'Recurso creado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al crear el recurso.'];
        }
    }

    // Mostrar un recurso específico
    public function show($id) {
        $this->resource->id = $id;
        if ($this->resource->readOne()) {
            return $this->resource;
        }
        return null;
    }

    // Mostrar formulario de edición
    public function edit($id) {
        $resource = $this->show($id);
        $departments = $this->department->read();
        
        return ['resource' => $resource, 'departments' => $departments];
    }

    // Actualizar recurso
    public function update($id, $data) {
        // Validar datos
        if (empty($data['nombre']) || empty($data['tipo'])) {
            return ['success' => false, 'message' => 'El nombre y tipo del recurso son obligatorios.'];
        }

        $this->resource->id = $id;
        
        // Asignar datos
        $this->resource->nombre = $data['nombre'];
        $this->resource->descripcion = $data['descripcion'] ?? '';
        $this->resource->tipo = $data['tipo'];
        $this->resource->capacidad = !empty($data['capacidad']) ? (int)$data['capacidad'] : null;
        $this->resource->caracteristicas = $data['caracteristicas'] ?? '';
        $this->resource->departamento_id = !empty($data['departamento_id']) ? $data['departamento_id'] : null;
        $this->resource->activo = isset($data['activo']) ? (bool)$data['activo'] : true;

        if ($this->resource->update()) {
            return ['success' => true, 'message' => 'Recurso actualizado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el recurso.'];
        }
    }

    // Eliminar recurso
    public function destroy($id) {
        $this->resource->id = $id;
        
        if ($this->resource->delete()) {
            return ['success' => true, 'message' => 'Recurso eliminado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar el recurso.'];
        }
    }

    // Verificar disponibilidad
    public function checkAvailability($resource_id, $fecha_inicio, $fecha_fin, $exclude_reservation_id = null) {
        $this->resource->id = $resource_id;
        
        if (!$this->resource->readOne()) {
            return ['success' => false, 'message' => 'Recurso no encontrado.'];
        }

        if (!$this->resource->activo) {
            return ['success' => false, 'message' => 'El recurso no está activo.'];
        }

        $available = $this->resource->checkAvailability($fecha_inicio, $fecha_fin, $exclude_reservation_id);
        
        if ($available) {
            return ['success' => true, 'message' => 'El recurso está disponible.'];
        } else {
            return ['success' => false, 'message' => 'El recurso no está disponible en el horario seleccionado.'];
        }
    }

    // Obtener reservas de un recurso
    public function getReservations($resource_id, $fecha_inicio = null, $fecha_fin = null) {
        $this->resource->id = $resource_id;
        
        if (!$this->resource->readOne()) {
            return null;
        }

        return $this->resource->getReservations($fecha_inicio, $fecha_fin);
    }

    // Obtener tipos de recursos únicos
    public function getResourceTypes() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT DISTINCT tipo FROM recursos WHERE activo = 1 ORDER BY tipo";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar recursos
    public function search($filters) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT r.id, r.nombre, r.descripcion, r.tipo, r.capacidad, r.caracteristicas, r.activo,
                         d.nombre as departamento_nombre
                  FROM recursos r
                  LEFT JOIN departamentos d ON r.departamento_id = d.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['nombre'])) {
            $query .= " AND r.nombre LIKE :nombre";
            $params[':nombre'] = '%' . $filters['nombre'] . '%';
        }
        
        if (!empty($filters['tipo'])) {
            $query .= " AND r.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        
        if (!empty($filters['departamento_id'])) {
            $query .= " AND r.departamento_id = :departamento_id";
            $params[':departamento_id'] = $filters['departamento_id'];
        }
        
        if (isset($filters['activo'])) {
            $query .= " AND r.activo = :activo";
            $params[':activo'] = (bool)$filters['activo'];
        }
        
        $query .= " ORDER BY r.nombre";
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>
