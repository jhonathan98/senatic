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
    rol ENUM('admin', 'mentor', 'usuario') DEFAULT 'usuario',
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
    usuario_id INT,
    nombre_completo VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    experiencia_anios INT,
    descripcion TEXT,
    nivel_educativo VARCHAR(50),
    foto_perfil VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
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
    metodo_pago ENUM('tarjeta', 'transferencia', 'paypal', 'efectivo'),
    monto_total DECIMAL(10,2) NOT NULL,
    monto_descuento DECIMAL(10,2) DEFAULT 0,
    codigo_promocional VARCHAR(50),
    estado ENUM('pendiente', 'completada', 'cancelada', 'en_proceso') DEFAULT 'pendiente',
    numero_transaccion VARCHAR(50),
    datos_pago TEXT,
    fecha_vencimiento DATE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE
);

-- Tabla de planes personalizados (para los paquetes mostrados en paquetes.php)
CREATE TABLE planes_personalizados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    tipo ENUM('basico', 'estandar', 'premium') NOT NULL,
    caracteristicas JSON,
    sesiones_mensuales INT,
    duracion_sesion DECIMAL(3,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de suscripciones de usuarios a planes
CREATE TABLE suscripciones_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    plan_id INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    estado ENUM('activa', 'suspendida', 'cancelada', 'vencida') DEFAULT 'activa',
    metodo_pago ENUM('tarjeta', 'transferencia', 'paypal', 'efectivo'),
    monto_mensual DECIMAL(10,2),
    proximo_pago DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES planes_personalizados(id) ON DELETE CASCADE
);

-- Tabla de historial de pagos
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suscripcion_id INT,
    compra_id INT,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('tarjeta', 'transferencia', 'paypal', 'efectivo'),
    numero_transaccion VARCHAR(100),
    estado ENUM('exitoso', 'fallido', 'pendiente', 'reembolsado') DEFAULT 'pendiente',
    datos_pago TEXT,
    FOREIGN KEY (suscripcion_id) REFERENCES suscripciones_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (compra_id) REFERENCES compras_paquetes(id) ON DELETE CASCADE
);


-- Insertar usuarios regulares (estudiantes)
INSERT INTO `usuarios` (`id`, `nombre_completo`, `correo_electronico`, `contrasena`, `rol`, `telefono`, `telefono_recuperacion`, `fecha_nacimiento`, `created_at`, `updated_at`, `status`) VALUES
(1, 'usuario1', 'usuario1@gmail.com', '$2y$10$nGUYY83aC4EBoxIpzD9Pmup3cN5iZTJ3McQzqatnvCeOOM1zVSEQW', 'usuario', '90292', '22902', '2000-04-10', '2025-08-27 01:15:46', '2025-08-27 01:15:46', 'activo'),
(2, 'usuario2', 'usuario2@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '90292', '22902', '2002-02-12', '2025-08-27 01:17:01', '2025-09-25 23:51:04', 'activo'),
(3, 'admin', 'adminMentor@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '90292', '22902', '1989-05-10', '2025-08-27 02:39:47', '2025-09-25 23:50:59', 'activo');

-- Insertar usuarios que serán mentores
INSERT INTO `usuarios` (`id`, `nombre_completo`, `correo_electronico`, `contrasena`, `rol`, `telefono`, `telefono_recuperacion`, `fecha_nacimiento`, `status`) VALUES
(4, 'Luis Pérez', 'luis.perez@mentorlab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '555-0001', '555-0101', '1985-03-15', 'activo'),
(5, 'Amanda Gómez', 'amanda.gomez@mentorlab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '555-0002', '555-0102', '1988-07-22', 'activo'),
(6, 'Carlos Rodríguez', 'carlos.rodriguez@mentorlab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '555-0003', '555-0103', '1990-11-08', 'activo');


-- Insertar datos de mentores asociados a usuarios con rol 'mentor'
INSERT INTO mentores (usuario_id, nombre_completo, especialidad, experiencia_anios, descripcion, nivel_educativo, foto_perfil) VALUES
(4, 'Luis Pérez', 'Matemáticas', 8, 'Especialista en enseñar conceptos numéricos y lógicos, guiando a los estudiantes en la resolución de problemas y el pensamiento crítico.', 'secundaria', 'https://via.placeholder.com/120'),
(5, 'Amanda Gómez', 'Idiomas', 5, 'Facilita el aprendizaje de nuevas lenguas, enfocándose en la comunicación oral, escrita y la comprensión cultural.', 'primaria', 'https://via.placeholder.com/120'),
(6, 'Carlos Rodríguez', 'Tecnología', 4, 'Enseña el uso y aplicación de herramientas digitales, programación y soluciones tecnológicas para el mundo actual.', 'preparatoria', 'https://via.placeholder.com/120');

INSERT INTO paquetes (nombre, descripcion, precio, tipo) VALUES
('Paquete Familiar', 'Paquete ideal para familias con múltiples miembros que buscan mejorar sus habilidades académicas', 1500.00, 'familiar'),
('Paquete Juvenil', 'Paquete diseñado específicamente para jóvenes estudiantes', 800.00, 'juvenil'),
('Paquete Infantil', 'Paquete especializado para niños en edad escolar', 500.00, 'infantil'),
('Paquete Escolar', 'Paquete completo para estudiantes de todos los niveles escolares', 1200.00, 'escolar');

-- Insertar planes personalizados
INSERT INTO planes_personalizados (nombre, descripcion, precio, tipo, caracteristicas, sesiones_mensuales, duracion_sesion) VALUES
('Paquete Básico', 'Plan básico ideal para comenzar con mentorías personalizadas', 299.00, 'basico', '["4 sesiones mensuales", "1 hora por sesión", "Material de estudio básico", "Soporte por correo"]', 4, 1.0),
('Paquete Estándar', 'Plan más popular con beneficios adicionales', 499.00, 'estandar', '["8 sesiones mensuales", "1.5 horas por sesión", "Material de estudio completo", "Soporte por WhatsApp", "Ejercicios prácticos"]', 8, 1.5),
('Paquete Premium', 'Plan premium con todas las características avanzadas', 799.00, 'premium', '["12 sesiones mensuales", "2 horas por sesión", "Material premium exclusivo", "Soporte 24/7", "Ejercicios avanzados", "Sesiones grupales adicionales"]', 12, 2.0);

-- Insertar datos de ejemplo para suscripciones y pagos
INSERT INTO suscripciones_usuarios (usuario_id, plan_id, fecha_inicio, fecha_fin, monto_mensual, proximo_pago) VALUES
(1, 2, '2025-09-01', '2026-09-01', 499.00, '2025-10-01'),
(2, 1, '2025-09-15', '2026-09-15', 299.00, '2025-10-15');

INSERT INTO historial_pagos (suscripcion_id, monto, metodo_pago, numero_transaccion, estado) VALUES
(1, 499.00, 'tarjeta', 'TXN001234567', 'exitoso'),
(2, 299.00, 'paypal', 'PP987654321', 'exitoso');

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