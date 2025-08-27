<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../config/db.php');
    
    // Obtener datos del body
    $data = json_decode(file_get_contents('php://input'), true);
    $mentor_id = $data['mentor_id'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$mentor_id || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE mentores SET status = ? WHERE id = ?");
        $stmt->execute([$status, $mentor_id]);
        
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
