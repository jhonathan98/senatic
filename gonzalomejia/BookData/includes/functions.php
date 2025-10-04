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
    $stmt = $pdo->prepare("SELECT * FROM books WHERE available_quantity > 0 ORDER BY title");
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
    
    // Check if user already has this book (active or overdue)
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status IN ('active', 'overdue')");
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
    $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?");
    $stmt->execute([$book_id]);
    
    return true;
}

// Function to return a book
function return_book($borrow_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get the borrowed book information
        $stmt = $pdo->prepare("SELECT book_id FROM borrowed_books WHERE id = ? AND status IN ('active', 'overdue')");
        $stmt->execute([$borrow_id]);
        $borrow = $stmt->fetch();
        
        if (!$borrow) {
            $pdo->rollBack();
            return false;
        }
        
        // Update the borrowed book status
        $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'returned', return_date = NOW() WHERE id = ?");
        $stmt->execute([$borrow_id]);
        
        // Increase available quantity
        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?");
        $stmt->execute([$borrow['book_id']]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
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
    
    $sql = "SELECT * FROM books WHERE category = ? AND available_quantity > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category]);
    
    return $stmt->fetchAll();
}

// Function to get books by search query and category
function get_books_by_search_and_category($search_query, $category) {
    global $pdo;
    
    if ($category === 'all') {
        $sql = "SELECT * FROM books WHERE (title LIKE ? OR author LIKE ?) AND available_quantity > 0";
        $search_param = "%{$search_query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_param, $search_param]);
    } else {
        $sql = "SELECT * FROM books WHERE (title LIKE ? OR author LIKE ?) AND category = ? AND available_quantity > 0";
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
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND available_quantity > 0");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            return ['success' => false, 'message' => 'El libro no está disponible.'];
        }
        
        // Clean up any inconsistent states and check if user can borrow this book
        if (!check_and_clean_borrow_state($user_id, $book_id)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Ya tienes este libro prestado o vencido. Debes devolverlo primero.'];
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
        
        // Insert into borrowed_books table with error handling
        try {
            $stmt = $pdo->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'active')");
            $result = $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);
            
            if (!$result) {
                throw new Exception("Error al insertar el registro de préstamo");
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Integrity constraint violation
                return ['success' => false, 'message' => 'Ya existe un préstamo activo para este libro. Por favor, verifica el estado de tus préstamos.'];
            } else {
                return ['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()];
            }
        }
        
        // Update book availability
        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Libro prestado exitosamente.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al procesar el préstamo: ' . $e->getMessage()];
    }
}

// Function to get user's borrowed books with detailed information
function get_user_borrowed_books_detailed($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT bb.*, b.title, b.author, b.cover_image, b.category,
               DATEDIFF(bb.due_date, CURDATE()) as days_until_due,
               CASE 
                   WHEN bb.due_date < CURDATE() THEN 'overdue'
                   WHEN DATEDIFF(bb.due_date, CURDATE()) <= 3 THEN 'due_soon'
                   ELSE 'active'
               END as status_detail
        FROM borrowed_books bb 
        JOIN books b ON bb.book_id = b.id 
        WHERE bb.user_id = ? AND bb.status = 'active'
        ORDER BY bb.due_date ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Function to get user's history with more details
function get_user_borrow_history_detailed($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT bb.*, b.title, b.author, b.cover_image, b.category,
               DATEDIFF(COALESCE(bb.return_date, bb.due_date), bb.borrow_date) as days_borrowed,
               CASE 
                   WHEN bb.return_date IS NOT NULL AND bb.return_date <= bb.due_date THEN 'returned_on_time'
                   WHEN bb.return_date IS NOT NULL AND bb.return_date > bb.due_date THEN 'returned_late'
                   ELSE bb.status
               END as return_status
        FROM borrowed_books bb 
        JOIN books b ON bb.book_id = b.id 
        WHERE bb.user_id = ? AND bb.status IN ('returned', 'overdue')
        ORDER BY COALESCE(bb.return_date, bb.due_date) DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Function to get user borrowing statistics
function get_user_borrow_stats($user_id) {
    global $pdo;
    
    $stats = [];
    
    // Active borrows
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $stats['active_borrows'] = $stmt->fetch()['count'];
    
    // Overdue borrows
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ? AND status = 'overdue'");
    $stmt->execute([$user_id]);
    $stats['overdue_borrows'] = $stmt->fetch()['count'];
    
    // Due soon (next 3 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ? AND status = 'active' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
    $stmt->execute([$user_id]);
    $stats['due_soon'] = $stmt->fetch()['count'];
    
    // Total historical borrows
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_borrows'] = $stmt->fetch()['count'];
    
    // Books returned on time
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ? AND status = 'returned' AND return_date <= due_date");
    $stmt->execute([$user_id]);
    $stats['returned_on_time'] = $stmt->fetch()['count'];
    
    // Average borrowing days
    $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(COALESCE(return_date, CURDATE()), borrow_date)) as avg_days FROM borrowed_books WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['avg_borrow_days'] = round($stmt->fetch()['avg_days'] ?? 0, 1);
    
    return $stats;
}

// Function to check and clean inconsistent borrow states
function check_and_clean_borrow_state($user_id, $book_id) {
    global $pdo;
    
    try {
        // Check for any existing borrows for this user-book combination
        $stmt = $pdo->prepare("
            SELECT id, status, due_date, return_date 
            FROM borrowed_books 
            WHERE user_id = ? AND book_id = ?
            ORDER BY borrow_date DESC
        ");
        $stmt->execute([$user_id, $book_id]);
        $existing_borrows = $stmt->fetchAll();
        
        foreach ($existing_borrows as $borrow) {
            // If there's a borrow without return_date but marked as returned, fix it
            if ($borrow['status'] === 'returned' && $borrow['return_date'] === null) {
                $stmt = $pdo->prepare("UPDATE borrowed_books SET return_date = NOW() WHERE id = ?");
                $stmt->execute([$borrow['id']]);
            }
            
            // If there's an overdue borrow that should be marked as such
            if ($borrow['status'] === 'active' && $borrow['due_date'] < date('Y-m-d')) {
                $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'overdue' WHERE id = ?");
                $stmt->execute([$borrow['id']]);
            }
        }
        
        // Check again for active/overdue borrows after cleanup
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM borrowed_books 
            WHERE user_id = ? AND book_id = ? AND status IN ('active', 'overdue')
        ");
        $stmt->execute([$user_id, $book_id]);
        $active_count = $stmt->fetch()['count'];
        
        return $active_count === 0;
        
    } catch (Exception $e) {
        return false;
    }
}

// Function to get detailed borrow status for debugging
function get_user_book_borrow_status($user_id, $book_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT bb.*, b.title, b.author,
               CASE 
                   WHEN bb.status = 'active' AND bb.due_date < CURDATE() THEN 'overdue_not_updated'
                   ELSE bb.status
               END as actual_status
        FROM borrowed_books bb 
        JOIN books b ON bb.book_id = b.id 
        WHERE bb.user_id = ? AND bb.book_id = ?
        ORDER BY bb.borrow_date DESC
    ");
    $stmt->execute([$user_id, $book_id]);
    return $stmt->fetchAll();
}

// Function to update overdue books status
function update_overdue_books() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE borrowed_books 
            SET status = 'overdue' 
            WHERE status = 'active' AND due_date < CURDATE()
        ");
        $result = $stmt->execute();
        return $stmt->rowCount(); // Returns number of updated records
    } catch (Exception $e) {
        return false;
    }
}
?>