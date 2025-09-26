<?php
// api/resource_schedule.php
// API para obtener el horario de un recurso específico

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../controllers/ResourceController.php';

try {
    $resourceController = new ResourceController();
    
    // Obtener parámetros
    $resource_id = $_GET['resource_id'] ?? null;
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Validar parámetros
    if (!$resource_id) {
        throw new Exception('ID del recurso es requerido');
    }
    
    // Validar que el recurso existe
    $resource = $resourceController->show($resource_id);
    if (!$resource) {
        throw new Exception('Recurso no encontrado');
    }
    
    // Obtener reservas del día
    $fecha_inicio = $date . ' 00:00:00';
    $fecha_fin = $date . ' 23:59:59';
    
    $reservations_stmt = $resourceController->getReservations($resource_id, $fecha_inicio, $fecha_fin);
    $reservations = [];
    
    if ($reservations_stmt) {
        while ($reservation = $reservations_stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservations[] = $reservation;
        }
    }
    
    echo json_encode($reservations);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
