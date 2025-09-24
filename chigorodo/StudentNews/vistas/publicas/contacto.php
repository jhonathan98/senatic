<?php
session_start();
require_once '../../includes/db.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar envío de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $asunto = trim($_POST['asunto'] ?? '');
        $mensaje_contenido = trim($_POST['mensaje'] ?? '');
        
        // Validaciones básicas
        if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje_contenido)) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email no tiene un formato válido.");
        }
        
        // Insertar en base de datos
        $stmt = $pdo->prepare("
            INSERT INTO contactos (nombre, email, asunto, mensaje, fecha_envio, estado) 
            VALUES (?, ?, ?, ?, NOW(), 'pendiente')
        ");
        
        $stmt->execute([$nombre, $email, $asunto, $mensaje_contenido]);
        
        $mensaje = "¡Gracias por contactarnos! Hemos recibido tu mensaje y te responderemos pronto.";
        $tipo_mensaje = 'success';
        
        // Limpiar campos después del envío exitoso
        $_POST = [];
        
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - Student News</title>
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Categorías</a>
                        <ul class="dropdown-menu">
                            <?php
                            try {
                                $pdo = getDB();
                                $stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria");
                                $categorias = $stmtCat->fetchAll();
                                foreach ($categorias as $cat):
                            ?>
                            <li><a class="dropdown-item" href="categorias.php?id=<?= $cat['id_categoria'] ?>">
                                <i class="<?= $cat['icono'] ?>"></i> <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            </a></li>
                            <?php endforeach; } catch (Exception $e) {} ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="participa.php">Participa</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contacto.php">Contacto</a></li>
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

    <!-- Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-2">
                        <i class="bi bi-envelope"></i> Contacto
                    </h1>
                    <p class="lead mb-0">
                        ¿Tienes preguntas o sugerencias? Nos encantaría escucharte
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white bg-opacity-20 rounded p-3">
                        <i class="bi bi-people fs-2"></i>
                        <div>Comunidad estudiantil</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container my-5">
        <div class="row">
            <!-- Formulario de contacto -->
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-chat-dots"></i> Envíanos un mensaje
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($mensaje) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="asunto" class="form-label">Asunto *</label>
                                <select class="form-select" id="asunto" name="asunto" required>
                                    <option value="">Selecciona un asunto</option>
                                    <option value="Consulta general" <?= ($_POST['asunto'] ?? '') == 'Consulta general' ? 'selected' : '' ?>>
                                        Consulta general
                                    </option>
                                    <option value="Problema técnico" <?= ($_POST['asunto'] ?? '') == 'Problema técnico' ? 'selected' : '' ?>>
                                        Problema técnico
                                    </option>
                                    <option value="Sugerencia" <?= ($_POST['asunto'] ?? '') == 'Sugerencia' ? 'selected' : '' ?>>
                                        Sugerencia de mejora
                                    </option>
                                    <option value="Colaboración" <?= ($_POST['asunto'] ?? '') == 'Colaboración' ? 'selected' : '' ?>>
                                        Propuesta de colaboración
                                    </option>
                                    <option value="Queja" <?= ($_POST['asunto'] ?? '') == 'Queja' ? 'selected' : '' ?>>
                                        Queja o reclamo
                                    </option>
                                    <option value="Otro" <?= ($_POST['asunto'] ?? '') == 'Otro' ? 'selected' : '' ?>>
                                        Otro
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="mensaje" class="form-label">Mensaje *</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="6" 
                                          placeholder="Escribe tu mensaje aquí..." required><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Enviar Mensaje
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información de contacto -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle"></i> Información de contacto
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-envelope-fill text-primary fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Email</h6>
                                <a href="mailto:contacto@studentnews.com" class="text-decoration-none">
                                    contacto@studentnews.com
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-clock-fill text-primary fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Horario de atención</h6>
                                <small class="text-muted">
                                    Lunes a Viernes<br>
                                    8:00 AM - 5:00 PM
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="bi bi-chat-square-dots-fill text-primary fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Tiempo de respuesta</h6>
                                <small class="text-muted">
                                    Respondemos en un máximo de 24 horas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people"></i> Nuestro equipo
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Student News es un proyecto estudiantil creado por y para estudiantes. 
                            Nuestro objetivo es crear un espacio donde la comunidad educativa pueda 
                            informarse y participar activamente.
                        </p>
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="bi bi-newspaper text-primary fs-1"></i>
                                <div class="small text-muted">Noticias</div>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-calendar-event text-success fs-1"></i>
                                <div class="small text-muted">Eventos</div>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-chat-heart text-danger fs-1"></i>
                                <div class="small text-muted">Comunidad</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-question-circle"></i> Preguntas frecuentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion accordion-flush" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        ¿Cómo puedo publicar un artículo?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Puedes registrarte en la plataforma y enviar tu artículo desde la sección "Participa".
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        ¿Quién puede participar?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Cualquier miembro de la comunidad educativa puede participar enviando propuestas.
                                    </div>
                                </div>
                            </div>
                        </div>
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
</body>
</html>
