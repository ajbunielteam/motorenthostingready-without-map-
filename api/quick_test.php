<?php
/**
 * Quick Database Connection Test
 * Upload this file to: public_html/api/quick_test.php
 * Access via: https://motorent.space/api/quick_test.php
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick DB Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Quick Database Connection Test</h1>
    
    <div class="info">
        <h3>Current Configuration:</h3>
        <p><strong>Host:</strong> <code><?php echo htmlspecialchars(DB_HOST); ?></code></p>
        <p><strong>User:</strong> <code><?php echo htmlspecialchars(DB_USER); ?></code></p>
        <p><strong>Database:</strong> <code><?php echo htmlspecialchars(DB_NAME); ?></code></p>
        <p><strong>Password:</strong> <code><?php echo (defined('DB_PASS') && DB_PASS !== 'YOUR_DATABASE_PASSWORD_HERE' ? '***' : '⚠️ NOT SET'); ?></code></p>
    </div>

    <?php
    // Test connection
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            echo '<div class="error">';
            echo '<h3>❌ Connection Failed</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>';
            
            if (strpos($conn->connect_error, 'Access denied') !== false) {
                echo '<h4>🔧 How to Fix:</h4>';
                echo '<ol>';
                echo '<li>Go to Hostinger hPanel → Databases → MySQL Databases</li>';
                echo '<li>Click "Enter phpMyAdmin" next to your database</li>';
                echo '<li>Click "User accounts" tab</li>';
                echo '<li>Find user: <code>' . htmlspecialchars(DB_USER) . '</code></li>';
                echo '<li>Click "Edit privileges"</li>';
                echo '<li>Under "Database-specific privileges":</li>';
                echo '<li>  - Select database: <code>' . htmlspecialchars(DB_NAME) . '</code></li>';
                echo '<li>  - Check "ALL PRIVILEGES"</li>';
                echo '<li>  - Click "Go"</li>';
                echo '</ol>';
            } elseif (strpos($conn->connect_error, 'Unknown database') !== false) {
                echo '<p><strong>Issue:</strong> Database does not exist. Make sure the database name is correct.</p>';
            }
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<h3>✅ Connection Successful!</h3>';
            echo '<p>Database connection is working correctly.</p>';
            
            // Check for tables
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                if (count($tables) > 0) {
                    echo '<p><strong>Tables found:</strong> ' . implode(', ', $tables) . '</p>';
                } else {
                    echo '<p>⚠️ No tables found. Run <code>setup_database.php</code> to create tables.</p>';
                }
            }
            echo '</div>';
            $conn->close();
        }
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h3>❌ Error</h3>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="info" style="margin-top: 20px;">
        <p><strong>⚠️ Security:</strong> Delete this file after testing!</p>
    </div>
</body>
</html>

