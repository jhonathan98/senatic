<?php
require_once 'config.php';
$parent_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM students WHERE parent_id = ?");
$stmt->execute([$parent_id]);
$students = $stmt->fetchAll();
?>

<h2>Mis Estudiantes</h2>
<?php if (empty($students)): ?>
    <div class="alert alert-info">No tienes estudiantes registrados.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($students as $s): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($s['name']) ?></h5>
                        <p><strong>Grado:</strong> <?= $s['grade'] ?></p>
                        <p><strong>Documento:</strong> <?= $s['document_number'] ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>