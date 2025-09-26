-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS periodico_digital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE periodico_digital_db;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(150) UNIQUE NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL, -- Siempre hashear la contraseña
    tipo_usuario ENUM('invitado', 'miembro', 'redactor', 'admin') DEFAULT 'invitado',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de Categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT
);

-- Tabla de Noticias
CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    resumen TEXT,
    imagen_principal VARCHAR(255), -- Ruta de la imagen subida
    id_autor INT NOT NULL, -- Relación con usuarios (redactor)
    id_categoria INT NOT NULL, -- Relación con categorias
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    destacada BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE, -- Para ocultar o mostrar noticias
    FOREIGN KEY (id_autor) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de Comentarios
CREATE TABLE IF NOT EXISTS comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_noticia INT NOT NULL, -- Relación con noticias
    id_usuario INT NOT NULL, -- Relación con usuarios
    contenido TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE, -- Para moderar comentarios
    FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de Reacciones (Simplificada - un usuario puede reaccionar una vez por noticia)
CREATE TABLE IF NOT EXISTS reacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_noticia INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo_reaccion VARCHAR(20) NOT NULL, -- Ej: 'me_gusta', 'me_encanta', 'triste', etc.
    fecha_reaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_post_reaction (id_noticia, id_usuario), -- Un usuario no puede reaccionar dos veces a la misma noticia
    FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de Notificaciones (Simplificada - para mostrar avisos importantes)
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_notificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE
);

-- Tabla de Eventos
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_evento DATE NOT NULL,
    hora_evento TIME,
    lugar VARCHAR(255),
    imagen_evento VARCHAR(255),
    id_creador INT NOT NULL, -- Usuario que crea el evento
    tipo_evento ENUM('academico', 'cultural', 'deportivo', 'institucional', 'otro') DEFAULT 'otro',
    estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') DEFAULT 'programado',
    cupo_maximo INT DEFAULT 0, -- 0 = sin límite
    precio DECIMAL(10,2) DEFAULT 0.00,
    destacado BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_creador) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de Inscripciones a Eventos (para eventos que requieren registro)
CREATE TABLE IF NOT EXISTS inscripciones_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    asistio BOOLEAN DEFAULT FALSE,
    comentarios TEXT,
    UNIQUE KEY unique_user_event (id_evento, id_usuario), -- Un usuario no puede inscribirse dos veces al mismo evento
    FOREIGN KEY (id_evento) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Insertar categorías iniciales
INSERT INTO categorias (nombre, descripcion) VALUES
('Cultura', 'Eventos, arte, literatura y expresiones culturales.'),
('Deportes', 'Actividades, competencias y logros deportivos.'),
('Opinión', 'Artículos y puntos de vista de la comunidad.'),
('Eventos', 'Fechas importantes, actos cívicos, bazares, etc.'),
('Académico', 'Logros, proyectos, concursos y noticias escolares.');

