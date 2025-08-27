<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../includes/db.php');
    
    try {
        // Procesar la imagen
        $foto_perfil = '';
        // if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        //     $upload_dir = dirname(__DIR__) . '/uploads/mentores/';
        //     if (!file_exists($upload_dir)) {
        //         if (!mkdir($upload_dir, 0777, true)) {
        //             throw new Exception('No se pudo crear el directorio de subidas');
        //         }
        //         chmod($upload_dir, 0777);
        //     }
            
        //     $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        //     $file_name = uniqid() . '.' . $file_extension;
        //     $target_file = $upload_dir . $file_name;
            
        //     if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
        //         chmod($target_file, 0644);
        //         $foto_perfil = 'uploads/mentores/' . $file_name;
        //     } else {
        //         throw new Exception('Error al mover el archivo subido');
        //     }
        // }
        
        // Insertar el nuevo mentor
        $stmt = $conn->prepare("INSERT INTO mentores (nombre_completo, correo, especialidad, nivel_educativo, descripcion, experiencia_anios, foto_perfil, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')");
        
        $stmt->execute([
            $_POST['nombre_completo'],
            $_POST['correo'],
            $_POST['especialidad'],
            $_POST['nivel_educativo'],
            $_POST['descripcion'],
            $_POST['experiencia_anios'],
            $foto_perfil
        ]);
        
        header('Location: ../dashboard.php?mensaje=mentor_agregado');
        
    } catch(PDOException $e) {
        header('Location: ../dashboard.php?error=Error al agregar mentor: ' . $e->getMessage());
    }
}
?>
