<?php
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

try {
    $conn = getConnection();
    
    // Obtener filtros
    $estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
    $plan_filtro = isset($_GET['plan']) ? $_GET['plan'] : '';
    
    // Construir consulta para obtener suscripciones con información del usuario y plan
    $query = "SELECT s.*, u.nombre_completo as usuario_nombre, u.correo_electronico, 
                     p.nombre as plan_nombre, p.precio as plan_precio, p.tipo as plan_tipo,
                     COUNT(hp.id) as total_pagos,
                     COALESCE(SUM(CASE WHEN hp.estado = 'exitoso' THEN hp.monto ELSE 0 END), 0) as total_pagado
              FROM suscripciones_usuarios s 
              JOIN usuarios u ON s.usuario_id = u.id 
              JOIN planes_personalizados p ON s.plan_id = p.id 
              LEFT JOIN historial_pagos hp ON s.id = hp.suscripcion_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($estado_filtro)) {
        $query .= " AND s.estado = ?";
        $params[] = $estado_filtro;
    }
    
    if (!empty($plan_filtro)) {
        $query .= " AND p.tipo = ?";
        $params[] = $plan_filtro;
    }
    
    $query .= " GROUP BY s.id ORDER BY s.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $suscripciones = $stmt->fetchAll();
    
    // Obtener estadísticas generales
    $stats_query = "SELECT 
                        COUNT(*) as total_suscripciones,
                        COUNT(CASE WHEN estado = 'activa' THEN 1 END) as activas,
                        COUNT(CASE WHEN estado = 'suspendida' THEN 1 END) as suspendidas,
                        COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as canceladas,
                        COALESCE(SUM(monto_mensual), 0) as ingresos_mensuales_estimados
                    FROM suscripciones_usuarios";
    $stats = $conn->query($stats_query)->fetch();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Suscripciones - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar { background-color: #8a2be2 !important; }
        .navbar-brand, .nav-link { color: white !important; }
        .nav-link:hover { color: #e6e6fa !important; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-responsive { border-radius: 10px; overflow: hidden; }
        .badge { font-size: 0.75rem; }
        .stats-card { background: linear-gradient(45deg, #8a2be2, #6a5acd); color: white; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Sistema de Mentorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="admin_mentores.php">Gestionar Mentores</a>
                <a class="nav-link" href="../logout.php">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Estadísticas generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5><?php echo $stats['total_suscripciones']; ?></h5>
                        <p class="mb-0">Total Suscripciones</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5><?php echo $stats['activas']; ?></h5>
                        <p class="mb-0">Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-pause-circle fa-2x mb-2"></i>
                        <h5><?php echo $stats['suspendidas']; ?></h5>
                        <p class="mb-0">Suspendidas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h5>$<?php echo number_format($stats['ingresos_mensuales_estimados'], 2); ?></h5>
                        <p class="mb-0">Ingresos Mensuales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="activa" <?php echo $estado_filtro === 'activa' ? 'selected' : ''; ?>>Activa</option>
                            <option value="suspendida" <?php echo $estado_filtro === 'suspendida' ? 'selected' : ''; ?>>Suspendida</option>
                            <option value="cancelada" <?php echo $estado_filtro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="vencida" <?php echo $estado_filtro === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Plan</label>
                        <select name="plan" class="form-select">
                            <option value="">Todos los planes</option>
                            <option value="basico" <?php echo $plan_filtro === 'basico' ? 'selected' : ''; ?>>Básico</option>
                            <option value="estandar" <?php echo $plan_filtro === 'estandar' ? 'selected' : ''; ?>>Estándar</option>
                            <option value="premium" <?php echo $plan_filtro === 'premium' ? 'selected' : ''; ?>>Premium</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Aplicar Filtros</button>
                        <a href="gestionar_suscripciones.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de suscripciones -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Suscripciones de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Estado</th>
                                <th>Fecha Inicio</th>
                                <th>Próximo Pago</th>
                                <th>Monto Mensual</th>
                                <th>Total Pagado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($suscripciones)): ?>
                                <?php foreach ($suscripciones as $suscripcion): ?>
                                    <tr>
                                        <td><?php echo $suscripcion['id']; ?></td>
                                        <td><?php echo htmlspecialchars($suscripcion['usuario_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($suscripcion['correo_electronico']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $suscripcion['plan_tipo'] === 'premium' ? 'warning' : ($suscripcion['plan_tipo'] === 'estandar' ? 'success' : 'primary'); ?>">
                                                <?php echo htmlspecialchars($suscripcion['plan_nombre']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $suscripcion['estado'] === 'activa' ? 'success' : ($suscripcion['estado'] === 'suspendida' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($suscripcion['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($suscripcion['fecha_inicio'])); ?></td>
                                        <td><?php echo $suscripcion['proximo_pago'] ? date('d/m/Y', strtotime($suscripcion['proximo_pago'])) : 'N/A'; ?></td>
                                        <td>$<?php echo number_format($suscripcion['monto_mensual'], 2); ?></td>
                                        <td>$<?php echo number_format($suscripcion['total_pagado'], 2); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="verDetalle(<?php echo $suscripcion['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" onclick="cambiarEstado(<?php echo $suscripcion['id']; ?>, '<?php echo $suscripcion['estado']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-success" onclick="verPagos(<?php echo $suscripcion['id']; ?>)">
                                                    <i class="fas fa-credit-card"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No se encontraron suscripciones</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Suscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(suscripcionId) {
            // Mostrar spinner
            document.getElementById('detalleContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            new bootstrap.Modal(document.getElementById('detalleModal')).show();
            
            // Hacer petición AJAX
            fetch('procesar_suscripcion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_detalle&suscripcion_id=${suscripcionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const detalle = data.detalle;
                    let pagosHtml = '';
                    
                    if (detalle.historial_pagos && detalle.historial_pagos.length > 0) {
                        detalle.historial_pagos.forEach(pago => {
                            const fecha = new Date(pago.fecha_pago).toLocaleDateString('es-ES');
                            const estadoBadge = pago.estado === 'exitoso' ? 'success' : (pago.estado === 'pendiente' ? 'warning' : 'danger');
                            pagosHtml += `
                                <tr>
                                    <td>${fecha}</td>
                                    <td>$${parseFloat(pago.monto).toFixed(2)}</td>
                                    <td><span class="badge bg-${estadoBadge}">${pago.estado}</span></td>
                                    <td>${pago.numero_transaccion || 'N/A'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        pagosHtml = '<tr><td colspan="4" class="text-center">No hay pagos registrados</td></tr>';
                    }
                    
                    let caracteristicasHtml = '';
                    if (detalle.caracteristicas && Array.isArray(detalle.caracteristicas)) {
                        caracteristicasHtml = detalle.caracteristicas.map(c => `<li><i class="fas fa-check text-success me-2"></i>${c}</li>`).join('');
                    }
                    
                    const content = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-user me-2"></i>Información del Usuario</h6>
                                <p><strong>Nombre:</strong> ${detalle.nombre_completo}</p>
                                <p><strong>Email:</strong> ${detalle.correo_electronico}</p>
                                <p><strong>Teléfono:</strong> ${detalle.telefono || 'No registrado'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-box me-2"></i>Información del Plan</h6>
                                <p><strong>Plan:</strong> ${detalle.plan_nombre}</p>
                                <p><strong>Precio:</strong> $${parseFloat(detalle.plan_precio).toFixed(2)}/mes</p>
                                <p><strong>Estado:</strong> <span class="badge bg-${detalle.estado === 'activa' ? 'success' : 'warning'}">${detalle.estado}</span></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6><i class="fas fa-list me-2"></i>Características del Plan</h6>
                                <ul class="list-unstyled">
                                    ${caracteristicasHtml}
                                </ul>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-line me-2"></i>Estadísticas</h6>
                                <p><strong>Total de Pagos:</strong> ${detalle.total_pagos}</p>
                                <p><strong>Total Pagado:</strong> <span class="text-success">$${parseFloat(detalle.total_pagado).toFixed(2)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar me-2"></i>Fechas</h6>
                                <p><strong>Inicio:</strong> ${new Date(detalle.fecha_inicio).toLocaleDateString('es-ES')}</p>
                                <p><strong>Próximo Pago:</strong> ${detalle.proximo_pago ? new Date(detalle.proximo_pago).toLocaleDateString('es-ES') : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <h6 class="mt-3"><i class="fas fa-history me-2"></i>Historial de Pagos Recientes</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Fecha</th><th>Monto</th><th>Estado</th><th>Transacción</th></tr>
                                </thead>
                                <tbody>
                                    ${pagosHtml}
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <button class="btn btn-warning btn-sm me-2" onclick="cambiarEstado(${suscripcionId}, '${detalle.estado}')">
                                <i class="fas fa-edit me-1"></i>Cambiar Estado
                            </button>
                            ${detalle.estado === 'activa' ? 
                                `<button class="btn btn-danger btn-sm" onclick="suspenderSuscripcion(${suscripcionId})">
                                    <i class="fas fa-pause me-1"></i>Suspender
                                </button>` :
                                `<button class="btn btn-success btn-sm" onclick="reactivarSuscripcion(${suscripcionId})">
                                    <i class="fas fa-play me-1"></i>Reactivar
                                </button>`
                            }
                        </div>
                    `;
                    document.getElementById('detalleContent').innerHTML = content;
                } else {
                    document.getElementById('detalleContent').innerHTML = '<div class="alert alert-danger">Error al cargar los detalles</div>';
                }
            })
            .catch(error => {
                document.getElementById('detalleContent').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
            });
        }

        function cambiarEstado(suscripcionId, estadoActual) {
            const estados = [
                {valor: 'activa', texto: 'Activa'},
                {valor: 'suspendida', texto: 'Suspendida'},
                {valor: 'cancelada', texto: 'Cancelada'},
                {valor: 'vencida', texto: 'Vencida'}
            ];
            
            let opciones = estados.map(e => `${e.valor} - ${e.texto}`).join('\n');
            const nuevoEstado = prompt(`Estado actual: ${estadoActual}\n\nSelecciona nuevo estado:\n${opciones}\n\nEscribe el valor exacto:`);
            
            if (nuevoEstado && estados.some(e => e.valor === nuevoEstado)) {
                if (confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) {
                    fetch('procesar_suscripcion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `accion=cambiar_estado&suscripcion_id=${suscripcionId}&nuevo_estado=${nuevoEstado}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.mensaje);
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('Error de conexión');
                    });
                }
            } else if (nuevoEstado) {
                alert('Estado no válido. Use: activa, suspendida, cancelada o vencida');
            }
        }

        function suspenderSuscripcion(suscripcionId) {
            const motivo = prompt('Motivo de la suspensión (opcional):') || 'Suspendido por administrador';
            if (confirm('¿Estás seguro de que deseas suspender esta suscripción?')) {
                fetch('procesar_suscripcion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=suspender_suscripcion&suscripcion_id=${suscripcionId}&motivo=${encodeURIComponent(motivo)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.mensaje);
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        function reactivarSuscripcion(suscripcionId) {
            if (confirm('¿Estás seguro de que deseas reactivar esta suscripción?')) {
                fetch('procesar_suscripcion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=reactivar_suscripcion&suscripcion_id=${suscripcionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.mensaje);
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        function verPagos(suscripcionId) {
            // Esta función podría abrir otra modal con el historial completo de pagos
            verDetalle(suscripcionId); // Por ahora, mostrar el detalle completo
        }
    </script>
</body>
</html>
