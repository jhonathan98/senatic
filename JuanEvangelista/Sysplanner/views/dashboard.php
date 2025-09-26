<?php
// views/dashboard.php
// Requiere autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Redirigir si no está logueado
    exit();
}

// Incluir conexión a base de datos
require_once '../config/database.php'; // Ruta relativa desde views/
// Aquí podrías incluir lógica para obtener datos del usuario o recursos

// Incluir encabezado común
include '../includes/header.php';
include '../includes/navbar.php'; // Incluir navbar
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>!</h2>
            <p>Panel de control de SysPlanner.</p>
            <?php if ($_SESSION['user_rol'] === 'admin'): ?>
                <h4>Panel de Administrador</h4>
                <a href="admin/manage_resources.php" class="btn btn-primary">Gestionar Recursos</a>
                <a href="admin/manage_users.php" class="btn btn-secondary">Gestionar Usuarios</a>
                <a href="admin/reports.php" class="btn btn-info">Ver Reportes</a>
            <?php else: ?>
                <h4>Panel de Usuario</h4>
                <a href="user/make_reservation.php" class="btn btn-success">Hacer una Reserva</a>
                <a href="user/view_schedule.php" class="btn btn-primary">Ver Horarios</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir pie de página común
include '../includes/footer.php';
?>