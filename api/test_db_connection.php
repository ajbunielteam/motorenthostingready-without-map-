<?php
/**
 * Database Connection Test Script for Hostinger
 * 
 * This script helps diagnose database connection issues.
 * Access via: https://yourdomain.com/api/test_db_connection.php
 * 
 * ⚠️ DELETE THIS FILE after fixing the connection!
 */

// Suppress errors for cleaner output
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test - MOTORENT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .test-item {
            margin: 15px 0;
            padding: 15px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .success {
            border-left-color: #4caf50;
            background: #e8f5e9;
        }
        .error {
            border-left-color: #f44336;
            background: #ffebee;
        }
        .warning {
            border-left-color: #ff9800;
            background: #fff3e0;
        }
        .info {
            border-left-color: #2196f3;
            background: #e3f2fd;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .credentials {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials h3 {
            margin-top: 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>
        
        <?php
        // Read config.php to get current settings
        require_once 'config.php';
        
        echo '<div class="credentials">';
        echo '<h3>📋 Current Configuration (from config.php)</h3>';
        echo '<p><strong>DB_HOST:</strong> <code>' . htmlspecialchars(DB_HOST) . '</code></p>';
        echo '<p><strong>DB_USER:</strong> <code>' . htmlspecialchars(DB_USER) . '</code></p>';
        echo '<p><strong>DB_NAME:</strong> <code>' . htmlspecialchars(DB_NAME) . '</code></p>';
        echo '<p><strong>DB_PASS:</strong> <code>' . (defined('DB_PASS') && DB_PASS !== 'YOUR_DATABASE_PASSWORD_HERE' ? '***' . substr(DB_PASS, -3) : '⚠️ NOT SET') . '</code></p>';
        echo '</div>';
        
        // Test 1: Check if password is set
        echo '<div class="test-item ' . (defined('DB_PASS') && DB_PASS !== '' && DB_PASS !== 'YOUR_DATABASE_PASSWORD_HERE' ? 'success' : 'error') . '">';
        echo '<h3>Test 1: Password Configuration</h3>';
        if (defined('DB_PASS') && DB_PASS !== '' && DB_PASS !== 'YOUR_DATABASE_PASSWORD_HERE') {
            echo '<p>✅ Password is configured</p>';
        } else {
            echo '<p>❌ Password is not set or still has placeholder value</p>';
            echo '<p><strong>Action:</strong> Update <code>DB_PASS</code> in config.php with your actual database password</p>';
        }
        echo '</div>';
        
        // Test 2: Try connecting without database (to test user credentials)
        echo '<div class="test-item">';
        echo '<h3>Test 2: User Credentials Test</h3>';
        try {
            $testConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if ($testConn->connect_error) {
                echo '<div class="error">';
                echo '<p>❌ <strong>Connection Failed:</strong> ' . htmlspecialchars($testConn->connect_error) . '</p>';
                
                // Provide specific guidance based on error
                if (strpos($testConn->connect_error, 'Access denied') !== false) {
                    echo '<p><strong>Possible Issues:</strong></p>';
                    echo '<ul>';
                    echo '<li>❌ <strong>Incorrect Password:</strong> Double-check your database password in Hostinger</li>';
                    echo '<li>❌ <strong>Wrong Username:</strong> Verify the username matches exactly (case-sensitive)</li>';
                    echo '<li>❌ <strong>User Not Created:</strong> Make sure you created the database user in Hostinger</li>';
                    echo '</ul>';
                    echo '<p><strong>How to Fix:</strong></p>';
                    echo '<ol>';
                    echo '<li>Go to Hostinger hPanel → Databases → MySQL Databases</li>';
                    echo '<li>Check your database user credentials</li>';
                    echo '<li>Verify the username format: <code>u291171953_motorent</code> (should match exactly)</li>';
                    echo '<li>Update config.php with the correct password</li>';
                    echo '</ol>';
                }
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '<p>✅ User credentials are correct! Can connect to MySQL server.</p>';
                echo '</div>';
                $testConn->close();
            }
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<p>❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        echo '</div>';
        
        // Test 3: Try connecting to the specific database
        echo '<div class="test-item">';
        echo '<h3>Test 3: Database Access Test</h3>';
        try {
            $dbConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($dbConn->connect_error) {
                echo '<div class="error">';
                echo '<p>❌ <strong>Database Connection Failed:</strong> ' . htmlspecialchars($dbConn->connect_error) . '</p>';
                
                if (strpos($dbConn->connect_error, 'Unknown database') !== false) {
                    echo '<p><strong>Issue:</strong> Database does not exist</p>';
                    echo '<p><strong>How to Fix:</strong></p>';
                    echo '<ol>';
                    echo '<li>Go to Hostinger hPanel → Databases → MySQL Databases</li>';
                    echo '<li>Verify the database name exists</li>';
                    echo '<li>Check the exact database name (case-sensitive)</li>';
                    echo '<li>Run <code>setup_database.php</code> to create tables after fixing connection</li>';
                    echo '</ol>';
                } elseif (strpos($dbConn->connect_error, 'Access denied') !== false) {
                    echo '<p><strong>Issue:</strong> User does not have privileges on this database</p>';
                    echo '<p><strong>How to Fix:</strong></p>';
                    echo '<ol>';
                    echo '<li>Go to Hostinger hPanel → Databases → MySQL Databases</li>';
                    echo '<li>Find your database user</li>';
                    echo '<li>Click "Manage" or "Edit" next to the user</li>';
                    echo '<li>Ensure the user has "ALL PRIVILEGES" on the database</li>';
                    echo '<li>Or delete and recreate the database user, making sure to assign it to the database</li>';
                    echo '</ol>';
                }
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '<p>✅ Successfully connected to database: <code>' . htmlspecialchars(DB_NAME) . '</code></p>';
                
                // Test query
                $result = $dbConn->query("SHOW TABLES");
                if ($result) {
                    $tables = [];
                    while ($row = $result->fetch_array()) {
                        $tables[] = $row[0];
                    }
                    if (count($tables) > 0) {
                        echo '<p>✅ Found ' . count($tables) . ' table(s): ' . implode(', ', $tables) . '</p>';
                    } else {
                        echo '<div class="warning">';
                        echo '<p>⚠️ Database is empty (no tables found)</p>';
                        echo '<p><strong>Action:</strong> Run <code>setup_database.php</code> to create tables</p>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                $dbConn->close();
            }
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<p>❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        echo '</div>';
        
        // Test 4: Check Hostinger-specific issues
        echo '<div class="test-item info">';
        echo '<h3>ℹ️ Hostinger-Specific Checklist</h3>';
        echo '<ul>';
        echo '<li>✅ Database created in Hostinger hPanel</li>';
        echo '<li>✅ Database user created and assigned to the database</li>';
        echo '<li>✅ User has ALL PRIVILEGES on the database</li>';
        echo '<li>✅ Database host is <code>localhost</code> (check in Hostinger if different)</li>';
        echo '<li>✅ Username format: <code>u291171953_motorent</code> (with prefix)</li>';
        echo '<li>✅ Database name format: <code>u291171953_motorent</code> (with prefix)</li>';
        echo '<li>✅ Password is correct (copy-paste to avoid typos)</li>';
        echo '</ul>';
        echo '</div>';
        ?>
        
        <div class="test-item warning">
            <h3>⚠️ Security Reminder</h3>
            <p><strong>Delete this file after testing!</strong> This file exposes configuration information.</p>
            <p>After fixing the connection, remove: <code>api/test_db_connection.php</code></p>
        </div>
    </div>
</body>
</html>

