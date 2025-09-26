-- Script SQL para SysPlanner (Básico)

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sysplanner_db;
USE sysplanner_db;

-- Tabla de Departamentos (opcional, para organizar recursos)
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE
);

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Usar password_hash() en PHP
    rol ENUM('admin', 'usuario') DEFAULT 'usuario', -- Puede expandirse
    departamento_id INT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- Tabla de Recursos
CREATE TABLE IF NOT EXISTS recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(100), -- Ej: Aula, Laboratorio, Sala de Sistemas, Equipo
    capacidad INT,
    caracteristicas TEXT, -- Características técnicas específicas
    departamento_id INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- Tabla de Reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recurso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_inicio DATETIME NOT NULL, -- Fecha y hora de inicio de la reserva
    fecha_fin DATETIME NOT NULL,    -- Fecha y hora de fin de la reserva
    motivo TEXT,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'finalizada') DEFAULT 'confirmada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para mejorar rendimiento en búsquedas comunes
CREATE INDEX idx_reservas_fecha ON reservas (fecha_inicio, fecha_fin);
CREATE INDEX idx_reservas_recurso ON reservas (recurso_id);
CREATE INDEX idx_recursos_departamento ON recursos (departamento_id);

-- Inserciones iniciales de ejemplo (Opcional)
INSERT INTO departamentos (nombre) VALUES ('Matemáticas'), ('Ciencias'), ('Sistemas'), ('Administración');

INSERT INTO usuarios (nombre_completo, email, password_hash, rol, departamento_id) VALUES
('Admin SysPlanner', 'admin@sysplanner.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
('Profesor Juan', 'juan@institucion.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', 1);

INSERT INTO recursos (nombre, descripcion, tipo, capacidad, departamento_id) VALUES
('Aula 101', 'Aula estándar con pizarra', 'Aula', 30, 1),
('Laboratorio Química', 'Laboratorio con mesones y fregadero', 'Laboratorio', 25, 2),
('Sala de Sistemas 1', 'Computadoras con software específico', 'Sala de Sistemas', 20, 3);

