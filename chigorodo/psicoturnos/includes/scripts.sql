-- Crear base de datos
CREATE DATABASE edu_bienestar;

USE edu_bienestar;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    rol ENUM('estudiante', 'psicologo', 'admin'),
    grado VARCHAR(20),
    edad INT,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de cuestionarios
CREATE TABLE cuestionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    edad_grupo ENUM('6-13', '14-18'),
    respuestas TEXT,
    nivel_urgencia ENUM('bajo', 'medio', 'alto', 'crítico'),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT,
    psicologo_id INT,
    fecha DATETIME,
    tipo ENUM('presencial', 'virtual'),
    estado ENUM('pendiente', 'confirmada', 'reprogramada', 'cancelada'),
    enlace VARCHAR(255),
    notas_psicologo TEXT,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
    FOREIGN KEY (psicologo_id) REFERENCES usuarios(id)
);

-- Tabla de horarios disponibles
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    psicologo_id INT,
    dia_semana ENUM('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
    hora_inicio TIME,
    hora_fin TIME,
    FOREIGN KEY (psicologo_id) REFERENCES usuarios(id)
);