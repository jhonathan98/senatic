<?php
session_start();
require_once '../../includes/db.php';

// Verificar que se proporcionó un ID de artículo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../index.php");
    exit();
}

$id_articulo = (int)$_GET['id'];

try {
    $pdo = getDB();
    
    // Obtener el artículo
    $stmt = $pdo->prepare("
        SELECT a.*, u.nombre_completo, u.nombre_usuario, c.nombre_categoria, c.icono
        FROM articulos a
        JOIN usuarios u ON a.id_autor = u.id_usuario
        JOIN categorias c ON a.id_categoria = c.id_categoria
        WHERE a.id_articulo = ? AND a.estado = 'publicado'
    ");
    $stmt->execute([$id_articulo]);
    $articulo = $stmt->fetch();
    
    if (!$articulo) {
        header("Location: ../../index.php");
        exit();
    }
    
    // Incrementar contador de vistas
    $stmt = $pdo->prepare("UPDATE articulos SET vistas = vistas + 1 WHERE id_articulo = ?");
    $stmt->execute([$id_articulo]);
    
    // Obtener artículos relacionados (misma categoría)
    $stmt = $pdo->prepare("
        SELECT a.id_articulo, a.titulo, a.imagen_destacada, a.fecha_publicacion, u.nombre_usuario
        FROM articulos a
        JOIN usuarios u ON a.id_autor = u.id_usuario
        WHERE a.id_categoria = ? AND a.id_articulo != ? AND a.estado = 'publicado'
        ORDER BY a.fecha_publicacion DESC
        LIMIT 3
    ");
    $stmt->execute([$articulo['id_categoria'], $id_articulo]);
    $articulos_relacionados = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error en articulo.php: " . $e->getMessage());
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($articulo['titulo']) ?> - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($articulo['contenido']), 0, 150)) ?>">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">
                STUDENT NEWS<br>
                <small class="text-light" style="font-size: 0.8rem;">Tu voz, nuestra noticia</small>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="../../index.php">
                    <i class="bi bi-arrow-left"></i> Volver al inicio
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <!-- Contenido principal del artículo -->
            <div class="col-lg-8">
                <article class="card shadow-sm">
                    <!-- Imagen destacada -->
                    <?php if (!empty($articulo['imagen_destacada'])): ?>
                    <img src="../../assets/images/uploads/<?= htmlspecialchars($articulo['imagen_destacada']) ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($articulo['titulo']) ?>"
                         style="height: 400px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <!-- Metadata del artículo -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary">
                                <i class="<?= $articulo['icono'] ?>"></i> <?= htmlspecialchars($articulo['nombre_categoria']) ?>
                            </span>
                            <small class="text-muted">
                                <i class="bi bi-eye"></i> <?= $articulo['vistas'] + 1 ?> vistas
                            </small>
                        </div>
                        
                        <!-- Título -->
                        <h1 class="card-title mb-4"><?= htmlspecialchars($articulo['titulo']) ?></h1>
                        
                        <!-- Información del autor y fecha -->
                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                            <div class="me-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <?= strtoupper(substr($articulo['nombre_completo'], 0, 1)) ?>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($articulo['nombre_completo']) ?></h6>
                                <small class="text-muted">
                                    @<?= htmlspecialchars($articulo['nombre_usuario']) ?> • 
                                    <?= date('d \d\e F \d\e Y', strtotime($articulo['fecha_publicacion'])) ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Contenido del artículo -->
                        <div class="article-content" style="line-height: 1.8;">
                            <?= nl2br($articulo['contenido']) ?>
                        </div>
                    </div>
                </article>
                
                <!-- Botones de compartir -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Compartir este artículo</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="compartirWhatsApp()">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="compartirTwitter()">
                                <i class="bi bi-twitter"></i> Twitter
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="copiarEnlace()">
                                <i class="bi bi-link"></i> Copiar enlace
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Artículos relacionados -->
                <?php if ($articulos_relacionados): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Artículos relacionados</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($articulos_relacionados as $relacionado): ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <?php if (!empty($relacionado['imagen_destacada'])): ?>
                                <img src="../../assets/images/uploads/<?= htmlspecialchars($relacionado['imagen_destacada']) ?>" 
                                     class="rounded" width="60" height="60" style="object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px; color: white;">
                                    <i class="bi bi-file-text"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">
                                    <a href="articulo.php?id=<?= $relacionado['id_articulo'] ?>" 
                                       class="text-decoration-none">
                                        <?= htmlspecialchars(substr($relacionado['titulo'], 0, 50)) ?>...
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?= date('d/m/Y', strtotime($relacionado['fecha_publicacion'])) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Banner de participación -->
                <div class="card bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-center">
                        <i class="bi bi-lightbulb fs-1 mb-3"></i>
                        <h5>¿Tienes una historia que contar?</h5>
                        <p class="mb-3">Comparte tu experiencia y forma parte de Student News</p>
                        <a href="participa.php" class="btn btn-light">
                            <i class="bi bi-plus-circle"></i> Participar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de página -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1"><strong>STUDENT NEWS</strong> • contacto@studentnews.com</p>
            <p class="mb-0">© 2024 Student News. Equipo estudiantil.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function compartirWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('<?= htmlspecialchars($articulo['titulo']) ?> - Student News');
            window.open(`https://wa.me/?text=${text} ${url}`, '_blank');
        }
        
        function compartirTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('<?= htmlspecialchars($articulo['titulo']) ?> - Student News');
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }
        
        function copiarEnlace() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Enlace copiado al portapapeles');
            });
        }
    </script>
</body>
</html>
