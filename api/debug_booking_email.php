<?php
/**
 * Debug Booking Email - Test if emails are being sent
 * Access via: http://localhost/motorent/api/debug_booking_email.php?email=test@gmail.com
 */

require_once 'config.php';
require_once 'email_config.php';
require_once 'send_email.php';

header('Content-Type: text/html; charset=utf-8');

$testEmail = isset($_GET['email']) ? $_GET['email'] : 'test@gmail.com';

echo "<h2>Booking Email Debug Test</h2>";
echo "<p>Testing email to: <strong>$testEmail</strong></p>";
echo "<hr>";

// Check configuration
echo "<h3>1. Configuration Check</h3>";
echo "<ul>";
echo "<li>EMAIL_ENABLED: " . (EMAIL_ENABLED ? '✅ TRUE' : '❌ FALSE') . "</li>";
echo "<li>SMTP_HOST: " . SMTP_HOST . "</li>";
echo "<li>SMTP_PORT: " . SMTP_PORT . "</li>";
echo "<li>SMTP_USERNAME: " . SMTP_USERNAME . "</li>";
echo "<li>SMTP_FROM_EMAIL: " . SMTP_FROM_EMAIL . "</li>";
echo "<li>SMTP_PASSWORD: " . (empty(SMTP_PASSWORD) ? '❌ NOT SET' : '✅ SET (' . strlen(SMTP_PASSWORD) . ' chars)') . "</li>";
echo "</ul>";

if (!EMAIL_ENABLED) {
    echo "<p style='color: red;'><strong>❌ Email is disabled! Enable it in email_config.php</strong></p>";
    exit;
}

if (empty(SMTP_PASSWORD)) {
    echo "<p style='color: red;'><strong>❌ SMTP password is not set! Set it in email_config.php</strong></p>";
    exit;
}

// Test email template
echo "<h3>2. Email Template Test</h3>";
$template = getEmailTemplate('booking_confirmation', [
    'customer_name' => 'Test Customer',
    'bike_name' => 'Test Motorcycle',
    'pickup_datetime' => 'December 3, 2025 1:45 PM',
    'return_datetime' => 'December 3, 2025 11:45 PM',
    'pickup_location' => 'Test Location',
    'total_price' => '700.00'
]);

if ($template) {
    echo "<p>✅ Template generated successfully (" . strlen($template) . " characters)</p>";
} else {
    echo "<p style='color: red;'>❌ Template generation failed!</p>";
    exit;
}

// Test sending email
echo "<h3>3. Sending Test Email</h3>";
$subject = 'Test: Your Motorcycle Rental Booking is Confirmed!';
$result = sendEmailAdvanced($testEmail, $subject, $template);

echo "<div style='padding: 15px; border: 2px solid " . ($result['success'] ? 'green' : 'red') . "; background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . ";'>";
if ($result['success']) {
    echo "<h4 style='color: green;'>✅ Email Sent Successfully!</h4>";
    echo "<p>Message: " . htmlspecialchars($result['message'] ?? 'No message') . "</p>";
} else {
    echo "<h4 style='color: red;'>❌ Email Failed!</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error'] ?? 'Unknown error') . "</p>";
}
echo "</div>";

// Check error logs
echo "<h3>4. Recent Error Logs</h3>";
$errorLogFile = __DIR__ . '/email_errors.log';
if (file_exists($errorLogFile)) {
    $errors = file_get_contents($errorLogFile);
    if (!empty($errors)) {
        echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars($errors);
        echo "</pre>";
    } else {
        echo "<p>No errors logged.</p>";
    }
} else {
    echo "<p>No error log file found.</p>";
}

// Check success logs
echo "<h3>5. Recent Success Logs</h3>";
$successLogFile = __DIR__ . '/email_success.log';
if (file_exists($successLogFile)) {
    $successes = file_get_contents($successLogFile);
    if (!empty($successes)) {
        echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars($successes);
        echo "</pre>";
    } else {
        echo "<p>No success logs yet.</p>";
    }
} else {
    echo "<p>No success log file found yet.</p>";
}

echo "<hr>";
echo "<p><a href='?email=$testEmail'>Refresh Test</a> | <a href='test_smtp.php'>Test SMTP Connection</a></p>";

