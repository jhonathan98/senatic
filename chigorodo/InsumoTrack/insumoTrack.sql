-- Script para crear la base de datos y tablas para InsumoTrack
-- Asumiendo un motor de base de datos MySQL

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS insumo_track_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE insumo_track_db;

-- Tabla de Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE -- Ej: 'admin', 'user'
);

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    tipo_documento ENUM('CC', 'TI', 'CE', 'Pasaporte') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    institucion_educativa VARCHAR(255),
    email VARCHAR(255) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL, -- Siempre almacenar el hash de la contraseña
    rol_id INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Insertar roles por defecto
INSERT INTO roles (nombre) VALUES ('admin'), ('user') ON DUPLICATE KEY UPDATE nombre=nombre;

-- Tabla de Insumos
CREATE TABLE IF NOT EXISTS insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE, -- Código único del insumo
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    estado ENUM('Disponible', 'Prestado', 'No disponible', 'Mantenimiento') DEFAULT 'Disponible',
    ubicacion VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Préstamos
CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    insumo_id INT NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_prestamo DATE NULL, -- Fecha real de préstamo (cuando se aprueba)
    fecha_devolucion_prevista DATE NOT NULL, -- Fecha prevista de devolución
    fecha_devolucion_real DATE NULL, -- Fecha real de devolución
    estado ENUM('Pendiente', 'Aprobado', 'Entregado', 'Devuelto', 'Atrasado', 'Rechazado') DEFAULT 'Pendiente',
    observaciones TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (insumo_id) REFERENCES insumos(id)
);

-- Índices para mejorar rendimiento en búsquedas frecuentes
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_insumos_estado ON insumos(estado);
CREATE INDEX idx_prestamos_estado_fecha ON prestamos(estado, fecha_devolucion_prevista);

-- INSERCIÓN DE DATOS DE PRUEBA
-- =====================================

-- Inserción de insumos de ejemplo
INSERT INTO insumos (codigo, nombre, descripcion, estado, ubicacion) VALUES
-- Laboratorio de Biología
('LAB001', 'Microscopio Óptico', 'Microscopio compuesto de 40x a 1000x con oculares WF10x', 'Disponible', 'Laboratorio de Biología'),
('LAB002', 'Microscopio Estereoscópico', 'Microscopio binocular con zoom 10x-40x', 'Disponible', 'Laboratorio de Biología'),
('LAB003', 'Balanza Analítica', 'Balanza de precisión 0.1mg, capacidad 220g', 'Disponible', 'Laboratorio de Biología'),
('LAB004', 'Centrifuga de Mesa', 'Centrifuga para tubos de ensayo, 4000 rpm', 'Prestado', 'Laboratorio de Biología'),
('LAB005', 'pH-metro Digital', 'Medidor de pH con electrodo de vidrio', 'Disponible', 'Laboratorio de Biología'),

-- Laboratorio de Química
('QUI001', 'Espectrofotómetro UV-Vis', 'Espectrofotómetro de doble haz, rango 190-1100nm', 'Disponible', 'Laboratorio de Química'),
('QUI002', 'Campana Extractora', 'Campana de extracción de gases con flujo laminar', 'Disponible', 'Laboratorio de Química'),
('QUI003', 'Agitador Magnético', 'Agitador con calentamiento, hasta 300°C', 'Prestado', 'Laboratorio de Química'),
('QUI004', 'Destilador de Agua', 'Destilador automático, capacidad 4L/h', 'Disponible', 'Laboratorio de Química'),
('QUI005', 'Rotaevaporador', 'Evaporador rotativo para síntesis orgánica', 'Mantenimiento', 'Laboratorio de Química'),

-- Laboratorio de Física
('FIS001', 'Osciloscopio Digital', 'Osciloscopio de 2 canales, 100MHz', 'Disponible', 'Laboratorio de Física'),
('FIS002', 'Generador de Funciones', 'Generador de señales sinusoidales, cuadradas y triangulares', 'Disponible', 'Laboratorio de Física'),
('FIS003', 'Fuente de Poder Variable', 'Fuente DC variable 0-30V, 0-5A', 'Prestado', 'Laboratorio de Física'),
('FIS004', 'Multímetro Digital Avanzado', 'Multímetro de 6.5 dígitos con interfaz USB', 'Disponible', 'Laboratorio de Física'),
('FIS005', 'Kit de Óptica', 'Kit completo para experimentos de óptica geométrica', 'Disponible', 'Laboratorio de Física'),

