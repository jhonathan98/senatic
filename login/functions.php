<?php
// functions.php - Funciones de autenticación

require_once 'config.php';

// Función para registrar usuario
function registerUser($username, $email, $password) {
    $pdo = getConnection();
    
    try {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'El usuario o email ya existe'];
        }
        
        // Hashear la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
    }
}

// Función para hacer login
function loginUser($username, $password) {
    $pdo = getConnection();
    
    try {
        // Buscar usuario por username o email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            return ['success' => true, 'message' => 'Login exitoso'];
        } else {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
    }
}

// Función para cerrar sesión
function logoutUser() {
    session_start();
    session_unset();
    session_destroy();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para validar contraseña
function isValidPassword($password) {
    return strlen($password) >= 6;
}
?>