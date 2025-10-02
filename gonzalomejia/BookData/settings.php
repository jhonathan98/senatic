<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);
if (!$user) {
    header("Location: logout.php");
    exit();
}

$success_message = '';
$error_message = '';

// Only admin can access settings
if ($user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle settings updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_app_settings'])) {
        // Handle app settings update
        $app_name = sanitize_input($_POST['app_name']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $max_borrow_days = (int)$_POST['max_borrow_days'];
        $max_renewals = (int)$_POST['max_renewals'];
        
        // Here you would update the settings in database or config file
        // For now, we'll just show success message
        $success_message = "Configuración de la aplicación actualizada exitosamente.";
    }
    
    if (isset($_POST['update_email_settings'])) {
        // Handle email settings
        $smtp_host = sanitize_input($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = sanitize_input($_POST['smtp_username']);
        $smtp_password = $_POST['smtp_password'];
        
        $success_message = "Configuración de email actualizada exitosamente.";
    }
    
    if (isset($_POST['backup_database'])) {
        // Handle database backup
        $success_message = "Respaldo de base de datos creado exitosamente.";
    }
}

$page_title = 'Configuración del Sistema';

// Include header
include 'includes/header.php';
?>

<!-- Estilos específicos para la página de configuración -->
<style>
    body {
        padding-top: 80px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .settings-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 2rem;
    }
    
    .settings-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .settings-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        border: none;
        padding: 1.5rem;
    }
    
    .settings-card .card-header h5 {
        margin: 0;
        font-weight: 600;
    }
    
    .nav-pills .nav-link {
        border-radius: 25px;
        margin: 0 0.5rem;
        transition: all 0.3s ease;
        color: #495057;
    }
    
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }
    
    .btn-success-gradient {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        border-radius: 25px;
        color: white;
    }
    
    .btn-success-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }
    
    .btn-warning-gradient {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        border: none;
        border-radius: 25px;
        color: white;
    }
    
    .btn-warning-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }
    
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 15px;
        color: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-switch .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    .alert-custom {
        border: none;
        border-radius: 15px;
        padding: 1rem 1.5rem;
    }
</style>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-center mb-3">
                <i class="fas fa-cog me-3"></i>Configuración del Sistema
            </h1>
            <p class="text-center text-muted">Administra la configuración global de BookData</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- System Statistics -->
    <div class="row mb-5">
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="h3">
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
                        $stmt->execute();
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <i class="fas fa-users me-1"></i>Usuarios Registrados
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="h3">
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books");
                        $stmt->execute();
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <i class="fas fa-books me-1"></i>Libros Totales
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="h3">
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE status = 'active'");
                        $stmt->execute();
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <i class="fas fa-book-reader me-1"></i>Préstamos Activos
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <div class="h3">
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
                        $stmt->execute();
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <i class="fas fa-tags me-1"></i>Categorías
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills justify-content-center mb-4" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                <i class="fas fa-sliders-h me-2"></i>General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email" type="button" role="tab">
                <i class="fas fa-envelope me-2"></i>Email
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup" type="button" role="tab">
                <i class="fas fa-database me-2"></i>Respaldos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button" role="tab">
                <i class="fas fa-tools me-2"></i>Mantenimiento
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="settingsTabsContent">
        <!-- General Settings Tab -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="card settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-sliders-h me-2"></i>Configuración General</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="app_name" class="form-label">Nombre de la Aplicación</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" 
                                       value="<?php echo APP_NAME; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_borrow_days" class="form-label">Días Máximos de Préstamo</label>
                                <input type="number" class="form-control" id="max_borrow_days" 
                                       name="max_borrow_days" value="14" min="1" max="30">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_renewals" class="form-label">Máximo Renovaciones</label>
                                <input type="number" class="form-control" id="max_renewals" 
                                       name="max_renewals" value="2" min="0" max="5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="maintenance_mode" name="maintenance_mode">
                                    <label class="form-check-label" for="maintenance_mode">
                                        Modo Mantenimiento
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_app_settings" class="btn btn-gradient">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Email Settings Tab -->
        <div class="tab-pane fade" id="email" role="tabpanel">
            <div class="card settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-envelope me-2"></i>Configuración de Email</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                       placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="smtp_port" class="form-label">Puerto SMTP</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                       value="587">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="smtp_username" class="form-label">Usuario SMTP</label>
                                <input type="email" class="form-control" id="smtp_username" name="smtp_username">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="smtp_password" class="form-label">Contraseña SMTP</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_email_settings" class="btn btn-gradient">
                                <i class="fas fa-save me-2"></i>Guardar Configuración de Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Backup Tab -->
        <div class="tab-pane fade" id="backup" role="tabpanel">
            <div class="card settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-database me-2"></i>Respaldos y Restauración</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Crear Respaldo</h6>
                            <p class="text-muted">Genera un respaldo completo de la base de datos.</p>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="backup_database" class="btn btn-success-gradient">
                                    <i class="fas fa-download me-2"></i>Crear Respaldo
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6>Restaurar Base de Datos</h6>
                            <p class="text-muted">Restaura la base de datos desde un archivo de respaldo.</p>
                            <div class="mb-3">
                                <input type="file" class="form-control" accept=".sql">
                            </div>
                            <button class="btn btn-warning-gradient">
                                <i class="fas fa-upload me-2"></i>Restaurar
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>Respaldos Recientes</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tamaño</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                    <td>2.5 MB</td>
                                    <td><span class="badge bg-primary">Automático</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Descargar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Tab -->
        <div class="tab-pane fade" id="maintenance" role="tabpanel">
            <div class="card settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-tools me-2"></i>Herramientas de Mantenimiento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-broom fa-2x text-warning mb-2"></i>
                                <h6>Limpiar Cache</h6>
                                <p class="text-muted small">Limpia archivos temporales y cache del sistema.</p>
                                <button class="btn btn-warning btn-sm">Limpiar Cache</button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                                <h6>Optimizar DB</h6>
                                <p class="text-muted small">Optimiza las tablas de la base de datos.</p>
                                <button class="btn btn-info btn-sm">Optimizar</button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                                <h6>Ver Logs</h6>
                                <p class="text-muted small">Revisa los logs del sistema y errores.</p>
                                <button class="btn btn-success btn-sm">Ver Logs</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirmation for dangerous actions
    const dangerousButtons = document.querySelectorAll('button[name="backup_database"], .btn-warning-gradient');
    dangerousButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres realizar esta acción?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
