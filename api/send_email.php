<?php
/**
 * Email Sending Service using PHP mail() function
 * For Gmail, you may need to configure sendmail or use PHPMailer
 */

require_once 'email_config.php';

// Simple SMTP email sending function (works with Gmail)
function sendEmailSMTP($to, $subject, $message, $isHTML = true) {
    if (!EMAIL_ENABLED) {
        return ['success' => false, 'error' => 'Email notifications are disabled'];
    }
    
    if (empty(SMTP_PASSWORD)) {
        return ['success' => false, 'error' => 'SMTP password not configured'];
    }
    
    // Create socket connection with proper context for TLS
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $smtp = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if (!$smtp) {
        return ['success' => false, 'error' => "Could not connect to SMTP server: $errstr ($errno). Make sure port 587 is not blocked."];
    }
    
    // Read server greeting
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ['success' => false, 'error' => 'SMTP server error: ' . trim($response)];
    }
    
    // Send EHLO
    fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
    // Read all EHLO response lines (multi-line response)
    // Lines start with "250-" (continuation) or "250 " (last line)
    do {
        $line = fgets($smtp, 515);
        if ($line === false) break;
        // Last line has space after 3-digit code (e.g., "250 " not "250-")
        if (strlen($line) >= 4 && $line[3] == ' ') {
            break;
        }
    } while (true);
    
    // Start TLS
    fputs($smtp, "STARTTLS\r\n");
    // Read STARTTLS response (should be single line "220 ...")
    $response = fgets($smtp, 515);
    if (!$response || substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ['success' => false, 'error' => 'STARTTLS failed. Expected 220, got: ' . trim($response ?: 'No response')];
    }
    
    // Enable crypto with multiple TLS methods for compatibility
    $cryptoMethods = STREAM_CRYPTO_METHOD_TLS_CLIENT;
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
        $cryptoMethods |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
    }
    
    if (!@stream_socket_enable_crypto($smtp, true, $cryptoMethods)) {
        $error = error_get_last();
        fclose($smtp);
        return ['success' => false, 'error' => 'Failed to enable TLS encryption. ' . ($error ? $error['message'] : 'Check PHP OpenSSL extension.')];
    }
    
    // Send EHLO again after TLS
    fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
    // Read all EHLO response lines (multi-line response ends with space after code)
    do {
        $line = fgets($smtp, 515);
        if ($line === false) break;
        // Last line has space after 3-digit code (e.g., "250 " not "250-")
        if (strlen($line) >= 4 && $line[3] == ' ') {
            break;
        }
    } while (true);
    
    // Authenticate
    fputs($smtp, "AUTH LOGIN\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
    $response = fgets($smtp, 515);
    
    // Remove spaces from password (Gmail App Passwords sometimes have spaces)
    $password = str_replace(' ', '', SMTP_PASSWORD);
    fputs($smtp, base64_encode($password) . "\r\n");
    
    // Read authentication response (usually single line "235 ...")
    $response = fgets($smtp, 515);
    if (!$response || substr($response, 0, 3) != '235') {
        fclose($smtp);
        return ['success' => false, 'error' => 'SMTP authentication failed. Response: ' . trim($response ?: 'No response') . '. Check your username and App Password.'];
    }
    
    // Send email
    fputs($smtp, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, "DATA\r\n");
    $response = fgets($smtp, 515);
    if (!$response || substr($response, 0, 3) != '354') {
        fclose($smtp);
        return ['success' => false, 'error' => 'DATA command failed: ' . trim($response ?: 'No response')];
    }
    
    // Email headers
    $emailHeaders = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $emailHeaders .= "To: <" . $to . ">\r\n";
    $emailHeaders .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $emailHeaders .= "Subject: " . $subject . "\r\n";
    $emailHeaders .= "MIME-Version: 1.0\r\n";
    if ($isHTML) {
        $emailHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $emailHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    $emailHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $emailHeaders .= "\r\n";
    
    // Send email content
    fputs($smtp, $emailHeaders . "\r\n" . $message . "\r\n.\r\n");
    
    // Read response (usually single line)
    $response = fgets($smtp, 515);
    if (!$response || substr($response, 0, 3) != '250') {
        fclose($smtp);
        $errorMsg = 'Failed to send email. Response: ' . trim($response ?: 'No response');
        error_log("SMTP send failed for $to: $errorMsg");
        return ['success' => false, 'error' => $errorMsg];
    }
    
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return ['success' => true, 'message' => 'Email sent successfully'];
}

// Simple email sending function using PHP mail() (fallback)
function sendEmail($to, $subject, $message, $isHTML = true) {
    if (!EMAIL_ENABLED) {
        return ['success' => false, 'error' => 'Email notifications are disabled'];
    }
    
    $headers = [];
    $headers[] = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">";
    $headers[] = "Reply-To: " . SMTP_FROM_EMAIL;
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    if ($isHTML) {
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
    }
    
    $headersString = implode("\r\n", $headers);
    
    // Use PHP mail() function
    $result = @mail($to, $subject, $message, $headersString);
    
    if ($result) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'error' => 'Failed to send email (mail() function failed)'];
    }
}

