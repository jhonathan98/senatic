<?php
// functions/auth.php - Manejo de autenticación

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determinar la ruta correcta para database.php
$database_path = __DIR__ . '/../config/database.php';
if (!file_exists($database_path)) {
    $database_path = dirname(__DIR__) . '/config/database.php';
}

require_once $database_path;

// Función para obtener usuario por email
function obtenerUsuarioPorEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para registrar usuario
function registrarUsuario($datos) {
    global $pdo;
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR numero_documento = ?");
    $stmt->execute([$datos['email'], $datos['numero_documento']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El email o número de documento ya están registrados'];
    }
    
    // Hash de la contraseña
    $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);
    
    // Obtener rol de usuario (por defecto 'user')
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'user'");
    $stmt->execute();
    $rol_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Insertar usuario
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, tipo_documento, numero_documento, institucion_educativa, email, telefono, password_hash, rol_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([
        $datos['nombre_completo'],
        $datos['tipo_documento'],
        $datos['numero_documento'],
        $datos['institucion_educativa'],
        $datos['email'],
        $datos['telefono'],
        $password_hash,
        $rol_user['id']
    ])) {
        return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al registrar usuario'];
    }
}

// Función para validar login
function validarLogin($email, $password) {
    $usuario = obtenerUsuarioPorEmail($email);
    
    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        // Login exitoso - establecer sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre_completo'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['institucion'] = $usuario['institucion_educativa'];
        
        return ['success' => true, 'usuario' => $usuario];
    } else {
        return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
    }
}

// Función para cerrar sesión
function cerrarSesion() {
    session_unset();
    session_destroy();
}

// Función para verificar si está logueado
function estaLogueado() {
    return isset($_SESSION['user_id']);
}

// Función para obtener rol del usuario
function obtenerRolUsuario() {
    return $_SESSION['rol'] ?? null;
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $resultado = validarLogin($email, $password);
        
        if ($resultado['success']) {
            // Redirigir según el rol
            $base_url = dirname(dirname($_SERVER['PHP_SELF']));
            if ($resultado['usuario']['rol'] === 'admin') {
                header("Location: " . $base_url . "/views/admin/dashboard.php");
            } else {
                header("Location: " . $base_url . "/views/user/dashboard.php");
            }
            exit;
        } else {
            $_SESSION['error'] = $resultado['message'];
            $base_url = dirname(dirname($_SERVER['PHP_SELF']));
            header("Location: " . $base_url . "/views/login.php");
            exit;
        }
    }
    
    if ($action === 'register') {
        $datos = [
            'nombre_completo' => $_POST['nombre_completo'] ?? '',
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'numero_documento' => $_POST['numero_documento'] ?? '',
            'institucion_educativa' => $_POST['institucion_educativa'] ?? '',
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];
        
        $resultado = registrarUsuario($datos);
        
        if ($resultado['success']) {
            $_SESSION['success'] = $resultado['message'];
        } else {
            $_SESSION['error'] = $resultado['message'];
        }
        
        $base_url = dirname(dirname($_SERVER['PHP_SELF']));
        header("Location: " . $base_url . "/views/login.php");
        exit;
    }
    
    if ($action === 'logout') {
        cerrarSesion();
        $base_url = dirname(dirname($_SERVER['PHP_SELF']));
        header("Location: " . $base_url . "/index.php");
        exit;
    }
}
?>
