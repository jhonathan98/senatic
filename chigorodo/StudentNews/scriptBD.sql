-- Crear base de datos
CREATE DATABASE student_news;
USE student_news;

-- Tabla de Roles
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE -- 'lector', 'redactor', 'usuarioRegular', 'administrador'
);

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL, -- Almacenar hash
    grado VARCHAR(50),
    id_rol INT DEFAULT 1, -- Por defecto, lector
    foto_perfil VARCHAR(255) DEFAULT 'default.jpg',
    descripcion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE SET NULL
);

-- Tabla de Categorías
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL UNIQUE, -- 'Noticias', 'Opinión', 'Cultura y Arte', etc.
    icono VARCHAR(50) -- Clase de Bootstrap o Font Awesome, ej: 'bi bi-newspaper'
);

-- Tabla de Artículos
CREATE TABLE articulos (
    id_articulo INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    id_autor INT NOT NULL,
    id_categoria INT NOT NULL,
    estado ENUM('borrador', 'pendiente', 'publicado', 'rechazado') DEFAULT 'borrador',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_publicacion DATETIME NULL,
    imagen_destacada VARCHAR(255),
    vistas INT DEFAULT 0,
    FOREIGN KEY (id_autor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE
);

-- Tabla de Eventos (Calendario)
CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_evento DATE NOT NULL,
    lugar VARCHAR(255),
    imagen VARCHAR(255)
);

-- Tabla de Multimedia (Galería)
CREATE TABLE multimedia (
    id_multimedia INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255),
    url_archivo VARCHAR(255) NOT NULL, -- Ruta de imagen o video
    tipo ENUM('imagen', 'video') NOT NULL,
    id_articulo_relacionado INT NULL, -- Opcional: vincular a un artículo
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_articulo_relacionado) REFERENCES articulos(id_articulo) ON DELETE SET NULL
);

-- Tabla de Participaciones (Formulario "Participa")
CREATE TABLE participaciones (
    id_participacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    tipo_participacion ENUM('noticia', 'evento', 'sugerencia', 'denuncia', 'otro') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente'
);

-- Tabla de Contacto
CREATE TABLE contactos (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'respondido') DEFAULT 'pendiente'
);

-- Insertar Roles Iniciales
INSERT INTO roles (nombre_rol) VALUES 
('lector'),
('redactor'),
('usuarioRegular'),
('administrador');

-- Insertar Categorías Iniciales (con iconos de Bootstrap Icons)
INSERT INTO categorias (nombre_categoria, icono) VALUES
('Noticias', 'bi bi-newspaper'),
('Opinión estudiantil', 'bi bi-chat-quote'),
('Cultura y arte', 'bi bi-palette'),
('Deportes', 'bi bi-trophy'),
('Multimedia', 'bi bi-camera-reels'),
('Entrevistas', 'bi bi-mic'),
('Tips escolares', 'bi bi-lightbulb'),
('Vida estudiantil', 'bi bi-people');

-- Usuarios de ejemplo para cada rol (CAMBIAR CONTRASEÑAS EN PRODUCCIÓN)

-- Usuario administrador (rol 4) - contraseña: password
INSERT INTO usuarios (nombre_completo, correo, nombre_usuario, contrasena, grado, id_rol, descripcion) VALUES
('Admin Principal', 'admin@studentnews.edu', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'N/A', 4, 'Administrador principal del sistema Student News');

-- Usuario regular/redactor (rol 3) - contraseña: password  
INSERT INTO usuarios (nombre_completo, correo, nombre_usuario, contrasena, grado, id_rol, descripcion) VALUES
('María García López', 'maria.garcia@studentnews.edu', 'maria_garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11°A', 3, 'Redactora principal especializada en noticias estudiantiles y eventos escolares');

-- Usuario redactor (rol 2) - contraseña: password
INSERT INTO usuarios (nombre_completo, correo, nombre_usuario, contrasena, grado, id_rol, descripcion) VALUES
('Carlos Mendoza Rivera', 'carlos.mendoza@studentnews.edu', 'carlos_escritor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '10°B', 2, 'Escritor apasionado por los deportes y la cultura estudiantil');

-- Usuario lector (rol 1) - contraseña: password
INSERT INTO usuarios (nombre_completo, correo, nombre_usuario, contrasena, grado, id_rol, descripcion) VALUES
('Ana Sofía Hernández', 'ana.hernandez@studentnews.edu', 'ana_sofia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9°C', 1, 'Estudiante entusiasta que le gusta mantenerse informada de las noticias escolares');

-- NOTA: Todos los usuarios tienen la contraseña: password
-- Hash generado con password_hash('password', PASSWORD_DEFAULT)

-- Insertar algunos eventos de ejemplo
INSERT INTO eventos (titulo, descripcion, fecha_evento, lugar) VALUES
('Feria de Ciencias 2024', 'Exposición anual de proyectos científicos de todos los grados', '2024-11-15', 'Auditorio Principal'),
('Torneo Intercolegial de Fútbol', 'Campeonato deportivo entre colegios de la región', '2024-10-20', 'Cancha de Fútbol'),
('Festival Cultural Estudiantil', 'Muestra de talentos artísticos y culturales', '2024-12-05', 'Teatro del Colegio');

-- Insertar algunos artículos de ejemplo
INSERT INTO articulos (titulo, contenido, id_autor, id_categoria, estado, fecha_publicacion, vistas) VALUES
('Bienvenidos al nuevo año escolar', '<p>Este año viene lleno de oportunidades y nuevos retos. Los estudiantes de todos los grados se preparan para un año académico exitoso con nuevas metodologías de enseñanza y proyectos innovadores.</p><p>El equipo directivo ha implementado nuevas estrategias pedagógicas que prometen mejorar la experiencia educativa de todos nuestros estudiantes.</p>', 1, 1, 'publicado', NOW(), 45),

('Tips para estudiar mejor', '<p>Compartimos algunos consejos efectivos para mejorar tus hábitos de estudio:</p><ul><li>Crear un horario de estudio consistente</li><li>Encontrar un lugar tranquilo y bien iluminado</li><li>Tomar descansos regulares</li><li>Usar técnicas de memorización</li><li>Formar grupos de estudio</li></ul><p>Recuerda que cada persona aprende de manera diferente, así que experimenta y encuentra lo que mejor funcione para ti.</p>', 2, 7, 'publicado', NOW(), 32),

('Resultados del torneo de ajedrez', '<p>El torneo anual de ajedrez ha culminado con gran éxito. Los ganadores de cada categoría son:</p><p><strong>Categoría Principiantes:</strong> Ana María Rodríguez (8°A)<br><strong>Categoría Intermedio:</strong> Luis Fernando Gómez (10°B)<br><strong>Categoría Avanzado:</strong> Carolina Herrera (11°A)</p><p>Felicitaciones a todos los participantes por su dedicación y espíritu deportivo.</p>', 3, 4, 'publicado', NOW(), 28);