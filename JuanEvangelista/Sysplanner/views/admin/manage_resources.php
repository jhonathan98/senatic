<?php
// views/admin/manage_resources.php
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php?error=" . urlencode("No tiene permisos para acceder a esta página"));
    exit();
}

$page_title = "Gestión de Recursos - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ResourceController.php';

$resourceController = new ResourceController();
$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $result = $resourceController->destroy($_POST['resource_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Obtener lista de recursos
$resources = $resourceController->index();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cube me-2"></i>Gestión de Recursos</h2>
                <a href="create_resource.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nuevo Recurso
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Capacidad</th>
                                    <th>Departamento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($resource = $resources->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo $resource['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($resource['nombre']); ?></strong>
                                            <?php if ($resource['descripcion']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($resource['descripcion']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($resource['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($resource['capacidad']): ?>
                                                <i class="fas fa-users me-1"></i><?php echo $resource['capacidad']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No especificada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($resource['departamento_nombre'] ?? 'Sin asignar'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $resource['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $resource['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_resource.php?id=<?php echo $resource['id']; ?>" 
                                                   class="btn btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $resource['id']; ?>, '<?php echo htmlspecialchars($resource['nombre']); ?>')"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar el recurso <strong id="resourceName"></strong>?
                <br><small class="text-muted">Esta acción eliminará también todas las reservas asociadas.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="resource_id" id="deleteResourceId">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(resourceId, resourceName) {
    document.getElementById('deleteResourceId').value = resourceId;
    document.getElementById('resourceName').textContent = resourceName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>