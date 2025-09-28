<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Gestión de Inventario";
include '../../includes/header.php';

// Incluir funciones de administrador con ruta dinámica
$admin_functions_path = '../../functions/admin_functions.php';
if (!file_exists($admin_functions_path)) {
    $admin_functions_path = dirname(dirname(__DIR__)) . '/functions/admin_functions.php';
}
require_once $admin_functions_path;

// Obtener todos los insumos
$insumos = obtenerTodosInsumos();
?>

<!-- Mensajes de alerta -->
<div id="alertMessage" class="alert d-none" role="alert"></div>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3"><i class="bi bi-archive me-2"></i>Gestión de Inventario</h1>
                <p class="text-muted">Administra todos los insumos del sistema</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarInsumo">
                <i class="bi bi-plus-circle me-2"></i>Agregar Insumo
            </button>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="buscarInsumo" placeholder="Buscar por código, nombre o ubicación...">
        </div>
    </div>
    <div class="col-md-6">
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="filtroEstado" id="estadoTodos" value="todos" checked>
            <label class="btn btn-outline-primary" for="estadoTodos">Todos</label>
            
            <input type="radio" class="btn-check" name="filtroEstado" id="estadoDisponible" value="disponible">
            <label class="btn btn-outline-success" for="estadoDisponible">Disponibles</label>
            
            <input type="radio" class="btn-check" name="filtroEstado" id="estadoPrestado" value="prestado">
            <label class="btn btn-outline-warning" for="estadoPrestado">Prestados</label>
            
            <input type="radio" class="btn-check" name="filtroEstado" id="estadoMantenimiento" value="mantenimiento">
            <label class="btn btn-outline-danger" for="estadoMantenimiento">Mantenimiento</label>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Inventario de Insumos</h5>
        <small class="text-muted">Total: <span id="totalInsumos"><?php echo count($insumos); ?></span> insumos</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaInsumos">
                    <?php foreach ($insumos as $insumo): ?>
                    <tr data-estado="<?php echo strtolower($insumo['estado']); ?>" data-insumo-id="<?php echo $insumo['id']; ?>">
                        <td><strong><?php echo htmlspecialchars($insumo['codigo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($insumo['nombre']); ?></td>
                        <td>
                            <small><?php echo htmlspecialchars(substr($insumo['descripcion'], 0, 50)); ?><?php echo strlen($insumo['descripcion']) > 50 ? '...' : ''; ?></small>
                        </td>
                        <td><small><?php echo htmlspecialchars($insumo['ubicacion']); ?></small></td>
                        <td>
                            <?php
                            $badge_class = 'secondary';
                            switch ($insumo['estado']) {
                                case 'Disponible': $badge_class = 'success'; break;
                                case 'Prestado': $badge_class = 'warning'; break;
                                case 'No disponible': $badge_class = 'secondary'; break;
                                case 'Mantenimiento': $badge_class = 'danger'; break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $insumo['estado']; ?></span>
                        </td>
                        <td><small><?php echo date('d/m/Y', strtotime($insumo['fecha_registro'])); ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary" onclick="editarInsumo(<?php echo $insumo['id']; ?>)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="verHistorial(<?php echo $insumo['id']; ?>)" title="Ver historial">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para agregar insumo -->
<div class="modal fade" id="modalAgregarInsumo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Agregar Nuevo Insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgregarInsumo">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="codigoNuevo" class="form-label">Código del Insumo:</label>
                            <input type="text" class="form-control" id="codigoNuevo" required placeholder="Ej: LAB001">
                            <small class="text-muted">Debe ser único</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombreNuevo" class="form-label">Nombre del Insumo:</label>
                            <input type="text" class="form-control" id="nombreNuevo" required placeholder="Ej: Microscopio Óptico">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionNuevo" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="descripcionNuevo" rows="3" placeholder="Descripción detallada del insumo..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="ubicacionNuevo" class="form-label">Ubicación:</label>
                        <input type="text" class="form-control" id="ubicacionNuevo" required placeholder="Ej: Laboratorio de Biología">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="agregarInsumo()">
                    <i class="bi bi-plus-circle me-2"></i>Agregar Insumo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar insumo -->
<div class="modal fade" id="modalEditarInsumo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarInsumo">
                    <input type="hidden" id="insumoIdEditar">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="codigoEditar" class="form-label">Código del Insumo:</label>
                            <input type="text" class="form-control" id="codigoEditar" readonly>
                            <small class="text-muted">No se puede modificar</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombreEditar" class="form-label">Nombre del Insumo:</label>
                            <input type="text" class="form-control" id="nombreEditar" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionEditar" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="descripcionEditar" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ubicacionEditar" class="form-label">Ubicación:</label>
                            <input type="text" class="form-control" id="ubicacionEditar" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estadoEditar" class="form-label">Estado:</label>
                            <select class="form-select" id="estadoEditar" required>
                                <option value="Disponible">Disponible</option>
                                <option value="Prestado">Prestado</option>
                                <option value="No disponible">No disponible</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="actualizarInsumo()">
                    <i class="bi bi-check me-2"></i>Actualizar Insumo
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

    // Búsqueda de insumos
    document.getElementById('buscarInsumo').addEventListener('input', function() {
        const termino = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaInsumos tr');
        let visibles = 0;
        
        filas.forEach(fila => {
            const texto = fila.textContent.toLowerCase();
            const mostrar = texto.includes(termino);
            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        
        document.getElementById('totalInsumos').textContent = visibles;
    });

    // Filtros por estado
    document.querySelectorAll('input[name="filtroEstado"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const filtro = this.value;
            const filas = document.querySelectorAll('#tablaInsumos tr');
            let visibles = 0;
            
            filas.forEach(fila => {
                const estado = fila.dataset.estado;
                let mostrar = true;
                
                if (filtro !== 'todos' && estado !== filtro) {
                    mostrar = false;
                }
                
                fila.style.display = mostrar ? '' : 'none';
                if (mostrar) visibles++;
            });
            
            document.getElementById('totalInsumos').textContent = visibles;
        });
    });

    // Función para agregar insumo
    window.agregarInsumo = function() {
        const formData = new FormData();
        formData.append('action', 'agregar_insumo');
        formData.append('codigo', document.getElementById('codigoNuevo').value);
        formData.append('nombre', document.getElementById('nombreNuevo').value);
        formData.append('descripcion', document.getElementById('descripcionNuevo').value);
        formData.append('ubicacion', document.getElementById('ubicacionNuevo').value);

        fetch('../../functions/admin_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalAgregarInsumo')).hide();
            
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al agregar insumo', 'danger');
            console.error('Error:', error);
        });
    };

    // Función para editar insumo
    window.editarInsumo = function(insumoId) {
        const fila = document.querySelector(`tr[data-insumo-id="${insumoId}"]`);
        if (fila) {
            const celdas = fila.cells;
            document.getElementById('insumoIdEditar').value = insumoId;
            document.getElementById('codigoEditar').value = celdas[0].textContent.trim();
            document.getElementById('nombreEditar').value = celdas[1].textContent.trim();
            document.getElementById('descripcionEditar').value = ''; // Se podría obtener de la BD
            document.getElementById('ubicacionEditar').value = celdas[3].textContent.trim();
            document.getElementById('estadoEditar').value = celdas[4].textContent.trim();
            
            new bootstrap.Modal(document.getElementById('modalEditarInsumo')).show();
        }
    };

    // Función para actualizar insumo
    window.actualizarInsumo = function() {
        const formData = new FormData();
        formData.append('action', 'actualizar_insumo');
        formData.append('id', document.getElementById('insumoIdEditar').value);
        formData.append('nombre', document.getElementById('nombreEditar').value);
        formData.append('descripcion', document.getElementById('descripcionEditar').value);
        formData.append('ubicacion', document.getElementById('ubicacionEditar').value);
        formData.append('estado', document.getElementById('estadoEditar').value);

        fetch('../../functions/admin_functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalEditarInsumo')).hide();
            
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        })
        .catch(error => {
            mostrarAlerta('Error al actualizar insumo', 'danger');
            console.error('Error:', error);
        });
    };

    // Función para ver historial
    window.verHistorial = function(insumoId) {
        // Por ahora solo mostramos un mensaje, se puede expandir
        mostrarAlerta('Funcionalidad de historial pendiente de implementación', 'info');
    };
});
</script>

<?php include '../../includes/footer.php'; ?>