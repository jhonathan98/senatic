<?php
session_start();
require_once '../../includes/db.php';

// Verificar que se proporcionó un ID de categoría
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../index.php");
    exit();
}

$id_categoria = (int)$_GET['id'];

try {
    $pdo = getDB();
    
    // Obtener información de la categoría
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
    $stmt->execute([$id_categoria]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        header("Location: ../../index.php");
        exit();
    }
    
    // Obtener artículos de esta categoría
    $stmt = $pdo->prepare("
        SELECT a.id_articulo, a.titulo, a.contenido, a.imagen_destacada, a.fecha_publicacion, a.vistas,
               u.nombre_completo, u.nombre_usuario
        FROM articulos a
        JOIN usuarios u ON a.id_autor = u.id_usuario
        WHERE a.id_categoria = ? AND a.estado = 'publicado'
        ORDER BY a.fecha_publicacion DESC
    ");
    $stmt->execute([$id_categoria]);
    $articulos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error en categorias.php: " . $e->getMessage());
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nombre_categoria']) ?> - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">
                STUDENT NEWS<br>
                <small class="text-light" style="font-size: 0.8rem;">Tu voz, nuestra noticia</small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../../index.php">Noticias</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">Categorías</a>
                        <ul class="dropdown-menu">
                            <?php
                            try {
                                $stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria");
                                $categorias = $stmtCat->fetchAll();
                                foreach ($categorias as $cat):
                            ?>
                            <li><a class="dropdown-item <?= $cat['id_categoria'] == $id_categoria ? 'active' : '' ?>" 
                                   href="categorias.php?id=<?= $cat['id_categoria'] ?>">
                                <i class="<?= $cat['icono'] ?>"></i> <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            </a></li>
                            <?php endforeach; } catch (Exception $e) {} ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="participa.php">Participa</a></li>
                    <li class="nav-item"><a class="nav-link" href="contacto.php">Contacto</a></li>
                </ul>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <?= htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../privadas/dashboard.php">Mi Panel</a></li>
                            <li><a class="dropdown-item" href="../privadas/perfil.php">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../../procesos/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar Sesión</a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Header de categoría -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-2">
                        <i class="<?= $categoria['icono'] ?>"></i> 
                        <?= htmlspecialchars($categoria['nombre_categoria']) ?>
                    </h1>
                    <p class="lead mb-0">
                        Explora todos los artículos de <?= htmlspecialchars($categoria['nombre_categoria']) ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white bg-opacity-20 rounded p-3">
                        <h3 class="mb-0"><?= count($articulos) ?></h3>
                        <small>Artículos publicados</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container my-5">
        <?php if ($articulos): ?>
            <div class="row">
                <?php foreach ($articulos as $articulo): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <?php if (!empty($articulo['imagen_destacada'])): ?>
                                <img src="../../assets/images/uploads/<?= htmlspecialchars($articulo['imagen_destacada']) ?>" 
                                     class="img-fluid rounded-start h-100" style="object-fit: cover;" 
                                     alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                                <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center h-100 rounded-start">
                                    <i class="<?= $categoria['icono'] ?> fs-1 text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body d-flex flex-column h-100">
                                    <h5 class="card-title">
                                        <a href="articulo.php?id=<?= $articulo['id_articulo'] ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($articulo['titulo']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text flex-grow-1">
                                        <?= substr(strip_tags($articulo['contenido']), 0, 120) ?>...
                                    </p>
                                    <div class="mt-auto">
                                        <small class="text-muted d-block">
                                            Por <?= htmlspecialchars($articulo['nombre_completo']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= date('d \d\e F, Y', strtotime($articulo['fecha_publicacion'])) ?> • 
                                            <i class="bi bi-eye"></i> <?= $articulo['vistas'] ?> vistas
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="<?= $categoria['icono'] ?> text-muted" style="font-size: 5rem;"></i>
                <h3 class="mt-3 text-muted">No hay artículos en esta categoría</h3>
                <p class="text-muted">Sé el primero en publicar contenido sobre <?= htmlspecialchars($categoria['nombre_categoria']) ?></p>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="../privadas/articulo_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Artículo
                </a>
                <?php else: ?>
                <a href="participa.php" class="btn btn-primary">
                    <i class="bi bi-lightbulb"></i> Enviar Idea
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pie de página -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1"><strong>STUDENT NEWS</strong> • contacto@studentnews.com</p>
            <p class="mb-0">© 2024 Student News. Equipo estudiantil.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
