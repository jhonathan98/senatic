<?php
// functions/admin_functions.php - Funciones para administradores

// Determinar la ruta correcta para database.php
$database_path = __DIR__ . '/../config/database.php';
if (!file_exists($database_path)) {
    $database_path = dirname(__DIR__) . '/config/database.php';
}

require_once $database_path;

// Función para obtener solicitudes pendientes
function obtenerSolicitudesPendientes() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre_completo, u.email, u.institucion_educativa,
               i.nombre as insumo_nombre, i.codigo as insumo_codigo
        FROM prestamos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN insumos i ON p.insumo_id = i.id
        WHERE p.estado = 'Pendiente'
        ORDER BY p.fecha_solicitud ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para aprobar préstamo
function aprobarPrestamo($prestamo_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado del préstamo
        $stmt = $pdo->prepare("UPDATE prestamos SET estado = 'Aprobado', fecha_prestamo = CURDATE() WHERE id = ?");
        $stmt->execute([$prestamo_id]);
        
        // Actualizar estado del insumo a 'Prestado'
        $stmt = $pdo->prepare("
            UPDATE insumos i 
            JOIN prestamos p ON i.id = p.insumo_id 
            SET i.estado = 'Prestado' 
            WHERE p.id = ?
        ");
        $stmt->execute([$prestamo_id]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Préstamo aprobado exitosamente'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al aprobar préstamo: ' . $e->getMessage()];
    }
}

// Función para rechazar préstamo
function rechazarPrestamo($prestamo_id, $motivo = '') {
    global $pdo;
    
    $observaciones = empty($motivo) ? 'Solicitud rechazada' : "Rechazado: $motivo";
    
    $stmt = $pdo->prepare("UPDATE prestamos SET estado = 'Rechazado', observaciones = ? WHERE id = ?");
    
    if ($stmt->execute([$observaciones, $prestamo_id])) {
        return ['success' => true, 'message' => 'Préstamo rechazado'];
    } else {
        return ['success' => false, 'message' => 'Error al rechazar préstamo'];
    }
}

// Función para marcar como entregado
function marcarComoEntregado($prestamo_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE prestamos SET estado = 'Entregado' WHERE id = ?");
    
    if ($stmt->execute([$prestamo_id])) {
        return ['success' => true, 'message' => 'Préstamo marcado como entregado'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar estado'];
    }
}

// Función para marcar como devuelto
function marcarComoDevuelto($prestamo_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado del préstamo
        $stmt = $pdo->prepare("UPDATE prestamos SET estado = 'Devuelto', fecha_devolucion_real = CURDATE() WHERE id = ?");
        $stmt->execute([$prestamo_id]);
        
        // Actualizar estado del insumo a 'Disponible'
        $stmt = $pdo->prepare("
            UPDATE insumos i 
            JOIN prestamos p ON i.id = p.insumo_id 
            SET i.estado = 'Disponible' 
            WHERE p.id = ?
        ");
        $stmt->execute([$prestamo_id]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Devolución registrada exitosamente'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al registrar devolución: ' . $e->getMessage()];
    }
}

// Función para obtener todos los préstamos
function obtenerTodosPrestamos($filtro = 'todos') {
    global $pdo;
    
    $where = '';
    switch ($filtro) {
        case 'activos':
            $where = "WHERE p.estado IN ('Aprobado', 'Entregado')";
            break;
        case 'atrasados':
            $where = "WHERE p.estado IN ('Entregado', 'Aprobado') AND p.fecha_devolucion_prevista < CURDATE()";
            break;
        case 'devueltos':
            $where = "WHERE p.estado = 'Devuelto'";
            break;
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre_completo, u.email, u.institucion_educativa,
               i.nombre as insumo_nombre, i.codigo as insumo_codigo
        FROM prestamos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN insumos i ON p.insumo_id = i.id
        $where
        ORDER BY p.fecha_solicitud DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para agregar nuevo insumo
function agregarInsumo($datos) {
    global $pdo;
    
    // Verificar que el código no exista
    $stmt = $pdo->prepare("SELECT id FROM insumos WHERE codigo = ?");
    $stmt->execute([$datos['codigo']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El código del insumo ya existe'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO insumos (codigo, nombre, descripcion, ubicacion, estado) VALUES (?, ?, ?, ?, 'Disponible')");
    
    if ($stmt->execute([
        $datos['codigo'],
        $datos['nombre'],
        $datos['descripcion'],
        $datos['ubicacion']
    ])) {
        return ['success' => true, 'message' => 'Insumo agregado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al agregar insumo'];
    }
}

// Función para obtener todos los insumos
function obtenerTodosInsumos() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM insumos ORDER BY nombre");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para actualizar insumo
function actualizarInsumo($id, $datos) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE insumos SET nombre = ?, descripcion = ?, ubicacion = ?, estado = ? WHERE id = ?");
    
    if ($stmt->execute([
        $datos['nombre'],
        $datos['descripcion'],
        $datos['ubicacion'],
        $datos['estado'],
        $id
    ])) {
        return ['success' => true, 'message' => 'Insumo actualizado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar insumo'];
    }
}

// Función para obtener estadísticas del sistema
function obtenerEstadisticasGeneral() {
    global $pdo;
    
    $estadisticas = [];
    
    // Total insumos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos");
    $estadisticas['total_insumos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Insumos disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'Disponible'");
    $estadisticas['insumos_disponibles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('Aprobado', 'Entregado')");
    $estadisticas['prestamos_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Pendiente'");
    $estadisticas['prestamos_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'user')");
    $estadisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos atrasados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('Entregado', 'Aprobado') AND fecha_devolucion_prevista < CURDATE()");
    $estadisticas['prestamos_atrasados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $estadisticas;
}

// Función para actualizar préstamos atrasados
function actualizarPrestamosAtrasados() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE prestamos 
        SET estado = 'Atrasado' 
        WHERE estado IN ('Entregado', 'Aprobado') 
        AND fecha_devolucion_prevista < CURDATE()
    ");
    
    return $stmt->execute();
}

// Procesar acciones AJAX/POST para administradores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'aprobar_prestamo':
            $resultado = aprobarPrestamo($_POST['prestamo_id'] ?? 0);
            echo json_encode($resultado);
            break;
            
        case 'rechazar_prestamo':
            $resultado = rechazarPrestamo($_POST['prestamo_id'] ?? 0, $_POST['motivo'] ?? '');
            echo json_encode($resultado);
            break;
            
        case 'marcar_entregado':
            $resultado = marcarComoEntregado($_POST['prestamo_id'] ?? 0);
            echo json_encode($resultado);
            break;
            
        case 'marcar_devuelto':
            $resultado = marcarComoDevuelto($_POST['prestamo_id'] ?? 0);
            echo json_encode($resultado);
            break;
            
        case 'agregar_insumo':
            $datos = [
                'codigo' => $_POST['codigo'] ?? '',
                'nombre' => $_POST['nombre'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'ubicacion' => $_POST['ubicacion'] ?? ''
            ];
            $resultado = agregarInsumo($datos);
            echo json_encode($resultado);
            break;
            
        case 'actualizar_insumo':
            $datos = [
                'nombre' => $_POST['nombre'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'ubicacion' => $_POST['ubicacion'] ?? '',
                'estado' => $_POST['estado'] ?? 'Disponible'
            ];
            $resultado = actualizarInsumo($_POST['id'] ?? 0, $datos);
            echo json_encode($resultado);
            break;
    }
    exit;
}
?>
