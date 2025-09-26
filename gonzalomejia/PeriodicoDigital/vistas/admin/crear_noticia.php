<?php
session_start();
// Verificar si el usuario está logueado y tiene permiso
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'redactor' && $_SESSION['tipo_usuario'] !== 'admin')) {
    header("Location: ../login.php");
    exit();
}

include_once '../../includes/header.php';
include_once '../../includes/conexion.php';
include_once '../../includes/navbar.php'; // Navbar con opciones de admin si aplica

$categorias = [];
$sql_cat = "SELECT id, nombre FROM categorias ORDER BY nombre";
$result_cat = mysqli_query($conexion, $sql_cat);
while ($row = mysqli_fetch_assoc($result_cat)) {
    $categorias[] = $row;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $resumen = trim($_POST['resumen']);
    $id_categoria = (int)$_POST['categoria'];
    $destacada = isset($_POST['destacada']) ? 1 : 0;
    $id_autor = $_SESSION['usuario_id'];

    $imagen_nombre = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen_tmp = $_FILES['imagen']['tmp_name'];
        $imagen_nombre = $_FILES['imagen']['name'];
        $upload_dir = '../../assets/images/noticias/';
        $imagen_path = $upload_dir . basename($imagen_nombre);

        // Validar tipo de archivo (opcional)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['imagen']['type'], $allowed_types)) {
            $mensaje = "Tipo de archivo no permitido. Solo JPEG, PNG o GIF.";
        } elseif (!move_uploaded_file($imagen_tmp, $imagen_path)) {
            $mensaje = "Error al subir la imagen.";
        }
    }

    if (empty($mensaje)) {
        $sql_insert = "INSERT INTO noticias (titulo, contenido, resumen, imagen_principal, id_autor, id_categoria, destacada) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql_insert);
        mysqli_stmt_bind_param($stmt, "ssssiii", $titulo, $contenido, $resumen, $imagen_nombre, $id_autor, $id_categoria, $destacada);

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Noticia publicada exitosamente.";
            // Limpiar campos
            $titulo = $contenido = $resumen = '';
        } else {
            $mensaje = "Error al publicar la noticia: " . mysqli_error($conexion);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<div class="container mt-4">
    <h2>Redactar Nueva Noticia</h2>
    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="resumen" class="form-label">Resumen</label>
            <textarea class="form-control" id="resumen" name="resumen" rows="2"><?php echo htmlspecialchars($resumen ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="contenido" class="form-label">Contenido</label>
            <textarea class="form-control" id="contenido" name="contenido" rows="8" required><?php echo htmlspecialchars($contenido ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="categoria" class="form-label">Categoría</label>
            <select class="form-select" id="categoria" name="categoria" required>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == ($id_categoria ?? 0)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="imagen" class="form-label">Imagen Principal</label>
            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="destacada" name="destacada">
            <label class="form-check-label" for="destacada">Marcar como Noticia Destacada</label>
        </div>
        <button type="submit" class="btn btn-success">Publicar Noticia</button>
    </form>
</div>
<?php
include_once '../../includes/footer.php';
?>