-- Laboratorio de Electrónica
('EQP001', 'Multímetro Digital', 'Multímetro Fluke 17B+ con autorange', 'Disponible', 'Laboratorio de Electrónica'),
('EQP002', 'Protoboard Grande', 'Protoboard de 830 puntos con buses de alimentación', 'Disponible', 'Laboratorio de Electrónica'),
('EQP003', 'Soldador de Estaño', 'Soldador controlado por temperatura 60W', 'Prestado', 'Laboratorio de Electrónica'),
('EQP004', 'Estación de Soldadura', 'Estación con control digital de temperatura', 'Disponible', 'Laboratorio de Electrónica'),
('EQP005', 'Analizador de Espectros', 'Analizador portátil hasta 3GHz', 'No disponible', 'Laboratorio de Electrónica'),

-- Biblioteca y Audiovisuales
('BIB001', 'Proyector HD', 'Proyector LED 1080p con 3000 lúmenes', 'Disponible', 'Biblioteca'),
('BIB002', 'Laptop para Presentaciones', 'Laptop Dell con Windows 11 y Office', 'Prestado', 'Biblioteca'),
('BIB003', 'Cámara Digital', 'Cámara DSLR Canon con lente 18-55mm', 'Disponible', 'Biblioteca'),
('BIB004', 'Micrófono Inalámbrico', 'Sistema de micrófono inalámbrico profesional', 'Disponible', 'Biblioteca'),
('BIB005', 'Tableta Gráfica', 'Tableta digitalizadora Wacom para diseño', 'Disponible', 'Biblioteca'),

-- Talleres y Mecánica
('TAL001', 'Taladro de Columna', 'Taladro vertical de precisión 16mm', 'Disponible', 'Taller de Mecánica'),
('TAL002', 'Calibrador Vernier', 'Pie de rey digital 0-150mm, precisión 0.01mm', 'Disponible', 'Taller de Mecánica'),
('TAL003', 'Micrómetro Digital', 'Micrómetro exterior 0-25mm, precisión 0.001mm', 'Prestado', 'Taller de Mecánica'),
('TAL004', 'Sierra de Cinta', 'Sierra eléctrica para corte de metales', 'Mantenimiento', 'Taller de Mecánica'),
('TAL005', 'Torno de Mesa', 'Torno para trabajos de precisión', 'Disponible', 'Taller de Mecánica');

