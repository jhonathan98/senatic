<?php
session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar que se haya proporcionado un número de transacción
$numero_transaccion = isset($_GET['txn']) ? $_GET['txn'] : '';
if (empty($numero_transaccion)) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/db.php';

try {
    $conn = getConnection();
    
    // Obtener información del pago
    $query = "SELECT hp.*, su.*, pp.nombre as plan_nombre, pp.precio as plan_precio, 
                     u.nombre_completo, u.correo_electronico
              FROM historial_pagos hp
              JOIN suscripciones_usuarios su ON hp.suscripcion_id = su.id
              JOIN planes_personalizados pp ON su.plan_id = pp.id
              JOIN usuarios u ON su.usuario_id = u.id
              WHERE hp.numero_transaccion = ? AND u.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$numero_transaccion, $_SESSION['user_id']]);
    $pago_info = $stmt->fetch();
    
    if (!$pago_info) {
        header("Location: dashboard.php");
        exit();
    }
    
} catch(PDOException $e) {
    $error = "Error al obtener información del pago: " . $e->getMessage();
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Confirmado - Sistema de Mentorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background-color: rgba(255,255,255,0.1) !important;
            backdrop-filter: blur(10px);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e6e6fa !important;
        }
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: checkmark 0.6s ease-in-out 0.3s both;
        }
        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
        .info-section {
            padding: 30px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            font-weight: 500;
            color: #212529;
        }
        .btn-action {
            background: linear-gradient(45deg, #8a2be2, #6a5acd);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.4);
            color: white;
        }
        .next-steps {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .step-number {
            width: 30px;
            height: 30px;
            background: #8a2be2;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 0.9rem;
        }
        .receipt-section {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Sistema de Mentorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <div class="confirmation-container">
        <div class="success-card">
            <!-- Header de éxito -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check fa-2x"></i>
                </div>
                <h2 class="mb-2">¡Pago Procesado Exitosamente!</h2>
                <p class="mb-0">Tu suscripción ha sido activada</p>
            </div>

            <div class="info-section">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php else: ?>
                    <!-- Información del pago -->
                    <h5 class="mb-4">
                        <i class="fas fa-receipt me-2"></i>
                        Detalles de la Transacción
                    </h5>
                    
                    <div class="receipt-section">
                        <div class="info-item">
                            <span class="info-label">Número de Transacción:</span>
                            <span class="info-value">
                                <strong><?php echo htmlspecialchars($numero_transaccion); ?></strong>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha del Pago:</span>
                            <span class="info-value">
                                <?php echo date('d/m/Y H:i', strtotime($pago_info['fecha_pago'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Plan Seleccionado:</span>
                            <span class="info-value">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($pago_info['plan_nombre']); ?></span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Método de Pago:</span>
                            <span class="info-value">
                                <i class="fas fa-<?php echo $pago_info['metodo_pago'] === 'tarjeta' ? 'credit-card' : ($pago_info['metodo_pago'] === 'paypal' ? 'paypal' : 'university'); ?> me-2"></i>
                                <?php echo ucfirst($pago_info['metodo_pago']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Monto Pagado:</span>
                            <span class="info-value">
                                <strong class="text-success">$<?php echo number_format($pago_info['monto'], 2); ?></strong>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span class="info-value">
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Pagado
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Información de la suscripción -->
                    <h5 class="mb-4 mt-4">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Información de tu Suscripción
                    </h5>
                    
                    <div class="info-item">
                        <span class="info-label">Fecha de Inicio:</span>
                        <span class="info-value">
                            <?php echo date('d/m/Y', strtotime($pago_info['fecha_inicio'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Próximo Pago:</span>
                        <span class="info-value">
                            <?php echo date('d/m/Y', strtotime($pago_info['proximo_pago'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Estado de Suscripción:</span>
                        <span class="info-value">
                            <span class="badge bg-success">Activa</span>
                        </span>
                    </div>

                    <!-- Próximos pasos -->
                    <div class="next-steps">
                        <h6 class="mb-3">
                            <i class="fas fa-route me-2"></i>
                            Próximos Pasos
                        </h6>
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div>
                                <strong>Revisa tu correo electrónico</strong><br>
                                <small class="text-muted">Te enviaremos la confirmación a <?php echo htmlspecialchars($pago_info['correo_electronico']); ?></small>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div>
                                <strong>Explora los mentores disponibles</strong><br>
                                <small class="text-muted">Encuentra el mentor perfecto para tus necesidades</small>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div>
                                <strong>Agenda tu primera sesión</strong><br>
                                <small class="text-muted">Comienza tu journey de aprendizaje</small>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn-action me-3">
                            <i class="fas fa-home me-2"></i>
                            Ir al Dashboard
                        </a>
                        <a href="paquetes.php" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>
                            Ver Otros Planes
                        </a>
                    </div>

                    <!-- Información de contacto -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-question-circle me-2"></i>¿Necesitas ayuda?</h6>
                        <p class="mb-0">
                            Si tienes alguna pregunta sobre tu suscripción o necesitas asistencia, no dudes en contactarnos:
                        </p>
                        <ul class="mb-0 mt-2">
                            <li>📧 Email: soporte@sistema-mentorias.com</li>
                            <li>📱 WhatsApp: +1 (555) 123-4567</li>
                            <li>🕒 Horario: Lunes a Viernes, 9:00 AM - 6:00 PM</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar notificación de éxito
        document.addEventListener('DOMContentLoaded', function() {
            // Confetti effect (simple version)
            setTimeout(function() {
                if (typeof confetti === 'function') {
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
            }, 500);

            // Guardar en localStorage para mostrar en dashboard
            localStorage.setItem('payment_success', 'true');
            localStorage.setItem('payment_plan', '<?php echo isset($pago_info) ? htmlspecialchars($pago_info['plan_nombre']) : ''; ?>');
        });

        // Función para imprimir recibo
        function imprimirRecibo() {
            window.print();
        }

        // Función para descargar recibo como PDF (simulación)
        function descargarRecibo() {
            alert('La descarga del recibo en PDF estará disponible próximamente.');
        }
    </script>
</body>
</html>
