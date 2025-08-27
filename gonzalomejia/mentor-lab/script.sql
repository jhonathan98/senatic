-- Script de base de datos para el sistema de mentorías

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_mentorias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_mentorias;

-- Tabla de usuarios (estudiantes)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    telefono_recuperacion VARCHAR(20),
    fecha_nacimiento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de mentores
CREATE TABLE mentores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    experiencia_anios INT,
    descripcion TEXT,
    nivel_educativo VARCHAR(50),
    foto_perfil VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de paquetes
CREATE TABLE paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    tipo ENUM('familiar', 'juvenil', 'infantil', 'escolar'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de promociones
CREATE TABLE promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    descuento_porcentaje DECIMAL(5,2),
    fecha_inicio DATE,
    fecha_fin DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de solicitudes de mentoría
CREATE TABLE solicitudes_mentoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    mentor_id INT,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'finalizada') DEFAULT 'pendiente',
    mensaje TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentores(id) ON DELETE CASCADE
);

-- Tabla de compras de paquetes
CREATE TABLE compras_paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    paquete_id INT,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('tarjeta', 'transferencia'),
    monto_total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    numero_transaccion VARCHAR(50),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo
INSERT INTO mentores (nombre_completo, especialidad, experiencia_anios, descripcion, nivel_educativo, foto_perfil) VALUES
('Luis Pérez', 'Matemáticas', 8, 'Especialista en enseñar conceptos numéricos y lógicos, guiando a los estudiantes en la resolución de problemas y el pensamiento crítico.', 'secundaria', 'https://via.placeholder.com/120'),
('Amanda Gómez', 'Idiomas', 5, 'Facilita el aprendizaje de nuevas lenguas, enfocándose en la comunicación oral, escrita y la comprensión cultural.', 'primaria', 'https://via.placeholder.com/120'),
('Carlos Rodríguez', 'Tecnología', 4, 'Enseña el uso y aplicación de herramientas digitales, programación y soluciones tecnológicas para el mundo actual.', 'preparatoria', 'https://via.placeholder.com/120');

INSERT INTO paquetes (nombre, descripcion, precio, tipo) VALUES
('Paquete Familiar', 'Paquete ideal para familias con múltiples miembros que buscan mejorar sus habilidades académicas', 1500.00, 'familiar'),
('Paquete Juvenil', 'Paquete diseñado específicamente para jóvenes estudiantes', 800.00, 'juvenil'),
('Paquete Infantil', 'Paquete especializado para niños en edad escolar', 500.00, 'infantil'),
('Paquete Escolar', 'Paquete completo para estudiantes de todos los niveles escolares', 1200.00, 'escolar');

INSERT INTO promociones (titulo, descripcion, descuento_porcentaje, fecha_inicio, fecha_fin) VALUES
('Promoción de Inicio de Año', 'Descuento del 20% en todos los paquetes durante el mes de enero', 20.00, '2023-01-01', '2023-01-31');

-- Crear índice para mejor rendimiento en búsquedas
CREATE INDEX idx_usuarios_correo ON usuarios(correo_electronico);
CREATE INDEX idx_mentores_especialidad ON mentores(especialidad);
CREATE INDEX idx_mentores_nivel ON mentores(nivel_educativo);
CREATE INDEX idx_solicitudes_usuario ON solicitudes_mentoria(usuario_id);
CREATE INDEX idx_solicitudes_mentor ON solicitudes_mentoria(mentor_id);
CREATE INDEX idx_compras_usuario ON compras_paquetes(usuario_id);
CREATE INDEX idx_compras_paquete ON compras_paquetes(paquete_id);