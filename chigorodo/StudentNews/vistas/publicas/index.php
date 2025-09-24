<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Obtener artículos destacados (últimos 3 publicados)
$stmt = $pdo->prepare("SELECT a.*, u.nombre_usuario, c.nombre_categoria FROM articulos a 
                       JOIN usuarios u ON a.id_autor = u.id_usuario 
                       JOIN categorias c ON a.id_categoria = c.id_categoria 
                       WHERE a.estado = 'publicado' 
                       ORDER BY a.fecha_publicacion DESC LIMIT 3");
$stmt->execute();
$articulos_destacados = $stmt->fetchAll();

// Obtener próximos eventos
$stmt = $pdo->prepare("SELECT * FROM eventos ORDER BY fecha_evento ASC LIMIT 3");
$stmt->execute();
$eventos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student News - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container my-4">
        <!-- Carrusel o Tarjetas Destacadas -->
        <section class="mb-5">
            <h2 class="text-center mb-4">Últimas noticias</h2>
            <div class="row">
                <?php foreach ($articulos_destacados as $articulo): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if ($articulo['imagen_destacada']): ?>
                        <img src="../assets/images/uploads/<?= htmlspecialchars($articulo['imagen_destacada']) ?>" class="card-img-top" alt="<?= htmlspecialchars($articulo['titulo']) ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($articulo['titulo']) ?></h5>
                            <p class="card-text text-muted small">Por <?= htmlspecialchars($articulo['nombre_usuario']) ?> en <?= htmlspecialchars($articulo['nombre_categoria']) ?></p>
                            <p class="card-text"><?= substr(strip_tags($articulo['contenido']), 0, 100) ?>...</p>
                            <a href="articulo.php?id=<?= $articulo['id_articulo'] ?>" class="btn btn-outline-primary mt-auto">Ver más</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Próximos Eventos -->
        <section class="mb-5">
            <h2 class="text-center mb-4">Próximos eventos</h2>
            <div class="row">
                <?php foreach ($eventos as $evento): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($evento['titulo']) ?></h5>
                            <p class="card-text"><i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($evento['fecha_evento'])) ?></p>
                            <p class="card-text"><?= htmlspecialchars($evento['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>