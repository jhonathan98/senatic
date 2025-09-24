<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $email_usuario = $_POST['correo'] ?? '';
    $password = $_POST['contrasena'] ?? '';

    // Limpiar datos de entrada (excepto la contraseña)
    $email_usuario = limpiarDatos($email_usuario);

    // Validaciones básicas
    if (empty($email_usuario) || empty($password)) {
        $_SESSION['mensaje'] = 'Por favor, complete todos los campos.';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../vistas/publicas/login.php');
        exit();
    }

    try {
        $pdo = getDB();

        // Buscar usuario por correo o nombre de usuario
        $sql = "SELECT u.*, r.nombre_rol 
                FROM usuarios u 
                LEFT JOIN roles r ON u.id_rol = r.id_rol 
                WHERE (u.correo = ? OR u.nombre_usuario = ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email_usuario, $email_usuario]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $_SESSION['mensaje'] = 'Usuario o contraseña incorrectos.';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: ../vistas/publicas/login.php');
            exit();
        }

        // Verificar contraseña
        if (!verificarPassword($password, $usuario['contrasena'])) {
            $_SESSION['mensaje'] = 'Usuario o contraseña incorrectos.';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: ../vistas/publicas/login.php');
            exit();
        }

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Establecer variables de sesión
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['grado'] = $usuario['grado'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['nombre_rol'] = $usuario['nombre_rol'] ?? 'lector';
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'];
        $_SESSION['descripcion'] = $usuario['descripcion'];
        $_SESSION['ultima_actividad'] = time();

        // Mensaje de bienvenida
        $_SESSION['mensaje'] = '¡Bienvenido de nuevo, ' . htmlspecialchars($usuario['nombre_completo']) . '!';
        $_SESSION['tipo_mensaje'] = 'success';

        // Redirigir según el rol del usuario
        if ($usuario['id_rol'] == 4) { // Administrador
            header('Location: ../vistas/privadas/admin/dashboard.php');
        } elseif (in_array($usuario['id_rol'], [2, 3])) { // Redactor o Usuario regular
            header('Location: ../vistas/privadas/dashboard.php');
        } else { // Lector (rol 1)
            header('Location: ../index.php');
        }
        exit();

    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        $_SESSION['mensaje'] = 'Error del sistema. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../vistas/publicas/login.php');
        exit();
    } catch (Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        $_SESSION['mensaje'] = 'Error inesperado. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../vistas/publicas/login.php');
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST
    header('Location: ../vistas/publicas/login.php');
    exit();
}
?>
