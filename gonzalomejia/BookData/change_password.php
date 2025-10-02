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

// Process password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Todos los campos son obligatorios.";
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
            
            // Clear form data on success
            $_POST = array();
        } else {
            $error_message = "Error al cambiar la contraseña.";
        }
    }
}

$page_title = 'Cambiar Contraseña';

// Include header
include 'includes/header.php';
?>

<!-- Estilos específicos para la página de cambio de contraseña -->
<style>
    body {
        padding-top: 80px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .password-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 2rem 0;
    }
    
    .password-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        overflow: hidden;
        background: white;
    }
    
    .password-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .password-header i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .password-body {
        padding: 2.5rem;
    }
    
    .form-group {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 1rem 1rem 1rem 3rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        background-color: white;
        transform: translateY(-2px);
    }
    
    .form-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        z-index: 10;
    }
    
    .toggle-password {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        cursor: pointer;
        z-index: 10;
        transition: color 0.3s ease;
    }
    
    .toggle-password:hover {
        color: #667eea;
    }
    
    .btn-change-password {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 1rem 2rem;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .btn-change-password:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 0.5rem;
        background-color: #e9ecef;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
        border-radius: 3px;
    }
    
    .strength-weak { background: linear-gradient(90deg, #dc3545, #fd7e14); }
    .strength-medium { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .strength-strong { background: linear-gradient(90deg, #28a745, #20c997); }
    
    .password-requirements {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .requirement {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
        color: #6c757d;
        transition: color 0.3s ease;
    }
    
    .requirement.valid {
        color: #28a745;
    }
    
    .requirement i {
        margin-right: 0.5rem;
        width: 16px;
    }
    
    .alert-custom {
        border: none;
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
    }
    
    .back-link {
        text-align: center;
        margin-top: 2rem;
    }
    
    .back-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .back-link a:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        .password-container {
            padding: 1rem;
        }
        
        .password-header {
            padding: 1.5rem;
        }
        
        .password-body {
            padding: 1.5rem;
        }
    }
</style>

<div class="container">
    <div class="password-container">
        <div class="card password-card">
            <div class="password-header">
                <i class="fas fa-key"></i>
                <h2 class="mb-0">Cambiar Contraseña</h2>
                <p class="mb-0 opacity-75">Actualiza tu contraseña para mantener tu cuenta segura</p>
            </div>
            
            <div class="password-body">
                <!-- Alert Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="changePasswordForm">
                    <!-- Current Password -->
                    <div class="form-group">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" placeholder="Contraseña actual" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                    </div>
                    
                    <!-- New Password -->
                    <div class="form-group">
                        <i class="fas fa-key form-icon"></i>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" placeholder="Nueva contraseña" required minlength="6">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <i class="fas fa-check-circle form-icon"></i>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" placeholder="Confirmar nueva contraseña" required minlength="6">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h6 class="mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Requisitos de la contraseña
                        </h6>
                        <div class="requirement" id="req-length">
                            <i class="fas fa-times-circle"></i>
                            <span>Al menos 6 caracteres</span>
                        </div>
                        <div class="requirement" id="req-uppercase">
                            <i class="fas fa-times-circle"></i>
                            <span>Una letra mayúscula</span>
                        </div>
                        <div class="requirement" id="req-lowercase">
                            <i class="fas fa-times-circle"></i>
                            <span>Una letra minúscula</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="fas fa-times-circle"></i>
                            <span>Un número</span>
                        </div>
                        <div class="requirement" id="req-match">
                            <i class="fas fa-times-circle"></i>
                            <span>Las contraseñas coinciden</span>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-change-password" id="submitBtn">
                        <i class="fas fa-shield-alt me-2"></i>Cambiar Contraseña
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="profile.php">
                        <i class="fas fa-arrow-left me-1"></i>Volver al perfil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strengthBar');
    const submitBtn = document.getElementById('submitBtn');
    
    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        const requirements = {
            length: password.length >= 6,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };
        
        // Update requirement indicators
        updateRequirement('req-length', requirements.length);
        updateRequirement('req-uppercase', requirements.uppercase);
        updateRequirement('req-lowercase', requirements.lowercase);
        updateRequirement('req-number', requirements.number);
        
        // Calculate strength
        Object.values(requirements).forEach(req => {
            if (req) strength++;
        });
        
        // Update strength bar
        const percentage = (strength / 4) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (strength <= 1) {
            strengthBar.className = 'password-strength-bar strength-weak';
        } else if (strength <= 3) {
            strengthBar.className = 'password-strength-bar strength-medium';
        } else {
            strengthBar.className = 'password-strength-bar strength-strong';
        }
        
        return strength >= 3; // Require at least 3 criteria
    }
    
    function updateRequirement(id, isValid) {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');
        
        if (isValid) {
            element.classList.add('valid');
            icon.className = 'fas fa-check-circle';
        } else {
            element.classList.remove('valid');
            icon.className = 'fas fa-times-circle';
        }
    }
    
    function checkPasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const match = newPassword === confirmPassword && newPassword.length > 0;
        
        updateRequirement('req-match', match);
        return match;
    }
    
    function validateForm() {
        const isStrong = checkPasswordStrength(newPasswordInput.value);
        const isMatch = checkPasswordMatch();
        const isValid = isStrong && isMatch;
        
        submitBtn.disabled = !isValid;
        submitBtn.style.opacity = isValid ? '1' : '0.6';
        
        return isValid;
    }
    
    newPasswordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
        validateForm();
    });
    
    confirmPasswordInput.addEventListener('input', function() {
        checkPasswordMatch();
        validateForm();
    });
    
    // Form validation on submit
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            alert('Por favor, asegúrate de que la contraseña cumple todos los requisitos.');
        }
    });
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Initial validation
    validateForm();
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash toggle-password';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye toggle-password';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
