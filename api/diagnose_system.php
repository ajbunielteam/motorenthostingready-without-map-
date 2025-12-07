<?php
/**
 * System Diagnosis - Check if system is using database or localStorage
 * Access via: http://localhost/motorent/api/diagnose_system.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnosis - MOTORENT</title>
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
        .success {
            color: #4caf50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
        }
        .info {
            color: #2196F3;
            font-weight: bold;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .file-check {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .file-check.exists {
            border-left-color: #4caf50;
            background: #d4edda;
        }
        .file-check.missing {
            border-left-color: #f44336;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <h1>🔍 MOTORENT System Diagnosis</h1>
    
    <?php
    // Check database connection
    require_once 'config.php';
    $conn = getDBConnection();
    
    echo '<div class="container">';
    echo '<h2>📊 Database Status</h2>';
    if ($conn) {
        echo '<p class="success">✅ Database Connected</p>';
        echo '<p><strong>Database:</strong> ' . DB_NAME . '</p>';
        echo '<p><strong>Host:</strong> ' . DB_HOST . '</p>';
        
        // Check tables
        $tables = ['accounts', 'profiles', 'motorcycles', 'bookings', 'tickets'];
        echo '<h3>Tables:</h3>';
        foreach ($tables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check && $check->num_rows > 0) {
                $count = $conn->query("SELECT COUNT(*) as total FROM $table");
                $row = $count->fetch_assoc();
                echo '<p class="success">✅ ' . $table . ': ' . $row['total'] . ' records</p>';
            } else {
                echo '<p class="error">❌ ' . $table . ': Table does not exist</p>';
            }
        }
    } else {
        echo '<p class="error">❌ Database Connection Failed</p>';
    }
    echo '</div>';
    
    // Check API files
    echo '<div class="container">';
    echo '<h2>📁 API Files Check</h2>';
    
    $apiFiles = [
        'api/api.js' => '../api/api.js',
        'api/accounts.php' => 'accounts.php',
        'api/bookings.php' => 'bookings.php',
        'api/motorcycles.php' => 'motorcycles.php',
        'api/auth.php' => 'auth.php',
        'api/config.php' => 'config.php',
        'api/send_email.php' => 'send_email.php',
        'api/email_config.php' => 'email_config.php'
    ];
    
    foreach ($apiFiles as $webPath => $filePath) {
        $fullPath = __DIR__ . '/' . $filePath;
        $exists = file_exists($fullPath);
        $class = $exists ? 'exists' : 'missing';
        $status = $exists ? '✅' : '❌';
        echo '<div class="file-check ' . $class . '">';
        echo $status . ' <strong>' . $webPath . '</strong>';
        if ($exists) {
            echo ' - ' . filesize($fullPath) . ' bytes';
        } else {
            echo ' - <span class="error">FILE MISSING</span>';
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Check main files
    echo '<div class="container">';
    echo '<h2>📄 Main Files Check</h2>';
    
    $mainFiles = [
        'index.html' => '../index.html',
        'script.js' => '../script.js',
        'style.css' => '../style.css'
    ];
    
    foreach ($mainFiles as $webPath => $filePath) {
        $fullPath = __DIR__ . '/' . $filePath;
        $exists = file_exists($fullPath);
        $class = $exists ? 'exists' : 'missing';
        $status = $exists ? '✅' : '❌';
        echo '<div class="file-check ' . $class . '">';
        echo $status . ' <strong>' . $webPath . '</strong>';
        if ($exists) {
            echo ' - ' . filesize($fullPath) . ' bytes';
        } else {
            echo ' - <span class="error">FILE MISSING</span>';
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Check if API is being used
    echo '<div class="container">';
    echo '<h2>🔌 API Usage Check</h2>';
    
    $scriptPath = __DIR__ . '/../script.js';
    if (file_exists($scriptPath)) {
        $scriptContent = file_get_contents($scriptPath);
        
        $apiUsage = [
            'AccountsAPI' => substr_count($scriptContent, 'AccountsAPI'),
            'BookingsAPI' => substr_count($scriptContent, 'BookingsAPI'),
            'MotorcyclesAPI' => substr_count($scriptContent, 'MotorcyclesAPI'),
            'localStorage.getItem' => substr_count($scriptContent, 'localStorage.getItem'),
            'localStorage.setItem' => substr_count($scriptContent, 'localStorage.setItem')
        ];
        
        echo '<h3>API Calls Found:</h3>';
        foreach ($apiUsage as $key => $count) {
            if (strpos($key, 'localStorage') === false) {
                echo '<p class="' . ($count > 0 ? 'success' : 'warning') . '">';
                echo ($count > 0 ? '✅' : '⚠️') . ' ' . $key . ': ' . $count . ' occurrences';
                echo '</p>';
            }
        }
        
        echo '<h3>localStorage Usage (should be minimal):</h3>';
        foreach ($apiUsage as $key => $count) {
            if (strpos($key, 'localStorage') !== false) {
                echo '<p class="' . ($count < 10 ? 'success' : 'warning') . '">';
                echo ($count < 10 ? '✅' : '⚠️') . ' ' . $key . ': ' . $count . ' occurrences';
                echo '</p>';
            }
        }
    } else {
        echo '<p class="error">❌ script.js not found</p>';
    }
    echo '</div>';
    
    // Recommendations
    echo '<div class="container">';
    echo '<h2>💡 Recommendations</h2>';
    
    if ($conn) {
        $accountsCount = $conn->query("SELECT COUNT(*) as total FROM accounts")->fetch_assoc()['total'];
        $bookingsCount = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
        
        if ($accountsCount == 0 && $bookingsCount == 0) {
            echo '<div class="warning">';
            echo '<h3>⚠️ Database is Empty</h3>';
            echo '<p>The database is connected but has no data. This could mean:</p>';
            echo '<ul>';
            echo '<li>The system is still using localStorage instead of the database</li>';
            echo '<li>No accounts or bookings have been created yet</li>';
            echo '<li>The API endpoints are not being called</li>';
            echo '</ul>';
            echo '<p><strong>Action:</strong> Check browser console for API errors when creating accounts/bookings.</p>';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<h3>✅ Database Has Data</h3>';
            echo '<p>Accounts: ' . $accountsCount . '</p>';
            echo '<p>Bookings: ' . $bookingsCount . '</p>';
            echo '<p>The system appears to be using the database.</p>';
            echo '</div>';
        }
    }
    
    echo '<p><a href="check_database.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
        View Database Details
    </a></p>';
    echo '</div>';
    
    $conn->close();
    ?>
</body>
</html>

