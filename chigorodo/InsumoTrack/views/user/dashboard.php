<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Dashboard - Usuario";
include '../../includes/header.php';

// Incluir funciones de usuario con ruta dinámica
$user_functions_path = '../../functions/user_functions.php';
if (!file_exists($user_functions_path)) {
    $user_functions_path = dirname(dirname(__DIR__)) . '/functions/user_functions.php';
}
require_once $user_functions_path;

// Obtener datos del usuario
$insumos_disponibles = obtenerInsumosDisponibles();
$prestamos_usuario = obtenerPrestamosUsuario($_SESSION['user_id']);
$estadisticas = obtenerEstadisticasUsuario($_SESSION['user_id']);
?>

<!-- Mensajes de alerta -->
<div id="alertMessage" class="alert d-none" role="alert"></div>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3"><i class="bi bi-house-fill me-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h1>
        <p class="text-muted">Gestiona tus préstamos de insumos desde aquí</p>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-archive text-primary" style="font-size: 2rem;"></i>
                <h5 class="card-title mt-2"><?php echo $estadisticas['total_prestamos']; ?></h5>
                <p class="card-text text-muted">Total Préstamos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                <h5 class="card-title mt-2"><?php echo $estadisticas['prestamos_activos']; ?></h5>
                <p class="card-text text-muted">Activos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                <h5 class="card-title mt-2"><?php echo $estadisticas['prestamos_pendientes']; ?></h5>
                <p class="card-text text-muted">Pendientes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                <h5 class="card-title mt-2"><?php echo $estadisticas['prestamos_atrasados']; ?></h5>
                <p class="card-text text-muted">Atrasados</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Solicitar Préstamo</h5>
            </div>
            <div class="card-body">
                <form id="solicitudPrestamoForm">
                    <div class="mb-3">
                        <label for="buscarInsumo" class="form-label">Buscar Insumo:</label>
                        <input type="text" class="form-control" id="buscarInsumo" placeholder="Buscar por nombre o código...">
                    </div>
                    <div class="mb-3">
                        <label for="insumoSeleccionado" class="form-label">Seleccionar Insumo:</label>
                        <select class="form-select" id="insumoSeleccionado" required>
                            <option value="">Seleccione un insumo...</option>
                            <?php foreach ($insumos_disponibles as $insumo): ?>
                                <option value="<?php echo $insumo['id']; ?>" 
                                        data-nombre="<?php echo htmlspecialchars($insumo['nombre']); ?>"
                                        data-codigo="<?php echo htmlspecialchars($insumo['codigo']); ?>"
                                        data-ubicacion="<?php echo htmlspecialchars($insumo['ubicacion']); ?>">
                                    <?php echo htmlspecialchars($insumo['nombre']) . ' (' . htmlspecialchars($insumo['codigo']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fechaDevolucion" class="form-label">Fecha de devolución prevista:</label>
                        <input type="date" class="form-control" id="fechaDevolucion" required 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones (opcional):</label>
                        <textarea class="form-control" id="observaciones" rows="3" placeholder="Propósito del préstamo, observaciones adicionales..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-2"></i>Enviar Solicitud
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Inventario Disponible</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="actualizarInventario()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaInventario">
                            <?php foreach ($insumos_disponibles as $insumo): ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($insumo['codigo']); ?></small></td>
                                <td><small><?php echo htmlspecialchars($insumo['nombre']); ?></small></td>
                                <td><small><?php echo htmlspecialchars($insumo['ubicacion']); ?></small></td>
                                <td><span class="badge bg-success">Disponible</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Mis Préstamos</h5>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="filtroEstado" id="todos" value="todos" checked>
                    <label class="btn btn-outline-primary btn-sm" for="todos">Todos</label>
                    
                    <input type="radio" class="btn-check" name="filtroEstado" id="activos" value="activos">
                    <label class="btn btn-outline-success btn-sm" for="activos">Activos</label>
                    
                    <input type="radio" class="btn-check" name="filtroEstado" id="pendientes" value="pendientes">
                    <label class="btn btn-outline-warning btn-sm" for="pendientes">Pendientes</label>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Fecha Solicitud</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMisPrestamos">
                            <?php foreach ($prestamos_usuario as $prestamo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($prestamo['insumo_nombre']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($prestamo['insumo_codigo']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_solicitud'])); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_prevista'])); ?>
                                    <?php if ($prestamo['fecha_devolucion_real']): ?>
                                        <br><small class="text-success">Devuelto: <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'secondary';
                                    switch ($prestamo['estado']) {
                                        case 'Pendiente': $badge_class = 'warning'; break;
                                        case 'Aprobado': $badge_class = 'info'; break;
                                        case 'Entregado': $badge_class = 'success'; break;
                                        case 'Devuelto': $badge_class = 'dark'; break;
                                        case 'Atrasado': $badge_class = 'danger'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $prestamo['estado']; ?></span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($prestamo['observaciones']); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($prestamos_usuario)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No tienes préstamos registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

    // Búsqueda de insumos
    document.getElementById('buscarInsumo').addEventListener('input', function() {
        const termino = this.value.toLowerCase();
        const select = document.getElementById('insumoSeleccionado');
        const opciones = select.getElementsByTagName('option');
        
        for (let i = 1; i < opciones.length; i++) {
            const texto = opciones[i].text.toLowerCase();
            opciones[i].style.display = texto.includes(termino) ? '' : 'none';
        }
    });

    // Manejar envío de solicitud de préstamo
    document.getElementById('solicitudPrestamoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'solicitar_prestamo');
        formData.append('insumo_id', document.getElementById('insumoSeleccionado').value);
        formData.append('fecha_devolucion', document.getElementById('fechaDevolucion').value);
        formData.append('observaciones', document.getElementById('observaciones').value);
        
        fetch('../../functions/user_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                this.reset();
                setTimeout(() => location.reload(), 2000);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al procesar solicitud', 'danger');
            console.error('Error:', error);
        });
    });

    // Función para actualizar inventario
    window.actualizarInventario = function() {
        const formData = new FormData();
        formData.append('action', 'buscar_insumos');
        formData.append('termino', '');
        
        fetch('../../functions/user_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('tablaInventario');
                tbody.innerHTML = '';
                
                data.insumos.forEach(insumo => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td><small>${insumo.codigo}</small></td>
                        <td><small>${insumo.nombre}</small></td>
                        <td><small>${insumo.ubicacion}</small></td>
                        <td><span class="badge bg-success">Disponible</span></td>
                    `;
                });
            }
        });
    };

    // Filtros para préstamos
    document.querySelectorAll('input[name="filtroEstado"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const filtro = this.value;
            const filas = document.querySelectorAll('#tablaMisPrestamos tr');
            
            filas.forEach(fila => {
                if (fila.cells.length === 1) return; // Skip empty row
                
                const estado = fila.cells[3].textContent.trim();
                let mostrar = true;
                
                if (filtro === 'activos' && !['Aprobado', 'Entregado'].includes(estado)) {
                    mostrar = false;
                } else if (filtro === 'pendientes' && estado !== 'Pendiente') {
                    mostrar = false;
                }
                
                fila.style.display = mostrar ? '' : 'none';
            });
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>