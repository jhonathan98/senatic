<?php
// api/calendar_events.php
// API para obtener eventos del calendario

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../controllers/ReservationController.php';

try {
    $reservationController = new ReservationController();
    
    // Obtener parámetros
    $start_date = $_GET['start'] ?? null;
    $end_date = $_GET['end'] ?? null;
    $resource_id = !empty($_GET['resource_id']) ? $_GET['resource_id'] : null;
    
    // Validar fechas
    if (!$start_date || !$end_date) {
        throw new Exception('Fechas de inicio y fin son requeridas');
    }
    
    // Obtener eventos
    $events = $reservationController->getCalendarEvents($start_date, $end_date);
    
    // Filtrar por recurso si se especifica
    if ($resource_id) {
        $events = array_filter($events, function($event) use ($resource_id) {
            // Aquí necesitarías agregar el resource_id a los eventos en el método getCalendarEvents
            return true; // Por ahora devolvemos todos
        });
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
