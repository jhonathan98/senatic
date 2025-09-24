<?php
/**
 * FUNCIONES.PHP - Funciones reutilizables
 * Validaciones, helpers y utilidades generales
 */

// Función para limpiar datos de entrada
function limpiarDatos($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validar contraseña (mínimo 6 caracteres)
function validarPassword($password) {
    return strlen($password) >= 6;
}

// Generar hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verificar contraseña
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generar slug a partir de un título
function generarSlug($texto) {
    // Convertir a minúsculas
    $slug = strtolower($texto);
    
    // Reemplazar caracteres especiales
    $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
    
    // Eliminar caracteres no alfanuméricos excepto espacios y guiones
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    
    // Reemplazar espacios y múltiples guiones con un solo guión
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    
    // Eliminar guiones al inicio y final
    $slug = trim($slug, '-');
    
    return $slug;
}

// Subir archivo
function subirArchivo($archivo, $directorio = 'uploads/', $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($archivo['error']) || is_array($archivo['error'])) {
        return ['error' => 'Parámetros de archivo inválidos'];
    }

    switch ($archivo['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['error' => 'No se seleccionó ningún archivo'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['error' => 'El archivo es demasiado grande'];
        default:
            return ['error' => 'Error desconocido en la subida'];
    }

    // Verificar tamaño del archivo (5MB máximo)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return ['error' => 'El archivo es demasiado grande (máximo 5MB)'];
    }

    // Verificar tipo de archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $tipos_permitidos)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }

    // Generar nombre único
    $nombre_archivo = uniqid() . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;

    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }

    // Mover archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return ['error' => 'No se pudo guardar el archivo'];
    }

    return ['success' => true, 'archivo' => $nombre_archivo, 'ruta' => $ruta_completa];
}

// Redimensionar imagen
function redimensionarImagen($archivo_origen, $archivo_destino, $ancho_max = 800, $alto_max = 600) {
    $info = getimagesize($archivo_origen);
    if (!$info) {
        return false;
    }

    $ancho_original = $info[0];
    $alto_original = $info[1];
    $tipo = $info[2];

    // Calcular nuevas dimensiones
    $ratio = min($ancho_max / $ancho_original, $alto_max / $alto_original);
    $nuevo_ancho = $ancho_original * $ratio;
    $nuevo_alto = $alto_original * $ratio;

    // Crear imagen según el tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_origen = imagecreatefromjpeg($archivo_origen);
            break;
        case IMAGETYPE_PNG:
            $imagen_origen = imagecreatefrompng($archivo_origen);
            break;
        case IMAGETYPE_GIF:
            $imagen_origen = imagecreatefromgif($archivo_origen);
            break;
        default:
            return false;
    }

    // Crear imagen redimensionada
    $imagen_destino = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
    
    // Preservar transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagen_destino, false);
        imagesavealpha($imagen_destino, true);
        $transparente = imagecolorallocatealpha($imagen_destino, 255, 255, 255, 127);
        imagefill($imagen_destino, 0, 0, $transparente);
    }

    imagecopyresampled($imagen_destino, $imagen_origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho_original, $alto_original);

    // Guardar imagen
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagen_destino, $archivo_destino, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagen_destino, $archivo_destino);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagen_destino, $archivo_destino);
            break;
    }

    imagedestroy($imagen_origen);
    imagedestroy($imagen_destino);

    return true;
}

// Formatear fecha
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($fecha));
}

// Formatear fecha relativa (hace X tiempo)
function fechaRelativa($fecha) {
    $tiempo = time() - strtotime($fecha);
    
    if ($tiempo < 60) {
        return 'hace unos segundos';
    } elseif ($tiempo < 3600) {
        $minutos = floor($tiempo / 60);
        return "hace $minutos minuto" . ($minutos != 1 ? 's' : '');
    } elseif ($tiempo < 86400) {
        $horas = floor($tiempo / 3600);
        return "hace $horas hora" . ($horas != 1 ? 's' : '');
    } elseif ($tiempo < 2592000) {
        $dias = floor($tiempo / 86400);
        return "hace $dias día" . ($dias != 1 ? 's' : '');
    } else {
        return formatearFecha($fecha, 'd/m/Y');
    }
}

// Truncar texto
function truncarTexto($texto, $longitud = 100, $sufijo = '...') {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    return substr($texto, 0, $longitud) . $sufijo;
}

// Generar extracto de HTML
function generarExtracto($html, $longitud = 200) {
    $texto = strip_tags($html);
    return truncarTexto($texto, $longitud);
}

// Paginar resultados
function paginar($total_registros, $registros_por_pagina = 10, $pagina_actual = 1) {
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    $pagina_actual = max(1, min($total_paginas, $pagina_actual));
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    
    return [
        'total_registros' => $total_registros,
        'registros_por_pagina' => $registros_por_pagina,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina_actual,
        'offset' => $offset,
        'tiene_anterior' => $pagina_actual > 1,
        'tiene_siguiente' => $pagina_actual < $total_paginas
    ];
}

// Mostrar alerta
function mostrarAlerta($mensaje, $tipo = 'info', $auto_hide = false) {
    $auto_hide_attr = $auto_hide ? 'data-auto-dismiss="true"' : '';
    echo "<div class='alert alert-$tipo' $auto_hide_attr>$mensaje</div>";
}

// Validar CSRF token
function generarCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitizar HTML
function sanitizarHTML($html) {
    // Lista básica de etiquetas permitidas
    $etiquetas_permitidas = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote>';
    return strip_tags($html, $etiquetas_permitidas);
}

// Enviar email simple
function enviarEmail($para, $asunto, $mensaje, $de = null) {
    if (!$de) {
        $de = 'noreply@estudiantes.edu';
    }
    
    $headers = [
        'From' => $de,
        'Reply-To' => $de,
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    return mail($para, $asunto, $mensaje, $headers);
}

// Logging simple
function log_mensaje($mensaje, $nivel = 'INFO') {
    $fecha = date('Y-m-d H:i:s');
    $log_entry = "[$fecha] [$nivel] $mensaje" . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/app.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Verificar si el usuario tiene permisos
function tienePermiso($rol_requerido, $rol_usuario) {
    $jerarquia = ['usuarioRegular' => 1, 'editor' => 2, 'admin' => 3];
    return $jerarquia[$rol_usuario] >= $jerarquia[$rol_requerido];
}

// Obtener configuración
function obtenerConfiguracion($clave, $valor_por_defecto = null) {
    static $configuraciones = null;
    
    if ($configuraciones === null) {
        $configuraciones = [
            'sitio_nombre' => 'Estudiantes News',
            'sitio_descripcion' => 'Periódico digital estudiantil',
            'articulos_por_pagina' => 10,
            'comentarios_moderados' => true,
            'registro_habilitado' => true
        ];
    }
    
    return isset($configuraciones[$clave]) ? $configuraciones[$clave] : $valor_por_defecto;
}

// Crear directorio de logs si no existe
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}
?>
