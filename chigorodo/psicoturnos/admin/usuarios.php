<?php
require '../includes/auth.php';
requireLogin();
checkRole('admin');
include '../includes/db.php';

// Mensajes
$mensaje = '';

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($id != $_SESSION['user_id']) { // No puede eliminarse a sí mismo
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = '<div class="alert alert-success">Usuario eliminado correctamente.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">No puedes eliminarte a ti mismo.</div>';
    }
}

// Listar usuarios
$stmt = $pdo->query("SELECT id, nombre, email, rol, grado, edad FROM usuarios ORDER BY rol, nombre");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand">Panel de Administración</span>
            <a href="../logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Gestión de Usuarios</h2>
        <?= $mensaje ?>

        <a href="#nuevoModal" class="btn btn-success mb-3" data-bs-toggle="modal">+ Nuevo Usuario</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Grado/Edad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge bg-<?= 
                        $u['rol'] === 'estudiante' ? 'info' : 
                        ($u['rol'] === 'psicologo' ? 'warning' : 'secondary') 
                    ?>">
                        <?= ucfirst($u['rol']) ?>
                    </span></td>
                    <td><?= $u['grado'] ?: $u['edad'] ?></td>
                    <td>
                        <a href="?eliminar=<?= $u['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('¿Eliminar a <?= addslashes($u['nombre']) ?>?')">
                           Eliminar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Nuevo Usuario -->
    <div class="modal fade" id="nuevoModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email (@colegio.edu)</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Rol</label>
                        <select name="rol" class="form-control" id="rolSelect">
                            <option value="estudiante">Estudiante</option>
                            <option value="psicologo">Psicólogo</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3" id="gradoField">
                        <label>Grado</label>
                        <input type="text" name="grado" class="form-control">
                    </div>
                    <div class="mb-3" id="edadField">
                        <label>Edad</label>
                        <input type="number" name="edad" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rolSelect = document.getElementById('rolSelect');
        const gradoField = document.getElementById('gradoField');
        const edadField = document.getElementById('edadField');

        rolSelect.addEventListener('change', () => {
            if (rolSelect.value === 'estudiante') {
                gradoField.style.display = 'block';
                edadField.style.display = 'block';
            } else {
                gradoField.style.display = rolSelect.value === 'estudiante' ? 'block' : 'none';
                edadField.style.display = 'block';
            }
        });
        rolSelect.dispatchEvent(new Event('change'));
    </script>
</body>
</html>

<?php
// Procesar nuevo usuario
if ($_POST && isset($_POST['nombre'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $grado = $_POST['grado'] ?? null;
    $edad = $_POST['edad'] ?? null;

    if (!str_ends_with($email, '@colegio.edu')) {
        $mensaje = '<div class="alert alert-danger">Solo correos institucionales (@colegio.edu).</div>';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $mensaje = '<div class="alert alert-warning">El correo ya existe.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, grado, edad) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $password, $rol, $grado, $edad])) {
                echo "<script>location.href='usuarios.php?msg=creado';</script>";
            } else {
                echo "<div class='alert alert-danger'>Error al crear.</div>";
            }
        }
    }
}
?>