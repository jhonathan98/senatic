<?php
// functions/user_functions.php - Funciones para usuarios

// Determinar la ruta correcta para database.php
$database_path = __DIR__ . '/../config/database.php';
if (!file_exists($database_path)) {
    $database_path = dirname(__DIR__) . '/config/database.php';
}

require_once $database_path;

// Función para obtener insumos disponibles
function obtenerInsumosDisponibles() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM insumos WHERE estado = 'Disponible' ORDER BY nombre");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para solicitar préstamo
function solicitarPrestamo($usuario_id, $insumo_id, $fecha_devolucion_prevista, $observaciones = '') {
    global $pdo;
    
    // Verificar que el insumo esté disponible
    $stmt = $pdo->prepare("SELECT estado FROM insumos WHERE id = ?");
    $stmt->execute([$insumo_id]);
    $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$insumo || $insumo['estado'] !== 'Disponible') {
        return ['success' => false, 'message' => 'El insumo no está disponible'];
    }
    
    // Crear solicitud de préstamo
    $stmt = $pdo->prepare("INSERT INTO prestamos (usuario_id, insumo_id, fecha_devolucion_prevista, observaciones) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$usuario_id, $insumo_id, $fecha_devolucion_prevista, $observaciones])) {
        return ['success' => true, 'message' => 'Solicitud de préstamo enviada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al enviar solicitud'];
    }
}

// Función para obtener préstamos del usuario
function obtenerPrestamosUsuario($usuario_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo 
        FROM prestamos p 
        JOIN insumos i ON p.insumo_id = i.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.fecha_solicitud DESC
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para buscar insumos
function buscarInsumos($termino = '') {
    global $pdo;
    if (empty($termino)) {
        return obtenerInsumosDisponibles();
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM insumos 
        WHERE (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ?) 
        AND estado = 'Disponible'
        ORDER BY nombre
    ");
    $termino = "%$termino%";
    $stmt->execute([$termino, $termino, $termino]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener estadísticas del usuario
function obtenerEstadisticasUsuario($usuario_id) {
    global $pdo;
    
    $estadisticas = [];
    
    // Total de préstamos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $estadisticas['total_prestamos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos activos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = ? AND estado IN ('Aprobado', 'Entregado')");
    $stmt->execute([$usuario_id]);
    $estadisticas['prestamos_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = ? AND estado = 'Pendiente'");
    $stmt->execute([$usuario_id]);
    $estadisticas['prestamos_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Préstamos atrasados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = ? AND estado = 'Atrasado'");
    $stmt->execute([$usuario_id]);
    $estadisticas['prestamos_atrasados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $estadisticas;
}

// Función para actualizar perfil de usuario
function actualizarPerfilUsuario($usuario_id, $datos) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET nombre_completo = ?, telefono = ?, institucion_educativa = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([
        $datos['nombre_completo'],
        $datos['telefono'],
        $datos['institucion_educativa'],
        $usuario_id
    ])) {
        // Actualizar datos de sesión
        $_SESSION['nombre'] = $datos['nombre_completo'];
        $_SESSION['institucion'] = $datos['institucion_educativa'];
        
        return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar perfil'];
    }
}

// Procesar acciones AJAX/POST para usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    switch ($action) {
        case 'solicitar_prestamo':
            $resultado = solicitarPrestamo(
                $user_id,
                $_POST['insumo_id'] ?? 0,
                $_POST['fecha_devolucion'] ?? '',
                $_POST['observaciones'] ?? ''
            );
            echo json_encode($resultado);
            break;
            
        case 'buscar_insumos':
            $insumos = buscarInsumos($_POST['termino'] ?? '');
            echo json_encode(['success' => true, 'insumos' => $insumos]);
            break;
            
        case 'actualizar_perfil':
            $datos = [
                'nombre_completo' => $_POST['nombre_completo'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'institucion_educativa' => $_POST['institucion_educativa'] ?? ''
            ];
            $resultado = actualizarPerfilUsuario($user_id, $datos);
            echo json_encode($resultado);
            break;
    }
    exit;
}
?>
