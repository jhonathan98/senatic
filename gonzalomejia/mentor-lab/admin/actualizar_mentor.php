<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../config/db.php');
    
    try {
        $foto_perfil = '';
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/mentores/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                $foto_perfil = 'uploads/mentores/' . $file_name;
            }
        }
        
        // Construir la consulta de actualización
        $sql = "UPDATE mentores SET nombre_completo = ?, correo_electronico = ?, especialidad = ?, 
                nivel_educativo = ?, descripcion = ?, experiencia_anios = ?";
        $params = [
            $_POST['nombre_completo'],
            $_POST['correo'],
            $_POST['especialidad'],
            $_POST['nivel_educativo'],
            $_POST['descripcion'],
            $_POST['experiencia_anios']
        ];
        
        // Si se subió una nueva foto, incluirla en la actualización
        if ($foto_perfil) {
            $sql .= ", foto_perfil = ?";
            $params[] = $foto_perfil;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $_POST['mentor_id'];
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        header('Location: ../dashboard.php?mensaje=mentor_actualizado');
        
    } catch(PDOException $e) {
        header('Location: ../dashboard.php?error=Error al actualizar mentor: ' . $e->getMessage());
    }
}
?>
