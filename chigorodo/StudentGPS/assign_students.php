<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=assign_students");
    exit;
}

// Solo admin puede asignar estudiantes
requireRole(['admin']);

require_once 'config.php';
require_once 'includes/functions.php';

$message = '';
$success = '';

// Procesar asignaciones
if ($_POST && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Token de seguridad inválido.";
    } else {
        $teacher_id = (int)$_POST['teacher_id'];
        $student_id = (int)$_POST['student_id'];
        
        if ($_POST['action'] === 'assign') {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO teacher_students (teacher_id, student_id) VALUES (?, ?)");
                $stmt->execute([$teacher_id, $student_id]);
                
                if ($stmt->rowCount() > 0) {
                    logActivity($pdo, $_SESSION['user']['id'], 'student_assigned', "Asignó estudiante ID $student_id al profesor ID $teacher_id");
                    $success = "Estudiante asignado correctamente.";
                } else {
                    $message = "El estudiante ya está asignado a este profesor.";
                }
            } catch (PDOException $e) {
                error_log("Error asignando estudiante: " . $e->getMessage());
                $message = "Error al asignar estudiante.";
            }
        } elseif ($_POST['action'] === 'unassign') {
            try {
                $stmt = $pdo->prepare("DELETE FROM teacher_students WHERE teacher_id = ? AND student_id = ?");
                $stmt->execute([$teacher_id, $student_id]);
                
                if ($stmt->rowCount() > 0) {
                    logActivity($pdo, $_SESSION['user']['id'], 'student_unassigned', "Desasignó estudiante ID $student_id del profesor ID $teacher_id");
                    $success = "Estudiante desasignado correctamente.";
                } else {
                    $message = "El estudiante no estaba asignado a este profesor.";
                }
            } catch (PDOException $e) {
                error_log("Error desasignando estudiante: " . $e->getMessage());
                $message = "Error al desasignar estudiante.";
            }
        }
    }
}

// Obtener profesores
$teachers = $pdo->query("
    SELECT u.id, u.name, u.username, COUNT(ts.student_id) as student_count
    FROM users u
    LEFT JOIN teacher_students ts ON u.id = ts.teacher_id
    WHERE u.role = 'teacher'
    GROUP BY u.id, u.name, u.username
    ORDER BY u.name
")->fetchAll();

// Obtener estudiantes
$students = $pdo->query("
    SELECT s.id, s.name, s.grade, s.document_number, u.name as parent_name
    FROM students s
    JOIN users u ON s.parent_id = u.id
    ORDER BY s.grade, s.name
")->fetchAll();

// Obtener asignaciones actuales
$assignments = $pdo->query("
    SELECT ts.teacher_id, ts.student_id, t.name as teacher_name, s.name as student_name, s.grade
    FROM teacher_students ts
    JOIN users t ON ts.teacher_id = t.id
    JOIN students s ON ts.student_id = s.id
    ORDER BY t.name, s.grade, s.name
")->fetchAll();

// Agrupar asignaciones por profesor
$teacher_assignments = [];
foreach ($assignments as $assignment) {
    $teacher_assignments[$assignment['teacher_id']][] = $assignment;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people-fill text-primary"></i> Asignar Estudiantes a Profesores</h2>
</div>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= $message ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="row">
    <!-- Formulario de asignación -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Asignación</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="assign">
                    
                    <div class="mb-3">
                        <label class="form-label">Profesor:</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Seleccionar profesor --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>">
                                    <?= htmlspecialchars($teacher['name']) ?> 
                                    (<?= $teacher['student_count'] ?> estudiantes)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estudiante:</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Seleccionar estudiante --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['name']) ?> - <?= $student['grade'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus"></i> Asignar Estudiante
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Lista de asignaciones actuales -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Asignaciones Actuales</h5>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">No hay asignaciones</h5>
                        <p>Comienza asignando estudiantes a profesores.</p>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="teacherAccordion">
                        <?php foreach ($teachers as $teacher): ?>
                            <?php if (isset($teacher_assignments[$teacher['id']])): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $teacher['id'] ?>">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?= $teacher['id'] ?>" 
                                            aria-expanded="false" 
                                            aria-controls="collapse<?= $teacher['id'] ?>">
                                        <strong><?= htmlspecialchars($teacher['name']) ?></strong>
                                        <span class="badge bg-primary ms-2">
                                            <?= count($teacher_assignments[$teacher['id']]) ?> estudiantes
                                        </span>
                                    </button>
                                </h2>
                                <div id="collapse<?= $teacher['id'] ?>" 
                                     class="accordion-collapse collapse" 
                                     aria-labelledby="heading<?= $teacher['id'] ?>" 
                                     data-bs-parent="#teacherAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Estudiante</th>
                                                        <th>Grado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($teacher_assignments[$teacher['id']] as $assignment): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($assignment['student_name']) ?></td>
                                                        <td><span class="badge bg-secondary"><?= $assignment['grade'] ?></span></td>
                                                        <td>
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('¿Desasignar este estudiante?')">
                                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                                <input type="hidden" name="action" value="unassign">
                                                                <input type="hidden" name="teacher_id" value="<?= $assignment['teacher_id'] ?>">
                                                                <input type="hidden" name="student_id" value="<?= $assignment['student_id'] ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                    <i class="bi bi-x-circle"></i> Desasignar
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4><?= count($teachers) ?></h4>
                <small>Profesores</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4><?= count($students) ?></h4>
                <small>Estudiantes</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4><?= count($assignments) ?></h4>
                <small>Asignaciones</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <?php
                $unassigned = count($students) - count(array_unique(array_column($assignments, 'student_id')));
                ?>
                <h4><?= $unassigned ?></h4>
                <small>Sin asignar</small>
            </div>
        </div>
    </div>
</div>
