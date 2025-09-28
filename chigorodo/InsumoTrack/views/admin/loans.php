<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Gestión de Préstamos";
include '../../includes/header.php';

// Incluir funciones de administrador con ruta dinámica
$admin_functions_path = '../../functions/admin_functions.php';
if (!file_exists($admin_functions_path)) {
    $admin_functions_path = dirname(dirname(__DIR__)) . '/functions/admin_functions.php';
}
require_once $admin_functions_path;

// Obtener préstamos según filtro
$filtro = $_GET['filtro'] ?? 'todos';
$prestamos = obtenerTodosPrestamos($filtro);
?>

<!-- Mensajes de alerta -->
<div id="alertMessage" class="alert d-none" role="alert"></div>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3"><i class="bi bi-list-check me-2"></i>Gestión de Préstamos</h1>
        <p class="text-muted">Administra todas las solicitudes y préstamos del sistema</p>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="btn-group" role="group">
            <a href="?filtro=todos" class="btn <?php echo $filtro === 'todos' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-list me-1"></i>Todos
            </a>
            <a href="?filtro=activos" class="btn <?php echo $filtro === 'activos' ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="bi bi-check-circle me-1"></i>Activos
            </a>
            <a href="?filtro=atrasados" class="btn <?php echo $filtro === 'atrasados' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                <i class="bi bi-exclamation-triangle me-1"></i>Atrasados
            </a>
            <a href="?filtro=devueltos" class="btn <?php echo $filtro === 'devueltos' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-archive me-1"></i>Devueltos
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="buscarPrestamo" placeholder="Buscar por usuario o insumo...">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <?php
            $titulo_filtro = [
                'todos' => 'Todos los Préstamos',
                'activos' => 'Préstamos Activos',
                'atrasados' => 'Préstamos Atrasados',
                'devueltos' => 'Préstamos Devueltos'
            ];
            echo $titulo_filtro[$filtro] ?? 'Préstamos';
            ?>
        </h5>
        <small class="text-muted">Total: <?php echo count($prestamos); ?> préstamos</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Insumo</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th>Institución</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaPrestamos">
                    <?php foreach ($prestamos as $prestamo): ?>
                    <tr data-prestamo-id="<?php echo $prestamo['id']; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($prestamo['nombre_completo']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($prestamo['email']); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($prestamo['insumo_nombre']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($prestamo['insumo_codigo']); ?></small>
                        </td>
                        <td>
                            <small>
                                <strong>Solicitado:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_solicitud'])); ?><br>
                                <?php if ($prestamo['fecha_prestamo']): ?>
                                    <strong>Prestado:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?><br>
                                <?php endif; ?>
                                <strong>Devolución:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_prevista'])); ?>
                                <?php if ($prestamo['fecha_devolucion_real']): ?>
                                    <br><strong>Devuelto:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])); ?>
                                <?php endif; ?>
                            </small>
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
                                case 'Rechazado': $badge_class = 'secondary'; break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $prestamo['estado']; ?></span>
                        </td>
                        <td><small><?php echo htmlspecialchars($prestamo['institucion_educativa']); ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php if ($prestamo['estado'] === 'Pendiente'): ?>
                                    <button class="btn btn-success" onclick="aprobarPrestamo(<?php echo $prestamo['id']; ?>)" title="Aprobar">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="rechazarPrestamo(<?php echo $prestamo['id']; ?>)" title="Rechazar">
                                        <i class="bi bi-x"></i>
                                    </button>
                                <?php elseif ($prestamo['estado'] === 'Aprobado'): ?>
                                    <button class="btn btn-info" onclick="marcarEntregado(<?php echo $prestamo['id']; ?>)" title="Marcar como entregado">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </button>
                                <?php elseif ($prestamo['estado'] === 'Entregado' || $prestamo['estado'] === 'Atrasado'): ?>
                                    <button class="btn btn-warning" onclick="marcarDevuelto(<?php echo $prestamo['id']; ?>)" title="Marcar como devuelto">
                                        <i class="bi bi-box-arrow-in-left"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-outline-info" onclick="verDetalles(<?php echo $prestamo['id']; ?>)" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay préstamos para mostrar</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    // Búsqueda de préstamos
    document.getElementById('buscarPrestamo').addEventListener('input', function() {
        const termino = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaPrestamos tr');
        
        filas.forEach(fila => {
            if (fila.cells.length === 1) return; // Skip empty row
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(termino) ? '' : 'none';
        });
    });

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
        const motivo = prompt('Motivo del rechazo (opcional):');
        if (motivo === null) return; // Usuario canceló

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

    // Función para marcar como entregado
    window.marcarEntregado = function(prestamoId) {
        if (!confirm('¿Confirma que el insumo ha sido entregado al usuario?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'marcar_entregado');
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

    // Función para marcar como devuelto
    window.marcarDevuelto = function(prestamoId) {
        if (!confirm('¿Confirma que el insumo ha sido devuelto en buen estado?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'marcar_devuelto');
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

    // Función para ver detalles
    window.verDetalles = function(prestamoId) {
        const fila = document.querySelector(`tr[data-prestamo-id="${prestamoId}"]`);
        if (fila) {
            const usuario = fila.cells[0].textContent.trim();
            const insumo = fila.cells[1].textContent.trim();
            const fechas = fila.cells[2].textContent.trim();
            const estado = fila.cells[3].textContent.trim();
            const institucion = fila.cells[4].textContent.trim();
            
            alert(`Detalles del préstamo:\n\nUsuario: ${usuario}\nInsumo: ${insumo}\nFechas: ${fechas}\nEstado: ${estado}\nInstitución: ${institucion}`);
        }
    };
});
</script>

<?php include '../../includes/footer.php'; ?>