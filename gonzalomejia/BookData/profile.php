<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);
if (!$user) {
    header("Location: logout.php");
    exit();
}

$success_message = '';
$error_message = '';

// Process profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    
    // Validate inputs
    if (empty($full_name) || empty($username) || empty($email)) {
        $error_message = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Por favor, ingrese un email válido.";
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error_message = "El nombre de usuario o email ya están en uso.";
        } else {
            // Update user profile
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$full_name, $username, $email, $_SESSION['user_id']])) {
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $user = get_user_by_id($_SESSION['user_id']); // Refresh user data
                $success_message = "Perfil actualizado exitosamente.";
            } else {
                $error_message = "Error al actualizar el perfil.";
            }
        }
    }
}

// Process password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Todos los campos de contraseña son obligatorios.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!verify_password($current_password, $user['password'])) {
        $error_message = "La contraseña actual es incorrecta.";
    } else {
        // Update password
        $new_password_hash = hash_password($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$new_password_hash, $_SESSION['user_id']])) {
            $success_message = "Contraseña cambiada exitosamente.";
        } else {
            $error_message = "Error al cambiar la contraseña.";
        }
    }
}

// Get user statistics
$borrowed_books_count = 0;
$returned_books_count = 0;
$overdue_books_count = 0;

$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM borrowed_books WHERE user_id = ? GROUP BY status");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetchAll();

foreach ($stats as $stat) {
    switch ($stat['status']) {
        case 'active':
            $borrowed_books_count = $stat['count'];
            break;
        case 'returned':
            $returned_books_count = $stat['count'];
            break;
        case 'overdue':
            $overdue_books_count = $stat['count'];
            break;
    }
}

$page_title = 'Mi Perfil';

// Include header
include 'includes/header.php';
?>

<!-- Estilos específicos para la página de perfil -->
<style>
        body {
            padding-top: 80px; /* Espacio para el navbar fijo */
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .nav-pills .nav-link {
            border-radius: 25px;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                text-align: center;
            }
            
            .nav-pills {
                justify-content: center;
            }
        }
</style>

<div class="container mt-4">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <div class="profile-avatar">
                    <?php if ($user['profile_image']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             alt="Foto de perfil" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-9">
                <h2 class="mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="mb-1"><i class="fas fa-user-tag me-2"></i><?php echo ucfirst($user['role']); ?></p>
                <p class="mb-0"><i class="fas fa-calendar me-2"></i>Miembro desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h4 class="text-primary"><?php echo $borrowed_books_count; ?></h4>
                    <p class="text-muted mb-0">Libros Prestados</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <div class="stats-icon bg-success text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="text-success"><?php echo $returned_books_count; ?></h4>
                    <p class="text-muted mb-0">Libros Devueltos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <div class="stats-icon bg-danger text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="text-danger"><?php echo $overdue_books_count; ?></h4>
                    <p class="text-muted mb-0">Libros Vencidos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <div class="stats-icon bg-info text-white">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4 class="text-info"><?php echo $borrowed_books_count + $returned_books_count; ?></h4>
                    <p class="text-muted mb-0">Total Préstamos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills justify-content-center mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                <i class="fas fa-user me-2"></i>Información Personal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                <i class="fas fa-lock me-2"></i>Seguridad
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity" type="button" role="tab">
                <i class="fas fa-history me-2"></i>Actividad Reciente
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="profileTabsContent">
        <!-- Profile Information Tab -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card form-card">
                        <div class="card-header bg-transparent border-0 pt-4">
                            <h5 class="mb-0 text-center">
                                <i class="fas fa-user-edit me-2"></i>Editar Información Personal
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nombre Completo
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>Nombre de Usuario
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-gradient">
                                        <i class="fas fa-save me-2"></i>Actualizar Perfil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card form-card">
                        <div class="card-header bg-transparent border-0 pt-4">
                            <h5 class="mb-0 text-center">
                                <i class="fas fa-key me-2"></i>Cambiar Contraseña
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Contraseña Actual
                                    </label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="fas fa-key me-1"></i>Nueva Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required minlength="6">
                                    <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-check-circle me-1"></i>Confirmar Nueva Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required minlength="6">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-gradient">
                                        <i class="fas fa-shield-alt me-2"></i>Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div class="tab-pane fade" id="activity" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card form-card">
                        <div class="card-header bg-transparent border-0 pt-4">
                            <h5 class="mb-0 text-center">
                                <i class="fas fa-history me-2"></i>Actividad Reciente
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get recent borrowing activity
                            $stmt = $pdo->prepare("
                                SELECT bb.*, b.title, b.author 
                                FROM borrowed_books bb 
                                JOIN books b ON bb.book_id = b.id 
                                WHERE bb.user_id = ? 
                                ORDER BY bb.created_at DESC 
                                LIMIT 10
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            $activities = $stmt->fetchAll();
                            ?>

                            <?php if (empty($activities)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No hay actividad reciente</h6>
                                    <p class="text-muted">Comienza a explorar nuestra biblioteca para ver tu actividad aquí.</p>
                                    <a href="dashboard.php" class="btn btn-gradient">
                                        <i class="fas fa-search me-2"></i>Explorar Libros
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                    <p class="mb-1 text-muted">por <?php echo htmlspecialchars($activity['author']); ?></p>
                                                    <small class="text-muted">
                                                        Prestado el <?php echo date('d/m/Y', strtotime($activity['borrow_date'])); ?>
                                                        <?php if ($activity['return_date']): ?>
                                                            • Devuelto el <?php echo date('d/m/Y', strtotime($activity['return_date'])); ?>
                                                        <?php else: ?>
                                                            • Vence el <?php echo date('d/m/Y', strtotime($activity['due_date'])); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="flex-shrink-0 ms-3">
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    $status_icon = '';
                                                    
                                                    switch ($activity['status']) {
                                                        case 'active':
                                                            $status_class = 'bg-primary';
                                                            $status_text = 'Activo';
                                                            $status_icon = 'fas fa-book-open';
                                                            break;
                                                        case 'returned':
                                                            $status_class = 'bg-success';
                                                            $status_text = 'Devuelto';
                                                            $status_icon = 'fas fa-check-circle';
                                                            break;
                                                        case 'overdue':
                                                            $status_class = 'bg-danger';
                                                            $status_text = 'Vencido';
                                                            $status_icon = 'fas fa-exclamation-triangle';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> rounded-pill">
                                                        <i class="<?php echo $status_icon; ?> me-1"></i><?php echo $status_text; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="my_borrows.php" class="btn btn-outline-primary rounded-pill">
                                        <i class="fas fa-list me-2"></i>Ver Todos los Préstamos
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    if (newPassword && confirmPassword) {
        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    }
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
