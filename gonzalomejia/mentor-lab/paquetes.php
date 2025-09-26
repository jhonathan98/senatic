<?php
session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener el nombre del usuario y su rol
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$es_admin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paquetes y Promociones - Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
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
        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }
        .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e6e6fa !important;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card.border-primary, .card.border-success, .card.border-warning {
            border-width: 2px;
        }
        .card .card-header {
            padding: 1rem;
        }
        .card .badge {
            position: absolute;
            top: -10px;
            right: 10px;
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        .bg-gradient {
            transition: transform 0.3s ease;
        }
        .bg-gradient:hover {
            transform: translateY(-5px);
        }
        .card-body ul li {
            margin-bottom: 0.8rem;
        }
        .card-body ul li i {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-purple">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Sistema de Mentorías</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Volver al Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h1 class="mb-5 text-center">Paquetes y Promociones</h1>
        
        <!-- Paquetes Section -->
        <div class="row">
            <!-- Paquete Básico -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="mb-0">Paquete Básico</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title text-center text-primary mb-4">$299/mes</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 4 sesiones mensuales</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 1 hora por sesión</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Material de estudio básico</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Soporte por correo</li>
                        </ul>
                        <button class="btn btn-primary w-100 mt-3" onclick="seleccionarPlan('basico', 299, 'Paquete Básico')">Seleccionar Plan</button>
                    </div>
                </div>
            </div>
            
            <!-- Paquete Estándar -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">Paquete Estándar</h3>
                        <span class="badge bg-warning text-dark">Más Popular</span>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title text-center text-success mb-4">$499/mes</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 8 sesiones mensuales</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 1.5 horas por sesión</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Material de estudio completo</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Soporte por WhatsApp</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Ejercicios prácticos</li>
                        </ul>
                        <button class="btn btn-success w-100 mt-3" onclick="seleccionarPlan('estandar', 499, 'Paquete Estándar')">Seleccionar Plan</button>
                    </div>
                </div>
            </div>
            
            <!-- Paquete Premium -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning text-dark text-center">
                        <h3 class="mb-0">Paquete Premium</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title text-center text-warning mb-4">$799/mes</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 12 sesiones mensuales</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 2 horas por sesión</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Material premium exclusivo</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Soporte 24/7</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Ejercicios avanzados</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Sesiones grupales adicionales</li>
                        </ul>
                        <button class="btn btn-warning w-100 mt-3" onclick="seleccionarPlan('premium', 799, 'Paquete Premium')">Seleccionar Plan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promociones Section -->
        <h2 class="mt-5 mb-4 text-center">Promociones Especiales</h2>
        <div class="row">
            <!-- Promoción 1 -->
            <div class="col-md-6 mb-4">
                <div class="card bg-gradient" style="background: linear-gradient(45deg, #6a11cb, #2575fc);">
                    <div class="card-body">
                        <h4 class="card-title">¡Oferta de Nuevo Ingreso!</h4>
                        <p class="card-text">50% de descuento en tu primer mes al suscribirte a cualquier paquete.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i> Válido hasta: 30 de septiembre</li>
                            <li><i class="fas fa-tag me-2"></i> Usa el código: NUEVO50</li>
                        </ul>
                        <button class="btn btn-light mt-3">Aprovechar Oferta</button>
                    </div>
                </div>
            </div>
            
            <!-- Promoción 2 -->
            <div class="col-md-6 mb-4">
                <div class="card bg-gradient" style="background: linear-gradient(45deg, #ff416c, #ff4b2b);">
                    <div class="card-body">
                        <h4 class="card-title">Plan Familiar</h4>
                        <p class="card-text">20% de descuento al inscribir 2 o más estudiantes de la misma familia.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-users me-2"></i> Válido para todos los paquetes</li>
                            <li><i class="fas fa-tag me-2"></i> Usa el código: FAMILIA20</li>
                        </ul>
                        <button class="btn btn-light mt-3">Aprovechar Oferta</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Paquetes y Promociones JavaScript -->
    <script>
        // Función para seleccionar un plan específico
        function seleccionarPlan(tipo, precio, nombre) {
            if (confirm(`¿Deseas suscribirte al ${nombre} por $${precio}/mes?`)) {
                // Redirigir a la página de pagos con los parámetros del plan
                window.location.href = `pagos.php?plan=${tipo}&precio=${precio}&nombre=${encodeURIComponent(nombre)}`;
            }
        }
        
        // Función para aplicar código promocional
        function aplicarPromocion(codigo) {
            alert(`Código promocional "${codigo}" aplicado. El descuento se verá reflejado en tu próxima factura.`);
        }
        
        // Agregar eventos a los botones de promociones
        document.querySelectorAll('.bg-gradient .btn-light').forEach(button => {
            button.addEventListener('click', function() {
                const promocion = this.closest('.card');
                const codigo = promocion.querySelector('.fa-tag').nextSibling.textContent.split(':')[1].trim();
                aplicarPromocion(codigo);
            });
        });
    </script>
</body>
</html>
