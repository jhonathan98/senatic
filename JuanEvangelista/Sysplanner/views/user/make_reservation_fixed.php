<?php
// views/user/make_reservation.php
session_start();

// Verificar autenticación (usuarios regulares y admin pueden hacer reservas)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=" . urlencode("Debe iniciar sesión para acceder"));
    exit();
}

$page_title = "Nueva Reserva - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ReservationController.php';
require_once '../../controllers/ResourceController.php';

$reservationController = new ReservationController();
$resourceController = new ResourceController();
$message = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'recurso_id' => $_POST['recurso_id'] ?? '',
        'usuario_id' => $_SESSION['user_id'],
        'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
        'fecha_fin' => $_POST['fecha_fin'] ?? '',
        'motivo' => $_POST['motivo'] ?? ''
    ];
    
    $result = $reservationController->store($data);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Obtener parámetros de filtro
$tipo_filtro = $_GET['tipo'] ?? '';
$departamento_filtro = $_GET['departamento'] ?? '';
$resource_id = $_GET['resource_id'] ?? '';

// Obtener recursos activos con filtros
if ($tipo_filtro || $departamento_filtro) {
    $filters = [
        'tipo' => $tipo_filtro,
        'departamento_id' => $departamento_filtro,
        'activo' => true
    ];
    $resources = $resourceController->search($filters);
} else {
    $resources = $resourceController->getActive();
}

// Obtener tipos de recursos únicos para el filtro
$resource_types = $resourceController->getResourceTypes();

// Obtener departamentos para el filtro
require_once '../../models/Department.php';
$department = new Department();
$departments = $department->read();
?>

<div class="container mt-4">
    <!-- Filtros de Recursos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-filter me-2"></i>Filtrar Recursos</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3" id="filterForm">
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo de Recurso</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos los tipos</option>
                                <?php while ($type = $resource_types->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($type['tipo']); ?>" 
                                            <?php echo $tipo_filtro === $type['tipo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['tipo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="departamento" name="departamento">
                                <option value="">Todos los departamentos</option>
                                <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo $departamento_filtro == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                            <a href="make_reservation.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-1"></i>Limpiar
                            </a>
                        </div>
                        
                        <!-- Mantener resource_id si viene desde otro lugar -->
                        <?php if ($resource_id): ?>
                            <input type="hidden" name="resource_id" value="<?php echo htmlspecialchars($resource_id); ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle me-2"></i>Nueva Reserva</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="reservationForm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="recurso_id" class="form-label">
                                        <i class="fas fa-cube me-1"></i>Recurso *
                                    </label>
                                    <select class="form-select" id="recurso_id" name="recurso_id" required>
                                        <option value="">Seleccionar recurso</option>
                                        <?php 
                                        $resource_count = 0;
                                        while ($resource = $resources->fetch(PDO::FETCH_ASSOC)): 
                                            $resource_count++;
                                            $is_selected = ($_POST['recurso_id'] ?? $resource_id) == $resource['id'];
                                        ?>
                                            <option value="<?php echo $resource['id']; ?>" 
                                                    data-tipo="<?php echo htmlspecialchars($resource['tipo']); ?>"
                                                    data-capacidad="<?php echo $resource['capacidad']; ?>"
                                                    data-departamento="<?php echo htmlspecialchars($resource['departamento_nombre'] ?? ''); ?>"
                                                    <?php echo $is_selected ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($resource['nombre']); ?> 
                                                - <?php echo htmlspecialchars($resource['tipo']); ?>
                                                <?php if ($resource['capacidad']): ?>
                                                    (Cap: <?php echo $resource['capacidad']; ?>)
                                                <?php endif; ?>
                                                <?php if ($resource['departamento_nombre']): ?>
                                                    - <?php echo htmlspecialchars($resource['departamento_nombre']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    
                                    <?php if ($resource_count === 0): ?>
                                        <div class="alert alert-warning mt-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No se encontraron recursos con los filtros seleccionados. 
                                            <a href="make_reservation.php">Limpiar filtros</a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-text">
                                        <?php if ($tipo_filtro || $departamento_filtro): ?>
                                            <strong>Filtros aplicados:</strong>
                                            <?php if ($tipo_filtro): ?>
                                                Tipo: <?php echo htmlspecialchars($tipo_filtro); ?>
                                            <?php endif; ?>
                                            <?php if ($departamento_filtro): ?>
                                                <?php if ($tipo_filtro) echo ' | '; ?>
                                                Departamento filtrado
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Mostrando todos los recursos disponibles
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Panel de información del recurso -->
                                <div id="resourceInfo" class="mt-3" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <small class="text-muted">Tipo:</small>
                                                    <div id="resourceType" class="fw-bold"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Capacidad:</small>
                                                    <div id="resourceCapacity" class="fw-bold"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Departamento:</small>
                                                    <div id="resourceDepartment" class="fw-bold"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">
                                        <i class="fas fa-play me-1"></i>Fecha y Hora de Inicio *
                                    </label>
                                    <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                           value="<?php echo htmlspecialchars($_POST['fecha_inicio'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">
                                        <i class="fas fa-stop me-1"></i>Fecha y Hora de Fin *
                                    </label>
                                    <input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin" 
                                           value="<?php echo htmlspecialchars($_POST['fecha_fin'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="motivo" class="form-label">
                                <i class="fas fa-comment me-1"></i>Motivo/Observaciones
                            </label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" 
                                      placeholder="Describe el motivo de la reserva..."><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../dashboard.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crear Reserva
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha mínima (ahora)
    const now = new Date();
    const dateString = now.toISOString().slice(0, 16);
    document.getElementById('fecha_inicio').min = dateString;
    document.getElementById('fecha_fin').min = dateString;
});

// Actualizar fecha fin cuando cambie fecha inicio
document.getElementById('fecha_inicio').addEventListener('change', function() {
    const fechaInicio = new Date(this.value);
    fechaInicio.setHours(fechaInicio.getHours() + 1);
    document.getElementById('fecha_fin').min = fechaInicio.toISOString().slice(0, 16);
    
    if (document.getElementById('fecha_fin').value < this.value) {
        document.getElementById('fecha_fin').value = fechaInicio.toISOString().slice(0, 16);
    }
});

// Mostrar información del recurso seleccionado
document.getElementById('recurso_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const resourceInfo = document.getElementById('resourceInfo');
    
    if (this.value) {
        // Mostrar información del recurso
        document.getElementById('resourceType').textContent = option.dataset.tipo || 'No especificado';
        document.getElementById('resourceCapacity').textContent = option.dataset.capacidad ? 
            option.dataset.capacidad + ' personas' : 'No especificada';
        document.getElementById('resourceDepartment').textContent = option.dataset.departamento || 'Sin asignar';
        resourceInfo.style.display = 'block';
    } else {
        resourceInfo.style.display = 'none';
    }
});

// Mostrar información del recurso preseleccionado
document.addEventListener('DOMContentLoaded', function() {
    const recursoSelect = document.getElementById('recurso_id');
    if (recursoSelect.value) {
        recursoSelect.dispatchEvent(new Event('change'));
    }
});

// Validación del formulario
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
    const fechaFin = new Date(document.getElementById('fecha_fin').value);
    
    if (fechaFin <= fechaInicio) {
        e.preventDefault();
        alert('La fecha de fin debe ser posterior a la fecha de inicio');
        return false;
    }
    
    const duration = (fechaFin - fechaInicio) / (1000 * 60 * 60); // horas
    if (duration > 8) {
        if (!confirm('La reserva es de más de 8 horas. ¿Desea continuar?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
