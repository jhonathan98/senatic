<?php
$page_title = "Calendario de Eventos - Periódico Digital";
include_once '../includes/header.php';
include_once '../includes/conexion.php';
include_once '../includes/navbar.php';

// Obtener mes y año actual o desde parámetros
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Obtener eventos del mes seleccionado
$sql_eventos = "SELECT * FROM eventos 
                WHERE activo = TRUE 
                AND MONTH(fecha_evento) = ? 
                AND YEAR(fecha_evento) = ?
                ORDER BY fecha_evento ASC";

$stmt_eventos = mysqli_prepare($conexion, $sql_eventos);
mysqli_stmt_bind_param($stmt_eventos, "ii", $mes, $ano);
mysqli_stmt_execute($stmt_eventos);
$eventos = mysqli_stmt_get_result($stmt_eventos);

// Crear array de eventos por día para el calendario
$eventos_por_dia = [];
mysqli_data_seek($eventos, 0);
while ($evento = mysqli_fetch_assoc($eventos)) {
    $dia = date('j', strtotime($evento['fecha_evento']));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = $evento;
}

// Obtener próximos eventos destacados
$sql_proximos = "SELECT * FROM eventos 
                 WHERE activo = TRUE 
                 AND fecha_evento >= CURDATE()
                 ORDER BY fecha_evento ASC
                 LIMIT 5";

$proximos_eventos = mysqli_query($conexion, $sql_proximos);

