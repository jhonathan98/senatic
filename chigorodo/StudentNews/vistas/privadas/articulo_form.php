<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión solo si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publicas/login.php");
    exit();
}

require_once '../../includes/db.php';

try {
    $pdo = getDB();
    // Cargar categorías
    $categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria")->fetchAll();
} catch (Exception $e) {
    error_log("Error en articulo_form.php: " . $e->getMessage());
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Artículo - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-4">
        <h2>Crear nuevo artículo</h2>

        <?php
        // Mostrar mensajes de error o éxito
        if (isset($_SESSION['mensaje'])) {
            $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
            echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION['mensaje'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        }

        // Obtener datos del formulario si hay errores
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        ?>

        <form action="../../procesos/articulo_proceso.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="titulo" 
                       value="<?= htmlspecialchars($form_data['titulo'] ?? '') ?>" 
                       maxlength="255" required>
                <div class="form-text">Mínimo 5 caracteres, máximo 255.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="id_categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>" 
                            <?= ($form_data['id_categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre_categoria']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Imagen destacada</label>
                <input type="file" class="form-control" name="imagen_destacada" accept="image/*">
            </div>
            <div class="mb-3">
                <label class="form-label">Contenido</label>
                <textarea class="form-control" name="contenido" rows="12" required><?= htmlspecialchars($form_data['contenido'] ?? '') ?></textarea>
                <div class="form-text">Mínimo 50 caracteres. Puedes usar HTML básico para formato.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">¿Qué deseas hacer?</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="accion" value="borrador" 
                           <?= ($form_data['accion'] ?? 'borrador') === 'borrador' ? 'checked' : '' ?> id="borrador">
                    <label class="form-check-label" for="borrador">
                        <strong>Guardar como borrador</strong><br>
                        <small class="text-muted">Podrás editarlo después</small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="accion" value="enviar" 
                           <?= ($form_data['accion'] ?? '') === 'enviar' ? 'checked' : '' ?> id="enviar">
                    <label class="form-check-label" for="enviar">
                        <strong>Enviar a revisión</strong><br>
                        <small class="text-muted">Un administrador lo revisará para publicación</small>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Guardar</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titulo = document.querySelector('input[name="titulo"]');
            const contenido = document.querySelector('textarea[name="contenido"]');
            const form = document.querySelector('form');

            // Contador de caracteres para título
            function updateTituloCounter() {
                const length = titulo.value.length;
                const maxLength = 255;
                let message = `${length}/${maxLength} caracteres`;
                
                if (length < 5) {
                    message += ' (mínimo 5)';
                    titulo.setCustomValidity('El título debe tener al menos 5 caracteres');
                } else if (length > maxLength) {
                    message += ' (excede el límite)';
                    titulo.setCustomValidity('El título no puede exceder 255 caracteres');
                } else {
                    titulo.setCustomValidity('');
                }
                
                // Actualizar el texto de ayuda
                const helpText = titulo.nextElementSibling;
                if (helpText && helpText.classList.contains('form-text')) {
                    helpText.textContent = `Mínimo 5 caracteres, máximo 255. ${message}`;
                }
            }

            // Contador de caracteres para contenido
            function updateContenidoCounter() {
                const length = contenido.value.length;
                const minLength = 50;
                let message = `${length} caracteres`;
                
                if (length < minLength) {
                    message += ` (mínimo ${minLength})`;
                    contenido.setCustomValidity('El contenido debe tener al menos 50 caracteres');
                } else {
                    contenido.setCustomValidity('');
                }
                
                // Actualizar el texto de ayuda
                const helpText = contenido.nextElementSibling;
                if (helpText && helpText.classList.contains('form-text')) {
                    helpText.textContent = `Mínimo 50 caracteres. Puedes usar HTML básico para formato. ${message}`;
                }
            }

            // Event listeners
            titulo.addEventListener('input', updateTituloCounter);
            contenido.addEventListener('input', updateContenidoCounter);

            // Inicializar contadores
            updateTituloCounter();
            updateContenidoCounter();

            // Validación antes de enviar
            form.addEventListener('submit', function(e) {
                const tituloLength = titulo.value.trim().length;
                const contenidoLength = contenido.value.trim().length;
                
                if (tituloLength < 5) {
                    e.preventDefault();
                    alert('El título debe tener al menos 5 caracteres');
                    titulo.focus();
                    return;
                }
                
                if (contenidoLength < 50) {
                    e.preventDefault();
                    alert('El contenido debe tener al menos 50 caracteres');
                    contenido.focus();
                    return;
                }
            });

            // Auto-resize textarea
            contenido.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>