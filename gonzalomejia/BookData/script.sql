-- MySQL Script for BookData Application

-- Create database
CREATE DATABASE IF NOT EXISTS bookdata;
USE bookdata;

-- Drop and recreate books table to ensure correct column names
DROP TABLE IF EXISTS borrowed_books;
DROP TABLE IF EXISTS book_reviews;
DROP TABLE IF EXISTS books;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    category VARCHAR(50),
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    availability ENUM('available', 'borrowed', 'unavailable') DEFAULT 'available',
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    publication_year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_availability (availability),
    INDEX idx_isbn (isbn)
);

-- Borrowed books table
CREATE TABLE borrowed_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('active', 'returned', 'overdue', 'pending') DEFAULT 'active',
    renewal_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id),
    INDEX idx_user_id (user_id),
    INDEX idx_book_id (book_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- Book reviews table
CREATE TABLE book_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_review (user_id, book_id),
    INDEX idx_user_id (user_id),
    INDEX idx_book_id (book_id)
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Insert sample data
INSERT INTO categories (name, description) VALUES 
('Ficción', 'Libros de ficción'),
('No ficción', 'Libros de no ficción'),
('Ciencia', 'Libros científicos'),
('Historia', 'Libros históricos'),
('Romance', 'Novelas románticas'),
('Misterio', 'Novelas de misterio'),
('Fantasía', 'Libros de fantasía'),
('Ciencia ficción', 'Libros de ciencia ficción'),
('Narrativo', 'Libros narrativos'),
('Ficción paranormal', 'Libros de ficción paranormal'),
('Poema épico', 'Poemas épicos'),
('Magia', 'Libros de magia y aventura');

-- Insert two administrators (password: "password")
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin1', 'admin1@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Principal', 'admin'),
('admin2', 'admin2@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Secundario', 'admin');

-- Sample books
INSERT INTO books (title, author, category, description, cover_image, availability, quantity, available_quantity, publication_year) VALUES 
('El Principito', 'Antoine de Saint-Exupéry', 'Ficción', 'Una fábula sobre la amistad, el amor y la importancia de ver con el corazón.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 3, 3, 1943),
('Ana de las Tejas Verdes', 'Lucy Maud Montgomery', 'Ficción', 'Una huérfana soñadora transforma la vida de quienes la rodean.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 2, 2, 1908),
('La Vida del Espacio', 'Albert Einstein', 'Ciencia', 'Explora cómo la teoría de la relatividad revolucionó nuestra visión del universo.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 1, 1, 1915),
('El libro que lee a personas', 'Autor desconocido', 'Fantasía', '"El libro que lee a personas" es una intrigante historia sobre un misterioso libro capaz de revelar los pensamientos, emociones y secretos más profundos de quienes lo abren.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 1, 1, 2023),
('El retrato de Dorian Gray', 'Oscar Wilde', 'Ficción', 'Una historia sobre la belleza, la juventud y la corrupción moral.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 2, 2, 1890),
('El rastro de tu sangre en la nieve', 'Gabriel García Márquez', 'Narrativo', 'Una novela sobre el poder de la memoria y la identidad.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 1, 1, 1985),
('Metamorfosis', 'Franz Kafka', 'Ciencia Ficción', 'Un hombre se despierta como un insecto gigante y debe enfrentar las consecuencias.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 3, 3, 1915),
('Coraline', 'Neil Gaiman', 'Ficción paranormal', 'Una niña descubre un mundo paralelo que parece perfecto pero tiene secretos oscuros.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 2, 2, 2002),
('La Divina Comedia', 'Dante Alighieri', 'Poema épico', 'Un viaje por el infierno, el purgatorio y el cielo.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 1, 1, 1320),
('Harry Potter y la piedra filosofal', 'J.K. Rowling', 'Magia', 'La historia de Harry Potter, un niño mago que descubre su verdadera identidad.', 'https://img.lovepik.com/png/20231109/book-cartoon-illustration-school-start-reading-reading-book_539915_wh1200.png', 'available', 5, 5, 1997);

-- Insert sample users (password: "password")
INSERT INTO users (username, email, password, full_name, role) VALUES 
('estudiante1', 'estudiante1@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Yeshuah Bedoya', 'student'),
('estudiante2', 'estudiante2@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emmanuel Rivas', 'student'),
('estudiante3', 'estudiante3@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alison Ramirez', 'student'),
('estudiante4', 'estudiante4@bookdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Isabella Blanco', 'student');

-- Sample borrowed books for testing the pending returns functionality
INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES 
-- Overdue books
(3, 1, '2024-09-01', '2024-09-15', 'overdue'),
(4, 3, '2024-09-05', '2024-09-19', 'overdue'),
-- Due soon books  
(3, 5, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active'),
(4, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'active'),
-- Normal active books
(3, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
(4, 4, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'active');

-- Update book availability for borrowed books
UPDATE books SET available_quantity = available_quantity - 1 WHERE id IN (1, 2, 3, 4, 5, 7);

-- Create a procedure to update overdue books automatically
/*DELIMITER //
CREATE PROCEDURE UpdateOverdueBooks()
BEGIN
    UPDATE borrowed_books 
    SET status = 'overdue' 
    WHERE status = 'active' 
    AND due_date < CURDATE();
END //
DELIMITER ;
*/
-- Create an event to run the procedure daily (requires event scheduler to be enabled)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS UpdateOverdueDaily
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_DATE + INTERVAL 1 DAY
-- DO CALL UpdateOverdueBooks();

-- Note: Additional indexes are already created in table definitions above