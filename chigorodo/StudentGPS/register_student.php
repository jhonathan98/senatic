<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=register_student");
    exit;
}
if ($_SESSION['user']['role'] !== 'parent' && $_SESSION['user']['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Acceso denegado.</div>";
    return;
}

require_once 'config.php';
$message = '';

if ($_POST) {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Token de seguridad inválido.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $doc = sanitizeInput($_POST['document_number']);
        $grade = sanitizeInput($_POST['grade']);
        $parent_id = $_SESSION['user']['id'];

        // Validaciones
        $errors = [];
        
        if (empty($name)) {
            $errors[] = "El nombre es obligatorio.";
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = "El nombre debe tener entre 2 y 100 caracteres.";
        }
        
        if (empty($doc)) {
            $errors[] = "El documento es obligatorio.";
        } elseif (!validateDocument($doc)) {
            $errors[] = "El documento debe ser válido (6-11 dígitos).";
        }
        
        if (empty($grade)) {
            $errors[] = "El grado es obligatorio.";
        } elseif (!array_key_exists($grade, getGrades())) {
            $errors[] = "Grado inválido.";
        }

        if (empty($errors)) {
            try {
                // Verificar si el documento ya existe
                $check_stmt = $pdo->prepare("SELECT id FROM students WHERE document_number = ?");
                $check_stmt->execute([$doc]);
                
                if ($check_stmt->fetch()) {
                    $message = "Error: Ya existe un estudiante con este documento.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (name, document_number, grade, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $doc, $grade, $parent_id]);
                    
                    // Log de actividad
                    logActivity($pdo, $parent_id, 'student_registered', "Registró estudiante: $name");
                    
                    $_SESSION['success'] = "✅ Estudiante registrado con éxito.";
                    redirect("dashboard.php?section=register_student");
                }
            } catch (PDOException $e) {
                error_log("Error registrando estudiante: " . $e->getMessage());
                $message = "Error interno. Por favor, inténtelo más tarde.";
            }
        } else {
            $message = "Errores encontrados:<br>• " . implode("<br>• ", $errors);
        }
    }
}
?>

<h2>Registrar Estudiante</h2>
<?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') ? 'danger' : 'success'; ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" class="card p-4">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div class="mb-3">
        <label class="form-label">Nombre completo del estudiante *</label>
        <input type="text" name="name" class="form-control" required 
               maxlength="100" placeholder="Ej: María José Pérez García"
               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        <div class="form-text">Ingrese el nombre completo del estudiante</div>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Documento de identidad *</label>
        <input type="text" name="document_number" class="form-control" required 
               pattern="[0-9]{6,11}" placeholder="Ej: 1234567890"
               value="<?= isset($_POST['document_number']) ? htmlspecialchars($_POST['document_number']) : '' ?>">
        <div class="form-text">Documento sin puntos ni espacios (6-11 dígitos)</div>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Grado escolar *</label>
        <select name="grade" class="form-select" required>
            <option value="">-- Selecciona el grado --</option>
            <?php 
            $selected_grade = $_POST['grade'] ?? '';
            foreach (getGrades() as $key => $value): 
            ?>
                <option value="<?= $key ?>" <?= $key === $selected_grade ? 'selected' : '' ?>>
                    <?= $value ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="reset" class="btn btn-outline-secondary me-md-2">
            <i class="bi bi-arrow-clockwise"></i> Limpiar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Registrar Estudiante
        </button>
    </div>
</form>

<!-- Información adicional -->
<div class="mt-4">
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Información importante:</strong>
        <ul class="mb-0 mt-2">
            <li>Todos los campos marcados con (*) son obligatorios</li>
            <li>El documento de identidad debe ser único por estudiante</li>
            <li>Una vez registrado, el estudiante aparecerá en su panel principal</li>
        </ul>
    </div>
</div>