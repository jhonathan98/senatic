<?php
/**
 * Funciones de utilidad para StudentGPS
 */

// Función para verificar si el usuario está autenticado
function requireAuth() {
    if (!isset($_SESSION['user'])) {
        redirect('index.php');
    }
}

// Función para verificar roles específicos
function requireRole($roles) {
    requireAuth();
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta página.';
        redirect('dashboard.php');
    }
}

// Función para formatear fechas
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Función para formatear fechas con tiempo
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

// Función para obtener el saludo según la hora
function getGreeting() {
    $hour = date('H');
    if ($hour < 12) {
        return 'Buenos días';
    } elseif ($hour < 18) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
}

// Función para obtener estudiantes por rol
function getStudentsByRole($pdo, $user) {
    if ($user['role'] === 'admin') {
        $stmt = $pdo->query("
            SELECT s.*, u.name as parent_name 
            FROM students s 
            JOIN users u ON s.parent_id = u.id 
            ORDER BY s.name
        ");
        return $stmt->fetchAll();
    } elseif ($user['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as parent_name 
            FROM students s 
            JOIN teacher_students ts ON s.id = ts.student_id 
            JOIN users u ON s.parent_id = u.id 
            WHERE ts.teacher_id = ? 
            ORDER BY s.name
        ");
        $stmt->execute([$user['id']]);
        return $stmt->fetchAll();
    } elseif ($user['role'] === 'parent') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as parent_name 
            FROM students s 
            JOIN users u ON s.parent_id = u.id 
            WHERE s.parent_id = ? 
            ORDER BY s.name
        ");
        $stmt->execute([$user['id']]);
        return $stmt->fetchAll();
    }
    return [];
}

// Función para obtener estadísticas del dashboard
function getDashboardStats($pdo, $user) {
    $stats = [];
    
    if ($user['role'] === 'admin') {
        // Estadísticas para admin
        $stats['students'] = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
        $stats['teachers'] = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")->fetch()['count'];
        $stats['parents'] = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent'")->fetch()['count'];
        $stats['attendance_today'] = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ?");
        $stats['attendance_today']->execute([date('Y-m-d')]);
        $stats['attendance_today'] = $stats['attendance_today']->fetch()['count'];
        
    } elseif ($user['role'] === 'teacher') {
        // Estadísticas para profesor
        $stats['my_students'] = $pdo->prepare("SELECT COUNT(*) as count FROM teacher_students WHERE teacher_id = ?");
        $stats['my_students']->execute([$user['id']]);
        $stats['my_students'] = $stats['my_students']->fetch()['count'];
        
        $stats['attendance_today'] = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM attendance a 
            JOIN teacher_students ts ON a.student_id = ts.student_id 
            WHERE a.date = ? AND ts.teacher_id = ?
        ");
        $stats['attendance_today']->execute([date('Y-m-d'), $user['id']]);
        $stats['attendance_today'] = $stats['attendance_today']->fetch()['count'];
        
        $stats['pending_attendance'] = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM teacher_students ts 
            WHERE ts.teacher_id = ? 
            AND ts.student_id NOT IN (
                SELECT student_id FROM attendance WHERE date = ?
            )
        ");
        $stats['pending_attendance']->execute([$user['id'], date('Y-m-d')]);
        $stats['pending_attendance'] = $stats['pending_attendance']->fetch()['count'];
        
    } elseif ($user['role'] === 'parent') {
        // Estadísticas para padre
        $stats['my_children'] = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE parent_id = ?");
        $stats['my_children']->execute([$user['id']]);
        $stats['my_children'] = $stats['my_children']->fetch()['count'];
        
        $stats['attendance_today'] = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM attendance a 
            JOIN students s ON a.student_id = s.id 
            WHERE a.date = ? AND s.parent_id = ? AND a.status = 'Asistió'
        ");
        $stats['attendance_today']->execute([date('Y-m-d'), $user['id']]);
        $stats['attendance_today'] = $stats['attendance_today']->fetch()['count'];
    }
    
    return $stats;
}

// Función para validar documento de identidad colombiano
function validateDocument($document) {
    // Remover espacios y caracteres especiales
    $document = preg_replace('/[^0-9]/', '', $document);
    
    // Debe tener entre 6 y 11 dígitos
    if (strlen($document) < 6 || strlen($document) > 11) {
        return false;
    }
    
    // Solo números
    return is_numeric($document);
}

// Función para generar contraseña temporal
function generateTempPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Función para crear log de actividades
function logActivity($pdo, $user_id, $action, $description = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Si no existe la tabla, no hacer nada (opcional)
        error_log("Error logging activity: " . $e->getMessage());
    }
}

// Función para obtener grados escolares
function getGrades() {
    return [
        'Preescolar' => 'Preescolar',
        '1°' => 'Primero',
        '2°' => 'Segundo', 
        '3°' => 'Tercero',
        '4°' => 'Cuarto',
        '5°' => 'Quinto',
        '6°' => 'Sexto',
        '7°' => 'Séptimo',
        '8°' => 'Octavo',
        '9°' => 'Noveno',
        '10°' => 'Décimo',
        '11°' => 'Once'
    ];
}

// Función para obtener estados de asistencia
function getAttendanceStatus() {
    return [
        'Asistió' => 'Asistió',
        'No asistió' => 'No asistió',
        'Tarde' => 'Llegó tarde',
        'Excusado' => 'Falta excusada'
    ];
}

// Función para mostrar alertas en formato Bootstrap
function showAlert($message, $type = 'info') {
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alertClass[$type] ?? 'alert-info';
    
    return "<div class='alert $class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>
