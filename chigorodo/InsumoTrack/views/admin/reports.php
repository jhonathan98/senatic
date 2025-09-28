<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pagina_titulo = "Reportes y Estadísticas";
include '../../includes/header.php';

// Incluir funciones de administrador con ruta dinámica
$admin_functions_path = '../../functions/admin_functions.php';
if (!file_exists($admin_functions_path)) {
    $admin_functions_path = dirname(dirname(__DIR__)) . '/functions/admin_functions.php';
}
require_once $admin_functions_path;

// Obtener estadísticas
$estadisticas = obtenerEstadisticasGeneral();
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes y Estadísticas</h1>
        <p class="text-muted">Visualiza métricas y genera reportes del sistema</p>
    </div>
</div>

<!-- Estadísticas principales -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-archive text-primary" style="font-size: 3rem;"></i>
                <h3 class="mt-2"><?php echo $estadisticas['total_insumos']; ?></h3>
                <p class="text-muted">Total Insumos</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                <h3 class="mt-2"><?php echo $estadisticas['prestamos_activos']; ?></h3>
                <p class="text-muted">Préstamos Activos</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-people text-info" style="font-size: 3rem;"></i>
                <h3 class="mt-2"><?php echo $estadisticas['total_usuarios']; ?></h3>
                <p class="text-muted">Usuarios Registrados</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                <h3 class="mt-2"><?php echo $estadisticas['prestamos_atrasados']; ?></h3>
                <p class="text-muted">Préstamos Atrasados</p>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos y visualizaciones -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart me-2"></i>Estado de Insumos</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <h4><?php echo $estadisticas['insumos_disponibles']; ?></h4>
                            <small>Disponibles</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-warning">
                            <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                            <h4><?php echo $estadisticas['total_insumos'] - $estadisticas['insumos_disponibles']; ?></h4>
                            <small>En Préstamo</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-info">
                            <i class="bi bi-clock" style="font-size: 2rem;"></i>
                            <h4><?php echo $estadisticas['prestamos_pendientes']; ?></h4>
                            <small>Pendientes</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-danger">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                            <h4><?php echo $estadisticas['prestamos_atrasados']; ?></h4>
                            <small>Atrasados</small>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <!-- Barra de progreso -->
                <div class="mb-2">
                    <small>Disponibilidad de Insumos</small>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: <?php echo $estadisticas['total_insumos'] > 0 ? round(($estadisticas['insumos_disponibles'] / $estadisticas['total_insumos']) * 100) : 0; ?>%">
                            <?php echo $estadisticas['total_insumos'] > 0 ? round(($estadisticas['insumos_disponibles'] / $estadisticas['total_insumos']) * 100) : 0; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart me-2"></i>Métricas del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Tasa de Aprobación:</span>
                        <strong class="text-success">
                            <?php 
                            $total_solicitudes = $estadisticas['prestamos_activos'] + $estadisticas['prestamos_pendientes'];
                            echo $total_solicitudes > 0 ? round(($estadisticas['prestamos_activos'] / $total_solicitudes) * 100, 1) : 0;
                            ?>%
                        </strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: <?php echo $total_solicitudes > 0 ? round(($estadisticas['prestamos_activos'] / $total_solicitudes) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Utilización de Inventario:</span>
                        <strong class="text-info">
                            <?php echo $estadisticas['total_insumos'] > 0 ? round((($estadisticas['total_insumos'] - $estadisticas['insumos_disponibles']) / $estadisticas['total_insumos']) * 100, 1) : 0; ?>%
                        </strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-info" style="width: <?php echo $estadisticas['total_insumos'] > 0 ? round((($estadisticas['total_insumos'] - $estadisticas['insumos_disponibles']) / $estadisticas['total_insumos']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Préstamos Atrasados:</span>
                        <strong class="text-danger">
                            <?php echo $estadisticas['prestamos_activos'] > 0 ? round(($estadisticas['prestamos_atrasados'] / $estadisticas['prestamos_activos']) * 100, 1) : 0; ?>%
                        </strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: <?php echo $estadisticas['prestamos_activos'] > 0 ? round(($estadisticas['prestamos_atrasados'] / $estadisticas['prestamos_activos']) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Herramientas de reporte -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-download me-2"></i>Generar Reportes</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Genera y descarga reportes personalizados del sistema</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Reportes Predefinidos:</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success" onclick="generarReporte('insumos')">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Reporte de Inventario (PDF)
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="generarReporte('prestamos')">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Historial de Préstamos (Excel)
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="generarReporte('usuarios')">
                                <i class="bi bi-file-earmark-text me-2"></i>Lista de Usuarios (PDF)
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="generarReporte('atrasados')">
                                <i class="bi bi-file-earmark-x me-2"></i>Préstamos Atrasados (PDF)
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Reporte Personalizado:</h6>
                        <form id="reportePersonalizado">
                            <div class="mb-3">
                                <label for="tipo_reporte" class="form-label">Tipo de Reporte:</label>
                                <select class="form-select" id="tipo_reporte" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="prestamos">Préstamos por Fecha</option>
                                    <option value="insumos">Estado de Insumos</option>
                                    <option value="usuarios">Actividad de Usuarios</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                                    <input type="date" class="form-control" id="fecha_inicio" required>
                                </div>
                                <div class="col-6">
                                    <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                                    <input type="date" class="form-control" id="fecha_fin" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Generar Reporte Personalizado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha por defecto (último mes)
    const hoy = new Date();
    const unMesAtras = new Date(hoy.getFullYear(), hoy.getMonth() - 1, hoy.getDate());
    
    document.getElementById('fecha_inicio').value = unMesAtras.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = hoy.toISOString().split('T')[0];

    // Función para generar reportes predefinidos
    window.generarReporte = function(tipo) {
        // Por ahora solo mostramos un mensaje
        let mensaje = '';
        switch(tipo) {
            case 'insumos':
                mensaje = 'Generando reporte de inventario...';
                break;
            case 'prestamos':
                mensaje = 'Generando historial de préstamos...';
                break;
            case 'usuarios':
                mensaje = 'Generando lista de usuarios...';
                break;
            case 'atrasados':
                mensaje = 'Generando reporte de préstamos atrasados...';
                break;
        }
        
        alert(mensaje + '\n\nEsta funcionalidad estará disponible en una versión futura.');
    };

    // Manejar formulario de reporte personalizado
    document.getElementById('reportePersonalizado').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const tipo = document.getElementById('tipo_reporte').value;
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        
        alert(`Generando reporte personalizado:\n\nTipo: ${tipo}\nFecha Inicio: ${fechaInicio}\nFecha Fin: ${fechaFin}\n\nEsta funcionalidad estará disponible en una versión futura.`);
    });
});
</script>

<?php include '../../includes/footer.php'; ?><div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Alertas por Atrasos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Insumo</th>
                                <th>Fecha Devolución Prevista</th>
                                <th>Días de Atraso</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAlertasAtrasos">
                            <!-- Las filas se cargarán dinámicamente con PHP desde la DB -->
                            <tr><td>Ana Gómez</td><td>Multímetro Digital (EQP002)</td><td>2025-11-02</td><td>3</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>