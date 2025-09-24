<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre_completo = $_POST['nombre_completo'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $grado = $_POST['grado'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $contrasena_confirm = $_POST['contrasena_confirm'] ?? '';

    // Limpiar datos de entrada
    $nombre_completo = limpiarDatos($nombre_completo);
    $correo = limpiarDatos($correo);
    $nombre_usuario = limpiarDatos($nombre_usuario);
    $grado = limpiarDatos($grado);

    // Array para almacenar errores
    $errores = [];

    // Validaciones básicas
    if (empty($nombre_completo)) {
        $errores[] = 'El nombre completo es requerido.';
    }

    if (empty($correo)) {
        $errores[] = 'El correo electrónico es requerido.';
    } elseif (!validarEmail($correo)) {
        $errores[] = 'El formato del correo electrónico no es válido.';
    }

    if (empty($nombre_usuario)) {
        $errores[] = 'El nombre de usuario es requerido.';
    } elseif (strlen($nombre_usuario) < 3) {
        $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nombre_usuario)) {
        $errores[] = 'El nombre de usuario solo puede contener letras, números y guiones bajos.';
    }

    if (empty($contrasena)) {
        $errores[] = 'La contraseña es requerida.';
    } elseif (!validarPassword($contrasena)) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($contrasena !== $contrasena_confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    // Si hay errores, redirigir con mensaje
    if (!empty($errores)) {
        $_SESSION['mensaje'] = implode('<br>', $errores);
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'nombre_completo' => $nombre_completo,
            'correo' => $correo,
            'nombre_usuario' => $nombre_usuario,
            'grado' => $grado
        ];
        header('Location: ../vistas/publicas/registro.php');
        exit();
    }

    try {
        $pdo = getDB();

        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $_SESSION['mensaje'] = 'Este correo electrónico ya está registrado.';
            $_SESSION['tipo_mensaje'] = 'danger';
            $_SESSION['form_data'] = [
                'nombre_completo' => $nombre_completo,
                'correo' => $correo,
                'nombre_usuario' => $nombre_usuario,
                'grado' => $grado
            ];
            header('Location: ../vistas/publicas/registro.php');
            exit();
        }

        // Verificar si el nombre de usuario ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?");
        $stmt->execute([$nombre_usuario]);
        if ($stmt->fetch()) {
            $_SESSION['mensaje'] = 'Este nombre de usuario ya está en uso.';
            $_SESSION['tipo_mensaje'] = 'danger';
            $_SESSION['form_data'] = [
                'nombre_completo' => $nombre_completo,
                'correo' => $correo,
                'nombre_usuario' => $nombre_usuario,
                'grado' => $grado
            ];
            header('Location: ../vistas/publicas/registro.php');
            exit();
        }

        // Hash de la contraseña
        $contrasena_hash = hashPassword($contrasena);

        // Insertar nuevo usuario (rol por defecto: 1 = lector)
        $sql = "INSERT INTO usuarios (nombre_completo, correo, nombre_usuario, contrasena, grado, id_rol) 
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $nombre_completo, 
            $correo, 
            $nombre_usuario, 
            $contrasena_hash, 
            $grado
        ]);

        if ($resultado) {
            // Obtener el ID del usuario recién creado
            $usuario_id = $pdo->lastInsertId();

            // Iniciar sesión automáticamente
            $_SESSION['id_usuario'] = $usuario_id;
            $_SESSION['nombre_completo'] = $nombre_completo;
            $_SESSION['nombre_usuario'] = $nombre_usuario;
            $_SESSION['correo'] = $correo;
            $_SESSION['grado'] = $grado;
            $_SESSION['id_rol'] = 1;
            $_SESSION['nombre_rol'] = 'lector';

            // Mensaje de éxito
            $_SESSION['mensaje'] = '¡Registro exitoso! Bienvenido a Student News, ' . htmlspecialchars($nombre_completo) . '.';
            $_SESSION['tipo_mensaje'] = 'success';

            // Limpiar datos del formulario
            unset($_SESSION['form_data']);

            // Redirigir al dashboard o página principal
            header('Location: ../index.php');
            exit();

        } else {
            throw new Exception('Error al crear la cuenta.');
        }

    } catch (PDOException $e) {
        error_log("Error en registro: " . $e->getMessage());
        $_SESSION['mensaje'] = 'Error en la base de datos. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'nombre_completo' => $nombre_completo,
            'correo' => $correo,
            'nombre_usuario' => $nombre_usuario,
            'grado' => $grado
        ];
        header('Location: ../vistas/publicas/registro.php');
        exit();
    } catch (Exception $e) {
        error_log("Error en registro: " . $e->getMessage());
        $_SESSION['mensaje'] = 'Error al procesar el registro. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'nombre_completo' => $nombre_completo,
            'correo' => $correo,
            'nombre_usuario' => $nombre_usuario,
            'grado' => $grado
        ];
        header('Location: ../vistas/publicas/registro.php');
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST
    header('Location: ../vistas/publicas/registro.php');
    exit();
}
?>
