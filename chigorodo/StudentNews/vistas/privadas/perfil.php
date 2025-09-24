<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publicas/login.php");
    exit();
}

require_once '../../includes/db.php';

$id_usuario = $_SESSION['id_usuario'];
$pdo = getDB();

// Obtener información del usuario
$stmt = $pdo->prepare("SELECT u.*, r.nombre_rol 
    FROM usuarios u 
    LEFT JOIN roles r ON u.id_rol = r.id_rol 
    WHERE u.id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

// Obtener estadísticas del usuario
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_articulos,
    SUM(CASE WHEN estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
    SUM(vistas) as total_vistas
    FROM articulos WHERE id_autor = ?");
$stmt->execute([$id_usuario]);
$estadisticas = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Student News</title>
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
                <small class="text-light" style="font-size: 0.8rem;">Mi Perfil</small>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="dashboard.php">
                    <i class="bi bi-arrow-left"></i> Volver al Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php
        // Mostrar mensajes de éxito/error
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

        <div class="row">
            <!-- Información del perfil -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php
                        $foto_perfil = $usuario['foto_perfil'] ?? 'default.jpg';
                        $ruta_foto = '../../assets/images/uploads/' . $foto_perfil;
                        
                        // Verificar si la imagen existe, si no usar default
                        if (!file_exists($ruta_foto) || $foto_perfil === 'default.jpg') {
                            // Usar un avatar por defecto basado en las iniciales
                            $iniciales = substr($usuario['nombre_completo'], 0, 1) . substr(strrchr($usuario['nombre_completo'], ' '), 1, 1);
                            echo '<div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; font-size: 2rem; font-weight: bold;">';
                            echo htmlspecialchars($iniciales);
                            echo '</div>';
                        } else {
                            echo '<img src="' . $ruta_foto . '" alt="Foto de perfil" class="rounded-circle mb-3" width="100" height="100" style="object-fit: cover;">';
                        }
                        ?>
                        
                        <h4><?= htmlspecialchars($usuario['nombre_completo']) ?></h4>
                        <p class="text-muted mb-1">@<?= htmlspecialchars($usuario['nombre_usuario']) ?></p>
                        <p class="text-muted mb-1"><?= htmlspecialchars($usuario['correo']) ?></p>
                        <span class="badge bg-primary mb-2"><?= ucfirst($usuario['nombre_rol'] ?? 'Usuario') ?></span>
                        
                        <?php if (!empty($usuario['descripcion'])): ?>
                        <p class="mt-3"><?= nl2br(htmlspecialchars($usuario['descripcion'])) ?></p>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> 
                                Miembro desde <?= date('F Y', strtotime($usuario['fecha_registro'])) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas del usuario -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Mis Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="text-primary"><?= $estadisticas['total_articulos'] ?? 0 ?></h4>
                                <small class="text-muted">Artículos</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-success"><?= $estadisticas['publicados'] ?? 0 ?></h4>
                                <small class="text-muted">Publicados</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-info"><?= $estadisticas['total_vistas'] ?? 0 ?></h4>
                                <small class="text-muted">Vistas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de edición -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-gear"></i> Editar Perfil</h5>
                    </div>
                    <div class="card-body">
                        <form action="../../procesos/perfil_proceso.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_completo" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                           value="<?= htmlspecialchars($form_data['nombre_completo'] ?? $usuario['nombre_completo']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="grado" class="form-label">Grado</label>
                                    <input type="text" class="form-control" id="grado" name="grado" 
                                           value="<?= htmlspecialchars($form_data['grado'] ?? $usuario['grado']) ?>" 
                                           placeholder="Ej: 10°A">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción personal</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          placeholder="Cuéntanos un poco sobre ti..."><?= htmlspecialchars($form_data['descripcion'] ?? $usuario['descripcion']) ?></textarea>
                                <div class="form-text">Máximo 500 caracteres.</div>
                            </div>

                            <div class="mb-3">
                                <label for="foto_perfil" class="form-label">Foto de perfil</label>
                                <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" 
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 2MB.</div>
                            </div>

                            <div class="mb-4">
                                <label for="correo_actual" class="form-label">Correo electrónico actual</label>
                                <input type="email" class="form-control" id="correo_actual" 
                                       value="<?= htmlspecialchars($usuario['correo']) ?>" disabled>
                                <div class="form-text">Para cambiar tu correo, contacta al administrador.</div>
                            </div>

                            <hr>

                            <h6 class="mb-3"><i class="bi bi-shield-lock"></i> Cambiar Contraseña (Opcional)</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nueva_contrasena" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" 
                                           minlength="6">
                                    <div class="form-text">Mínimo 6 caracteres. Déjalo vacío si no quieres cambiarla.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_contrasena" class="form-label">Confirmar nueva contraseña</label>
                                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" 
                                           minlength="6">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nuevaContrasena = document.getElementById('nueva_contrasena');
            const confirmarContrasena = document.getElementById('confirmar_contrasena');
            const descripcion = document.getElementById('descripcion');

            // Validación de contraseñas
            function validarContrasenas() {
                if (nuevaContrasena.value || confirmarContrasena.value) {
                    if (nuevaContrasena.value !== confirmarContrasena.value) {
                        confirmarContrasena.setCustomValidity('Las contraseñas no coinciden');
                    } else if (nuevaContrasena.value.length < 6) {
                        nuevaContrasena.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
                    } else {
                        nuevaContrasena.setCustomValidity('');
                        confirmarContrasena.setCustomValidity('');
                    }
                } else {
                    nuevaContrasena.setCustomValidity('');
                    confirmarContrasena.setCustomValidity('');
                }
            }

            nuevaContrasena.addEventListener('input', validarContrasenas);
            confirmarContrasena.addEventListener('input', validarContrasenas);

            // Contador de caracteres para descripción
            descripcion.addEventListener('input', function() {
                const maxLength = 500;
                const currentLength = this.value.length;
                const helpText = this.nextElementSibling;
                
                if (currentLength > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                    return;
                }
                
                helpText.textContent = `Máximo 500 caracteres. ${currentLength}/${maxLength}`;
                
                if (currentLength > maxLength * 0.9) {
                    helpText.classList.add('text-warning');
                } else {
                    helpText.classList.remove('text-warning');
                }
            });
        });
    </script>
</body>
</html>