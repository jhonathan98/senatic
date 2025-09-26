<?php
session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener parámetros del plan seleccionado
$plan_tipo = isset($_GET['plan']) ? $_GET['plan'] : '';
$plan_precio = isset($_GET['precio']) ? floatval($_GET['precio']) : 0;
$plan_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';

// Verificar que se hayan recibido los parámetros necesarios
if (empty($plan_tipo) || $plan_precio <= 0) {
    header("Location: paquetes.php");
    exit();
}

require_once 'config/db.php';

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$usuario_id = $_SESSION['user_id'];

// Definir características del plan basado en el tipo
$caracteristicas = [];
switch($plan_tipo) {
    case 'basico':
        $caracteristicas = [
            "4 sesiones mensuales",
            "1 hora por sesión", 
            "Material de estudio básico",
            "Soporte por correo"
        ];
        break;
    case 'estandar':
        $caracteristicas = [
            "8 sesiones mensuales",
            "1.5 horas por sesión",
            "Material de estudio completo", 
            "Soporte por WhatsApp",
            "Ejercicios prácticos"
        ];
        break;
    case 'premium':
        $caracteristicas = [
            "12 sesiones mensuales",
            "2 horas por sesión",
            "Material premium exclusivo",
            "Soporte 24/7",
            "Ejercicios avanzados", 
            "Sesiones grupales adicionales"
        ];
        break;
}

