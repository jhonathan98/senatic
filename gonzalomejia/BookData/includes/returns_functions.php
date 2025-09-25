<?php
/**
 * Returns Management Functions
 * Functions specific to handling book returns and overdue management
 */

/**
 * Get overdue books count for a specific user or all users
 */
function get_overdue_count($user_id = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as count FROM borrowed_books WHERE status = 'overdue'";
    $params = [];
    
    if ($user_id) {
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['count'];
}

/**
 * Get books due soon (within specified days)
 */
function get_due_soon_count($days = 3, $user_id = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as count FROM borrowed_books 
            WHERE status = 'active' 
            AND due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params = [$days];
    
    if ($user_id) {
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['count'];
}

/**
 * Send reminder notification (placeholder for email/SMS integration)
 */
function send_return_reminder($borrow_id, $type = 'overdue') {
    global $pdo;
    
    // Get borrow details
    $stmt = $pdo->prepare("
        SELECT bb.*, b.title, b.author, u.full_name, u.email
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.id
        JOIN users u ON bb.user_id = u.id
        WHERE bb.id = ?
    ");
    $stmt->execute([$borrow_id]);
    $borrow = $stmt->fetch();
    
    if (!$borrow) {
        return false;
    }
    
    // Log the reminder (in a real app, this would send email/SMS)
    $log_message = date('Y-m-d H:i:s') . " - Reminder sent to {$borrow['email']} for book '{$borrow['title']}' ({$type})\n";
    file_put_contents('logs/notifications.log', $log_message, FILE_APPEND | LOCK_EX);
    
    // Here you would integrate with email service (PHPMailer, SendGrid, etc.)
    // Example:
    // send_email($borrow['email'], $subject, $message);
    
    return true;
}

/**
 * Calculate fine for overdue book
 */
function calculate_overdue_fine($borrow_id, $daily_fine = 1.0) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT DATEDIFF(CURDATE(), due_date) as days_overdue
        FROM borrowed_books 
        WHERE id = ? AND status = 'overdue'
    ");
    $stmt->execute([$borrow_id]);
    $result = $stmt->fetch();
    
    if (!$result || $result['days_overdue'] <= 0) {
        return 0;
    }
    
    return $result['days_overdue'] * $daily_fine;
}

/**
 * Get return statistics for reports
 */
function get_return_statistics($start_date = null, $end_date = null) {
    global $pdo;
    
    $where_clause = "";
    $params = [];
    
    if ($start_date && $end_date) {
        $where_clause = "WHERE return_date BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_returns,
            COUNT(CASE WHEN return_date > due_date THEN 1 END) as late_returns,
            COUNT(CASE WHEN return_date <= due_date THEN 1 END) as on_time_returns,
            AVG(DATEDIFF(return_date, borrow_date)) as avg_borrow_duration,
            AVG(CASE WHEN return_date > due_date THEN DATEDIFF(return_date, due_date) ELSE 0 END) as avg_days_late
        FROM borrowed_books 
        WHERE status = 'returned' {$where_clause}
    ");
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Auto-renew book if eligible
 */
function auto_renew_book($borrow_id, $days_to_extend = 14) {
    global $pdo;
    
    // Check if book is eligible for renewal (not already renewed too many times)
    $stmt = $pdo->prepare("
        SELECT renewal_count, book_id
        FROM borrowed_books 
        WHERE id = ? AND status = 'active' AND renewal_count < 2
    ");
    $stmt->execute([$borrow_id]);
    $borrow = $stmt->fetch();
    
    if (!$borrow) {
        return false; // Not eligible for renewal
    }
    
    // Check if book has waiting list (future enhancement)
    // For now, just renew
    
    $new_due_date = date('Y-m-d', strtotime("+{$days_to_extend} days"));
    
    $stmt = $pdo->prepare("
        UPDATE borrowed_books 
        SET due_date = ?, renewal_count = renewal_count + 1, status = 'active'
        WHERE id = ?
    ");
    
    return $stmt->execute([$new_due_date, $borrow_id]);
}

/**
 * Mark multiple books as returned (bulk operation)
 */
function bulk_return_books($borrow_ids) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        foreach ($borrow_ids as $borrow_id) {
            // Get book_id for updating availability
            $stmt = $pdo->prepare("SELECT book_id FROM borrowed_books WHERE id = ?");
            $stmt->execute([$borrow_id]);
            $book_id = $stmt->fetchColumn();
            
            if ($book_id) {
                // Update borrow record
                $stmt = $pdo->prepare("
                    UPDATE borrowed_books 
                    SET status = 'returned', return_date = CURDATE() 
                    WHERE id = ?
                ");
                $stmt->execute([$borrow_id]);
                
                // Update book availability
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_quantity = available_quantity + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$book_id]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk return error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update overdue status for all active loans
 */
function update_overdue_status() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE borrowed_books 
        SET status = 'overdue' 
        WHERE status = 'active' 
        AND due_date < CURDATE()
    ");
    
    return $stmt->execute();
}

/**
 * Get user's borrowing history with return information
 */
function get_user_return_history($user_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT bb.*, b.title, b.author, b.cover_image,
               DATEDIFF(bb.return_date, bb.due_date) as days_late,
               CASE 
                   WHEN bb.return_date IS NULL THEN 'active'
                   WHEN bb.return_date <= bb.due_date THEN 'on_time'
                   ELSE 'late'
               END as return_status
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.id
        WHERE bb.user_id = ?
        ORDER BY bb.borrow_date DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}
?>