// Función para formatear fecha en español
function formatearMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? 'Mes';
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h1><i class="bi bi-calendar-event"></i> Calendario de Eventos</h1>
            <p class="text-muted">Mantente al día con todos los eventos del colegio</p>

            <!-- Navegación del calendario -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="?mes=<?php echo $mes == 1 ? 12 : $mes - 1; ?>&ano=<?php echo $mes == 1 ? $ano - 1 : $ano; ?>" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left"></i> Anterior
                </a>
                <h3><?php echo formatearMes($mes) . ' ' . $ano; ?></h3>
                <a href="?mes=<?php echo $mes == 12 ? 1 : $mes + 1; ?>&ano=<?php echo $mes == 12 ? $ano + 1 : $ano; ?>" 
                   class="btn btn-outline-primary">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <!-- Calendario visual -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar3"></i> <?php echo formatearMes($mes) . ' ' . $ano; ?></h5>
                </div>
                <div class="card-body">
                    <div class="calendar-grid">
                        <!-- Encabezados de días -->
                        <div class="row text-center border-bottom pb-2 mb-2">
                            <div class="col"><small class="fw-bold text-muted">Dom</small></div>
                            <div class="col"><small class="fw-bold text-muted">Lun</small></div>
                            <div class="col"><small class="fw-bold text-muted">Mar</small></div>
                            <div class="col"><small class="fw-bold text-muted">Mié</small></div>
                            <div class="col"><small class="fw-bold text-muted">Jue</small></div>
                            <div class="col"><small class="fw-bold text-muted">Vie</small></div>
                            <div class="col"><small class="fw-bold text-muted">Sáb</small></div>
                        </div>
                        
                        <?php
                        // Calcular primer día del mes y días totales
                        $primer_dia = date('Y-m-01', mktime(0, 0, 0, $mes, 1, $ano));
                        $dia_semana_inicio = date('w', strtotime($primer_dia));
                        $dias_mes = date('t', mktime(0, 0, 0, $mes, 1, $ano));
                        $dia_actual = 1;
                        $fecha_hoy = date('Y-m-d');
                        
                        // Generar semanas
                        for ($semana = 0; $semana < 6; $semana++) {
                            if ($dia_actual > $dias_mes) break;
                            
                            echo '<div class="row mb-1">';
                            
                            for ($dia_sem = 0; $dia_sem < 7; $dia_sem++) {
                                echo '<div class="col p-1" style="min-height: 80px; border: 1px solid #eee;">';
                                
                                if (($semana == 0 && $dia_sem < $dia_semana_inicio) || $dia_actual > $dias_mes) {
                                    // Días vacíos
                                    echo '<div class="text-muted">&nbsp;</div>';
                                } else {
                                    // Día del mes
                                    $fecha_dia = sprintf('%04d-%02d-%02d', $ano, $mes, $dia_actual);
                                    $es_hoy = ($fecha_dia == $fecha_hoy);
                                    $clase_dia = $es_hoy ? 'bg-primary text-white rounded' : '';
                                    
                                    echo '<div class="' . $clase_dia . ' p-1 text-center mb-1">';
                                    echo '<small class="fw-bold">' . $dia_actual . '</small>';
                                    echo '</div>';
                                    
                                    // Mostrar eventos del día
                                    if (isset($eventos_por_dia[$dia_actual])) {
                                        foreach ($eventos_por_dia[$dia_actual] as $evento) {
                                            $color_tipo = '';
                                            switch ($evento['tipo_evento']) {
                                                case 'academico': $color_tipo = 'primary'; break;
                                                case 'cultural': $color_tipo = 'success'; break;
                                                case 'deportivo': $color_tipo = 'warning'; break;
                                                case 'institucional': $color_tipo = 'info'; break;
                                                default: $color_tipo = 'secondary';
                                            }
                                            
                                            echo '<div class="event-item bg-' . $color_tipo . ' text-white p-1 mb-1 rounded" style="font-size: 10px; cursor: pointer;" title="' . htmlspecialchars($evento['titulo']) . '">';
                                            echo '<small>' . htmlspecialchars(substr($evento['titulo'], 0, 15)) . (strlen($evento['titulo']) > 15 ? '...' : '') . '</small>';
                                            if ($evento['hora_evento']) {
                                                echo '<br><small>' . date('H:i', strtotime($evento['hora_evento'])) . '</small>';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                    
                                    $dia_actual++;
                                }
                                
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-clock"></i> Próximos Eventos</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($proximos_eventos) > 0): ?>
                        <?php while ($proximo = mysqli_fetch_assoc($proximos_eventos)): ?>
                            <div class="mb-3 border-bottom pb-2">
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($proximo['titulo']); ?>
                                    <?php if ($proximo['destacado']): ?>
                                        <span class="badge bg-warning text-dark ms-1">
                                            <i class="bi bi-star-fill" style="font-size: 0.8rem;"></i>
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <div class="small text-muted">
                                    <div>
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($proximo['fecha_evento'])); ?>
                                    </div>
                                    <?php if ($proximo['hora_evento']): ?>
                                        <div>
                                            <i class="bi bi-clock"></i> 
                                            <?php echo date('H:i', strtotime($proximo['hora_evento'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($proximo['lugar']): ?>
                                        <div>
                                            <i class="bi bi-geo-alt"></i> 
                                            <?php echo htmlspecialchars($proximo['lugar']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="badge bg-<?php 
                                        switch ($proximo['tipo_evento']) {
                                            case 'academico': echo 'primary'; break;
                                            case 'cultural': echo 'success'; break;
                                            case 'deportivo': echo 'warning'; break;
                                            case 'institucional': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?> mt-1">
                                        <?php echo ucfirst($proximo['tipo_evento']); ?>
                                    </span>
                                </div>
                                <?php if ($proximo['descripcion']): ?>
                                    <p class="text-muted small mt-1 mb-0">
                                        <?php echo htmlspecialchars(substr($proximo['descripcion'], 0, 80)); ?>
                                        <?php echo strlen($proximo['descripcion']) > 80 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <i class="bi bi-calendar-check text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No hay eventos próximos</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fechas importantes -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-bookmark-star"></i> Fechas Importantes</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">Período Académico</h6>
                        <small class="text-muted">Enero - Noviembre <?php echo date('Y'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">Vacaciones de Mitad de Año</h6>
                        <small class="text-muted">Junio - Julio <?php echo date('Y'); ?></small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-primary">Período de Exámenes</h6>
                        <small class="text-muted">Noviembre <?php echo date('Y'); ?></small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-primary">Graduación</h6>
                        <small class="text-muted">Diciembre <?php echo date('Y'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>