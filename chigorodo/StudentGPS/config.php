<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'studentgps');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de la aplicación
define('APP_NAME', 'StudentGPS');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/Bogota');

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para sanitizar datos
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para validar roles
function hasRole($required_roles) {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    $user_role = $_SESSION['user']['role'];
    return in_array($user_role, (array)$required_roles);
}

// Función para redireccionar de forma segura
function redirect($url) {
    header("Location: $url");
    exit;
}
?>