<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../includes/db.php';
require_once '../../../includes/auth.php';

// Verificar que sea administrador
verificarRol(['administrador']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Student News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../assets/css/styles.css">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../../index.php">
                STUDENT NEWS<br>
                <small class="text-light" style="font-size: 0.8rem;">Panel de Administración</small>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nombre_completo']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="../../../index.php">Ver Sitio</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../procesos/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php
        // Mostrar mensajes de éxito/error
        if (isset($_SESSION['mensaje'])) {
            $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
            echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION['mensaje'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        }
        ?>

        <h2 class="mb-4">Panel de Administración</h2>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <?php
            $pdo = getDB();
            
            // Contar usuarios
            $total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
            
            // Contar artículos
            $total_articulos = $pdo->query("SELECT COUNT(*) as total FROM articulos")->fetch()['total'];
            
            // Contar artículos pendientes
            $articulos_pendientes = $pdo->query("SELECT COUNT(*) as total FROM articulos WHERE estado = 'pendiente'")->fetch()['total'];
            
            // Contar eventos
            $total_eventos = $pdo->query("SELECT COUNT(*) as total FROM eventos")->fetch()['total'];
            ?>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $total_usuarios ?></h4>
                                <p class="mb-0">Usuarios</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $total_articulos ?></h4>
                                <p class="mb-0">Artículos</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-newspaper fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $articulos_pendientes ?></h4>
                                <p class="mb-0">Pendientes</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $total_eventos ?></h4>
                                <p class="mb-0">Eventos</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-calendar-event fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Artículos Pendientes de Revisión</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT a.id_articulo, a.titulo, a.fecha_creacion, u.nombre_completo, c.nombre_categoria
                            FROM articulos a
                            JOIN usuarios u ON a.id_autor = u.id_usuario
                            JOIN categorias c ON a.id_categoria = c.id_categoria
                            WHERE a.estado = 'pendiente'
                            ORDER BY a.fecha_creacion ASC
                            LIMIT 5
                        ");
                        $stmt->execute();
                        $articulos_pendientes_lista = $stmt->fetchAll();
                        
                        if ($articulos_pendientes_lista): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Categoría</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($articulos_pendientes_lista as $articulo): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($articulo['titulo'], 0, 50)) ?>...</td>
                                            <td><?= htmlspecialchars($articulo['nombre_completo']) ?></td>
                                            <td><?= htmlspecialchars($articulo['nombre_categoria']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($articulo['fecha_creacion'])) ?></td>
                                            <td>
                                                <a href="revisar_articulo.php?id=<?= $articulo['id_articulo'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Revisar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No hay artículos pendientes de revisión.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../articulo_form.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Crear Artículo
                            </a>
                            <a href="gestionar_usuarios.php" class="btn btn-outline-secondary">
                                <i class="bi bi-people"></i> Gestionar Usuarios
                            </a>
                            <a href="gestionar_categorias.php" class="btn btn-outline-secondary">
                                <i class="bi bi-tags"></i> Gestionar Categorías
                            </a>
                            <a href="gestionar_eventos.php" class="btn btn-outline-secondary">
                                <i class="bi bi-calendar-event"></i> Gestionar Eventos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
