<?php
// api/change_reservation_status.php
// API para cambiar el estado de una reserva

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../controllers/ReservationController.php';

try {
    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $reservationController = new ReservationController();
    
    // Validar parámetros requeridos
    $reservation_id = $data['reservation_id'] ?? null;
    $new_status = $data['status'] ?? null;
    
    if (!$reservation_id || !$new_status) {
        throw new Exception('Parámetros requeridos: reservation_id, status');
    }
    
    // Validar estados permitidos
    $valid_statuses = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Estado no válido');
    }
    
    // Verificar permisos: solo admin o el propietario de la reserva pueden cambiar el estado
    $reservation = $reservationController->show($reservation_id);
    if (!$reservation) {
        throw new Exception('Reserva no encontrada');
    }
    
    // Los usuarios regulares solo pueden cancelar sus propias reservas
    if ($_SESSION['user_rol'] !== 'admin' && 
        ($reservation->usuario_id != $_SESSION['user_id'] || $new_status !== 'cancelada')) {
        throw new Exception('No tiene permisos para realizar esta acción');
    }
    
    // Cambiar el estado
    $result = $reservationController->changeStatus($reservation_id, $new_status);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
