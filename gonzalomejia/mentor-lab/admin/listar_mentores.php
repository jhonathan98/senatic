<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once(__DIR__ . '/../config/db.php');

try {
    // Obtener conexión usando la función
    $conn = getConnection();
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare("SELECT * FROM mentores ORDER BY nombre_completo");
    $stmt->execute();
    $mentores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($mentores);
    
} catch(PDOException $e) {
    error_log("Error en listar_mentores.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener la lista de mentores',
        'error' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("Error general en listar_mentores.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del sistema',
        'error' => $e->getMessage()
    ]);
}
?>
