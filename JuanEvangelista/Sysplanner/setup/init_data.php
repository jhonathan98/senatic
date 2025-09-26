<?php
// setup/init_data.php
// Script para insertar datos iniciales en la base de datos
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Iniciando inserción de datos iniciales...\n";
    
    // Insertar departamentos
    $departments = [
        'Matemáticas',
        'Ciencias',
        'Sistemas',
        'Administración',
        'Inglés',
        'Educación Física'
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO departamentos (nombre) VALUES (?)");
    foreach ($departments as $dept) {
        $stmt->execute([$dept]);
        echo "Departamento insertado: $dept\n";
    }
    
    // Insertar usuario administrador por defecto
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $user_password = password_hash('user123', PASSWORD_DEFAULT);
    
    $users = [
        ['Admin SysPlanner', 'admin@sysplanner.local', $admin_password, 'admin', null],
        ['Juan Pérez', 'juan@institucion.edu', $user_password, 'usuario', 1],
        ['María González', 'maria@institucion.edu', $user_password, 'usuario', 2],
        ['Carlos López', 'carlos@institucion.edu', $user_password, 'usuario', 3],
        ['Ana Martínez', 'ana@institucion.edu', $user_password, 'usuario', 4]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO usuarios (nombre_completo, email, password_hash, rol, departamento_id) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute($user);
        echo "Usuario insertado: {$user[1]}\n";
    }
    
    // Insertar recursos
    $resources = [
        ['Aula 101', 'Aula estándar con pizarra y proyector', 'Aula', 30, 'Pizarra, proyector, aire acondicionado', 1],
        ['Aula 102', 'Aula con pizarra inteligente', 'Aula', 25, 'Pizarra inteligente, sonido', 1],
        ['Laboratorio Química', 'Laboratorio con mesones y fregadero', 'Laboratorio', 20, 'Mesones, campanas extractoras, fregaderos', 2],
        ['Laboratorio Física', 'Laboratorio de experimentos', 'Laboratorio', 25, 'Equipos de medición, mesones', 2],
        ['Sala de Sistemas 1', 'Computadoras con software específico', 'Sala de Sistemas', 20, '20 computadoras, software actualizado', 3],
        ['Sala de Sistemas 2', 'Laboratorio de programación', 'Sala de Sistemas', 25, '25 computadoras, IDEs de desarrollo', 3],
        ['Auditorio Principal', 'Auditorio para eventos grandes', 'Auditorio', 200, 'Sistema de sonido, proyector, escenario', null],
        ['Sala de Reuniones A', 'Sala pequeña para reuniones', 'Sala de Reuniones', 8, 'Mesa redonda, TV, aire acondicionado', 4],
        ['Sala de Reuniones B', 'Sala mediana para juntas', 'Sala de Reuniones', 12, 'Mesa de juntas, proyector, videoconferencia', 4],
        ['Cancha de Fútbol', 'Cancha sintética', 'Instalación Deportiva', 22, 'Césped sintético, porterías', 6],
        ['Gimnasio', 'Gimnasio cubierto', 'Instalación Deportiva', 50, 'Cancha múltiple, equipos deportivos', 6],
        ['Aula de Inglés 1', 'Aula especializada para idiomas', 'Aula', 20, 'Equipo de audio, material didáctico', 5],
        ['Proyector Móvil 1', 'Proyector portátil', 'Equipo', null, 'Full HD, cables incluidos', null],
        ['Proyector Móvil 2', 'Proyector con sonido', 'Equipo', null, 'HD, altavoces integrados', null],
        ['Cámara Fotográfica', 'Cámara digital profesional', 'Equipo', null, 'DSLR, lentes intercambiables', null]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO recursos (nombre, descripcion, tipo, capacidad, caracteristicas, departamento_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($resources as $resource) {
        $stmt->execute($resource);
        echo "Recurso insertado: {$resource[0]}\n";
    }
    
    // Insertar algunas reservas de ejemplo para la próxima semana
    $next_week = date('Y-m-d', strtotime('+7 days'));
    $sample_reservations = [
        [1, 2, $next_week . ' 08:00:00', $next_week . ' 10:00:00', 'Clase de Álgebra Lineal', 'confirmada'],
        [1, 3, $next_week . ' 10:30:00', $next_week . ' 12:00:00', 'Tutoría de Matemáticas', 'confirmada'],
        [3, 3, $next_week . ' 14:00:00', $next_week . ' 16:00:00', 'Práctica de Laboratorio', 'confirmada'],
        [5, 4, $next_week . ' 09:00:00', $next_week . ' 11:00:00', 'Desarrollo de Software', 'confirmada'],
        [7, 2, $next_week . ' 16:00:00', $next_week . ' 18:00:00', 'Conferencia Departamental', 'pendiente']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO reservas (recurso_id, usuario_id, fecha_inicio, fecha_fin, motivo, estado) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($sample_reservations as $reservation) {
        $stmt->execute($reservation);
        echo "Reserva de ejemplo insertada\n";
    }
    
    echo "\n¡Datos iniciales insertados exitosamente!\n";
    echo "\nCredenciales de acceso:\n";
    echo "Administrador: admin@sysplanner.local / admin123\n";
    echo "Usuario: juan@institucion.edu / user123\n";
    echo "Usuario: maria@institucion.edu / user123\n";
    echo "Usuario: carlos@institucion.edu / user123\n";
    echo "Usuario: ana@institucion.edu / user123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
