<?php
/**
 * Email Configuration for BookData Notifications
 * Configure email settings for sending reminders and notifications
 */

// Email configuration (using PHPMailer or similar)
define('SMTP_HOST', 'localhost'); // Your SMTP host
define('SMTP_PORT', 587); // SMTP port
define('SMTP_USERNAME', 'noreply@bookdata.com'); // SMTP username
define('SMTP_PASSWORD', ''); // SMTP password
define('SMTP_SECURE', 'tls'); // tls or ssl

// From email settings
define('MAIL_FROM_EMAIL', 'noreply@bookdata.com');
define('MAIL_FROM_NAME', 'BookData Library System');

// Email templates
$email_templates = [
    'overdue_reminder' => [
        'subject' => 'Recordatorio: Libro vencido - {{book_title}}',
        'body' => '
            <h2>Recordatorio de Devolución</h2>
            <p>Estimado/a {{user_name}},</p>
            <p>El libro "{{book_title}}" de {{author}} está vencido desde el {{due_date}}.</p>
            <p>Por favor, devuélvelo lo antes posible para evitar multas adicionales.</p>
            <p>Días de retraso: {{days_overdue}}</p>
            <p>Multa acumulada: ${{fine_amount}}</p>
            <br>
            <p>Gracias,<br>Sistema de Biblioteca BookData</p>
        '
    ],
    'due_soon_reminder' => [
        'subject' => 'Recordatorio: Libro por vencer - {{book_title}}',
        'body' => '
            <h2>Tu libro vence pronto</h2>
            <p>Estimado/a {{user_name}},</p>
            <p>El libro "{{book_title}}" de {{author}} vence el {{due_date}}.</p>
            <p>Días restantes: {{days_remaining}}</p>
            <p>Si necesitas más tiempo, puedes renovar el préstamo en línea.</p>
            <br>
            <p>Gracias,<br>Sistema de Biblioteca BookData</p>
        '
    ]
];

/**
 * Function to send email (placeholder - requires PHPMailer or similar)
 */
function send_notification_email($to_email, $to_name, $template_key, $variables) {
    global $email_templates;
    
    if (!isset($email_templates[$template_key])) {
        return false;
    }
    
    $template = $email_templates[$template_key];
    $subject = $template['subject'];
    $body = $template['body'];
    
    // Replace variables in template
    foreach ($variables as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', $value, $subject);
        $body = str_replace('{{' . $key . '}}', $value, $body);
    }
    
    // Log the email (in production, replace with actual email sending)
    $log_entry = date('Y-m-d H:i:s') . " - Email to: {$to_email}, Subject: {$subject}\n";
    file_put_contents('logs/notifications.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    // TODO: Implement actual email sending with PHPMailer
    /*
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
    */
    
    return true; // Placeholder return
}
?>
