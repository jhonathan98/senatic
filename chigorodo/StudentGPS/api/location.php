<?php
/**
 * API para recibir y procesar ubicaciones de estudiantes
 * Este archivo simula la recepción de datos GPS desde dispositivos móviles
 */

header('Content-Type: application/json');
require_once 'config.php';

// Solo permitir métodos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
if (!isset($input['student_id']) || !isset($input['latitude']) || !isset($input['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos faltantes: student_id, latitude, longitude son requeridos']);
    exit;
}

$student_id = (int)$input['student_id'];
$latitude = (float)$input['latitude'];
$longitude = (float)$input['longitude'];
$api_key = $input['api_key'] ?? '';

// Validar API key (simple validación)
if ($api_key !== 'StudentGPS2024') {
    http_response_code(401);
    echo json_encode(['error' => 'API key inválida']);
    exit;
}

// Validar que el estudiante existe
try {
    $stmt = $pdo->prepare("SELECT id, name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        http_response_code(404);
        echo json_encode(['error' => 'Estudiante no encontrado']);
        exit;
    }
    
    // Validar coordenadas (rangos aproximados para Colombia)
    if ($latitude < -4.5 || $latitude > 13.5 || $longitude < -79 || $longitude < -66) {
        http_response_code(400);
        echo json_encode(['error' => 'Coordenadas fuera del rango válido para Colombia']);
        exit;
    }
    
    // Insertar nueva ubicación
    $stmt = $pdo->prepare("
        INSERT INTO locations (student_id, latitude, longitude, timestamp) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$student_id, $latitude, $longitude]);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Ubicación registrada correctamente',
        'student' => $student['name'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Error guardando ubicación: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
