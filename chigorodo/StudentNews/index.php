<?php
session_start();

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: vistas/publicas/login.php");
    exit();
}

// Conexión a la base de datos
require_once 'includes/db.php';

try {
    // Obtener la conexión PDO
    $pdo = getDB();

// Obtener las últimas 3 noticias publicadas
$stmt = $pdo->prepare("
    SELECT a.id_articulo, a.titulo, a.contenido, a.imagen_destacada, a.fecha_publicacion,
           u.nombre_usuario, c.nombre_categoria
    FROM articulos a
    JOIN usuarios u ON a.id_autor = u.id_usuario
    JOIN categorias c ON a.id_categoria = c.id_categoria
    WHERE a.estado = 'publicado'
    ORDER BY a.fecha_publicacion DESC
    LIMIT 3
");
$stmt->execute();
$noticias_destacadas = $stmt->fetchAll();

// Obtener los próximos 3 eventos
$stmt = $pdo->prepare("
    SELECT titulo, descripcion, fecha_evento
    FROM eventos
    WHERE fecha_evento >= CURDATE()
    ORDER BY fecha_evento ASC
    LIMIT 3
");
$stmt->execute();
$eventos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // En caso de error, inicializar arrays vacíos
    $noticias_destacadas = [];
    $eventos = [];
    error_log("Error en index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student News - Tu voz, nuestra noticia</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .hero {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .hero h1 {
            font-weight: 800;
            font-size: 2.8rem;
        }
        .hero p {
            font-size: 1.3rem;
            opacity: 0.9;
        }
        .card-img-top {
            object-fit: cover;
            height: 200px;
        }
        .calendar-day {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
            margin: 2px;
        }
        .calendar-event {
            background-color: #e0f7fa;
            border-left: 4px solid #0288d1;
            padding: 8px;
            margin-top: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            STUDENT NEWS<br>
            <small class="text-light" style="font-size: 0.8rem;">Tu voz, nuestra noticia</small>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">Noticias</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Categorías</a>
                    <ul class="dropdown-menu">
                        <?php
                        $stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria");
                        $categorias = $stmtCat->fetchAll();
                        foreach ($categorias as $cat):
                        ?>
                        <li><a class="dropdown-item" href="vistas/publicas/categorias.php?id=<?= $cat['id_categoria'] ?>">
                            <i class="<?= $cat['icono'] ?>"></i> <?= htmlspecialchars($cat['nombre_categoria']) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="vistas/publicas/participa.php">Participa</a></li>
                <li class="nav-item"><a class="nav-link" href="vistas/publicas/contacto.php">Contacto</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="vistas/privadas/dashboard.php">Mi Panel</a></li>
                        <li><a class="dropdown-item" href="vistas/privadas/perfil.php">Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="procesos/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero / Lema -->
<div class="hero">
    <div class="container">
        <h1>STUDENT NEWS</h1>
        <p class="lead">Tu voz, nuestra noticia</p>
    </div>
</div>

<!-- Contenido principal -->
<main class="container my-5">
    <!-- Últimas noticias -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Últimas noticias</h2>
        <div class="row">
            <?php if ($noticias_destacadas): ?>
                <?php foreach ($noticias_destacadas as $noticia): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($noticia['imagen_destacada'])): ?>
                        <img src="assets/images/uploads/<?= htmlspecialchars($noticia['imagen_destacada']) ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2"><?= htmlspecialchars($noticia['nombre_categoria']) ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($noticia['titulo']) ?></h5>
                            <p class="card-text flex-grow-1"><?= substr(strip_tags($noticia['contenido']), 0, 100) ?>...</p>
                            <a href="vistas/publicas/articulo.php?id=<?= $noticia['id_articulo'] ?>" class="btn btn-outline-primary mt-auto">Ver más</a>
                        </div>
                        <div class="card-footer text-muted small">
                            Por <?= htmlspecialchars($noticia['nombre_usuario']) ?> • <?= date('d M Y', strtotime($noticia['fecha_publicacion'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center text-muted">No hay noticias publicadas aún.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Próximos eventos (Calendario simplificado) -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Próximos eventos</h2>
        <div class="row">
            <div class="col-md-8">
                <!-- Calendario visual (simplificado) -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="d-flex flex-wrap mb-3">
                        <?php
                        // Generar días del mes actual (simplificado)
                        $hoy = getdate();
                        $diasMes = cal_days_in_month(CAL_GREGORIAN, $hoy['mon'], $hoy['year']);
                        for ($i = 1; $i <= min(31, $diasMes); $i++) {
                            $esHoy = ($i == $hoy['mday']);
                            $tieneEvento = false;
                            foreach ($eventos as $e) {
                                if (date('j', strtotime($e['fecha_evento'])) == $i) {
                                    $tieneEvento = true;
                                    break;
                                }
                            }
                            $claseDia = $esHoy ? 'bg-primary text-white' : ($tieneEvento ? 'bg-warning' : 'bg-light');
                            echo "<div class='calendar-day $claseDia'>$i</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <h5>Eventos destacados</h5>
                <?php if ($eventos): ?>
                    <?php foreach ($eventos as $evento): ?>
                    <div class="calendar-event">
                        <strong><?= htmlspecialchars($evento['titulo']) ?></strong><br>
                        <small><?= date('d M', strtotime($evento['fecha_evento'])) ?></small>
                        <p class="mb-0"><?= htmlspecialchars(substr($evento['descripcion'], 0, 60)) ?>...</p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No hay eventos programados.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<!-- Pie de página -->
<footer class="bg-dark text-light py-4">
    <div class="container text-center">
        <p class="mb-1"><strong>STUDENT NEWS</strong> • contacto@studentnews.com</p>
        <p class="mb-0">© 2024 Student News. Equipo estudiantil.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>