// Send email using PHPMailer (if available) or fallback to SMTP or mail()
function sendEmailAdvanced($to, $subject, $message, $isHTML = true) {
    if (!EMAIL_ENABLED) {
        return ['success' => false, 'error' => 'Email notifications are disabled'];
    }
    
    // Try SMTP first (works with Gmail) - this is the most reliable method
    $result = sendEmailSMTP($to, $subject, $message, $isHTML);
    if ($result['success']) {
        error_log("Email sent successfully via SMTP to: $to");
        return $result;
    }
    
    // Log the SMTP error
    error_log("SMTP email failed for $to: " . ($result['error'] ?? 'Unknown error'));
    
    // Check if PHPMailer is available as fallback
    if (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
        $phpmailerResult = sendEmailPHPMailer($to, $subject, $message, $isHTML);
        if ($phpmailerResult['success']) {
            error_log("Email sent successfully via PHPMailer to: $to");
            return $phpmailerResult;
        }
        error_log("PHPMailer email failed for $to: " . ($phpmailerResult['error'] ?? 'Unknown error'));
    }
    
    // Last resort: try simple mail() if SMTP and PHPMailer both fail
    $mailResult = sendEmail($to, $subject, $message, $isHTML);
    if (!$mailResult['success']) {
        error_log("All email methods failed for $to. SMTP error: " . ($result['error'] ?? 'Unknown'));
    }
    return $mailResult;
}

// Send email using PHPMailer (more reliable for Gmail)
function sendEmailPHPMailer($to, $subject, $message, $isHTML = true) {
    // Check if PHPMailer files exist
    if (!file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
        // Fallback to simple mail() if PHPMailer not available
        return sendEmail($to, $subject, $message, $isHTML);
    }
    
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    require_once __DIR__ . '/PHPMailer/Exception.php';
    
    // Use fully qualified class names instead of use statements
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        if (!$isHTML) {
            $mail->AltBody = strip_tags($message);
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return ['success' => false, 'error' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

// Email templates
function getEmailTemplate($templateName, $variables = []) {
    $templates = [
        'account_approved' => '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .button { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🎉 Account Approved!</h1>
                    </div>
                    <div class="content">
                        <p>Dear {{username}},</p>
                        <p>Great news! Your MOTORENT rental provider account has been <strong>approved</strong>.</p>
                        <p>You can now log in and start adding your motorcycles to the platform.</p>
                        <p><a href="{{login_url}}" class="button">Login to Your Account</a></p>
                        <p>If you have any questions, please don\'t hesitate to contact us.</p>
                        <p>Best regards,<br>MOTORENT Admin Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ',
        'account_denied' => '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Account Application Status</h1>
                    </div>
                    <div class="content">
                        <p>Dear {{username}},</p>
                        <p>We regret to inform you that your MOTORENT rental provider account application has been <strong>denied</strong>.</p>
                        <p>If you have any questions or would like to appeal this decision, please contact us at {{admin_email}}.</p>
                        <p>Best regards,<br>MOTORENT Admin Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ',
        'booking_confirmation' => '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>✅ Booking Confirmed!</h1>
                    </div>
                    <div class="content">
                        <p>Dear {{customer_name}},</p>
                        <p>Your motorcycle rental booking has been confirmed!</p>
                        <div class="booking-details">
                            <p><strong>Motorcycle:</strong> {{bike_name}}</p>
                            <p><strong>Pickup Date & Time:</strong> {{pickup_datetime}}</p>
                            <p><strong>Return Date & Time:</strong> {{return_datetime}}</p>
                            <p><strong>Pickup Location:</strong> {{pickup_location}}</p>
                            <p><strong>Total Price:</strong> ₱{{total_price}}</p>
                        </div>
                        <p>Please arrive on time for pickup. If you have any questions, contact the rental provider.</p>
                        <p>Best regards,<br>MOTORENT Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ',
        'new_booking_notification' => '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
                    .button { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>📋 New Booking Received</h1>
                    </div>
                    <div class="content">
                        <p>Hello {{provider_name}},</p>
                        <p>You have received a new booking for your motorcycle!</p>
                        <div class="booking-details">
                            <p><strong>Motorcycle:</strong> {{bike_name}}</p>
                            <p><strong>Customer:</strong> {{customer_name}}</p>
                            <p><strong>Email:</strong> {{customer_email}}</p>
                            <p><strong>Pickup:</strong> {{pickup_datetime}}</p>
                            <p><strong>Return:</strong> {{return_datetime}}</p>
                            <p><strong>Total Price:</strong> ₱{{total_price}}</p>
                        </div>
                        <p><a href="{{dashboard_url}}" class="button">View Booking in Dashboard</a></p>
                        <p>Best regards,<br>MOTORENT Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ',
        'ticket_submitted' => '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🎫 Ticket Submitted</h1>
                    </div>
                    <div class="content">
                        <p>Dear {{name}},</p>
                        <p>Thank you for contacting us! We have received your support ticket.</p>
                        <p><strong>Subject:</strong> {{subject}}</p>
                        <p><strong>Your Message:</strong></p>
                        <p>{{message}}</p>
                        <p>We will review your ticket and get back to you as soon as possible.</p>
                        <p>Best regards,<br>MOTORENT Support Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        '
    ];
    
    $template = $templates[$templateName] ?? '';
    
    // Replace variables
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    
    return $template;
}

// Helper function to replace variables in email templates
function replaceEmailVariables($template, $variables) {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
    }
    return $template;
}

