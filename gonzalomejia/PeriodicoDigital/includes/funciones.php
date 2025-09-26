<?php
// Funciones auxiliares para el sistema

/**
 * Función para limpiar y validar entrada de datos
 */
function limpiar_entrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Función para generar nombre único de archivo
 */
function generar_nombre_archivo($extension) {
    return time() . '_' . uniqid() . '.' . $extension;
}

/**
 * Función para validar tipo de archivo de imagen
 */
function validar_imagen($archivo) {
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    $tipo = $archivo['type'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    return in_array($tipo, $tipos_permitidos) && in_array($extension, $extensiones_permitidas);
}

/**
 * Función para redimensionar imagen (opcional para futuros desarrollos)
 */
function redimensionar_imagen($archivo_origen, $archivo_destino, $ancho_max = 800, $alto_max = 600) {
    // Esta función se puede implementar para optimizar imágenes
    // Por ahora solo copiamos el archivo
    return copy($archivo_origen, $archivo_destino);
}

/**
 * Función para formatear fecha en español
 */
function formatear_fecha($fecha, $formato = 'd/m/Y') {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $mes_numero = date('n', $timestamp);
    $mes_nombre = $meses[$mes_numero];
    
    if ($formato == 'completa') {
        return date('d', $timestamp) . ' de ' . $mes_nombre . ' de ' . date('Y', $timestamp);
    }
    
    return date($formato, $timestamp);
}

/**
 * Función para truncar texto
 */
function truncar_texto($texto, $longitud = 100, $sufijo = '...') {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    
    return substr($texto, 0, $longitud) . $sufijo;
}

/**
 * Función para verificar permisos de usuario
 */
function verificar_permisos($tipos_permitidos = []) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    if (empty($tipos_permitidos)) {
        return true; // Solo requiere estar logueado
    }
    
    return in_array($_SESSION['tipo_usuario'], $tipos_permitidos);
}

/**
 * Función para redirigir con mensaje
 */
function redirigir_con_mensaje($url, $mensaje, $tipo = 'info') {
    $parametro = ($tipo == 'error') ? 'error' : 'mensaje';
    header("Location: $url?$parametro=" . urlencode($mensaje));
    exit();
}

/**
 * Función para log de errores personalizado
 */
function log_error($mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $log_mensaje = "[$fecha] $mensaje" . PHP_EOL;
    
    // Crear directorio de logs si no existe
    $directorio_logs = __DIR__ . '/../logs/';
    if (!is_dir($directorio_logs)) {
        mkdir($directorio_logs, 0755, true);
    }
    
    file_put_contents($directorio_logs . 'error.log', $log_mensaje, FILE_APPEND | LOCK_EX);
}

/**
 * Función para obtener estadísticas básicas
 */
function obtener_estadisticas_basicas($conexion) {
    $stats = [];
    
    // Total de noticias activas
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) as total FROM noticias WHERE activa = TRUE");
    $stats['noticias_activas'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Total de usuarios activos
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE activo = TRUE");
    $stats['usuarios_activos'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Total de comentarios
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) as total FROM comentarios WHERE activo = TRUE");
    $stats['comentarios_total'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Noticias destacadas
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) as total FROM noticias WHERE destacada = TRUE AND activa = TRUE");
    $stats['noticias_destacadas'] = mysqli_fetch_assoc($resultado)['total'];
    
    return $stats;
}

/**
 * Configuración de paginación
 */
function configurar_paginacion($total_registros, $registros_por_pagina = 10, $pagina_actual = 1) {
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    $pagina_actual = max(1, min($pagina_actual, $total_paginas));
    $inicio = ($pagina_actual - 1) * $registros_por_pagina;
    
    return [
        'total_registros' => $total_registros,
        'registros_por_pagina' => $registros_por_pagina,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina_actual,
        'inicio' => $inicio
    ];
}

/**
 * Función para generar breadcrumb automático
 */
function generar_breadcrumb($pagina_actual) {
    $breadcrumbs = [
        'index.php' => 'Inicio',
        'noticias_todas.php' => 'Todas las Noticias',
        'calendario.php' => 'Calendario',
        'login.php' => 'Iniciar Sesión',
        'registro.php' => 'Registro',
        'perfil.php' => 'Mi Perfil'
    ];
    
    return $breadcrumbs[$pagina_actual] ?? 'Página';
}
?>
