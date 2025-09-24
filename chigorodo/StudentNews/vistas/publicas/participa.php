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
        $telefono = trim($_POST['telefono'] ?? '');
        $tipo_participacion = $_POST['tipo_participacion'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // Validaciones básicas
        if (empty($nombre) || empty($email) || empty($tipo_participacion) || empty($titulo) || empty($descripcion)) {
            throw new Exception("Todos los campos obligatorios deben estar completos.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email no tiene un formato válido.");
        }
        
        // Insertar en base de datos (tabla de participaciones/sugerencias)
        $stmt = $pdo->prepare("
            INSERT INTO participaciones (nombre, email, telefono, tipo_participacion, titulo, descripcion, fecha_envio, estado) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pendiente')
        ");
        
        $stmt->execute([$nombre, $email, $telefono, $tipo_participacion, $titulo, $descripcion]);
        
        $mensaje = "¡Gracias por tu participación! Hemos recibido tu propuesta y la revisaremos pronto.";
        $tipo_mensaje = 'success';
        
        // Limpiar campos después del envío exitoso
        $_POST = [];
        
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener estadísticas de participación
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM participaciones WHERE estado = 'aprobada'");
    $participaciones_aprobadas = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    $participaciones_aprobadas = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participa - Student News</title>
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
                    <li class="nav-item"><a class="nav-link active" href="participa.php">Participa</a></li>
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

    <!-- Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-2">
                        <i class="bi bi-lightbulb"></i> Participa
                    </h1>
                    <p class="lead mb-0">
                        Comparte tus ideas, noticias y propuestas con la comunidad estudiantil
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white bg-opacity-20 rounded p-3">
                        <h3 class="mb-0"><?= $participaciones_aprobadas ?></h3>
                        <small>Participaciones publicadas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container my-5">
        <div class="row">
            <!-- Formulario de participación -->
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-pencil"></i> Envía tu propuesta
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

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono (opcional)</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_participacion" class="form-label">Tipo de participación *</label>
                                    <select class="form-select" id="tipo_participacion" name="tipo_participacion" required>
                                        <option value="">Selecciona una opción</option>
                                        <option value="noticia" <?= ($_POST['tipo_participacion'] ?? '') == 'noticia' ? 'selected' : '' ?>>
                                            Noticia/Artículo
                                        </option>
                                        <option value="evento" <?= ($_POST['tipo_participacion'] ?? '') == 'evento' ? 'selected' : '' ?>>
                                            Evento estudiantil
                                        </option>
                                        <option value="sugerencia" <?= ($_POST['tipo_participacion'] ?? '') == 'sugerencia' ? 'selected' : '' ?>>
                                            Sugerencia
                                        </option>
                                        <option value="denuncia" <?= ($_POST['tipo_participacion'] ?? '') == 'denuncia' ? 'selected' : '' ?>>
                                            Denuncia/Problema
                                        </option>
                                        <option value="otro" <?= ($_POST['tipo_participacion'] ?? '') == 'otro' ? 'selected' : '' ?>>
                                            Otro
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título de tu propuesta *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" 
                                       placeholder="Escribe un título llamativo y descriptivo" required>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción detallada *</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="6" 
                                          placeholder="Describe tu propuesta con el mayor detalle posible..." required><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Enviar Propuesta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información lateral -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle"></i> ¿Cómo participar?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <small>1</small>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Completa el formulario</h6>
                                <small class="text-muted">Proporciona toda la información necesaria</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <small>2</small>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Revisión del equipo</h6>
                                <small class="text-muted">Nuestro equipo revisará tu propuesta</small>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <small>3</small>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Publicación</h6>
                                <small class="text-muted">Si es aprobada, se publicará en el sitio</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Consejos importantes
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Sé específico y detallado en tu descripción
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Verifica que tu información sea veraz
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Respeta las normas de convivencia
                            </li>
                            <li class="mb-0">
                                <i class="bi bi-check-circle text-success"></i>
                                Mantén un lenguaje apropiado y respetuoso
                            </li>
                        </ul>
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
