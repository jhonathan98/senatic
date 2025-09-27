<?php
// includes/config.php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ose_db');

// Configuración de la aplicación
define('APP_NAME', 'OSE - Optimización del Sistema Educativo');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/senatic/senatic/JuanEvangelista/OSE/');

// Configuración de sesión (solo si no hay sesión activa)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 3600); // 1 hora
    ini_set('session.gc_maxlifetime', 3600);
}

// Configuración de gamificación
define('PUNTOS_POR_RESPUESTA_CORRECTA', 10);
define('PUNTOS_EXAMEN_COMPLETO', 50);
define('PUNTOS_RACHA_PERFECTA', 100);

// Niveles de logros
$logros = [
    'primer_examen' => ['nombre' => 'Primer Paso', 'descripcion' => 'Completaste tu primer examen', 'puntos' => 25],
    'cinco_examenes' => ['nombre' => 'Dedicado', 'descripcion' => 'Completaste 5 exámenes', 'puntos' => 100],
    'diez_examenes' => ['nombre' => 'Experto', 'descripcion' => 'Completaste 10 exámenes', 'puntos' => 250],
    'puntuacion_perfecta' => ['nombre' => 'Perfección', 'descripcion' => 'Obtuviste 100% en un examen', 'puntos' => 150],
    'racha_perfecta' => ['nombre' => 'Imparable', 'descripcion' => 'Obtuviste 100% en 3 exámenes consecutivos', 'puntos' => 500]
];

// Configuración de timezone
date_default_timezone_set('America/Bogota');

// Conexión a la base de datos usando PDO
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
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
