<?php
// api/check_availability.php
// API para verificar disponibilidad de recursos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../controllers/ResourceController.php';

try {
    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        // Si no hay JSON, intentar con $_POST
        $data = $_POST;
    }
    
    $resourceController = new ResourceController();
    
    // Validar parámetros requeridos
    $resource_id = $data['resource_id'] ?? null;
    $fecha_inicio = $data['fecha_inicio'] ?? null;
    $fecha_fin = $data['fecha_fin'] ?? null;
    $exclude_reservation_id = $data['exclude_reservation_id'] ?? null;
    
    if (!$resource_id || !$fecha_inicio || !$fecha_fin) {
        throw new Exception('Parámetros requeridos: resource_id, fecha_inicio, fecha_fin');
    }
    
    // Verificar disponibilidad
    $result = $resourceController->checkAvailability($resource_id, $fecha_inicio, $fecha_fin, $exclude_reservation_id);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
