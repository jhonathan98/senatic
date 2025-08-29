<?php
require_once 'db_connection.php';

// Function to hash passwords
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to get user by ID
function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Function to get user by username or email
function get_user_by_username_or_email($username_or_email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username_or_email, $username_or_email]);
    return $stmt->fetch();
}

// Function to get books
function get_books() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM books ORDER BY title");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get available books
function get_available_books() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT b.* FROM books b LEFT JOIN borrowed_books bb ON b.id = bb.book_id AND bb.status = 'active' WHERE bb.id IS NULL OR bb.id IS NOT NULL AND bb.status = 'returned'");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get user's borrowed books
function get_user_borrowed_books($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.cover_image FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.user_id = ? AND bb.status = 'active'");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Function to get user's history of borrowed books
function get_user_borrow_history($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.cover_image FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.user_id = ? AND bb.status IN ('returned', 'overdue')");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Function to borrow a book
function borrow_book($user_id, $book_id) {
    global $pdo;
    
    // Check if book is available
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND availability = 'available'");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        return false;
    }
    
    // Check if user already has this book
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status = 'active'");
    $stmt->execute([$user_id, $book_id]);
    if ($stmt->rowCount() > 0) {
        return false;
    }
    
    // Calculate due date (14 days from now)
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+14 days'));
    
    // Insert into borrowed_books table
    $stmt = $pdo->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);
    
    // Update book availability
    $stmt = $pdo->prepare("UPDATE books SET copies_available = copies_available - 1 WHERE id = ?");
    $stmt->execute([$book_id]);
    
    return true;
}

// Function to return a book
function return_book($borrow_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE id = ?");
    $stmt->execute([$borrow_id]);
    $borrow = $stmt->fetch();
    
    if (!$borrow || $borrow['status'] !== 'active') {
        return false;
    }
    
    // Update book availability
    $stmt = $pdo->prepare("UPDATE books SET copies_available = copies_available + 1 WHERE id = ?");
    $stmt->execute([$borrow['book_id']]);
    
    // Update borrowing record
    $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'returned', return_date = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $borrow_id]);
    
    return true;
}

// Function to renew a book
function renew_book($borrow_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE id = ?");
    $stmt->execute([$borrow_id]);
    $borrow = $stmt->fetch();
    
    if (!$borrow || $borrow['status'] !== 'active') {
        return false;
    }
    
    // Check if renewal limit reached
    $stmt = $pdo->prepare("SELECT COUNT(*) as renewal_count FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status = 'active'");
    $stmt->execute([$borrow['user_id'], $borrow['book_id']]);
    $renewal_count = $stmt->fetch()['renewal_count'];
    
    if ($renewal_count >= 2) {
        return false;
    }
    
    // Calculate new due date (14 days from now)
    $new_due_date = date('Y-m-d', strtotime('+14 days'));
    
    // Update borrowing record
    $stmt = $pdo->prepare("UPDATE borrowed_books SET due_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
    $stmt->execute([$new_due_date, $borrow_id]);
    
    return true;
}

// Function to get books by category
function get_books_by_category($category_slug) {
    global $pdo;
    
    // Convertir el slug de vuelta a formato normal
    $category = ucwords(str_replace('-', ' ', $category_slug));
    
    $sql = "SELECT * FROM books WHERE category = ? AND copies_available > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category]);
    
    return $stmt->fetchAll();
}

// Function to get books by search query and category
function get_books_by_search_and_category($search_query, $category) {
    global $pdo;
    
    if ($category === 'all') {
        $sql = "SELECT * FROM books WHERE (title LIKE ? OR author LIKE ?) AND copies_available > 0";
        $search_param = "%{$search_query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_param, $search_param]);
    } else {
        $sql = "SELECT * FROM books WHERE (title LIKE ? OR author LIKE ?) AND category = ? AND copies_available > 0";
        $search_param = "%{$search_query}%";
        $stmt = $pdo->prepare($sql);
        $category_name = ucwords(str_replace('-', ' ', $category));
        $stmt->execute([$search_param, $search_param, $category_name]);
    }
    
    return $stmt->fetchAll();
}

// Function to get all unique book categories
function get_unique_categories() {
    global $pdo;
    
    $sql = "SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category";
    $stmt = $pdo->query($sql);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Function to get book details by ID
function get_book_by_id($book_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    return $stmt->fetch();
}

// Function to process book borrowing with custom dates
function borrow_book_with_dates($user_id, $book_id, $borrow_date, $due_date) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Check if book is available
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND copies_available > 0");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            return ['success' => false, 'message' => 'El libro no está disponible.'];
        }
        
        // Check if user already has this book
        $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status = 'active'");
        $stmt->execute([$user_id, $book_id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Ya tienes este libro prestado.'];
        }
        
        // Validate dates
        $borrow_timestamp = strtotime($borrow_date);
        $due_timestamp = strtotime($due_date);
        $today = strtotime(date('Y-m-d'));
        
        if ($borrow_timestamp < $today) {
            return ['success' => false, 'message' => 'La fecha de préstamo no puede ser anterior a hoy.'];
        }
        
        if ($due_timestamp <= $borrow_timestamp) {
            return ['success' => false, 'message' => 'La fecha de devolución debe ser posterior a la fecha de préstamo.'];
        }
        
        // Insert into borrowed_books table
        $stmt = $pdo->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);
        
        // Update book availability
        $stmt = $pdo->prepare("UPDATE books SET copies_available = copies_available - 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Libro prestado exitosamente.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al procesar el préstamo: ' . $e->getMessage()];
    }
}
?>