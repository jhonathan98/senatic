<?php
// api/resource_actions.php
// API para acciones de recursos (eliminar, etc.)

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

require_once '../controllers/ResourceController.php';

$controller = new ResourceController();

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$action = $_POST['action'] ?? '';
$resource_id = $_POST['id'] ?? null;

if (!$resource_id || !is_numeric($resource_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de recurso inválido']);
    exit();
}

switch ($action) {
    case 'delete':
        // Verificar que el recurso no tenga reservas activas
        require_once '../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $check_query = "SELECT COUNT(*) as count FROM reservas WHERE recurso_id = ? AND estado IN ('confirmada', 'pendiente')";
        $stmt = $conn->prepare($check_query);
        $stmt->execute([$resource_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'No se puede eliminar un recurso con reservas activas'
            ]);
            exit();
        }
        
        $response = $controller->destroy($resource_id);
        break;
        
    case 'toggle_status':
        // Cambiar estado activo/inactivo
        $resource = $controller->show($resource_id);
        if (!$resource) {
            echo json_encode(['success' => false, 'message' => 'Recurso no encontrado']);
            exit();
        }
        
        $new_status = !$resource->activo;
        $response = $controller->update($resource_id, [
            'nombre' => $resource->nombre,
            'tipo' => $resource->tipo,
            'descripcion' => $resource->descripcion,
            'capacidad' => $resource->capacidad,
            'caracteristicas' => $resource->caracteristicas,
            'departamento_id' => $resource->departamento_id,
            'activo' => $new_status
        ]);
        
        if ($response['success']) {
            $response['new_status'] = $new_status;
            $response['message'] = $new_status ? 'Recurso activado' : 'Recurso desactivado';
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
