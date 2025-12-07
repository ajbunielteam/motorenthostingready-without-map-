<?php
/**
 * Test Email Configuration
 * Access: http://localhost/motorent/api/test_email.php
 */

require_once 'email_config.php';
require_once 'send_email.php';

header('Content-Type: application/json');

// Test email configuration
$configTest = testEmailConfig();

if (!$configTest['success']) {
    echo json_encode($configTest);
    exit;
}

// Test email sending - use your actual email or the configured email
$testEmail = isset($_GET['email']) && !empty($_GET['email']) && $_GET['email'] !== 'your-email@gmail.com'
    ? $_GET['email'] 
    : SMTP_FROM_EMAIL;
$subject = 'MOTORENT Email Test';
$message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ Email Test Successful!</h1>
            </div>
            <div class="content">
                <p>If you are reading this, your email configuration is working correctly!</p>
                <p><strong>Test Details:</strong></p>
                <ul>
                    <li>SMTP Host: ' . SMTP_HOST . '</li>
                    <li>SMTP Port: ' . SMTP_PORT . '</li>
                    <li>From Email: ' . SMTP_FROM_EMAIL . '</li>
                    <li>Test Time: ' . date('Y-m-d H:i:s') . '</li>
                </ul>
                <p>Your MOTORENT email notifications are ready to use!</p>
            </div>
        </div>
    </body>
    </html>
';

$result = sendEmailAdvanced($testEmail, $subject, $message);

echo json_encode([
    'config_test' => $configTest,
    'email_test' => $result,
    'test_email_sent_to' => $testEmail
]);

?>