// Procesar el formulario de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getConnection();
        
        // Obtener datos del formulario
        $metodo_pago = $_POST['metodo_pago'];
        $codigo_promocional = !empty($_POST['codigo_promocional']) ? $_POST['codigo_promocional'] : null;
        $monto_original = $plan_precio;
        $descuento = 0;
        
        // Aplicar descuentos si hay código promocional
        if ($codigo_promocional) {
            switch($codigo_promocional) {
                case 'NUEVO50':
                    $descuento = $monto_original * 0.5; // 50% descuento
                    break;
                case 'FAMILIA20':
                    $descuento = $monto_original * 0.2; // 20% descuento
                    break;
            }
        }
        
        $monto_final = $monto_original - $descuento;
        
        // Obtener el ID del plan personalizado
        $plan_query = "SELECT id FROM planes_personalizados WHERE tipo = ?";
        $plan_stmt = $conn->prepare($plan_query);
        $plan_stmt->execute([$plan_tipo]);
        $plan_id = $plan_stmt->fetchColumn();
        
        // Crear la suscripción
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d', strtotime('+1 year'));
        $proximo_pago = date('Y-m-d', strtotime('+1 month'));
        
        $suscripcion_query = "INSERT INTO suscripciones_usuarios 
                             (usuario_id, plan_id, fecha_inicio, fecha_fin, metodo_pago, monto_mensual, proximo_pago) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
        $suscripcion_stmt = $conn->prepare($suscripcion_query);
        $suscripcion_stmt->execute([
            $usuario_id, $plan_id, $fecha_inicio, $fecha_fin, 
            $metodo_pago, $monto_final, $proximo_pago
        ]);
        
        $suscripcion_id = $conn->lastInsertId();
        
        // Registrar el pago
        $numero_transaccion = 'TXN' . time() . rand(1000, 9999);
        $datos_pago = json_encode($_POST);
        
        $pago_query = "INSERT INTO historial_pagos 
                      (suscripcion_id, monto, metodo_pago, numero_transaccion, estado, datos_pago) 
                      VALUES (?, ?, ?, ?, 'exitoso', ?)";
        $pago_stmt = $conn->prepare($pago_query);
        $pago_stmt->execute([
            $suscripcion_id, $monto_final, $metodo_pago, 
            $numero_transaccion, $datos_pago
        ]);
        
        // Redirigir a página de confirmación
        header("Location: confirmacion_pago.php?txn=" . $numero_transaccion);
        exit();
        
    } catch(PDOException $e) {
        $error_pago = "Error al procesar el pago: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Pago - Sistema de Mentorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #8a2be2 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e6e6fa !important;
        }
        .payment-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border: none;
        }
        .plan-summary {
            background: linear-gradient(45deg, #8a2be2, #6a5acd);
            color: white;
        }
        .payment-form {
            background-color: white;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #8a2be2;
            box-shadow: 0 0 0 0.2rem rgba(138, 43, 226, 0.25);
        }
        .btn-pay {
            background: linear-gradient(45deg, #8a2be2, #6a5acd);
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.4);
        }
        .security-info {
            background-color: #000;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 0 10px 10px 0;
        }
        .discount-applied {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 10px;
            color: #155724;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Sistema de Mentorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="paquetes.php">Volver a Paquetes</a>
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <div class="payment-container">
        <?php if (isset($error_pago)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_pago; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Resumen del plan -->
            <div class="col-lg-5 mb-4">
                <div class="card plan-summary h-100">
                    <div class="card-header text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-crown me-2"></i>
                            <?php echo htmlspecialchars($plan_nombre); ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h1 class="display-4">$<?php echo number_format($plan_precio, 2); ?></h1>
                            <p class="mb-0">por mes</p>
                        </div>
                        
                        <h5 class="mb-3">Características incluidas:</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($caracteristicas as $caracteristica): ?>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($caracteristica); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="security-info mt-4">
                            <h6><i class="fas fa-shield-alt me-2"></i>Seguridad Garantizada</h6>
                            <small>
                                <i class="fas fa-lock me-1"></i> Conexión SSL segura<br>
                                <i class="fas fa-credit-card me-1"></i> Pagos encriptados<br>
                                <i class="fas fa-undo me-1"></i> Cancela cuando quieras
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de pago -->
            <div class="col-lg-7">
                <div class="card payment-form">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Información de Pago
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="paymentForm">
                            <!-- Código promocional -->
                            <div class="mb-4">
                                <label class="form-label">Código Promocional (Opcional)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="codigo_promocional" id="codigoPromocional" placeholder="Ingresa tu código">
                                    <button type="button" class="btn btn-outline-secondary" onclick="aplicarDescuento()">Aplicar</button>
                                </div>
                                <small class="text-muted">Códigos disponibles: NUEVO50 (50% desc.), FAMILIA20 (20% desc.)</small>
                                <div id="discountMessage" class="mt-2" style="display: none;"></div>
                            </div>

                            <!-- Método de pago -->
                            <div class="mb-4">
                                <label class="form-label">Método de Pago</label>
                                <select class="form-select" name="metodo_pago" required onchange="mostrarFormularioPago(this.value)">
                                    <option value="">Selecciona un método de pago</option>
                                    <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="transferencia">Transferencia Bancaria</option>
                                </select>
                            </div>

                            <!-- Formulario de tarjeta -->
                            <div id="tarjetaForm" style="display: none;">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Número de Tarjeta</label>
                                        <input type="text" class="form-control" name="numero_tarjeta" placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fecha de Vencimiento</label>
                                        <input type="text" class="form-control" name="fecha_vencimiento" placeholder="MM/AA" maxlength="5">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">CVV</label>
                                        <input type="text" class="form-control" name="cvv" placeholder="123" maxlength="4">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Titular</label>
                                    <input type="text" class="form-control" name="nombre_titular" placeholder="Como aparece en la tarjeta">
                                </div>
                            </div>

                            <!-- Formulario de PayPal -->
                            <div id="paypalForm" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fab fa-paypal fa-2x float-start me-3"></i>
                                    <p class="mb-0">Serás redirigido a PayPal para completar tu pago de forma segura.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email de PayPal</label>
                                    <input type="email" class="form-control" name="paypal_email" placeholder="tu-email@paypal.com">
                                </div>
                            </div>

                            <!-- Formulario de transferencia -->
                            <div id="transferenciaForm" style="display: none;">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-university me-2"></i>Datos para Transferencia</h6>
                                    <p class="mb-1"><strong>Banco:</strong> Banco Nacional</p>
                                    <p class="mb-1"><strong>Cuenta:</strong> 1234-5678-9012-3456</p>  
                                    <p class="mb-0"><strong>Titular:</strong> Sistema de Mentorías S.A.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Número de Referencia</label>
                                    <input type="text" class="form-control" name="referencia_transferencia" placeholder="Número de la transferencia">
                                </div>
                            </div>

                            <!-- Resumen del pago -->
                            <div class="card bg-light mt-4">
                                <div class="card-body">
                                    <h6>Resumen del Pago</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">$<?php echo number_format($plan_precio, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Descuento:</span>
                                        <span id="descuento" class="text-success">$0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="total">$<?php echo number_format($plan_precio, 2); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Términos y condiciones -->
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="terminos" required>
                                <label class="form-check-label" for="terminos">
                                    Acepto los <a href="#">términos y condiciones</a> y la <a href="#">política de privacidad</a>
                                </label>
                            </div>

                            <!-- Botón de pago -->
                            <button type="submit" class="btn btn-pay text-white w-100 mt-4">
                                <i class="fas fa-lock me-2"></i>
                                Procesar Pago Seguro - <span id="totalBtn">$<?php echo number_format($plan_precio, 2); ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const precioOriginal = <?php echo $plan_precio; ?>;
        let descuentoActual = 0;

        function mostrarFormularioPago(metodo) {
            // Ocultar todos los formularios
            document.getElementById('tarjetaForm').style.display = 'none';
            document.getElementById('paypalForm').style.display = 'none';
            document.getElementById('transferenciaForm').style.display = 'none';
            
            // Mostrar el formulario correspondiente
            if (metodo === 'tarjeta') {
                document.getElementById('tarjetaForm').style.display = 'block';
                // Formatear número de tarjeta
                const numeroTarjeta = document.querySelector('input[name="numero_tarjeta"]');
                numeroTarjeta.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
                    let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
                    if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
                    e.target.value = formattedValue;
                });
                
                // Formatear fecha de vencimiento
                const fechaVencimiento = document.querySelector('input[name="fecha_vencimiento"]');
                fechaVencimiento.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            } else if (metodo === 'paypal') {
                document.getElementById('paypalForm').style.display = 'block';
            } else if (metodo === 'transferencia') {
                document.getElementById('transferenciaForm').style.display = 'block';
            }
        }

        function aplicarDescuento() {
            const codigo = document.getElementById('codigoPromocional').value.trim().toUpperCase();
            const discountDiv = document.getElementById('discountMessage');
            
            let descuento = 0;
            let mensaje = '';
            
            switch(codigo) {
                case 'NUEVO50':
                    descuento = precioOriginal * 0.5;
                    mensaje = '¡Descuento del 50% aplicado!';
                    break;
                case 'FAMILIA20':
                    descuento = precioOriginal * 0.2;
                    mensaje = '¡Descuento del 20% aplicado!';
                    break;
                default:
                    if (codigo) {
                        mensaje = 'Código promocional no válido';
                        discountDiv.className = 'alert alert-danger mt-2';
                    }
                    break;
            }
            
            if (descuento > 0) {
                descuentoActual = descuento;
                discountDiv.className = 'discount-applied mt-2';
                discountDiv.innerHTML = `<i class="fas fa-tag me-2"></i>${mensaje}`;
                actualizarTotales();
            } else if (codigo) {
                discountDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${mensaje}`;
            }
            
            discountDiv.style.display = codigo ? 'block' : 'none';
        }

        function actualizarTotales() {
            const total = precioOriginal - descuentoActual;
            
            document.getElementById('descuento').textContent = `-$${descuentoActual.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
            document.getElementById('totalBtn').textContent = `$${total.toFixed(2)}`;
        }

        // Validación del formulario
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const metodo = document.querySelector('select[name="metodo_pago"]').value;
            let camposRequeridos = [];
            
            if (metodo === 'tarjeta') {
                camposRequeridos = ['numero_tarjeta', 'fecha_vencimiento', 'cvv', 'nombre_titular'];
            } else if (metodo === 'paypal') {
                camposRequeridos = ['paypal_email'];
            } else if (metodo === 'transferencia') {
                camposRequeridos = ['referencia_transferencia'];
            }
            
            for (let campo of camposRequeridos) {
                const input = document.querySelector(`input[name="${campo}"]`);
                if (input && !input.value.trim()) {
                    e.preventDefault();
                    alert(`Por favor, completa el campo: ${input.previousElementSibling.textContent}`);
                    input.focus();
                    return;
                }
            }
            
            // Simular procesamiento
            if (!e.defaultPrevented) {
                const btn = e.target.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                btn.disabled = true;
            }
        });
    </script>
</body>
</html>
