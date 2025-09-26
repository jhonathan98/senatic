<?php
// views/admin/edit_resource.php
// Vista para editar recursos existentes

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$page_title = "Editar Recurso - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ResourceController.php';
require_once '../../models/Department.php';

$resourceController = new ResourceController();
$department = new Department();

$resource_id = $_GET['id'] ?? null;
$errors = [];
$success = "";

if (!$resource_id) {
    header("Location: manage_resources.php?error=" . urlencode("ID de recurso no válido"));
    exit();
}

// Obtener datos del recurso
$resource = $resourceController->show($resource_id);

if (!$resource) {
    header("Location: manage_resources.php?error=" . urlencode("Recurso no encontrado"));
    exit();
}

// Obtener departamentos para el select
$departments = $department->read();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad = $_POST['capacidad'] ?? null;
    $caracteristicas = trim($_POST['caracteristicas'] ?? '');
    $departamento_id = $_POST['departamento_id'] ?? null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (empty($nombre)) {
        $errors[] = "El nombre es requerido";
    }
    if (empty($tipo)) {
        $errors[] = "El tipo es requerido";
    }
    if ($capacidad !== null && $capacidad !== '' && (!is_numeric($capacidad) || $capacidad < 0)) {
        $errors[] = "La capacidad debe ser un número positivo";
    }
    if ($departamento_id && !is_numeric($departamento_id)) {
        $errors[] = "Departamento inválido";
    }

    // Verificar si el nombre ya existe (excepto el recurso actual)
    if (empty($errors)) {
        require_once '../../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $check_query = "SELECT id FROM recursos WHERE nombre = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->execute([$nombre, $resource_id]);
        
        if ($stmt->fetch()) {
            $errors[] = "Ya existe un recurso con ese nombre";
        }
    }

    if (empty($errors)) {
        $result = $resourceController->update($resource_id, [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'capacidad' => $capacidad === '' ? null : (int)$capacidad,
            'caracteristicas' => $caracteristicas,
            'departamento_id' => $departamento_id === '' ? null : (int)$departamento_id,
            'activo' => $activo
        ]);

        if ($result) {
            $success = "Recurso actualizado exitosamente";
            // Recargar datos del recurso
            $resource = $resourceController->show($resource_id);
        } else {
            $errors[] = "Error al actualizar el recurso";
        }
    }
}

// Obtener estadísticas del recurso
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stats_query = "
    SELECT 
        COUNT(*) as total_reservas,
        COUNT(CASE WHEN estado = 'confirmada' THEN 1 END) as confirmadas,
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN DATE(fecha_inicio) >= CURDATE() THEN 1 END) as futuras
    FROM reservas 
    WHERE recurso_id = ? 
    AND estado IN ('confirmada', 'pendiente', 'finalizada')
";

