<?php
$page_title = "Todas las Noticias - Periódico Digital";
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

// Paginación
$por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $por_pagina;

// Filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria_filtro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

// Construir consulta
$where_conditions = ["n.activa = TRUE"];
$params = [];
$types = "";

if (!empty($busqueda)) {
    $where_conditions[] = "(n.titulo LIKE ? OR n.contenido LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "ss";
}

if ($categoria_filtro > 0) {
    $where_conditions[] = "n.id_categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Contar total de noticias
$sql_total = "SELECT COUNT(*) as total 
              FROM noticias n 
              JOIN categorias c ON n.id_categoria = c.id 
              WHERE $where_clause";

$stmt_total = mysqli_prepare($conexion, $sql_total);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_total, $types, ...$params);
}
mysqli_stmt_execute($stmt_total);
$resultado_total = mysqli_stmt_get_result($stmt_total);
$total_noticias = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_noticias / $por_pagina);

// Obtener noticias
$sql_noticias = "SELECT n.id, n.titulo, n.resumen, n.imagen_principal, n.fecha_publicacion, 
                        c.nombre AS categoria_nombre, u.nombre AS autor_nombre, u.apellido AS autor_apellido
                 FROM noticias n
                 JOIN categorias c ON n.id_categoria = c.id
                 JOIN usuarios u ON n.id_autor = u.id
                 WHERE $where_clause
                 ORDER BY n.fecha_publicacion DESC
                 LIMIT ?, ?";

$stmt_noticias = mysqli_prepare($conexion, $sql_noticias);
$params[] = $inicio;
$params[] = $por_pagina;
$types .= "ii";
mysqli_stmt_bind_param($stmt_noticias, $types, ...$params);
mysqli_stmt_execute($stmt_noticias);
$noticias = mysqli_stmt_get_result($stmt_noticias);

// Obtener categorías para el filtro
$sql_categorias = "SELECT * FROM categorias ORDER BY nombre";
$categorias = mysqli_query($conexion, $sql_categorias);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h1><i class="bi bi-newspaper"></i> Todas las Noticias</h1>
            <p class="text-muted">Explora todas nuestras publicaciones</p>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="mb-0"><strong>Total:</strong> <?php echo $total_noticias; ?> noticias</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="busqueda" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Buscar en título o contenido...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="0">Todas las categorías</option>
                        <?php while ($categoria = mysqli_fetch_assoc($categorias)): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo ($categoria_filtro == $categoria['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Noticias -->
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
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($noticia['categoria_nombre']); ?></span>
                            </div>
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
                                <small>
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
                            <a class="page-link" href="?pagina=<?php echo ($pagina_actual - 1); ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $categoria_filtro; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $categoria_filtro; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?php echo ($pagina_actual + 1); ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo $categoria_filtro; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-3 text-muted">No se encontraron noticias</h3>
            <p class="text-muted">Prueba ajustando los filtros de búsqueda.</p>
            <a href="noticias_todas.php" class="btn btn-primary">Ver todas las noticias</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>