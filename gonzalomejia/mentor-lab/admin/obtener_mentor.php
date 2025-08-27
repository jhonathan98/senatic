<?php
// Permitir solicitudes desde cualquier origen
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Configuración para mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar errores en un archivo
error_log("Iniciando obtener_mentor.php");

try {
    error_log("Iniciando obtención de mentor...");
    
    // Incluir archivo de conexión
    require_once __DIR__ . '/../config/db.php';
    
    // Obtener conexión
    $conn = getConnection();

    // Verificar si se recibió el ID del mentor
    if (!isset($_GET['id'])) {
        throw new Exception('No se proporcionó el ID del mentor');
    }

    $mentor_id = (int)$_GET['id'];
    error_log("ID del mentor convertido a entero: " . $mentor_id);

    // Preparar la consulta
    $query = "SELECT id, nombre_completo, correo, especialidad, nivel_educativo, descripcion, experiencia_anios, foto_perfil, status 
              FROM mentores 
              WHERE id = :id";
    error_log("Consulta SQL preparada: " . $query);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Error al preparar la consulta: " . implode(" ", $conn->errorInfo()));
        throw new Exception("Error al preparar la consulta SQL");
    }
    
    if (!$stmt->bindParam(':id', $mentor_id, PDO::PARAM_INT)) {
        error_log("Error al vincular parámetro ID");
        throw new Exception("Error al vincular el ID del mentor");
    }
    error_log("Parámetro ID vinculado correctamente: " . $mentor_id);
    
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . implode(" ", $stmt->errorInfo()));
        throw new Exception("Error al ejecutar la consulta SQL");
    }
    error_log("Consulta ejecutada correctamente");
    
    // Obtener el mentor
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Resultado de la consulta: " . ($mentor ? "Mentor encontrado" : "Mentor no encontrado"));
    
    if ($mentor) {
        // Devolver los datos del mentor
        echo json_encode([
            'success' => true,
            'id' => $mentor['id'],
            'nombre_completo' => $mentor['nombre_completo'],
            'correo' => $mentor['correo'],
            'especialidad' => $mentor['especialidad'],
            'nivel_educativo' => $mentor['nivel_educativo'],
            'descripcion' => $mentor['descripcion'],
            'experiencia_anios' => $mentor['experiencia_anios'],
            'foto_perfil' => $mentor['foto_perfil'],
            'status' => $mentor['status']
        ]);
    } else {
        throw new Exception('Mentor no encontrado');
    }
    
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error general',
        'message' => $e->getMessage()
    ]);
}
?>
