<?php
/**
 * Check Email Logs - View recent email sending attempts
 * Access via: http://localhost/motorent/api/check_email_logs.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - MOTORENT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #667eea;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .log-entry {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .log-entry.success {
            border-left-color: #4caf50;
            background: #d4edda;
        }
        .log-entry.error {
            border-left-color: #f44336;
            background: #f8d7da;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .empty {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>📧 Email Logs Viewer</h1>
    
    <div class="container">
        <h2>✅ Success Logs</h2>
        <?php
        $successLog = __DIR__ . '/email_success.log';
        if (file_exists($successLog) && filesize($successLog) > 0) {
            $content = file_get_contents($successLog);
            $lines = explode("\n", trim($content));
            $recent = array_slice(array_reverse($lines), 0, 50); // Last 50 entries
            echo '<pre>';
            foreach ($recent as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre>';
        } else {
            echo '<p class="empty">No success logs found yet.</p>';
        }
        ?>
    </div>
    
    <div class="container">
        <h2>❌ Error Logs</h2>
        <?php
        $errorLog = __DIR__ . '/email_errors.log';
        if (file_exists($errorLog) && filesize($errorLog) > 0) {
            $content = file_get_contents($errorLog);
            $lines = explode("\n", trim($content));
            $recent = array_slice(array_reverse($lines), 0, 50); // Last 50 entries
            echo '<pre>';
            foreach ($recent as $line) {
                if (!empty(trim($line))) {
                    echo '<div class="log-entry error">' . htmlspecialchars($line) . '</div>';
                }
            }
            echo '</pre>';
        } else {
            echo '<p class="empty">No error logs found. This could mean emails are working, or no emails have been attempted yet.</p>';
        }
        ?>
    </div>
    
    <div class="container">
        <h2>🔧 Quick Actions</h2>
        <p>
            <a href="test_all_booking_emails.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                Test All Booking Emails
            </a>
            <a href="debug_booking_email.php" style="padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                Test Single Email
            </a>
            <a href="test_smtp.php" style="padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                Test SMTP Connection
            </a>
        </p>
    </div>
    
    <div class="container">
        <h2>📋 PHP Error Log (Last 20 lines)</h2>
        <?php
        $phpErrorLog = ini_get('error_log');
        if (empty($phpErrorLog)) {
            $phpErrorLog = __DIR__ . '/../php_errors.log';
        }
        
        if (file_exists($phpErrorLog) && filesize($phpErrorLog) > 0) {
            $content = file_get_contents($phpErrorLog);
            $lines = explode("\n", trim($content));
            $recent = array_slice(array_reverse($lines), 0, 20);
            echo '<pre>';
            foreach ($recent as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre>';
        } else {
            echo '<p class="empty">PHP error log not found or empty. Check: ' . htmlspecialchars($phpErrorLog) . '</p>';
        }
        ?>
    </div>
</body>
</html>

