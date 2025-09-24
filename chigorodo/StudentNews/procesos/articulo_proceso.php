<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/funciones.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../vistas/publicas/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $titulo = $_POST['titulo'] ?? '';
    $id_categoria = $_POST['id_categoria'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    $accion = $_POST['accion'] ?? 'borrador';
    $id_autor = $_SESSION['id_usuario'];

    // Limpiar datos de entrada
    $titulo = limpiarDatos($titulo);
    $contenido = trim($contenido); // No aplicar htmlspecialchars al contenido para permitir HTML
    $id_categoria = filter_var($id_categoria, FILTER_VALIDATE_INT);

    // Array para almacenar errores
    $errores = [];

    // Validaciones básicas
    if (empty($titulo)) {
        $errores[] = 'El título es requerido.';
    } elseif (strlen($titulo) < 5) {
        $errores[] = 'El título debe tener al menos 5 caracteres.';
    } elseif (strlen($titulo) > 255) {
        $errores[] = 'El título no puede exceder 255 caracteres.';
    }

    if (!$id_categoria || $id_categoria <= 0) {
        $errores[] = 'Debe seleccionar una categoría válida.';
    }

    if (empty($contenido)) {
        $errores[] = 'El contenido es requerido.';
    } elseif (strlen($contenido) < 50) {
        $errores[] = 'El contenido debe tener al menos 50 caracteres.';
    }

    if (!in_array($accion, ['borrador', 'enviar'])) {
        $errores[] = 'Acción no válida.';
    }

    // Procesar imagen destacada si se subió
    $imagen_destacada = null;
    if (isset($_FILES['imagen_destacada']) && $_FILES['imagen_destacada']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado_imagen = subirArchivo(
            $_FILES['imagen_destacada'], 
            '../assets/images/uploads/', 
            ['jpg', 'jpeg', 'png', 'gif', 'webp']
        );

        if (isset($resultado_imagen['error'])) {
            $errores[] = 'Error en la imagen: ' . $resultado_imagen['error'];
        } else {
            $imagen_destacada = $resultado_imagen['archivo'];
        }
    }

    // Si hay errores, redirigir con mensaje
    if (!empty($errores)) {
        $_SESSION['mensaje'] = implode('<br>', $errores);
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'titulo' => $titulo,
            'id_categoria' => $id_categoria,
            'contenido' => $contenido,
            'accion' => $accion
        ];
        header('Location: ../vistas/privadas/articulo_form.php');
        exit();
    }

    try {
        $pdo = getDB();

        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ?");
        $stmt->execute([$id_categoria]);
        if (!$stmt->fetch()) {
            $_SESSION['mensaje'] = 'La categoría seleccionada no es válida.';
            $_SESSION['tipo_mensaje'] = 'danger';
            $_SESSION['form_data'] = [
                'titulo' => $titulo,
                'id_categoria' => $id_categoria,
                'contenido' => $contenido,
                'accion' => $accion
            ];
            header('Location: ../vistas/privadas/articulo_form.php');
            exit();
        }

        // Determinar el estado del artículo
        $estado = ($accion === 'enviar') ? 'pendiente' : 'borrador';
        $fecha_publicacion = null;
        
        // Si es un administrador o redactor, puede publicar directamente
        if ($accion === 'enviar' && isset($_SESSION['id_rol']) && in_array($_SESSION['id_rol'], [3, 4])) {
            $estado = 'publicado';
            $fecha_publicacion = date('Y-m-d H:i:s');
        }

        // Insertar el artículo
        $sql = "INSERT INTO articulos (titulo, contenido, id_autor, id_categoria, estado, imagen_destacada, fecha_publicacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $titulo,
            $contenido,
            $id_autor,
            $id_categoria,
            $estado,
            $imagen_destacada,
            $fecha_publicacion
        ]);

        if ($resultado) {
            $articulo_id = $pdo->lastInsertId();

            // Mensaje de éxito según la acción
            if ($estado === 'publicado') {
                $_SESSION['mensaje'] = '¡Artículo publicado exitosamente!';
            } elseif ($estado === 'pendiente') {
                $_SESSION['mensaje'] = '¡Artículo enviado a revisión exitosamente! Un administrador lo revisará pronto.';
            } else {
                $_SESSION['mensaje'] = '¡Borrador guardado exitosamente!';
            }
            
            $_SESSION['tipo_mensaje'] = 'success';

            // Limpiar datos del formulario
            unset($_SESSION['form_data']);

            // Redirigir al dashboard o a ver el artículo
            if ($estado === 'publicado') {
                header('Location: ../vistas/publicas/articulo.php?id=' . $articulo_id);
            } else {
                header('Location: ../vistas/privadas/dashboard.php');
            }
            exit();

        } else {
            throw new Exception('Error al guardar el artículo.');
        }

    } catch (PDOException $e) {
        error_log("Error en artículo: " . $e->getMessage());
        
        // Si hubo error y se subió una imagen, intentar eliminarla
        if ($imagen_destacada && file_exists('../assets/images/uploads/' . $imagen_destacada)) {
            unlink('../assets/images/uploads/' . $imagen_destacada);
        }

        $_SESSION['mensaje'] = 'Error en la base de datos. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'titulo' => $titulo,
            'id_categoria' => $id_categoria,
            'contenido' => $contenido,
            'accion' => $accion
        ];
        header('Location: ../vistas/privadas/articulo_form.php');
        exit();

    } catch (Exception $e) {
        error_log("Error en artículo: " . $e->getMessage());
        
        // Si hubo error y se subió una imagen, intentar eliminarla
        if ($imagen_destacada && file_exists('../assets/images/uploads/' . $imagen_destacada)) {
            unlink('../assets/images/uploads/' . $imagen_destacada);
        }

        $_SESSION['mensaje'] = 'Error al procesar el artículo. Por favor, intente nuevamente.';
        $_SESSION['tipo_mensaje'] = 'danger';
        $_SESSION['form_data'] = [
            'titulo' => $titulo,
            'id_categoria' => $id_categoria,
            'contenido' => $contenido,
            'accion' => $accion
        ];
        header('Location: ../vistas/privadas/articulo_form.php');
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST
    header('Location: ../vistas/privadas/dashboard.php');
    exit();
}
?>
