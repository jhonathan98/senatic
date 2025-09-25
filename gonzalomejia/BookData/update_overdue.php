<?php
/**
 * Cron job script to update overdue books status
 * This script should be run daily via cron job or scheduled task
 * Example cron: 0 2 * * * /usr/bin/php /path/to/update_overdue.php
 */

require_once 'includes/config.php';
require_once 'includes/db_connection.php';

try {
    // Update overdue books
    $stmt = $pdo->prepare("
        UPDATE borrowed_books 
        SET status = 'overdue' 
        WHERE status = 'active' 
        AND due_date < CURDATE()
    ");
    $stmt->execute();
    
    $updated_count = $stmt->rowCount();
    
    // Log the update
    $log_message = date('Y-m-d H:i:s') . " - Updated {$updated_count} books to overdue status\n";
    file_put_contents('logs/overdue_updates.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "Successfully updated {$updated_count} overdue books.\n";
    
} catch (PDOException $e) {
    $error_message = date('Y-m-d H:i:s') . " - Error updating overdue books: " . $e->getMessage() . "\n";
    file_put_contents('logs/overdue_errors.log', $error_message, FILE_APPEND | LOCK_EX);
    
    echo "Error updating overdue books: " . $e->getMessage() . "\n";
    exit(1);
}
?>
