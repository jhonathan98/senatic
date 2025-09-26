<?php
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

require_once '../config/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$accion = $_POST['accion'] ?? '';
$suscripcion_id = $_POST['suscripcion_id'] ?? '';

try {
    $conn = getConnection();
    
    switch ($accion) {
        case 'cambiar_estado':
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            $estados_validos = ['activa', 'suspendida', 'cancelada', 'vencida'];
            
            if (!in_array($nuevo_estado, $estados_validos)) {
                throw new Exception('Estado no válido');
            }
            
            $query = "UPDATE suscripciones_usuarios SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$nuevo_estado, $suscripcion_id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => "Estado cambiado a '$nuevo_estado' exitosamente"
            ]);
            break;
            
        case 'obtener_detalle':
            $query = "SELECT su.*, u.nombre_completo, u.correo_electronico, u.telefono,
                             pp.nombre as plan_nombre, pp.precio as plan_precio, pp.caracteristicas,
                             COUNT(hp.id) as total_pagos,
                             COALESCE(SUM(CASE WHEN hp.estado = 'exitoso' THEN hp.monto ELSE 0 END), 0) as total_pagado
                      FROM suscripciones_usuarios su
                      JOIN usuarios u ON su.usuario_id = u.id
                      JOIN planes_personalizados pp ON su.plan_id = pp.id
                      LEFT JOIN historial_pagos hp ON su.id = hp.suscripcion_id
                      WHERE su.id = ?
                      GROUP BY su.id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$suscripcion_id]);
            $detalle = $stmt->fetch();
            
            if (!$detalle) {
                throw new Exception('Suscripción no encontrada');
            }
            
            // Obtener historial de pagos
            $pagos_query = "SELECT * FROM historial_pagos WHERE suscripcion_id = ? ORDER BY fecha_pago DESC LIMIT 5";
            $pagos_stmt = $conn->prepare($pagos_query);
            $pagos_stmt->execute([$suscripcion_id]);
            $pagos = $pagos_stmt->fetchAll();
            
            $detalle['historial_pagos'] = $pagos;
            $detalle['caracteristicas'] = json_decode($detalle['caracteristicas'], true);
            
            echo json_encode([
                'success' => true,
                'detalle' => $detalle
            ]);
            break;
            
        case 'suspender_suscripcion':
            $motivo = $_POST['motivo'] ?? 'Suspendido por administrador';
            
            $conn->beginTransaction();
            
            // Cambiar estado a suspendida
            $query = "UPDATE suscripciones_usuarios SET estado = 'suspendida', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$suscripcion_id]);
            
            // Registrar la acción (podrías crear una tabla de logs)
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Suscripción suspendida exitosamente'
            ]);
            break;
            
        case 'reactivar_suscripcion':
            $query = "UPDATE suscripciones_usuarios SET estado = 'activa', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$suscripcion_id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Suscripción reactivada exitosamente'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