-- Inserción de usuarios administradores
-- Contraseña para todos: admin123 (hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
INSERT INTO usuarios (nombre_completo, tipo_documento, numero_documento, institucion_educativa, email, telefono, password_hash, rol_id) VALUES
('Admin Principal', 'CC', '0000000000', 'SENA - Centro de Biotecnología Agropecuaria', 'admin@sena.edu.co', '3001234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'admin')),
('María González', 'CC', '12345678', 'SENA - Centro de Biotecnología Agropecuaria', 'maria.gonzalez@sena.edu.co', '3009876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'admin'));

-- Inserción de usuarios estudiantes/docentes
-- Contraseña para todos: user123 (hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
INSERT INTO usuarios (nombre_completo, tipo_documento, numero_documento, institucion_educativa, email, telefono, password_hash, rol_id) VALUES
-- Estudiantes de Biotecnología
('Carlos Andrés Pérez', 'CC', '23456789', 'SENA - Centro de Biotecnología Agropecuaria', 'carlos.perez@aprendiz.sena.edu.co', '3012345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Ana María López', 'CC', '34567890', 'SENA - Centro de Biotecnología Agropecuaria', 'ana.lopez@aprendiz.sena.edu.co', '3023456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Juan David Rodríguez', 'CC', '45678901', 'SENA - Centro de Biotecnología Agropecuaria', 'juan.rodriguez@aprendiz.sena.edu.co', '3034567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Laura Sofía Martínez', 'CC', '56789012', 'SENA - Centro de Biotecnología Agropecuaria', 'laura.martinez@aprendiz.sena.edu.co', '3045678901', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Miguel Ángel García', 'CC', '67890123', 'SENA - Centro de Biotecnología Agropecuaria', 'miguel.garcia@aprendiz.sena.edu.co', '3056789012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),

-- Estudiantes de otras instituciones
('Sebastián Torres', 'CC', '78901234', 'Universidad de Antioquia', 'sebastian.torres@udea.edu.co', '3067890123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Valentina Herrera', 'CC', '89012345', 'Universidad Nacional', 'valentina.herrera@unal.edu.co', '3078901234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Andrés Felipe Ruiz', 'CC', '90123456', 'Politécnico Colombiano JIC', 'andres.ruiz@elpoli.edu.co', '3089012345', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Camila Andrea Sánchez', 'TI', '01234567', 'Institución Educativa La Esperanza', 'camila.sanchez@estudiante.edu.co', '3090123456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Daniel Eduardo Vargas', 'CC', '12340987', 'SENA - Centro Industrial', 'daniel.vargas@aprendiz.sena.edu.co', '3001230987', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),

-- Docentes e Investigadores
('Dr. Roberto Jiménez', 'CC', '11223344', 'SENA - Centro de Biotecnología Agropecuaria', 'roberto.jimenez@sena.edu.co', '3011223344', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Dra. Patricia Morales', 'CC', '22334455', 'Universidad de Medellín', 'patricia.morales@udem.edu.co', '3022334455', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user')),
('Ing. Fernando Castro', 'CC', '33445566', 'SENA - Centro de Automatización Industrial', 'fernando.castro@sena.edu.co', '3033445566', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE nombre = 'user'));

-- Inserción de préstamos de ejemplo con diferentes estados
INSERT INTO prestamos (usuario_id, insumo_id, fecha_solicitud, fecha_prestamo, fecha_devolucion_prevista, fecha_devolucion_real, estado, observaciones) VALUES
-- Préstamos completados (devueltos)
(3, 1, '2024-09-01 10:30:00', '2024-09-02', '2024-09-09', '2024-09-08', 'Devuelto', 'Proyecto de investigación en microbiología'),
(4, 15, '2024-09-03 14:15:00', '2024-09-04', '2024-09-11', '2024-09-10', 'Devuelto', 'Práctica de laboratorio de física'),
(5, 7, '2024-09-05 09:45:00', '2024-09-06', '2024-09-13', '2024-09-12', 'Devuelto', 'Análisis de muestras orgánicas'),

-- Préstamos activos (entregados)
(6, 4, '2024-09-10 11:20:00', '2024-09-11', '2024-09-25', NULL, 'Entregado', 'Separación de componentes celulares'),
(7, 8, '2024-09-12 16:30:00', '2024-09-13', '2024-09-27', NULL, 'Entregado', 'Síntesis de compuesto farmacéutico'),
(8, 17, '2024-09-15 08:15:00', '2024-09-16', '2024-09-30', NULL, 'Entregado', 'Reparación de circuito electrónico'),

-- Préstamos atrasados
(9, 19, '2024-08-20 13:45:00', '2024-08-21', '2024-09-04', NULL, 'Atrasado', 'Proyecto de fin de semestre'),
(10, 25, '2024-08-25 10:00:00', '2024-08-26', '2024-09-09', NULL, 'Atrasado', 'Mediciones de precisión'),

-- Préstamos aprobados (listos para entregar)
(11, 12, '2024-09-20 14:30:00', '2024-09-21', '2024-10-05', NULL, 'Aprobado', 'Experimentos de electromagnetismo'),
(12, 20, '2024-09-22 09:15:00', '2024-09-23', '2024-10-07', NULL, 'Aprobado', 'Grabación de conferencia académica'),

-- Préstamos pendientes de aprobación
(13, 2, '2024-09-25 11:45:00', NULL, '2024-10-09', NULL, 'Pendiente', 'Observación de preparaciones histológicas'),
(14, 14, '2024-09-26 15:20:00', NULL, '2024-10-10', NULL, 'Pendiente', 'Medición de voltajes en circuitos'),
(3, 22, '2024-09-27 08:30:00', NULL, '2024-10-11', NULL, 'Pendiente', 'Documentación de proceso productivo'),
(4, 16, '2024-09-27 16:45:00', NULL, '2024-10-11', NULL, 'Pendiente', 'Análisis de frecuencias'),

-- Préstamos rechazados
(5, 26, '2024-09-15 12:00:00', NULL, '2024-09-29', NULL, 'Rechazado', 'Rechazado: Equipo requiere capacitación previa'),
(6, 5, '2024-09-18 10:30:00', NULL, '2024-10-02', NULL, 'Rechazado', 'Rechazado: Insumo reservado para otro proyecto');

-- Actualizar estados de insumos según préstamos activos
UPDATE insumos SET estado = 'Prestado' WHERE id IN (4, 8, 17, 19, 25);
UPDATE insumos SET estado = 'No disponible' WHERE id = 21;

-- INFORMACIÓN IMPORTANTE SOBRE CONTRASEÑAS:
-- ==========================================
-- Administradores:
-- - admin@sena.edu.co → Contraseña: admin123
-- - maria.gonzalez@sena.edu.co → Contraseña: admin123
--
-- Usuarios (estudiantes/docentes):
-- - Todos los usuarios tienen la contraseña: user123
--
-- Para generar nuevos hashes de contraseña en PHP:
-- password_hash('tu_contraseña', PASSWORD_DEFAULT);