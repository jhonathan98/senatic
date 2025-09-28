<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Mi Perfil";
include '../../includes/header.php';

// Incluir funciones de usuario con ruta dinámica
$user_functions_path = '../../functions/user_functions.php';
if (!file_exists($user_functions_path)) {
    $user_functions_path = dirname(dirname(__DIR__)) . '/functions/user_functions.php';
}
require_once $user_functions_path;

// Obtener datos del usuario
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$estadisticas = obtenerEstadisticasUsuario($_SESSION['user_id']);
?>

<!-- Mensajes de alerta -->
<div id="alertMessage" class="alert d-none" role="alert"></div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Información Personal</h5>
            </div>
            <div class="card-body">
                <form id="perfilForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo:</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                                   value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                            <small class="text-muted">El email no se puede modificar</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_documento" class="form-label">Tipo de Documento:</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['tipo_documento']); ?>" readonly>
                            <small class="text-muted">No se puede modificar</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_documento" class="form-label">Número de Documento:</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($usuario['numero_documento']); ?>" readonly>
                            <small class="text-muted">No se puede modificar</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono:</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_registro" class="form-label">Fecha de Registro:</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="institucion_educativa" class="form-label">Institución Educativa:</label>
                        <input type="text" class="form-control" id="institucion_educativa" name="institucion_educativa" 
                               value="<?php echo htmlspecialchars($usuario['institucion_educativa']); ?>" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check me-2"></i>Actualizar Perfil
                        </button>
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalCambiarPassword">
                            <i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Estadísticas</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
                    <h6 class="mt-2"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h6>
                    <span class="badge bg-primary"><?php echo ucfirst($usuario['tipo_documento']); ?></span>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0"><?php echo $estadisticas['total_prestamos']; ?></h4>
                        <small class="text-muted">Total Préstamos</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0"><?php echo $estadisticas['prestamos_activos']; ?></h4>
                        <small class="text-muted">Activos</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-warning mb-0"><?php echo $estadisticas['prestamos_pendientes']; ?></h4>
                        <small class="text-muted">Pendientes</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-danger mb-0"><?php echo $estadisticas['prestamos_atrasados']; ?></h4>
                        <small class="text-muted">Atrasados</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-house me-2"></i>Ir al Dashboard
                </a>
                <button class="btn btn-outline-info" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Imprimir Perfil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar contraseña -->
<div class="modal fade" id="modalCambiarPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cambiarPasswordForm">
                    <div class="mb-3">
                        <label for="passwordActual" class="form-label">Contraseña Actual:</label>
                        <input type="password" class="form-control" id="passwordActual" required>
                    </div>
                    <div class="mb-3">
                        <label for="passwordNueva" class="form-label">Nueva Contraseña:</label>
                        <input type="password" class="form-control" id="passwordNueva" required minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="mb-3">
                        <label for="passwordConfirmar" class="form-label">Confirmar Nueva Contraseña:</label>
                        <input type="password" class="form-control" id="passwordConfirmar" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="cambiarPassword()">
                    <i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo) {
        const alertDiv = document.getElementById('alertMessage');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertDiv.scrollIntoView({ behavior: 'smooth' });
    }

    // Manejar actualización de perfil
    document.getElementById('perfilForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'actualizar_perfil');
        formData.append('nombre_completo', document.getElementById('nombre_completo').value);
        formData.append('telefono', document.getElementById('telefono').value);
        formData.append('institucion_educativa', document.getElementById('institucion_educativa').value);
        
        fetch('../../functions/user_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al actualizar perfil', 'danger');
            console.error('Error:', error);
        });
    });

    // Función para cambiar contraseña
    window.cambiarPassword = function() {
        const passwordActual = document.getElementById('passwordActual').value;
        const passwordNueva = document.getElementById('passwordNueva').value;
        const passwordConfirmar = document.getElementById('passwordConfirmar').value;
        
        if (passwordNueva !== passwordConfirmar) {
            mostrarAlerta('Las contraseñas no coinciden', 'danger');
            return;
        }
        
        if (passwordNueva.length < 6) {
            mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'danger');
            return;
        }
        
        // Por ahora solo mostramos un mensaje, la funcionalidad se puede implementar después
        mostrarAlerta('Funcionalidad de cambio de contraseña pendiente de implementación', 'info');
        bootstrap.Modal.getInstance(document.getElementById('modalCambiarPassword')).hide();
    };
});
</script>

<?php include '../../includes/footer.php'; ?>
