-- -----------------------------------------------------
-- Crear base de datos
-- -----------------------------------------------------
DROP DATABASE IF EXISTS studentgps;
CREATE DATABASE studentgps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studentgps;

-- -----------------------------------------------------
-- Tabla: users (usuarios: acudientes, profesores, admin)
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'parent', 'teacher') NOT NULL DEFAULT 'parent',
    document_number VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_document (document_number),
    INDEX idx_active (is_active),
    INDEX idx_username (username)
);

-- -----------------------------------------------------
-- Tabla: students (estudiantes)
-- -----------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    document_number VARCHAR(20) NOT NULL UNIQUE,
    grade VARCHAR(10) NOT NULL,
    parent_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_grade (grade),
    INDEX idx_document_stu (document_number)
);

-- -----------------------------------------------------
-- Tabla: teacher_students (relación muchos a muchos)
-- -----------------------------------------------------
CREATE TABLE teacher_students (
    teacher_id INT,
    student_id INT,
    PRIMARY KEY (teacher_id, student_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Tabla: attendance (asistencia diaria)
-- -----------------------------------------------------
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Asistió', 'No asistió', 'Tarde', 'Excusado') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_attendance (student_id, date),
    INDEX idx_date (date),
    INDEX idx_student_date (student_id, date),
    INDEX idx_status (status)
);

-- -----------------------------------------------------
-- Tabla: locations (ubicación en tiempo real)
-- -----------------------------------------------------
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_time (student_id, timestamp)
);

-- -----------------------------------------------------
-- Datos de prueba
-- -----------------------------------------------------

-- Admin
INSERT INTO users (username, password, role, document_number, name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '000000000', 'Administrador');

-- Acudiente
INSERT INTO users (username, password, role, document_number, name) VALUES 
('juanperez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', '123456789', 'Juan Pérez');

-- Estudiante
INSERT INTO students (name, document_number, grade, parent_id) VALUES 
('María Pérez', '987654321', '10°', 2);

-- Profesor
INSERT INTO users (username, password, role, document_number, name) VALUES 
('profesor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '555555555', 'Prof. Ana Gómez');

-- Asociar profesor con estudiante
INSERT INTO teacher_students (teacher_id, student_id) VALUES (3, 1);

-- -----------------------------------------------------
-- Tabla: activity_log (registro de actividades)
-- -----------------------------------------------------
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_action (action)
);

-- -----------------------------------------------------
-- Tabla: notifications (notificaciones)
-- -----------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
);

-- -----------------------------------------------------
-- Insertar ubicaciones de ejemplo
-- -----------------------------------------------------
INSERT INTO locations (student_id, latitude, longitude, timestamp) VALUES 
(1, 7.6656, -76.6281, NOW() - INTERVAL 5 MINUTE),
(1, 7.6658, -76.6279, NOW() - INTERVAL 2 MINUTE),
(1, 7.6660, -76.6277, NOW());