<?php
session_start();
header('Content-Type: application/json');

include_once '../includes/conexion.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para inscribirse']);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$id_evento = (int)($_POST['id_evento'] ?? 0);
$accion = $_POST['accion'] ?? '';
$id_usuario = $_SESSION['usuario_id'];

if ($id_evento <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de evento inválido']);
    exit();
}

try {
    switch ($accion) {
        case 'inscribir':
            // Verificar que el evento existe y esté activo
            $sql_evento = "SELECT * FROM eventos WHERE id = ? AND activo = 1";
            $stmt_evento = mysqli_prepare($conexion, $sql_evento);
            mysqli_stmt_bind_param($stmt_evento, "i", $id_evento);
            mysqli_stmt_execute($stmt_evento);
            $resultado_evento = mysqli_stmt_get_result($stmt_evento);
            
            if (mysqli_num_rows($resultado_evento) == 0) {
                echo json_encode(['success' => false, 'message' => 'El evento no existe o no está disponible']);
                exit();
            }
            
            $evento = mysqli_fetch_assoc($resultado_evento);
            
            // Verificar que el evento esté en estado programado
            if ($evento['estado'] !== 'programado') {
                echo json_encode(['success' => false, 'message' => 'El evento no está disponible para inscripciones']);
                exit();
            }
            
            // Verificar que el evento sea futuro
            if (strtotime($evento['fecha_evento']) < strtotime(date('Y-m-d'))) {
                echo json_encode(['success' => false, 'message' => 'No se puede inscribir a eventos pasados']);
                exit();
            }
            
            // Verificar que el usuario no esté ya inscrito
            $sql_verificar = "SELECT id FROM inscripciones_eventos WHERE id_evento = ? AND id_usuario = ?";
            $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
            mysqli_stmt_bind_param($stmt_verificar, "ii", $id_evento, $id_usuario);
            mysqli_stmt_execute($stmt_verificar);
            $resultado_verificar = mysqli_stmt_get_result($stmt_verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                echo json_encode(['success' => false, 'message' => 'Ya estás inscrito en este evento']);
                exit();
            }
            
            // Verificar cupo máximo si aplica
            if ($evento['cupo_maximo'] > 0) {
                $sql_cupo = "SELECT COUNT(*) as total FROM inscripciones_eventos WHERE id_evento = ?";
                $stmt_cupo = mysqli_prepare($conexion, $sql_cupo);
                mysqli_stmt_bind_param($stmt_cupo, "i", $id_evento);
                mysqli_stmt_execute($stmt_cupo);
                $resultado_cupo = mysqli_stmt_get_result($stmt_cupo);
                $total_inscritos = mysqli_fetch_assoc($resultado_cupo)['total'];
                
                if ($total_inscritos >= $evento['cupo_maximo']) {
                    echo json_encode(['success' => false, 'message' => 'El evento ha alcanzado su cupo máximo']);
                    exit();
                }
                mysqli_stmt_close($stmt_cupo);
            }
            
            // Inscribir al usuario
            $sql_inscribir = "INSERT INTO inscripciones_eventos (id_evento, id_usuario) VALUES (?, ?)";
            $stmt_inscribir = mysqli_prepare($conexion, $sql_inscribir);
            mysqli_stmt_bind_param($stmt_inscribir, "ii", $id_evento, $id_usuario);
            
            if (mysqli_stmt_execute($stmt_inscribir)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Te has inscrito exitosamente al evento: ' . $evento['titulo']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al procesar la inscripción']);
            }
            
            mysqli_stmt_close($stmt_inscribir);
            mysqli_stmt_close($stmt_verificar);
            mysqli_stmt_close($stmt_evento);
            break;
            
        case 'cancelar_inscripcion':
            // Verificar que el usuario esté inscrito
            $sql_verificar = "SELECT * FROM inscripciones_eventos ie 
                             JOIN eventos e ON ie.id_evento = e.id 
                             WHERE ie.id_evento = ? AND ie.id_usuario = ?";
            $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
            mysqli_stmt_bind_param($stmt_verificar, "ii", $id_evento, $id_usuario);
            mysqli_stmt_execute($stmt_verificar);
            $resultado_verificar = mysqli_stmt_get_result($stmt_verificar);
            
            if (mysqli_num_rows($resultado_verificar) == 0) {
                echo json_encode(['success' => false, 'message' => 'No estás inscrito en este evento']);
                exit();
            }
            
            $inscripcion = mysqli_fetch_assoc($resultado_verificar);
            
            // Verificar que el evento no haya comenzado
            if (strtotime($inscripcion['fecha_evento']) <= strtotime(date('Y-m-d'))) {
                echo json_encode(['success' => false, 'message' => 'No se puede cancelar la inscripción de eventos que ya han comenzado']);
                exit();
            }
            
            // Cancelar inscripción
            $sql_cancelar = "DELETE FROM inscripciones_eventos WHERE id_evento = ? AND id_usuario = ?";
            $stmt_cancelar = mysqli_prepare($conexion, $sql_cancelar);
            mysqli_stmt_bind_param($stmt_cancelar, "ii", $id_evento, $id_usuario);
            
            if (mysqli_stmt_execute($stmt_cancelar)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tu inscripción ha sido cancelada exitosamente'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cancelar la inscripción']);
            }
            
            mysqli_stmt_close($stmt_cancelar);
            mysqli_stmt_close($stmt_verificar);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    error_log("Error en procesar_inscripcion.php: " . $e->getMessage());
}

mysqli_close($conexion);
?>
