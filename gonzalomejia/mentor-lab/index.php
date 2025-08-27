<?php
// index.php - Página de inicio

// Verificar si hay una sesión activa
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Mentorías</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .hero-section {
            background: linear-gradient(rgba(138, 43, 226, 0.8), rgba(138, 43, 226, 0.8)), url('https://via.placeholder.com/1920x1080') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        .btn-get-started {
            background-color: #fff;
            color: #8a2be2;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-get-started:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }
        .features-section {
            padding: 80px 0;
        }
        .feature-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 40px;
            color: #8a2be2;
            margin-bottom: 20px;
        }
        .cta-section {
            background-color: #8a2be2;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .cta-button {
            background-color: white;
            color: #8a2be2;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-purple">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistema de Mentorías</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Paquetes y promociones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Filtros</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registro.php">Crear cuenta</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="display-4 mb-4">Conecta con Expertos para Tu Desarrollo Académico</h1>
            <p class="lead mb-5">Encuentra mentores especializados en diversas áreas para mejorar tus habilidades y alcanzar tus metas educativas.</p>
            <a href="registro.php" class="btn btn-get-started">Comenzar Ahora</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="text-center mb-5">Cómo Funciona</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4>Encuentra tu Mentor</h4>
                        <p>Busca mentores especializados en las áreas que necesitas, filtrando por categoría, nivel educativo y experiencia.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Solicita Mentoría</h4>
                        <p>Selecciona el mentor que más te convenga y solicita la mentoría. El mentor se contactará contigo para coordinar.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4>Aprende y Crece</h4>
                        <p>Recibe apoyo personalizado para superar desafíos académicos y desarrollar habilidades clave para tu futuro.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-5 mb-4">¿Listo para mejorar tus habilidades?</h2>
            <p class="lead mb-5">Únete a miles de estudiantes que ya están aprovechando nuestra plataforma para su desarrollo académico.</p>
            <a href="registro.php" class="btn cta-button">Crear Cuenta Gratuitamente</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Mentorías</h5>
                    <p>Conectamos estudiantes con mentores expertos para mejorar sus habilidades académicas y personales.</p>
                </div>
                <div class="col-md-6">
                    <h5>Contacto</h5>
                    <p>Email: contacto@mentorias.com</p>
                    <p>Teléfono: +52 55 1234 5678</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; 2023 Sistema de Mentorías. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>