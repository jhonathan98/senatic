<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=register_teacher");
    exit;
}

// Solo admin puede registrar profesores
requireRole(['admin']);

require_once 'config.php';
require_once 'includes/functions.php';
$message = '';

if ($_POST) {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Token de seguridad inválido.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $username = sanitizeInput($_POST['username']);
        $doc = sanitizeInput($_POST['document_number']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        
        // Generar contraseña temporal
        $temp_password = generateTempPassword();
        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

        // Validaciones
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "El nombre es obligatorio y debe tener al menos 2 caracteres.";
        }
        
        if (empty($username) || strlen($username) < 4) {
            $errors[] = "El usuario es obligatorio y debe tener al menos 4 caracteres.";
        }
        
        if (!validateDocument($doc)) {
            $errors[] = "El documento debe ser válido (6-11 dígitos).";
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El email no es válido.";
        }

        if (empty($errors)) {
            try {
                // Verificar si ya existe
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR document_number = ?");
                $check_stmt->execute([$username, $doc]);
                
                if ($check_stmt->fetch()) {
                    $message = "Error: Ya existe un usuario con este documento o nombre de usuario.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, username, password, role, document_number, email, phone, created_at) 
                        VALUES (?, ?, ?, 'teacher', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $username, $password_hash, $doc, $email ?: null, $phone ?: null]);
                    
                    // Log de actividad
                    logActivity($pdo, $_SESSION['user']['id'], 'teacher_registered', "Registró profesor: $name");
                    
                    $_SESSION['success'] = "✅ Profesor registrado con éxito.<br><strong>Usuario:</strong> $username<br><strong>Contraseña temporal:</strong> <code>$temp_password</code><br><small>El profesor debe cambiar su contraseña en el primer acceso.</small>";
                    redirect("dashboard.php?section=register_teacher");
                }
            } catch (PDOException $e) {
                error_log("Error registrando profesor: " . $e->getMessage());
                $message = "Error interno. Por favor, inténtelo más tarde.";
            }
        } else {
            $message = "Errores encontrados:<br>• " . implode("<br>• ", $errors);
        }
    }
}
?>

<h2><i class="bi bi-person-workspace text-success"></i> Registrar Profesor</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" class="card p-4">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Nombre completo *</label>
                <input type="text" name="name" class="form-control" required 
                       maxlength="100" placeholder="Ej: Ana María García López"
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Nombre de usuario *</label>
                <input type="text" name="username" class="form-control" required 
                       maxlength="50" placeholder="Ej: ana.garcia"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                <div class="form-text">Será usado para iniciar sesión</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Documento de identidad *</label>
                <input type="text" name="document_number" class="form-control" required 
                       pattern="[0-9]{6,11}" placeholder="Ej: 1234567890"
                       value="<?= isset($_POST['document_number']) ? htmlspecialchars($_POST['document_number']) : '' ?>">
                <div class="form-text">Sin puntos ni espacios (6-11 dígitos)</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Correo electrónico (opcional)</label>
                <input type="email" name="email" class="form-control" 
                       placeholder="Ej: ana.garcia@colegio.edu.co"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Teléfono (opcional)</label>
                <input type="tel" name="phone" class="form-control" 
                       placeholder="Ej: 300 123 4567"
                       value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
            </div>
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="reset" class="btn btn-outline-secondary me-md-2">
            <i class="bi bi-arrow-clockwise"></i> Limpiar
        </button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Registrar Profesor
        </button>
    </div>
</form>

<!-- Información adicional -->
<div class="mt-4">
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Información importante:</strong>
        <ul class="mb-0 mt-2">
            <li>Se generará una contraseña temporal automáticamente</li>
            <li>El profesor debe cambiar su contraseña en el primer acceso</li>
            <li>Puede asignar estudiantes al profesor después del registro</li>
            <li>Los campos marcados con (*) son obligatorios</li>
        </ul>
    </div>
</div>