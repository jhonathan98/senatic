<?php
$page_title = "Enviar Opinión - Periódico Digital";
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $asunto = trim($_POST['asunto']);
    $mensaje_usuario = trim($_POST['mensaje']);
    
    if (empty($nombre) || empty($correo) || empty($asunto) || empty($mensaje_usuario)) {
        $error = 'Por favor, complete todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un correo electrónico válido.';
    } else {
        // En un sistema real, aquí se enviaría por correo o se guardaría en base de datos
        // Por ahora solo mostramos mensaje de confirmación
        $mensaje = 'Gracias por tu mensaje. Lo revisaremos y te contactaremos pronto.';
        
        // Limpiar campos después del envío exitoso
        $nombre = $correo = $asunto = $mensaje_usuario = '';
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h2 class="mb-0"><i class="bi bi-envelope-paper"></i> Enviar Opinión</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        ¿Tienes alguna sugerencia, comentario o idea para nuestro periódico digital? 
                        Nos encantaría escucharte. Tu opinión es muy importante para nosotros.
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="correo" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="correo" name="correo" 
                                       value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="asunto" class="form-label">Asunto *</label>
                            <select class="form-select" id="asunto" name="asunto" required>
                                <option value="">Seleccionar tipo de mensaje</option>
                                <option value="Sugerencia" <?php echo (isset($asunto) && $asunto == 'Sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                                <option value="Felicitacion" <?php echo (isset($asunto) && $asunto == 'Felicitacion') ? 'selected' : ''; ?>>Felicitación</option>
                                <option value="Queja" <?php echo (isset($asunto) && $asunto == 'Queja') ? 'selected' : ''; ?>>Queja</option>
                                <option value="Idea para articulo" <?php echo (isset($asunto) && $asunto == 'Idea para articulo') ? 'selected' : ''; ?>>Idea para artículo</option>
                                <option value="Colaboracion" <?php echo (isset($asunto) && $asunto == 'Colaboracion') ? 'selected' : ''; ?>>Quiero colaborar</option>
                                <option value="Otro" <?php echo (isset($asunto) && $asunto == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Tu Mensaje *</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="6" 
                                      placeholder="Escribe aquí tu mensaje, sugerencia o comentario..." 
                                      required><?php echo isset($mensaje_usuario) ? htmlspecialchars($mensaje_usuario) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 
                                Todos los campos marcados con (*) son obligatorios. 
                                Nos pondremos en contacto contigo a la brevedad posible.
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../index.php" class="btn btn-outline-secondary me-md-2">
                                <i class="bi bi-arrow-left"></i> Volver al Inicio
                            </a>
                            <button type="submit" class="btn btn-info">
                                <i class="bi bi-send"></i> Enviar Mensaje
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información de contacto adicional -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-telephone"></i> Otras Formas de Contacto</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-geo-alt text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Visítanos</h6>
                            <p class="text-muted small">
                                Colegio Gonzalo Mejía<br>
                                Dirección del Colegio<br>
                                Ciudad, País
                            </p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-telephone text-success" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Llámanos</h6>
                            <p class="text-muted small">
                                +57 (xxx) xxx-xxxx<br>
                                Lunes a Viernes<br>
                                8:00 AM - 4:00 PM
                            </p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-envelope text-info" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Escríbenos</h6>
                            <p class="text-muted small">
                                info@colegiogonzalomejia.edu.co<br>
                                periodico@colegiogonzalomejia.edu.co<br>
                                Respuesta en 24-48 horas
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>