-- Insertar usuarios de ejemplo
-- IMPORTANTE: Todas las contraseñas están hasheadas con password_hash()
INSERT INTO usuarios (nombre, apellido, correo_electronico, contrasena_hash, tipo_usuario) VALUES
('Admin', 'Colegio', 'admin@colegio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- Contraseña: 'admin123'
('María', 'González', 'maria.gonzalez@colegio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'redactor'), -- Contraseña: 'admin123'
('Carlos', 'Rodríguez', 'carlos.rodriguez@colegio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'redactor'), -- Contraseña: 'admin123'
('Ana', 'Martínez', 'ana.martinez@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'miembro'), -- Contraseña: 'admin123'
('Luis', 'Pérez', 'luis.perez@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'miembro'), -- Contraseña: 'admin123'
('Sofia', 'Torres', 'sofia.torres@estudiante.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'miembro'), -- Contraseña: 'admin123'
('Invitado', 'Visitante', 'invitado@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'invitado'); -- Contraseña: 'admin123'

-- Insertar noticias de ejemplo
INSERT INTO noticias (titulo, contenido, resumen, imagen_principal, id_autor, id_categoria, destacada, fecha_publicacion) VALUES
-- Noticias destacadas (destacada = TRUE)
('Festival de Arte Estudiantil 2025', 
'El próximo mes de octubre se llevará a cabo el Festival de Arte Estudiantil más grande del año en nuestro colegio. Este evento reunirá las mejores obras de arte, música, teatro y danza de nuestros talentosos estudiantes.

Durante tres días consecutivos, los estudiantes de todos los grados presentarán sus proyectos artísticos que han estado preparando durante todo el semestre. Habrá exposiciones de pintura, escultura, fotografía, presentaciones musicales, obras de teatro y espectáculos de danza.

El festival no solo es una oportunidad para que nuestros estudiantes muestren su creatividad, sino también para que la comunidad educativa se una y celebre el talento de nuestros jóvenes artistas.

La entrada será gratuita para todos los miembros de la comunidad educativa y sus familias. Se habilitarán espacios especiales para que los padres de familia puedan apreciar de cerca el trabajo de sus hijos.

Además, se realizará una votación popular para elegir las mejores obras de cada categoría, y los ganadores recibirán reconocimientos especiales durante la ceremonia de clausura.',
'El Festival de Arte Estudiantil 2025 reunirá las mejores obras de arte, música, teatro y danza de nuestros estudiantes durante tres días de celebración cultural.',
NULL, 2, 1, TRUE, '2025-09-20 10:00:00'),

('Campeonato Intercolegiado de Fútbol', 
'Nuestro equipo de fútbol masculino ha clasificado a la final del campeonato intercolegiado después de una temporada extraordinaria. El partido final se jugará el próximo sábado en el estadio municipal.

Durante toda la temporada, nuestros jugadores han demostrado un nivel excepcional de juego, trabajo en equipo y deportividad. Bajo la dirección del profesor Carlos Rodríguez, el equipo ha logrado ganar 8 de los 10 partidos jugados, con un rendimiento sobresaliente tanto en defensa como en ataque.

El partido final será contra el Colegio San Andrés, un rival histórico que también ha tenido una temporada excelente. Esperamos que toda la comunidad educativa nos acompañe para apoyar a nuestros representantes.

La entrada al estadio será gratuita para estudiantes y sus familias. Habrá transporte especial desde el colegio para quienes deseen acompañar al equipo.

Este logro es motivo de orgullo para toda nuestra institución y demuestra la calidad de nuestro programa deportivo.',
'Nuestro equipo de fútbol masculino clasificó a la final del campeonato intercolegiado tras una temporada extraordinaria.',
NULL, 3, 2, TRUE, '2025-09-22 14:30:00'),

('Proyecto de Ciencias Ambientales Gana Reconocimiento Nacional', 
'El proyecto de ciencias ambientales desarrollado por estudiantes de grado 11 ha sido reconocido a nivel nacional por su innovación y impacto positivo en la comunidad.

El proyecto, titulado "Huerta Escolar Sostenible", fue desarrollado por un grupo de 15 estudiantes bajo la supervisión de la profesora de biología, María González. La iniciativa consiste en la creación de una huerta escolar que utiliza técnicas de agricultura sostenible y reciclaje de residuos orgánicos.

Durante ocho meses, los estudiantes trabajaron en el diseño e implementación de un sistema de compostaje, cultivo de vegetales orgánicos y educación ambiental para la comunidad. El proyecto no solo ha producido alimentos frescos para el restaurante escolar, sino que también ha servido como laboratorio vivo para las clases de ciencias naturales.

El reconocimiento nacional fue otorgado por el Ministerio de Educación en la categoría de "Mejor Proyecto Ambiental Estudiantil 2025". Los estudiantes recibirán una beca parcial para estudios universitarios y el colegio obtendrá financiación para expandir el proyecto.

Esta es una muestra más del compromiso de nuestra institución con la educación integral y el cuidado del medio ambiente.',
'Estudiantes de grado 11 obtienen reconocimiento nacional por su innovador proyecto de huerta escolar sostenible.',
NULL, 2, 5, TRUE, '2025-09-18 09:15:00'),

-- Noticias generales (destacada = FALSE)
('Semana Cultural: Celebrando Nuestras Tradiciones', 
'Del 15 al 19 de octubre se celebrará la Semana Cultural en nuestro colegio, un evento que busca rescatar y celebrar las tradiciones de nuestra región.

Durante esta semana, los estudiantes participarán en diversas actividades como concursos de danza folclórica, exposiciones gastronómicas, talleres de artesanías tradicionales y presentaciones musicales típicas.

Cada día estará dedicado a un aspecto diferente de nuestra cultura: música, danza, gastronomía, artesanías y literatura. Los estudiantes han estado preparándose durante varias semanas para mostrar lo mejor de nuestras tradiciones.

La comunidad está invitada a participar y disfrutar de todas las actividades programadas. Será una excelente oportunidad para que nuestros jóvenes conozcan y valoren su patrimonio cultural.',
'La Semana Cultural del 15 al 19 de octubre celebrará las tradiciones regionales con danza, música, gastronomía y artesanías.',
NULL, 2, 1, FALSE, '2025-09-25 11:00:00'),

('Nuevo Laboratorio de Informática', 
'El colegio ha inaugurado un nuevo laboratorio de informática equipado con la última tecnología para mejorar la educación digital de nuestros estudiantes.

El laboratorio cuenta con 30 computadores de última generación, conexión a internet de alta velocidad, proyector interactivo y software especializado para diferentes áreas del conocimiento.

Esta inversión forma parte del plan de modernización tecnológica que busca preparar mejor a nuestros estudiantes para los desafíos del siglo XXI. El laboratorio será utilizado para clases de informática, robótica y programación.

Los estudiantes podrán desarrollar proyectos de innovación tecnológica y participar en competencias de programación a nivel regional y nacional.',
'Nuevo laboratorio de informática con tecnología de punta mejorará la educación digital de los estudiantes.',
NULL, 1, 5, FALSE, '2025-09-23 16:20:00'),

('Charla sobre Orientación Vocacional para Estudiantes de Grado 11', 
'El próximo viernes 29 de septiembre se realizará una charla especial sobre orientación vocacional dirigida a los estudiantes de grado 11 que se preparan para ingresar a la educación superior.

La charla será dictada por profesionales de diferentes universidades de la región y abordará temas como elección de carrera, proceso de admisión universitaria, becas y financiación de estudios.

Los estudiantes tendrán la oportunidad de hacer preguntas directamente a los expertos y recibir orientación personalizada sobre sus opciones académicas y profesionales.

Esta actividad forma parte del programa de orientación vocacional que el colegio desarrolla para apoyar a sus estudiantes en la transición hacia la educación superior.',
'Charla sobre orientación vocacional ayudará a estudiantes de grado 11 en la elección de su futuro académico.',
NULL, 2, 5, FALSE, '2025-09-24 13:45:00'),

('Celebración del Día del Maestro', 
'El pasado 15 de mayo celebramos el Día del Maestro con una ceremonia especial en reconocimiento a la labor de nuestros dedicados educadores.

Durante la ceremonia, se destacó el trabajo de varios profesores que han demostrado excelencia en su labor pedagógica y compromiso con la formación integral de los estudiantes.

Los estudiantes prepararon presentaciones artísticas y entregaron reconocimientos simbólicos a sus maestros favoritos. Fue una jornada llena de emociones y gratitud hacia quienes dedican su vida a la educación.

La celebración incluyó un almuerzo especial y actividades recreativas para toda la comunidad educativa.',
'Celebración especial del Día del Maestro reconoce la dedicada labor de nuestros educadores.',
NULL, 3, 4, FALSE, '2025-05-16 10:30:00'),

('Torneo de Ajedrez Estudiantil', 
'Se llevó a cabo el torneo anual de ajedrez estudiantil con la participación de más de 50 estudiantes de todos los grados.

El torneo se desarrolló durante tres días en modalidad suiza, con partidas de diferentes categorías según la edad de los participantes. Los ganadores de cada categoría recibieron medallas y trofeos.

El ajedrez es una disciplina que se ha fortalecido mucho en nuestro colegio, desarrollando en los estudiantes habilidades de pensamiento lógico, concentración y toma de decisiones.

Los estudiantes ganadores representarán al colegio en el campeonato intercolegiado de ajedrez que se realizará el próximo mes.',
'Más de 50 estudiantes participaron en el torneo anual de ajedrez, fortaleciendo habilidades de pensamiento lógico.',
NULL, 3, 2, FALSE, '2025-09-21 15:00:00'),

('Campaña de Reciclaje Alcanza sus Objetivos', 
'La campaña de reciclaje "Colegio Verde" ha superado todas las expectativas, recolectando más de 500 kilogramos de material reciclable en su primer mes.

La iniciativa, liderada por el comité ambiental estudiantil, ha logrado involucrar a toda la comunidad educativa en prácticas sostenibles y cuidado del medio ambiente.

Se han instalado puntos de recolección selectiva en diferentes áreas del colegio y se han realizado talleres educativos sobre la importancia del reciclaje y la reducción de residuos.

Los materiales recolectados serán entregados a empresas recicladoras locales, y los recursos obtenidos se destinarán a proyectos ambientales del colegio.',
'La campaña "Colegio Verde" recolectó más de 500 kg de material reciclable, superando las expectativas.',
NULL, 2, 5, FALSE, '2025-09-19 12:10:00'),

('Taller de Escritura Creativa para Estudiantes', 
'Se ha inaugurado un taller de escritura creativa dirigido a estudiantes interesados en desarrollar sus habilidades literarias y expresión escrita.

El taller es dirigido por la profesora de español y literatura, y se realizará todos los miércoles en horario extracurricular. Los estudiantes trabajarán en diferentes géneros literarios como cuento, poesía y ensayo.

Al final del semestre, se publicará una antología con los mejores trabajos de los participantes, la cual será distribuida en la comunidad educativa.

Esta iniciativa busca fomentar la creatividad, mejorar la expresión escrita y descubrir nuevos talentos literarios entre nuestros estudiantes.',
'Nuevo taller de escritura creativa ayudará a estudiantes a desarrollar habilidades literarias y expresión escrita.',
NULL, 2, 1, FALSE, '2025-09-17 14:20:00');

-- Insertar comentarios de ejemplo
INSERT INTO comentarios (id_noticia, id_usuario, contenido, fecha_comentario) VALUES
(1, 4, '¡Qué emocionante! No puedo esperar a ver todas las presentaciones artísticas. Especialmente las obras de teatro.', '2025-09-20 12:30:00'),
(1, 5, 'Mi hermana está participando en la exposición de pintura. Toda la familia iremos a apoyarla.', '2025-09-20 15:45:00'),
(1, 6, 'Es genial que el colegio promueva tanto el arte. Esto ayuda mucho al desarrollo integral de los estudiantes.', '2025-09-21 09:15:00'),

(2, 4, '¡Vamos equipo! Hemos esperado mucho tiempo para llegar a la final. Todo el colegio los apoya.', '2025-09-22 16:00:00'),
(2, 5, 'El profesor Carlos ha hecho un trabajo increíble con el equipo. Merecen ganar esta final.', '2025-09-22 18:20:00'),
(2, 6, 'Estaré ahí el sábado gritando por nuestro equipo. ¡Vamos Gonzalo Mejía!', '2025-09-23 07:30:00'),

(3, 4, 'Felicitaciones a todos los estudiantes involucrados. Es un orgullo para el colegio este reconocimiento.', '2025-09-18 11:00:00'),
(3, 5, 'La profesora María González siempre motiva a sus estudiantes a dar lo mejor. Este premio es muy merecido.', '2025-09-18 14:25:00'),

(4, 6, 'Me encanta que celebremos nuestras tradiciones. Voy a participar en el concurso de danza folclórica.', '2025-09-25 13:10:00'),
(4, 4, 'La gastronomía tradicional siempre es mi parte favorita de la semana cultural.', '2025-09-25 16:30:00'),

(5, 5, 'Por fin tenemos un laboratorio moderno. Esto va a mejorar mucho nuestras clases de informática.', '2025-09-23 17:45:00'),
(5, 6, 'Espero que pronto podamos aprender programación avanzada con estos nuevos equipos.', '2025-09-24 08:15:00'),

(6, 4, 'Esta charla llegó en el momento perfecto. Tengo muchas dudas sobre qué carrera estudiar.', '2025-09-24 14:50:00'),

(7, 5, 'Los maestros de nuestro colegio son los mejores. Se merecen todo nuestro reconocimiento.', '2025-05-16 12:40:00'),

(8, 6, 'Me encanta jugar ajedrez. El próximo año definitivamente voy a participar en el torneo.', '2025-09-21 16:25:00'),

(9, 4, 'Es importante que todos contribuyamos al cuidado del medio ambiente. Excelente iniciativa.', '2025-09-19 13:35:00'),

(10, 5, 'Siempre me ha gustado escribir. Definitivamente voy a inscribirme en este taller.', '2025-09-17 15:50:00');

-- Insertar algunas reacciones de ejemplo
INSERT INTO reacciones (id_noticia, id_usuario, tipo_reaccion, fecha_reaccion) VALUES
(1, 4, 'me_gusta', '2025-09-20 12:35:00'),
(1, 5, 'me_encanta', '2025-09-20 15:50:00'),
(1, 6, 'me_gusta', '2025-09-21 09:20:00'),
(2, 4, 'me_encanta', '2025-09-22 16:05:00'),
(2, 5, 'me_gusta', '2025-09-22 18:25:00'),
(2, 6, 'me_encanta', '2025-09-23 07:35:00'),
(3, 4, 'me_encanta', '2025-09-18 11:05:00'),
(3, 5, 'me_gusta', '2025-09-18 14:30:00'),
(4, 6, 'me_gusta', '2025-09-25 13:15:00'),
(4, 4, 'me_gusta', '2025-09-25 16:35:00'),
(5, 5, 'me_gusta', '2025-09-23 17:50:00'),
(5, 6, 'me_encanta', '2025-09-24 08:20:00'),
(6, 4, 'me_gusta', '2025-09-24 14:55:00'),
(7, 5, 'me_encanta', '2025-05-16 12:45:00'),
(8, 6, 'me_gusta', '2025-09-21 16:30:00'),
(9, 4, 'me_gusta', '2025-09-19 13:40:00'),
(10, 5, 'me_encanta', '2025-09-17 15:55:00');

-- Insertar notificaciones de ejemplo
INSERT INTO notificaciones (titulo, mensaje, fecha_notificacion) VALUES
('Bienvenido al Periódico Digital', 'Mantente informado con las últimas noticias y eventos de nuestro colegio.', '2025-09-15 08:00:00'),
('Festival de Arte Estudiantil', 'No te pierdas el Festival de Arte que se realizará en octubre. ¡Inscripciones abiertas!', '2025-09-20 10:30:00'),
('Final de Fútbol', 'Apoya a nuestro equipo en la final del campeonato intercolegiado este sábado.', '2025-09-22 15:00:00'),
('Mantenimiento del Sistema', 'El sistema estará en mantenimiento el domingo de 2:00 AM a 6:00 AM.', '2025-09-24 20:00:00');

-- Insertar eventos de ejemplo
INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_evento, lugar, tipo_evento, id_creador, destacado, cupo_maximo, precio) VALUES
('Festival de Arte Estudiantil 2025', 
'Gran festival donde los estudiantes presentarán sus mejores obras de arte, música, teatro y danza. Tres días de celebración cultural con exposiciones, presentaciones y concursos.', 
'2025-10-15', '09:00:00', 'Auditorio Principal y Patio Central', 'cultural', 1, TRUE, 0, 0.00),

('Final Campeonato Intercolegiado', 
'Partido final del campeonato intercolegiado de fútbol. Nuestro equipo se enfrentará al Colegio San Andrés por el título.', 
'2025-10-28', '15:00:00', 'Estadio Municipal', 'deportivo', 1, TRUE, 500, 0.00),

('Semana de la Ciencia y Tecnología', 
'Exposición de proyectos científicos estudiantiles, talleres de robótica, experimentos en vivo y conferencias con expertos en tecnología.', 
'2025-11-05', '08:00:00', 'Laboratorios y Aulas de Ciencias', 'academico', 2, TRUE, 0, 0.00),

('Feria Gastronómica Tradicional', 
'Celebración de la gastronomía tradicional colombiana con la participación de estudiantes y padres de familia. Habrá degustaciones y concursos culinarios.', 
'2025-11-20', '10:00:00', 'Cafetería y Patio Central', 'cultural', 3, FALSE, 0, 5000.00),

('Conferencia de Orientación Vocacional', 
'Charla dirigida a estudiantes de grados 10° y 11° sobre opciones de carrera universitaria y técnica. Participarán universidades y institutos técnicos de la región.', 
'2025-12-03', '14:00:00', 'Auditorio Principal', 'academico', 2, FALSE, 200, 0.00),

('Torneo de Baloncesto Intercursos', 
'Campeonato interno de baloncesto entre todos los cursos del colegio. Categorías masculina y femenina.', 
'2025-12-10', '08:00:00', 'Cancha de Baloncesto', 'deportivo', 3, FALSE, 0, 0.00),

('Posadas Navideñas Institucionales', 
'Celebración tradicional navideña con villancicos, obra de teatro navideña, intercambio de regalos y cena comunitaria.', 
'2025-12-18', '18:00:00', 'Auditorio Principal', 'institucional', 1, TRUE, 300, 15000.00),

('Taller de Periodismo Digital', 
'Taller práctico para estudiantes interesados en el periodismo digital. Se enseñarán técnicas de redacción, fotografía y manejo de redes sociales.', 
'2026-01-25', '13:00:00', 'Sala de Informática', 'academico', 2, FALSE, 25, 0.00);

-- Insertar algunas inscripciones de ejemplo
INSERT INTO inscripciones_eventos (id_evento, id_usuario) VALUES
(1, 4), (1, 5), (1, 6), -- Festival de Arte
(2, 4), (2, 5), (2, 6), (2, 7), -- Final de Fútbol
(3, 5), (3, 6), -- Semana de Ciencia
(5, 4), (5, 5), -- Orientación Vocacional
(7, 4), (7, 5), (7, 6), -- Posadas Navideñas
(8, 4), (8, 5); -- Taller de Periodismo

