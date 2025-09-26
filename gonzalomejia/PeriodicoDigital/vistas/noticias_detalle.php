<?php
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

$id_noticia = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_noticia <= 0) {
    header('Location: ../index.php');
    exit();
}

// Obtener la noticia
$sql_noticia = "SELECT n.*, c.nombre AS categoria_nombre, u.nombre AS autor_nombre, u.apellido AS autor_apellido
                FROM noticias n
                JOIN categorias c ON n.id_categoria = c.id
                JOIN usuarios u ON n.id_autor = u.id
                WHERE n.id = ? AND n.activa = TRUE";

$stmt = mysqli_prepare($conexion, $sql_noticia);
mysqli_stmt_bind_param($stmt, "i", $id_noticia);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$noticia = mysqli_fetch_assoc($resultado);

if (!$noticia) {
    header('Location: ../index.php');
    exit();
}

$page_title = htmlspecialchars($noticia['titulo']) . " - Periódico Digital";

// Obtener comentarios
$sql_comentarios = "SELECT c.*, u.nombre, u.apellido 
                   FROM comentarios c
                   JOIN usuarios u ON c.id_usuario = u.id
                   WHERE c.id_noticia = ? AND c.activo = TRUE
                   ORDER BY c.fecha_comentario DESC";

$stmt_comentarios = mysqli_prepare($conexion, $sql_comentarios);
mysqli_stmt_bind_param($stmt_comentarios, "i", $id_noticia);
mysqli_stmt_execute($stmt_comentarios);
$comentarios = mysqli_stmt_get_result($stmt_comentarios);

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $contenido_comentario = trim($_POST['comentario']);
    
    if (!empty($contenido_comentario)) {
        $sql_insertar = "INSERT INTO comentarios (id_noticia, id_usuario, contenido) VALUES (?, ?, ?)";
        $stmt_insertar = mysqli_prepare($conexion, $sql_insertar);
        mysqli_stmt_bind_param($stmt_insertar, "iis", $id_noticia, $_SESSION['usuario_id'], $contenido_comentario);
        
        if (mysqli_stmt_execute($stmt_insertar)) {
            header("Location: noticias_detalle.php?id=$id_noticia#comentarios");
            exit();
        }
        mysqli_stmt_close($stmt_insertar);
    }
}
?>
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="noticias_categoria.php?id=<?php echo $noticia['id_categoria']; ?>"><?php echo htmlspecialchars($noticia['categoria_nombre']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($noticia['titulo']); ?></li>
        </ol>
    </nav>

    <article>
        <h1><?php echo htmlspecialchars($noticia['titulo']); ?></h1>
        <p class="text-muted">Por <?php echo htmlspecialchars($noticia['autor_nombre'] . ' ' . $noticia['autor_apellido']); ?> en <?php echo htmlspecialchars($noticia['categoria_nombre']); ?> | <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?></p>

        <?php if ($noticia['imagen_principal']): ?>
            <img src="../assets/images/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" class="img-fluid my-3" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
        <?php endif; ?>

        <div class="contenido-noticia">
            <?php echo nl2br(htmlspecialchars($noticia['contenido'])); ?>
        </div>

        <!-- Reacciones (Simplificado para el ejemplo) -->
        <div class="mt-4">
            <h5>Reacciones:</h5>
            <button class="btn btn-sm btn-outline-primary">👍</button>
            <button class="btn btn-sm btn-outline-warning">❤️</button>
            <button class="btn btn-sm btn-outline-info">😮</button>
        </div>

        <!-- Comentarios (Simplificado para el ejemplo) -->
        <div class="mt-4">
            <h5>Comentarios:</h5>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form method="post" action="#">
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Tu comentario:</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar Comentario</button>
                </form>
            <?php else: ?>
                <p><a href="login.php">Inicia sesión</a> para comentar.</p>
            <?php endif; ?>

            <!-- Aquí irían los comentarios cargados de la base de datos -->
            <div class="mt-3">
                <div class="card mb-2">
                    <div class="card-body">
                        <p class="card-text">Comentario de un usuario.</p>
                        <small class="text-muted">Usuario Anónimo - Hace 1 hora</small>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>
<?php
include_once '../includes/footer.php';
?>