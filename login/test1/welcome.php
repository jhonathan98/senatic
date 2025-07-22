<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="logo.png" alt="Logo" width="40" height="40" class="me-2">
                <span class="fw-bold">MiApp</span>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-2"><?= htmlspecialchars($usuario) ?></span>
                <i class="bi bi-person-circle fs-4"></i>
                <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container text-center mt-5">
        <img src="logo_central.png" alt="Logo Central" width="120" class="mb-4">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <form>
                    <div class="mb-3">
                        <label for="materia" class="form-label">Selecciona la materia</label>
                        <select class="form-select" id="materia" name="materia" required>
                            <option value="">Elige una opción</option>
                            <option value="idiomas">Idiomas</option>
                            <option value="matematicas">Matemáticas</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grado" class="form-label">Selecciona el grado escolar</label>
                        <select class="form-select" id="grado" name="grado" required>
                            <option value="">Elige un grado</option>
                            <option value="primero">Primero</option>
                            <option value="segundo">Segundo</option>
                            <option value="tercero">Tercero</option>
                            <!-- Agrega más grados si lo necesitas -->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Generar preguntas</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>