$stmt = $conn->prepare($stats_query);
$stmt->execute([$resource_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit me-2"></i>Editar Recurso</h2>
        <div>
            <a href="view_resource.php?id=<?php echo $resource->id; ?>" class="btn btn-info">
                <i class="fas fa-eye me-1"></i>Ver Detalles
            </a>
            <a href="manage_resources.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Errores encontrados:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario de Edición -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-edit me-2"></i>Información del Recurso</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editResourceForm">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="nombre" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Nombre *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($resource->nombre); ?>" 
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label for="capacidad" class="form-label">
                                    <i class="fas fa-users me-1"></i>Capacidad
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="capacidad" 
                                       name="capacidad" 
                                       min="0" 
                                       value="<?php echo $resource->capacidad; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">
                                    <i class="fas fa-cube me-1"></i>Tipo *
                                </label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="Aula" <?php echo $resource->tipo === 'Aula' ? 'selected' : ''; ?>>Aula</option>
                                    <option value="Laboratorio" <?php echo $resource->tipo === 'Laboratorio' ? 'selected' : ''; ?>>Laboratorio</option>
                                    <option value="Sala de Sistemas" <?php echo $resource->tipo === 'Sala de Sistemas' ? 'selected' : ''; ?>>Sala de Sistemas</option>
                                    <option value="Auditorio" <?php echo $resource->tipo === 'Auditorio' ? 'selected' : ''; ?>>Auditorio</option>
                                    <option value="Sala de Reuniones" <?php echo $resource->tipo === 'Sala de Reuniones' ? 'selected' : ''; ?>>Sala de Reuniones</option>
                                    <option value="Equipo" <?php echo $resource->tipo === 'Equipo' ? 'selected' : ''; ?>>Equipo</option>
                                    <option value="Instalación Deportiva" <?php echo $resource->tipo === 'Instalación Deportiva' ? 'selected' : ''; ?>>Instalación Deportiva</option>
                                    <option value="Biblioteca" <?php echo $resource->tipo === 'Biblioteca' ? 'selected' : ''; ?>>Biblioteca</option>
                                    <option value="Vehículo" <?php echo $resource->tipo === 'Vehículo' ? 'selected' : ''; ?>>Vehículo</option>
                                    <option value="Otro" <?php echo $resource->tipo === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="departamento_id" class="form-label">
                                    <i class="fas fa-building me-1"></i>Departamento
                                </label>
                                <select class="form-select" id="departamento_id" name="departamento_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo $resource->departamento_id == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Descripción
                            </label>
                            <textarea class="form-control" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Descripción del recurso..."><?php echo htmlspecialchars($resource->descripcion); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="caracteristicas" class="form-label">
                                <i class="fas fa-list-ul me-1"></i>Características y Equipamiento
                            </label>
                            <textarea class="form-control" 
                                      id="caracteristicas" 
                                      name="caracteristicas" 
                                      rows="4" 
                                      placeholder="Ej: Proyector, Sistema de sonido, Aire acondicionado, WiFi, etc."><?php echo htmlspecialchars($resource->caracteristicas); ?></textarea>
                            <div class="form-text">
                                Describe el equipamiento y características especiales del recurso
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       <?php echo $resource->activo ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    <i class="fas fa-toggle-on me-1"></i>Recurso Activo
                                </label>
                                <div class="form-text">
                                    Los recursos inactivos no aparecerán disponibles para reservas
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Cambios
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-1"></i>Deshacer Cambios
                            </button>
                            <a href="manage_resources.php" class="btn btn-outline-danger">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel de Información -->
        <div class="col-lg-4">
            <!-- Estado del Recurso -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle me-2"></i>Estado del Recurso</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="resource-status-badge">
                            <?php if ($resource->activo): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check-circle me-1"></i>Activo
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger fs-6">
                                    <i class="fas fa-times-circle me-1"></i>Inactivo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">Creado</small>
                            <div class="fw-bold">
                                <?php 
                                $created = new DateTime($resource->fecha_creacion);
                                echo $created->format('d/m/Y');
                                ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">ID</small>
                            <div class="fw-bold">#<?php echo $resource->id; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Uso -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar me-2"></i>Estadísticas de Uso</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border-end">
                                <h4 class="text-primary mb-0"><?php echo $stats['total_reservas']; ?></h4>
                                <small class="text-muted">Total Reservas</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="text-success mb-0"><?php echo $stats['confirmadas']; ?></h4>
                            <small class="text-muted">Confirmadas</small>
                        </div>
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-warning mb-0"><?php echo $stats['pendientes']; ?></h4>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info mb-0"><?php echo $stats['futuras']; ?></h4>
                            <small class="text-muted">Futuras</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Adicionales -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-tools me-2"></i>Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="view_resource.php?id=<?php echo $resource->id; ?>" 
                           class="btn btn-outline-info btn-sm">
                            <i class="fas fa-eye me-1"></i>Ver Detalles Completos
                        </a>
                        
                        <button type="button" 
                                class="btn btn-outline-warning btn-sm" 
                                onclick="duplicateResource()">
                            <i class="fas fa-copy me-1"></i>Duplicar Recurso
                        </button>
                        
                        <?php if ($stats['total_reservas'] == 0): ?>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm" 
                                    onclick="deleteResource(<?php echo $resource->id; ?>)">
                                <i class="fas fa-trash me-1"></i>Eliminar Recurso
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm" 
                                    disabled
                                    title="No se puede eliminar un recurso con reservas">
                                <i class="fas fa-trash me-1"></i>Eliminar Recurso
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este recurso?</p>
                <div class="alert alert-warning">
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Datos originales del formulario
let originalFormData = {};

document.addEventListener('DOMContentLoaded', function() {
    // Guardar datos originales
    const form = document.getElementById('editResourceForm');
    const formData = new FormData(form);
    for (let [key, value] of formData.entries()) {
        originalFormData[key] = value;
    }
    
    // Agregar checkbox state
    originalFormData['activo'] = document.getElementById('activo').checked;
});

function resetForm() {
    Swal.fire({
        title: '¿Deshacer cambios?',
        text: 'Se perderán todos los cambios no guardados',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, deshacer',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Restaurar valores originales
            for (let [key, value] of Object.entries(originalFormData)) {
                const element = document.getElementById(key);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = value;
                    } else {
                        element.value = value;
                    }
                }
            }
            
            Swal.fire({
                title: 'Cambios deshacidos',
                text: 'El formulario ha sido restaurado',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

function duplicateResource() {
    const form = document.getElementById('editResourceForm');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'id') {
            params.append(key, value);
        }
    }
    
    window.location.href = `create_resource.php?duplicate=1&${params.toString()}`;
}

function deleteResource(resourceId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    
    document.getElementById('confirmDelete').onclick = function() {
        // Hacer petición AJAX para eliminar
        fetch('../../api/resource_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${resourceId}`
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            if (data.success) {
                Swal.fire({
                    title: 'Eliminado',
                    text: 'El recurso ha sido eliminado exitosamente',
                    icon: 'success'
                }).then(() => {
                    window.location.href = 'manage_resources.php';
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Error al eliminar el recurso',
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modal.hide();
            Swal.fire({
                title: 'Error',
                text: 'Error de conexión',
                icon: 'error'
            });
        });
    };
}

// Validación en tiempo real
document.getElementById('editResourceForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const tipo = document.getElementById('tipo').value;
    
    if (!nombre || !tipo) {
        e.preventDefault();
        Swal.fire({
            title: 'Campos requeridos',
            text: 'Por favor complete todos los campos obligatorios',
            icon: 'warning'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando cambios...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});

// Auto-resize textarea
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
</script>

<style>
.resource-status-badge {
    margin-bottom: 1rem;
}

.border-end {
    border-right: 1px solid #dee2e6;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

textarea {
    resize: vertical;
    min-height: 80px;
}

.form-text {
    font-size: 0.875rem;
}

.btn-sm {
    font-size: 0.875rem;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
