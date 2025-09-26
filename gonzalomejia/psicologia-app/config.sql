-- Crear base de datos
CREATE DATABASE IF NOT EXISTS psicologia_db;
USE psicologia_db;

-- Tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('estudiante', 'psicologo') NOT NULL,
    edad INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de horarios disponibles
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    psicologo_id INT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (psicologo_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de turnos
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT,
    psicologo_id INT,
    horario_id INT,
    sintomas TEXT,
    urgencia INT,
    ayuda_previa BOOLEAN,
    detalles TEXT,
    notas_psicologo TEXT,
    estado ENUM('pendiente', 'completada', 'cancelada', 'no asistió') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES users(id),
    FOREIGN KEY (psicologo_id) REFERENCES users(id),
    FOREIGN KEY (horario_id) REFERENCES horarios(id)
);

INSERT INTO `users` (`id`, `nombre`, `email`, `password`, `rol`, `edad`, `created_at`) VALUES
(1, 'psicologo1', 'psicologo1@colegio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'psicologo', 0, '2025-08-14 00:58:40'),
(2, 'estudiante1', 'estudiante1@colegio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante', 13, '2025-08-14 03:37:14'),
(3, 'estudiante2', 'estudiante2@colegio.edu', '$2y$10$K/dSykYH7Qf1Ern3wp8Wwuf.wjtVbRrmo7yJvZWITO1P44FczzDKy', 'estudiante', 14, '2025-08-14 04:32:18'),
(4, 'psicologo3', 'psicologo3@institucion.edu', '$2y$10$7StrOqgaGCGTiKAb3YzSgelxoBd2LQEs7yY4k0Pnf4wyB0sQUFo8C', 'psicologo', 0, '2025-08-27 01:37:31');

--