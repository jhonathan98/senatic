<?php
// api/get_filtered_resources.php
// API para obtener recursos filtrados dinámicamente

header('Content-Type: application/json');

require_once '../controllers/ResourceController.php';
require_once '../models/Department.php';

$resourceController = new ResourceController();

// Obtener parámetros
$tipo = $_GET['tipo'] ?? '';
$departamento_id = $_GET['departamento_id'] ?? '';
$include_info = $_GET['include_info'] ?? false;

try {
    // Construir filtros
    $filters = ['activo' => true];
    
    if (!empty($tipo)) {
        $filters['tipo'] = $tipo;
    }
    
    if (!empty($departamento_id) && is_numeric($departamento_id)) {
        $filters['departamento_id'] = $departamento_id;
    }
    
    // Obtener recursos
    $stmt = $resourceController->search($filters);
    $resources = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resource = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'tipo' => $row['tipo'],
            'capacidad' => $row['capacidad'] ? (int)$row['capacidad'] : null,
            'departamento_nombre' => $row['departamento_nombre'] ?? null
        ];
        
        if ($include_info) {
            $resource['descripcion'] = $row['descripcion'] ?? '';
            $resource['caracteristicas'] = $row['caracteristicas'] ?? '';
            $resource['activo'] = (bool)$row['activo'];
        }
        
        $resources[] = $resource;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $resources,
        'count' => count($resources),
        'filters_applied' => [
            'tipo' => $tipo,
            'departamento_id' => $departamento_id
        ]
    ]);
    
} catch (Exception $e) {
    // Error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>
