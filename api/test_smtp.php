<?php
/**
 * Simple SMTP Test
 * This will test the SMTP connection and show detailed error messages
 */

require_once 'email_config.php';

header('Content-Type: application/json');

$testEmail = isset($_GET['email']) ? $_GET['email'] : SMTP_FROM_EMAIL;

echo "<h2>SMTP Connection Test</h2>";
echo "<p>Testing connection to: " . SMTP_HOST . ":" . SMTP_PORT . "</p>";

// Test connection
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
    echo "<p style='color: red;'>❌ Connection failed: $errstr ($errno)</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Firewall blocking port 587</li>";
    echo "<li>Internet connection issue</li>";
    echo "<li>SMTP server down</li>";
    echo "</ul>";
    exit;
}

echo "<p style='color: green;'>✅ Connected to SMTP server</p>";

// Read greeting
$response = fgets($smtp, 515);
echo "<p>Server greeting: " . htmlspecialchars(trim($response)) . "</p>";

// Send EHLO
fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
// Read all EHLO response lines (multi-line)
echo "<p>EHLO response:</p><ul>";
$ehloComplete = false;
while ($line = fgets($smtp, 515)) {
    echo "<li>" . htmlspecialchars(trim($line)) . "</li>";
    // Check if this is the last line (space after 3-digit code)
    if (strlen($line) > 3 && $line[3] == ' ') {
        $ehloComplete = true;
        break;
    }
}
echo "</ul>";

if (!$ehloComplete) {
    echo "<p style='color: orange;'>⚠️ EHLO response may be incomplete</p>";
}

// Start TLS
fputs($smtp, "STARTTLS\r\n");
$response = fgets($smtp, 515);
echo "<p>STARTTLS response: " . htmlspecialchars(trim($response)) . "</p>";

if (substr($response, 0, 3) == '220') {
    // Enable TLS
    if (stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        echo "<p style='color: green;'>✅ TLS encryption enabled</p>";
        
        // Try authentication
        fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        echo "<p>AUTH LOGIN response: " . htmlspecialchars(trim($response)) . "</p>";
        
        fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
        $response = fgets($smtp, 515);
        echo "<p>Username sent, response: " . htmlspecialchars(trim($response)) . "</p>";
        
        $password = str_replace(' ', '', SMTP_PASSWORD);
        fputs($smtp, base64_encode($password) . "\r\n");
        $response = fgets($smtp, 515);
        echo "<p>Password sent, response: " . htmlspecialchars(trim($response)) . "</p>";
        
        if (substr($response, 0, 3) == '235') {
            echo "<p style='color: green;'>✅ Authentication successful!</p>";
            echo "<p>SMTP connection is working correctly. You can now send emails.</p>";
        } else {
            echo "<p style='color: red;'>❌ Authentication failed: " . htmlspecialchars(trim($response)) . "</p>";
            echo "<p>Check your username and password in email_config.php</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to enable TLS encryption</p>";
        echo "<p>Check if PHP OpenSSL extension is enabled</p>";
    }
} else {
    echo "<p style='color: red;'>❌ STARTTLS failed: " . htmlspecialchars(trim($response)) . "</p>";
}

fputs($smtp, "QUIT\r\n");
fclose($smtp);

?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
    }
    p {
        padding: 10px;
        background: white;
        border-radius: 5px;
        margin: 5px 0;
    }
</style>

