<?php
// views/admin/create_resource.php
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../../index.php?error=" . urlencode("No tiene permisos para acceder a esta página"));
    exit();
}

$page_title = "Crear Recurso - SysPlanner";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
require_once '../../controllers/ResourceController.php';

$resourceController = new ResourceController();
$message = '';
$error = '';

// Datos para duplicar recurso
$duplicate_data = [];
if (isset($_GET['duplicate']) && $_GET['duplicate'] == '1') {
    $duplicate_data = [
        'nombre' => $_GET['nombre'] ?? '',
        'tipo' => $_GET['tipo'] ?? '',
        'descripcion' => $_GET['descripcion'] ?? '',
        'capacidad' => $_GET['capacidad'] ?? '',
        'caracteristicas' => $_GET['caracteristicas'] ?? '',
        'departamento_id' => $_GET['departamento_id'] ?? '',
        'activo' => isset($_GET['activo']) ? 1 : 0
    ];
    
    // Modificar el nombre para indicar que es una copia
    if (!empty($duplicate_data['nombre']) && !str_contains($duplicate_data['nombre'], '(Copia)')) {
        $duplicate_data['nombre'] .= ' (Copia)';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $resourceController->store($_POST);
    
    if ($result['success']) {
        $message = $result['message'];
        // Limpiar datos de duplicación después de crear
        $duplicate_data = [];
    } else {
        $error = $result['message'];
    }
}

// Obtener departamentos para el formulario
$departments = $resourceController->create();
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Recurso</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['duplicate']) && $_GET['duplicate'] == '1'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-copy me-2"></i>
                            <strong>Duplicando recurso:</strong> Los datos del recurso original se han cargado. 
                            Modifica la información según sea necesario y guarda el nuevo recurso.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? $duplicate_data['nombre'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo *</label>
                                    <select class="form-control" id="tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <?php 
                                        $selected_tipo = $_POST['tipo'] ?? $duplicate_data['tipo'] ?? '';
                                        ?>
                                        <option value="Aula" <?php echo $selected_tipo === 'Aula' ? 'selected' : ''; ?>>Aula</option>
                                        <option value="Laboratorio" <?php echo $selected_tipo === 'Laboratorio' ? 'selected' : ''; ?>>Laboratorio</option>
                                        <option value="Sala de Sistemas" <?php echo $selected_tipo === 'Sala de Sistemas' ? 'selected' : ''; ?>>Sala de Sistemas</option>
                                        <option value="Auditorio" <?php echo $selected_tipo === 'Auditorio' ? 'selected' : ''; ?>>Auditorio</option>
                                        <option value="Sala de Reuniones" <?php echo $selected_tipo === 'Sala de Reuniones' ? 'selected' : ''; ?>>Sala de Reuniones</option>
                                        <option value="Equipo" <?php echo $selected_tipo === 'Equipo' ? 'selected' : ''; ?>>Equipo</option>
                                        <option value="Vehículo" <?php echo $selected_tipo === 'Vehículo' ? 'selected' : ''; ?>>Vehículo</option>
                                        <option value="Otro" <?php echo $selected_tipo === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($_POST['descripcion'] ?? $duplicate_data['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="capacidad" class="form-label">Capacidad</label>
                                    <input type="number" class="form-control" id="capacidad" name="capacidad" min="1"
                                           value="<?php echo htmlspecialchars($_POST['capacidad'] ?? $duplicate_data['capacidad'] ?? ''); ?>">
                                    <div class="form-text">Número máximo de personas que puede albergar</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="departamento_id" class="form-label">Departamento</label>
                                    <select class="form-control" id="departamento_id" name="departamento_id">
                                        <option value="">Seleccionar departamento</option>
                                        <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo ($_POST['departamento_id'] ?? $duplicate_data['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="caracteristicas" class="form-label">Características Técnicas</label>
                            <textarea class="form-control" id="caracteristicas" name="caracteristicas" rows="3"><?php echo htmlspecialchars($_POST['caracteristicas'] ?? $duplicate_data['caracteristicas'] ?? ''); ?></textarea>
                            <div class="form-text">Detalles específicos del recurso (equipamiento, software, etc.)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                       <?php echo ($_POST['activo'] ?? $duplicate_data['activo'] ?? '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Recurso activo
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="manage_resources.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crear Recurso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
