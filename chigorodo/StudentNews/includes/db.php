<?php
/**
 * DB.PHP - Conexión a la base de datos
 * Gestión de la conexión PDO con MySQL
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_news');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación del objeto
    private function __clone() {}
    
    // Prevenir deserialización del objeto
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para ejecutar consultas preparadas
function executeQuery($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return false;
    }
}

// Función para obtener un registro
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Función para obtener múltiples registros
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

// Función para insertar y obtener el ID
function insertAndGetId($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return getDB()->lastInsertId();
    }
    return false;
}

// Función para contar registros
function countRecords($table, $where = '', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    
    $result = fetchOne($sql, $params);
    return $result ? $result['total'] : 0;
}

// Función para verificar si existe un registro
function recordExists($table, $where, $params = []) {
    return countRecords($table, $where, $params) > 0;
}

/**
 * Script SQL para crear las tablas necesarias
 * Ejecutar una sola vez para configurar la base de datos
 */
function createTables() {
    $sql = "
    -- Tabla de usuarios
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        rol ENUM('admin', 'editor', 'usuarioRegular') DEFAULT 'usuarioRegular',
        foto_perfil VARCHAR(255) DEFAULT 'default.jpg',
        activo TINYINT(1) DEFAULT 1,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_acceso TIMESTAMP NULL
    );

    -- Tabla de categorías
    CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        color VARCHAR(7) DEFAULT '#3498db',
        activa TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Tabla de artículos
    CREATE TABLE IF NOT EXISTS articulos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        contenido LONGTEXT NOT NULL,
        extracto TEXT,
        imagen_destacada VARCHAR(255),
        categoria_id INT,
        autor_id INT NOT NULL,
        estado ENUM('borrador', 'revision', 'publicado', 'archivado') DEFAULT 'borrador',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_publicacion TIMESTAMP NULL,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        vistas INT DEFAULT 0,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
        FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );

    -- Tabla de comentarios
    CREATE TABLE IF NOT EXISTS comentarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        articulo_id INT NOT NULL,
        usuario_id INT NOT NULL,
        comentario TEXT NOT NULL,
        aprobado TINYINT(1) DEFAULT 0,
        fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );

    -- Tabla de eventos/calendario
    CREATE TABLE IF NOT EXISTS eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        fecha_evento DATE NOT NULL,
        hora_evento TIME,
        lugar VARCHAR(255),
        creador_id INT NOT NULL,
        activo TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );

    -- Tabla de galería multimedia
    CREATE TABLE IF NOT EXISTS galeria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        archivo VARCHAR(255) NOT NULL,
        tipo ENUM('imagen', 'video') NOT NULL,
        usuario_id INT NOT NULL,
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );

    -- Tabla de participaciones/ideas
    CREATE TABLE IF NOT EXISTS participaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        asunto VARCHAR(255) NOT NULL,
        mensaje TEXT NOT NULL,
        tipo ENUM('idea', 'sugerencia', 'queja', 'felicitacion') DEFAULT 'idea',
        estado ENUM('pendiente', 'revisado', 'implementado', 'descartado') DEFAULT 'pendiente',
        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Tabla de contactos
    CREATE TABLE IF NOT EXISTS contactos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        asunto VARCHAR(255) NOT NULL,
        mensaje TEXT NOT NULL,
        leido TINYINT(1) DEFAULT 0,
        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Insertar datos iniciales
    INSERT IGNORE INTO usuarios (usuario, email, password, nombre, apellido, rol) VALUES
    ('admin', 'admin@estudiantes.edu', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrador', 'Sistema', 'admin');

    INSERT IGNORE INTO categorias (nombre, descripcion, color) VALUES
    ('Noticias', 'Noticias generales del colegio', '#3498db'),
    ('Deportes', 'Eventos y noticias deportivas', '#27ae60'),
    ('Cultura', 'Actividades culturales y artísticas', '#9b59b6'),
    ('Académico', 'Información académica', '#f39c12'),
    ('Eventos', 'Próximos eventos', '#e74c3c');
    ";

    try {
        $db = getDB();
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error creando tablas: " . $e->getMessage());
        return false;
    }
}

// Crear tablas si no existen (solo en desarrollo)
if (!file_exists(__DIR__ . '/../.tables_created')) {
    if (createTables()) {
        file_put_contents(__DIR__ . '/../.tables_created', date('Y-m-d H:i:s'));
    }
}

// Variable global para compatibilidad
$pdo = getDB();
?>
