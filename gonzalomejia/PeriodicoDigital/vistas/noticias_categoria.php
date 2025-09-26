<?php
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

$id_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

if ($id_categoria <= 0) {
    header('Location: noticias_todas.php');
    exit();
}

// Obtener información de la categoría
$sql_categoria = "SELECT * FROM categorias WHERE id = ?";
$stmt_categoria = mysqli_prepare($conexion, $sql_categoria);
mysqli_stmt_bind_param($stmt_categoria, "i", $id_categoria);
mysqli_stmt_execute($stmt_categoria);
$resultado_categoria = mysqli_stmt_get_result($stmt_categoria);
$categoria = mysqli_fetch_assoc($resultado_categoria);

if (!$categoria) {
    header('Location: noticias_todas.php');
    exit();
}

$page_title = "Categoría: " . htmlspecialchars($categoria['nombre']) . " - Periódico Digital";

// Paginación
$por_pagina = 9;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $por_pagina;

// Contar total de noticias en la categoría
$sql_total = "SELECT COUNT(*) as total FROM noticias WHERE id_categoria = ? AND activa = TRUE";
$stmt_total = mysqli_prepare($conexion, $sql_total);
mysqli_stmt_bind_param($stmt_total, "i", $id_categoria);
mysqli_stmt_execute($stmt_total);
$resultado_total = mysqli_stmt_get_result($stmt_total);
$total_noticias = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_noticias / $por_pagina);

// Obtener noticias de la categoría
$sql_noticias = "SELECT n.id, n.titulo, n.resumen, n.imagen_principal, n.fecha_publicacion,
                        u.nombre AS autor_nombre, u.apellido AS autor_apellido
                 FROM noticias n
                 JOIN usuarios u ON n.id_autor = u.id
                 WHERE n.id_categoria = ? AND n.activa = TRUE
                 ORDER BY n.fecha_publicacion DESC
                 LIMIT ?, ?";

$stmt_noticias = mysqli_prepare($conexion, $sql_noticias);
mysqli_stmt_bind_param($stmt_noticias, "iii", $id_categoria, $inicio, $por_pagina);
mysqli_stmt_execute($stmt_noticias);
$noticias = mysqli_stmt_get_result($stmt_noticias);
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="noticias_todas.php">Todas las Noticias</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($categoria['nombre']); ?></li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="bi bi-tag"></i> <?php echo htmlspecialchars($categoria['nombre']); ?></h1>
            <?php if ($categoria['descripcion']): ?>
                <p class="lead text-muted"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="mb-0"><strong>Total:</strong> <?php echo $total_noticias; ?> noticias</p>
        </div>
    </div>

    <?php if (mysqli_num_rows($noticias) > 0): ?>
        <div class="row">
            <?php while ($noticia = mysqli_fetch_assoc($noticias)): ?>
                <?php
                $imagen_url = $noticia['imagen_principal'] ? 
                    '../assets/images/noticias/' . htmlspecialchars($noticia['imagen_principal']) : 
                    'https://quizizz.com/media/resource/gs/quizizz-media/quizzes/1eb3e518-27ae-4fce-9411-01ca3e1aabe9?w=200&h=200/350x200?text=Sin+Imagen';
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $imagen_url; ?>" class="card-img-top" 
                             alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="noticias_detalle.php?id=<?php echo $noticia['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($noticia['titulo']); ?>
                                </a>
                            </h5>
                            <?php if ($noticia['resumen']): ?>
                                <p class="card-text flex-grow-1">
                                    <?php echo htmlspecialchars($noticia['resumen']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="news-meta">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($noticia['autor_nombre'] . ' ' . $noticia['autor_apellido']); ?>
                                    <br>
                                    <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="noticias_detalle.php?id=<?php echo $noticia['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye"></i> Leer más
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="saveArticle(<?php echo $noticia['id']; ?>)">
                                            <i class="bi bi-bookmark"></i> Guardar
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="shareArticle(<?php echo $noticia['id']; ?>)">
                                            <i class="bi bi-share"></i> Compartir
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegación de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?categoria=<?php echo $id_categoria; ?>&pagina=<?php echo ($pagina_actual - 1); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <a class="page-link" href="?categoria=<?php echo $id_categoria; ?>&pagina=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?categoria=<?php echo $id_categoria; ?>&pagina=<?php echo ($pagina_actual + 1); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-folder-x text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-3 text-muted">No hay noticias en esta categoría</h3>
            <p class="text-muted">Aún no se han publicado noticias en <?php echo htmlspecialchars($categoria['nombre']); ?>.</p>
            <a href="noticias_todas.php" class="btn btn-primary">Ver todas las noticias</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>