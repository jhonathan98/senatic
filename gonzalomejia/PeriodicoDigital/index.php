<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// includes/header.php (Incluye Bootstrap CSS)
include_once 'includes/header.php';
include_once 'includes/conexion.php';
include_once 'includes/navbar.php'; // Incluye la barra de navegación dinámica

// Obtener noticias destacadas (ejemplo simple)
$sql_destacadas = "SELECT n.id, n.titulo, n.resumen, n.imagen_principal, c.nombre AS categoria_nombre
                   FROM noticias n
                   JOIN categorias c ON n.id_categoria = c.id
                   WHERE n.destacada = TRUE AND n.activa = TRUE
                   ORDER BY n.fecha_publicacion DESC LIMIT 3";
$result_destacadas = mysqli_query($conexion, $sql_destacadas);

// Obtener noticias generales
$sql_generales = "SELECT n.id, n.titulo, n.resumen, n.imagen_principal, c.nombre AS categoria_nombre
                  FROM noticias n
                  JOIN categorias c ON n.id_categoria = c.id
                  WHERE n.destacada = FALSE AND n.activa = TRUE
                  ORDER BY n.fecha_publicacion DESC LIMIT 10";
$result_generales = mysqli_query($conexion, $sql_generales);
?>
<div class="container mt-4">
    <h1 class="text-center mb-4">Periódico Digital - Colegio Gonzalo Mejía</h1>
    <p class="text-center">¡Hola! Mira lo más reciente</p>

    <!-- Carrusel de Noticias Destacadas -->
    <div id="carruselNoticias" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php
            $i = 0;
            while ($fila = mysqli_fetch_assoc($result_destacadas)) {
                $active_class = ($i == 0) ? 'active' : '';
                echo '<button type="button" data-bs-target="#carruselNoticias" data-bs-slide-to="' . $i . '" class="' . $active_class . '" aria-label="Slide ' . ($i + 1) . '"></button>';
                $i++;
            }
            // Reiniciar el puntero para volver a usarlo
            mysqli_data_seek($result_destacadas, 0);
            ?>
        </div>
        <div class="carousel-inner">
            <?php
            $i = 0;
            while ($fila = mysqli_fetch_assoc($result_destacadas)) {
                $active_class = ($i == 0) ? 'active' : '';
                $imagen_url = $fila['imagen_principal'] ? 'assets/images/noticias/' . htmlspecialchars($fila['imagen_principal']) : 'https://i.ytimg.com/vi/5clLNMc7S7c/maxresdefault.jpg?text=Sin+Imagen';
                echo '<div class="carousel-item ' . $active_class . '">';
                echo '<img src="' . $imagen_url . '" class="d-block w-100" alt="' . htmlspecialchars($fila['titulo']) . '">';
                echo '<div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75">';
                echo '<h5>' . htmlspecialchars($fila['titulo']) . '</h5>';
                echo '<p>' . htmlspecialchars($fila['resumen']) . '</p>';
                echo '<a href="vistas/noticias_detalle.php?id=' . $fila['id'] . '" class="btn btn-primary">Leer más</a>';
                echo '</div>';
                echo '</div>';
                $i++;
            }
            ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carruselNoticias" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carruselNoticias" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    </div>

    <!-- Contenido General (Tarjetas de Noticias) -->
    <div class="row">
        <?php
        while ($fila = mysqli_fetch_assoc($result_generales)) {
            $imagen_url = $fila['imagen_principal'] ? 'assets/images/noticias/' . htmlspecialchars($fila['imagen_principal']) : 'https://quizizz.com/media/resource/gs/quizizz-media/quizzes/1eb3e518-27ae-4fce-9411-01ca3e1aabe9?w=200&h=200/150x150?text=Sin+Imagen';
            echo '<div class="col-md-6 col-lg-4 mb-4">';
            echo '<div class="card h-100">';
            echo '<img src="' . $imagen_url . '" class="card-img-top" alt="' . htmlspecialchars($fila['titulo']) . '">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($fila['titulo']) . '</h5>';
            echo '<p class="card-text">' . htmlspecialchars($fila['resumen']) . '</p>';
            echo '<small class="text-muted">Categoría: ' . htmlspecialchars($fila['categoria_nombre']) . '</small>';
            echo '</div>';
            echo '<div class="card-footer">';
            echo '<a href="vistas/noticias_detalle.php?id=' . $fila['id'] . '" class="btn btn-primary">Leer Artículo</a>';
            // Botón de opciones (guardar, compartir, reaccionar)
            echo '<div class="dropdown float-end">';
            echo '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownOpciones' . $fila['id'] . '" data-bs-toggle="dropdown" aria-expanded="false">...</button>';
            echo '<ul class="dropdown-menu" aria-labelledby="dropdownOpciones' . $fila['id'] . '">';
            echo '<li><a class="dropdown-item" href="#">Guardar</a></li>';
            echo '<li><a class="dropdown-item" href="#">Compartir</a></li>';
            echo '<li><a class="dropdown-item" href="#">Reaccionar</a></li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>
<?php
// includes/footer.php (Incluye Bootstrap JS y scripts personalizados)
include_once 'includes/footer.php';
?>