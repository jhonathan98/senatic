-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS ose_db;
USE ose_db;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('estudiante', 'docente', 'admin') DEFAULT 'estudiante',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Grados
CREATE TABLE IF NOT EXISTS grados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_grado VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla de Materias
CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_materia VARCHAR(100) NOT NULL,
    grado_id INT NOT NULL,
    FOREIGN KEY (grado_id) REFERENCES grados(id) ON DELETE CASCADE
);

-- Tabla de Examenes
CREATE TABLE IF NOT EXISTS examenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    materia_id INT NOT NULL,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
);

-- Tabla de Preguntas
CREATE TABLE IF NOT EXISTS preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examen_id INT NOT NULL,
    texto_pregunta TEXT NOT NULL,
    tipo_pregunta ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
    FOREIGN KEY (examen_id) REFERENCES examenes(id) ON DELETE CASCADE
);

-- Tabla de Respuestas
CREATE TABLE IF NOT EXISTS respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    texto_respuesta TEXT NOT NULL,
    es_correcta BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
);

-- Tabla de Resultados
CREATE TABLE IF NOT EXISTS resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    examen_id INT NOT NULL,
    puntuacion DECIMAL(5,2) NOT NULL,
    fecha_tomado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (examen_id) REFERENCES examenes(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo

-- Insertar grados
INSERT INTO grados (nombre_grado) VALUES 
('Primero de Primaria'),
('Segundo de Primaria'),
('Tercero de Primaria'),
('Cuarto de Primaria'),
('Quinto de Primaria'),
('Sexto de Primaria'),
('Séptimo'),
('Octavo'),
('Noveno'),
('Décimo'),
('Once');

-- Insertar materias para algunos grados
INSERT INTO materias (nombre_materia, grado_id) VALUES 
-- Primaria
('Matemáticas', 1),
('Lengua Castellana', 1),
('Ciencias Naturales', 1),
('Ciencias Sociales', 1),
('Matemáticas', 2),
('Lengua Castellana', 2),
('Ciencias Naturales', 2),
('Ciencias Sociales', 2),
('Matemáticas', 3),
('Lengua Castellana', 3),
('Ciencias Naturales', 3),
('Ciencias Sociales', 3),
-- Bachillerato
('Matemáticas', 7),
('Lengua Castellana', 7),
('Biología', 7),
('Física', 7),
('Química', 7),
('Historia', 7),
('Geografía', 7),
('Inglés', 7),
('Matemáticas', 8),
('Lengua Castellana', 8),
('Biología', 8),
('Física', 8),
('Química', 8),
('Historia', 8);

-- Insertar algunos exámenes de ejemplo
INSERT INTO examenes (titulo, materia_id, descripcion) VALUES 
-- Matemáticas Primero
('Números del 1 al 10', 1, 'Examen básico sobre números del 1 al 10 y operaciones simples'),
('Sumas Básicas', 1, 'Ejercicios de suma con números de una cifra'),
('Figuras Geométricas', 1, 'Identificación de figuras geométricas básicas'),

-- Lengua Castellana Primero
('El Alfabeto', 2, 'Conocimiento del alfabeto y orden de las letras'),
('Vocales y Consonantes', 2, 'Diferenciación entre vocales y consonantes'),

-- Matemáticas Séptimo
('Números Enteros', 13, 'Operaciones con números enteros positivos y negativos'),
('Fracciones', 13, 'Suma, resta, multiplicación y división de fracciones'),
('Ecuaciones Lineales', 13, 'Resolución de ecuaciones lineales simples'),

-- Biología Séptimo
('La Célula', 15, 'Estructura y funciones de la célula'),
('Sistemas del Cuerpo Humano', 15, 'Conocimiento básico de los sistemas corporales');

-- Insertar preguntas y respuestas de ejemplo

-- Preguntas para "Números del 1 al 10"
INSERT INTO preguntas (examen_id, texto_pregunta, tipo_pregunta) VALUES 
(1, '¿Cuál es el número que viene después del 5?', 'multiple_choice'),
(1, '¿Cuántos dedos tienes en una mano?', 'multiple_choice'),
(1, 'El número 3 es mayor que el número 2', 'true_false');

-- Respuestas para la pregunta 1
INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES 
(1, '4', FALSE),
(1, '6', TRUE),
(1, '7', FALSE),
(1, '3', FALSE);

-- Respuestas para la pregunta 2
INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES 
(2, '4', FALSE),
(2, '5', TRUE),
(2, '6', FALSE),
(2, '3', FALSE);

-- Respuestas para la pregunta 3
INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES 
(3, 'Verdadero', TRUE),
(3, 'Falso', FALSE);

-- Preguntas para "Sumas Básicas"
INSERT INTO preguntas (examen_id, texto_pregunta, tipo_pregunta) VALUES 
(2, '¿Cuánto es 2 + 3?', 'multiple_choice'),
(2, '¿Cuánto es 4 + 1?', 'multiple_choice'),
(2, '¿Cuánto es 3 + 3?', 'multiple_choice');

-- Respuestas para sumas básicas
INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES 
(4, '4', FALSE),
(4, '5', TRUE),
(4, '6', FALSE),
(4, '7', FALSE),

(5, '4', FALSE),
(5, '5', TRUE),
(5, '6', FALSE),
(5, '3', FALSE),

(6, '5', FALSE),
(6, '6', TRUE),
(6, '7', FALSE),
(6, '4', FALSE);

-- Preguntas para "La Célula"
INSERT INTO preguntas (examen_id, texto_pregunta, tipo_pregunta) VALUES 
(8, '¿Cuál es la unidad básica de la vida?', 'multiple_choice'),
(8, '¿Qué estructura controla las actividades de la célula?', 'multiple_choice'),
(8, 'Todas las células tienen núcleo', 'true_false');

-- Respuestas para "La Célula"
INSERT INTO respuestas (pregunta_id, texto_respuesta, es_correcta) VALUES 
(7, 'El átomo', FALSE),
(7, 'La célula', TRUE),
(7, 'El tejido', FALSE),
(7, 'El órgano', FALSE),

(8, 'La membrana celular', FALSE),
(8, 'El núcleo', TRUE),
(8, 'El citoplasma', FALSE),
(8, 'Las mitocondrias', FALSE),

(9, 'Verdadero', FALSE),
(9, 'Falso', TRUE);

-- Insertar usuario de prueba
-- Contraseña: 123456 (hasheada)
INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES 
('Estudiante de Prueba', 'estudiante@ose.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Docente de Prueba', 'docente@ose.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docente'),
('Admin de Prueba', 'admin@ose.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');