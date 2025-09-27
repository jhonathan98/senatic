<?php
// includes/functions.php
// Funciones auxiliares para el sistema OSE

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para sanitizar datos
function sanitizeData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para obtener datos del usuario
function getUserById($pdo, $userId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

// Función para obtener grados disponibles
function getGrados($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM grados ORDER BY id ASC');
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para obtener materias por grado
function getMateriasByGrado($pdo, $gradoId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM materias WHERE grado_id = ? ORDER BY nombre_materia ASC');
        $stmt->execute([$gradoId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para obtener exámenes por materia
function getExamenesByMateria($pdo, $materiaId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM examenes WHERE materia_id = ? ORDER BY fecha_creacion DESC');
        $stmt->execute([$materiaId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para obtener preguntas de un examen
function getPreguntasByExamen($pdo, $examenId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id ASC');
        $stmt->execute([$examenId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para obtener respuestas de una pregunta específica
function getRespuestasByPregunta($pdo, $preguntaId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM respuestas WHERE pregunta_id = ? ORDER BY id ASC');
        $stmt->execute([$preguntaId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para registrar resultado de examen
function registrarResultado($pdo, $usuarioId, $examenId, $puntuacion) {
    try {
        $stmt = $pdo->prepare('INSERT INTO resultados (usuario_id, examen_id, puntuacion) VALUES (?, ?, ?)');
        return $stmt->execute([$usuarioId, $examenId, $puntuacion]);
    } catch(PDOException $e) {
        return false;
    }
}

// Función para obtener resultados de un usuario
function getResultadosUsuario($pdo, $usuarioId) {
    try {
        $stmt = $pdo->prepare('
            SELECT r.*, e.titulo as examen_titulo, m.nombre_materia, g.nombre_grado
            FROM resultados r
            JOIN examenes e ON r.examen_id = e.id
            JOIN materias m ON e.materia_id = m.id
            JOIN grados g ON m.grado_id = g.id
            WHERE r.usuario_id = ?
            ORDER BY r.fecha_tomado DESC
        ');
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Función para calcular estadísticas del usuario
function getEstadisticasUsuario($pdo, $usuarioId) {
    try {
        // Obtener total de exámenes tomados
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM resultados WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $total_examenes = $stmt->fetch()['total'];
        
        // Obtener promedio de puntuación
        $stmt = $pdo->prepare('SELECT AVG(puntuacion) as promedio FROM resultados WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $promedio = $stmt->fetch()['promedio'] ?? 0;
        
        // Obtener mejor puntuación
        $stmt = $pdo->prepare('SELECT MAX(puntuacion) as mejor FROM resultados WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $mejor_puntuacion = $stmt->fetch()['mejor'] ?? 0;
        
        return [
            'total_examenes' => $total_examenes,
            'promedio' => round($promedio, 2),
            'mejor_puntuacion' => $mejor_puntuacion
        ];
    } catch(PDOException $e) {
        return [
            'total_examenes' => 0,
            'promedio' => 0,
            'mejor_puntuacion' => 0
        ];
    }
}

// Función para verificar credenciales de usuario
function verificarCredenciales($pdo, $email, $password) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && verifyPassword($password, $usuario['contrasena'])) {
            return $usuario;
        }
        
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Función para registrar nuevo usuario
function registrarUsuario($pdo, $nombre, $email, $password, $rol = 'estudiante') {
    try {
        // Verificar si el email ya existe
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['error' => 'El email ya está registrado'];
        }
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (?, ?, ?, ?)');
        $hashedPassword = hashPassword($password);
        
        if ($stmt->execute([$nombre, $email, $hashedPassword, $rol])) {
            return ['success' => true, 'user_id' => $pdo->lastInsertId()];
        }
        
        return ['error' => 'Error al registrar usuario'];
    } catch(PDOException $e) {
        return ['error' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

// Función para inicializar sesión de usuario
function iniciarSesion($usuario) {
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['fecha_login'] = date('Y-m-d H:i:s');
}

// Función para cerrar sesión
function cerrarSesion() {
    session_unset();
    session_destroy();
}

// Función para verificar si el usuario está logueado
function usuarioLogueado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar rol del usuario
function verificarRol($rolesPermitidos) {
    if (!usuarioLogueado()) {
        return false;
    }
    
    if (is_string($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }
    
    return in_array($_SESSION['rol'], $rolesPermitidos);
}

// Función para calcular logros del usuario
function calcularLogros($pdo, $usuarioId) {
    try {
        $logros = [];
        
        // Obtener estadísticas básicas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_examenes, AVG(puntuacion) as promedio FROM resultados WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $stats = $stmt->fetch();
        
        if ($stats && $stats['total_examenes'] > 0) {
            // Logro: Primer examen
            if ($stats['total_examenes'] >= 1) {
                $logros[] = [
                    'titulo' => 'Primer Paso',
                    'descripcion' => 'Completaste tu primer examen',
                    'icono' => 'fas fa-baby',
                    'color' => 'success'
                ];
            }
            
            // Logro: 5 exámenes
            if ($stats['total_examenes'] >= 5) {
                $logros[] = [
                    'titulo' => 'Estudiante Activo',
                    'descripcion' => 'Completaste 5 exámenes',
                    'icono' => 'fas fa-book',
                    'color' => 'info'
                ];
            }
            
            // Logro: Promedio alto
            if ($stats['promedio'] >= 90) {
                $logros[] = [
                    'titulo' => 'Excelencia Académica',
                    'descripcion' => 'Mantén un promedio superior al 90%',
                    'icono' => 'fas fa-trophy',
                    'color' => 'warning'
                ];
            }
        }
        
        return $logros;
    } catch(PDOException $e) {
        return [];
    }
}

// Función para obtener mensaje motivacional
function getMensajeMotivacional($puntuacion) {
    if ($puntuacion >= 90) {
        return "¡Excelente trabajo! Tienes un dominio sobresaliente del tema.";
    } elseif ($puntuacion >= 80) {
        return "¡Muy bien! Tienes un buen entendimiento del material.";
    } elseif ($puntuacion >= 70) {
        return "Buen trabajo. Con un poco más de práctica mejorarás aún más.";
    } elseif ($puntuacion >= 60) {
        return "Has hecho un esfuerzo. Revisa los temas y vuelve a intentarlo.";
    } else {
        return "No te desanimes. La práctica hace al maestro. ¡Sigue adelante!";
    }
}

function guardarResultado($usuarioId, $examenId, $puntuacion) {
    global $pdo;
    
    try {
        // Verificar si ya existe un resultado para este usuario y examen
        $sql = "SELECT id FROM resultados WHERE usuario_id = ? AND examen_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId, $examenId]);
        $existeResultado = $stmt->fetch();
        
        if ($existeResultado) {
            // Actualizar resultado existente
            $sql = "UPDATE resultados SET puntuacion = ?, fecha_tomado = NOW() WHERE usuario_id = ? AND examen_id = ?";
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([$puntuacion, $usuarioId, $examenId]);
        } else {
            // Insertar nuevo resultado
            $sql = "INSERT INTO resultados (usuario_id, examen_id, puntuacion, fecha_tomado) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([$usuarioId, $examenId, $puntuacion]);
        }
        
        return $resultado;
    } catch (PDOException $e) {
        error_log("Error al guardar resultado: " . $e->getMessage());
        return false;
    }
}

?>
