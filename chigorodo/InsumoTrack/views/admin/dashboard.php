<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Dashboard - Administrador";
include '../../includes/header.php';

// Incluir funciones de administrador con ruta dinámica
$admin_functions_path = '../../functions/admin_functions.php';
if (!file_exists($admin_functions_path)) {
    $admin_functions_path = dirname(dirname(__DIR__)) . '/functions/admin_functions.php';
}
require_once $admin_functions_path;

// Actualizar préstamos atrasados
actualizarPrestamosAtrasados();

// Obtener datos para el dashboard
$solicitudes_pendientes = obtenerSolicitudesPendientes();
$estadisticas = obtenerEstadisticasGeneral();
$prestamos_activos = obtenerTodosPrestamos('activos');
?>

<!-- Mensajes de alerta -->
<div id="alertMessage" class="alert d-none" role="alert"></div>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3"><i class="bi bi-speedometer2 me-2"></i>Panel de Administración</h1>
        <p class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>. Gestiona el sistema de préstamos desde aquí.</p>
    </div>
</div>

<!-- Estadísticas principales -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-archive" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0"><?php echo $estadisticas['total_insumos']; ?></h5>
                    <p class="card-text">Total Insumos</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0"><?php echo $estadisticas['prestamos_activos']; ?></h5>
                    <p class="card-text">Préstamos Activos</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-clock" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0"><?php echo $estadisticas['prestamos_pendientes']; ?></h5>
                    <p class="card-text">Pendientes</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0"><?php echo $estadisticas['prestamos_atrasados']; ?></h5>
                    <p class="card-text">Atrasados</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Solicitudes Pendientes</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="actualizarSolicitudes()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Insumo</th>
                                <th>Fecha</th>
                                <th>Institución</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaSolicitudesPendientes">
                            <?php foreach ($solicitudes_pendientes as $solicitud): ?>
                            <tr data-prestamo-id="<?php echo $solicitud['id']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($solicitud['email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($solicitud['insumo_nombre']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($solicitud['insumo_codigo']); ?></small>
                                </td>
                                <td>
                                    <small>Solicitado: <?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></small><br>
                                    <small>Devolución: <?php echo date('d/m/Y', strtotime($solicitud['fecha_devolucion_prevista'])); ?></small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($solicitud['institucion_educativa']); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-success" onclick="aprobarPrestamo(<?php echo $solicitud['id']; ?>)" title="Aprobar">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="rechazarPrestamo(<?php echo $solicitud['id']; ?>)" title="Rechazar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <button class="btn btn-info" onclick="verDetalles(<?php echo $solicitud['id']; ?>)" title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($solicitudes_pendientes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay solicitudes pendientes</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Resumen del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Insumos Disponibles:</span>
                        <strong class="text-success"><?php echo $estadisticas['insumos_disponibles']; ?></strong>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" style="width: <?php echo ($estadisticas['total_insumos'] > 0) ? round(($estadisticas['insumos_disponibles'] / $estadisticas['total_insumos']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Usuarios Activos:</span>
                        <strong class="text-info"><?php echo $estadisticas['total_usuarios']; ?></strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Tasa de Aprobación:</span>
                        <strong class="text-primary">
                            <?php 
                            $total_solicitudes = $estadisticas['prestamos_activos'] + $estadisticas['prestamos_pendientes'];
                            echo $total_solicitudes > 0 ? round(($estadisticas['prestamos_activos'] / $total_solicitudes) * 100, 1) : 0;
                            ?>%
                        </strong>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="inventory.php" class="btn btn-outline-primary">
                        <i class="bi bi-archive me-2"></i>Gestionar Inventario
                    </a>
                    <a href="loans.php" class="btn btn-outline-success">
                        <i class="bi bi-list-check me-2"></i>Ver Todos los Préstamos
                    </a>
                    <a href="reports.php" class="btn btn-outline-info">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>Generar Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para rechazar préstamo -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formRechazar">
                    <input type="hidden" id="prestamoIdRechazar">
                    <div class="mb-3">
                        <label for="motivoRechazo" class="form-label">Motivo del rechazo (opcional):</label>
                        <textarea class="form-control" id="motivoRechazo" rows="3" placeholder="Explique el motivo del rechazo..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarRechazo()">
                    <i class="bi bi-x me-2"></i>Rechazar Solicitud
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

    // Función para aprobar préstamo
    window.aprobarPrestamo = function(prestamoId) {
        if (!confirm('¿Está seguro de que desea aprobar esta solicitud?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'aprobar_prestamo');
        formData.append('prestamo_id', prestamoId);

        fetch('../../functions/admin_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al procesar solicitud', 'danger');
            console.error('Error:', error);
        });
    };

    // Función para rechazar préstamo
    window.rechazarPrestamo = function(prestamoId) {
        document.getElementById('prestamoIdRechazar').value = prestamoId;
        document.getElementById('motivoRechazo').value = '';
        new bootstrap.Modal(document.getElementById('modalRechazar')).show();
    };

    // Confirmar rechazo
    window.confirmarRechazo = function() {
        const prestamoId = document.getElementById('prestamoIdRechazar').value;
        const motivo = document.getElementById('motivoRechazo').value;

        const formData = new FormData();
        formData.append('action', 'rechazar_prestamo');
        formData.append('prestamo_id', prestamoId);
        formData.append('motivo', motivo);

        fetch('../../functions/admin_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalRechazar')).hide();
            
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al procesar solicitud', 'danger');
            console.error('Error:', error);
        });
    };

    // Función para ver detalles
    window.verDetalles = function(prestamoId) {
        // Por ahora solo mostramos un alert, se puede expandir a un modal con más detalles
        const fila = document.querySelector(`tr[data-prestamo-id="${prestamoId}"]`);
        if (fila) {
            const usuario = fila.cells[0].textContent.trim();
            const insumo = fila.cells[1].textContent.trim();
            const fecha = fila.cells[2].textContent.trim();
            const institucion = fila.cells[3].textContent.trim();
            
            alert(`Detalles de la solicitud:\n\nUsuario: ${usuario}\nInsumo: ${insumo}\nFechas: ${fecha}\nInstitución: ${institucion}`);
        }
    };

    // Función para actualizar solicitudes
    window.actualizarSolicitudes = function() {
        location.reload();
    };
});
</script>

<?php include '../../includes/footer.php'; ?>