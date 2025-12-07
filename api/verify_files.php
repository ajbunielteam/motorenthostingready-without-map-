<?php
/**
 * Verify All Files and Their Locations
 * Access via: http://localhost/motorent/api/verify_files.php
 */

header('Content-Type: text/html; charset=utf-8');

$baseDir = dirname(__DIR__);
$apiDir = __DIR__;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Verification - MOTORENT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1400px;
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
        .file-item {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item.exists {
            border-left-color: #4caf50;
            background: #d4edda;
        }
        .file-item.missing {
            border-left-color: #f44336;
            background: #f8d7da;
        }
        .file-item.warning {
            border-left-color: #ff9800;
            background: #fff3cd;
        }
        .file-path {
            font-family: monospace;
            color: #666;
            font-size: 0.9em;
        }
        .file-size {
            color: #999;
            font-size: 0.9em;
        }
        .action-btn {
            padding: 5px 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>📁 File Verification & Location Check</h1>
    
    <?php
    // Required files structure
    $requiredFiles = [
        // Frontend files (should be in htdocs/motorent/)
        'Frontend' => [
            'index.html' => $baseDir . '/index.html',
            'script.js' => $baseDir . '/script.js',
            'style.css' => $baseDir . '/style.css',
            'logo.png' => $baseDir . '/logo.png',
        ],
        // API JavaScript (should be in htdocs/motorent/api/)
        'API JavaScript' => [
            'api/api.js' => $baseDir . '/api/api.js',
        ],
        // PHP API files (should be in htdocs/motorent/api/)
        'PHP API Files' => [
            'api/config.php' => $apiDir . '/config.php',
            'api/accounts.php' => $apiDir . '/accounts.php',
            'api/auth.php' => $apiDir . '/auth.php',
            'api/bookings.php' => $apiDir . '/bookings.php',
            'api/motorcycles.php' => $apiDir . '/motorcycles.php',
            'api/profiles.php' => $apiDir . '/profiles.php',
            'api/tickets.php' => $apiDir . '/tickets.php',
            'api/search.php' => $apiDir . '/search.php',
        ],
        // Email files
        'Email System' => [
            'api/email_config.php' => $apiDir . '/email_config.php',
            'api/send_email.php' => $apiDir . '/send_email.php',
        ],
        // Utility files
        'Utility Files' => [
            'api/setup_database.php' => $apiDir . '/setup_database.php',
            'api/check_database.php' => $apiDir . '/check_database.php',
            'api/diagnose_system.php' => $apiDir . '/diagnose_system.php',
            'api/test_all_booking_emails.php' => $apiDir . '/test_all_booking_emails.php',
            'api/debug_booking_email.php' => $apiDir . '/debug_booking_email.php',
            'api/check_email_logs.php' => $apiDir . '/check_email_logs.php',
            'api/test_smtp.php' => $apiDir . '/test_smtp.php',
            'api/test_email.php' => $apiDir . '/test_email.php',
        ],
    ];
    
    $missingFiles = [];
    $existingFiles = [];
    
    foreach ($requiredFiles as $category => $files) {
        echo '<div class="container">';
        echo '<h2>' . $category . '</h2>';
        
        foreach ($files as $relativePath => $fullPath) {
            $exists = file_exists($fullPath);
            $class = $exists ? 'exists' : 'missing';
            $status = $exists ? '✅' : '❌';
            
            if ($exists) {
                $existingFiles[] = $relativePath;
                $size = filesize($fullPath);
                $sizeFormatted = $size > 1024 ? number_format($size / 1024, 2) . ' KB' : $size . ' bytes';
            } else {
                $missingFiles[] = ['path' => $relativePath, 'full' => $fullPath];
                $sizeFormatted = 'N/A';
            }
            
            echo '<div class="file-item ' . $class . '">';
            echo '<div>';
            echo '<strong>' . $status . ' ' . $relativePath . '</strong><br>';
            echo '<span class="file-path">' . htmlspecialchars($fullPath) . '</span>';
            echo '</div>';
            echo '<div>';
            echo '<span class="file-size">' . $sizeFormatted . '</span>';
            if (!$exists) {
                echo ' <span style="color: red;">MISSING</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    // Check for files in wrong locations
    echo '<div class="container">';
    echo '<h2>🔍 Files in User Directory (c:\\Users\\ajbun\\)</h2>';
    echo '<p>These files should be copied to: <code>c:\\xampp\\htdocs\\motorent\\</code></p>';
    
    $userDirFiles = [
        'c:\\Users\\ajbun\\index.html',
        'c:\\Users\\ajbun\\script.js',
        'c:\\Users\\ajbun\\style.css',
        'c:\\Users\\ajbun\\api\\api.js',
    ];
    
    foreach ($userDirFiles as $userFile) {
        if (file_exists($userFile)) {
            $relativePath = str_replace('c:\\Users\\ajbun\\', '', $userFile);
            $targetPath = $baseDir . '/' . $relativePath;
            
            echo '<div class="file-item warning">';
            echo '<div>';
            echo '<strong>⚠️ Found in User Directory</strong><br>';
            echo '<span class="file-path">' . htmlspecialchars($userFile) . '</span><br>';
            echo '<span style="color: #666;">Should be at: ' . htmlspecialchars($targetPath) . '</span>';
            echo '</div>';
            echo '<div>';
            if (file_exists($targetPath)) {
                echo '<span style="color: green;">✅ Also exists in XAMPP</span>';
            } else {
                echo '<span style="color: red;">❌ Missing in XAMPP - NEEDS COPY</span>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
    echo '</div>';
    
    // Summary
    echo '<div class="container">';
    echo '<h2>📊 Summary</h2>';
    echo '<p><strong>Total Files Checked:</strong> ' . count($existingFiles) . ' found, ' . count($missingFiles) . ' missing</p>';
    
    if (count($missingFiles) > 0) {
        echo '<h3>❌ Missing Files:</h3>';
        echo '<ul>';
        foreach ($missingFiles as $file) {
            echo '<li><code>' . htmlspecialchars($file['path']) . '</code></li>';
        }
        echo '</ul>';
        echo '<p class="warning"><strong>Action Required:</strong> Copy missing files to their correct locations.</p>';
    } else {
        echo '<p class="success"><strong>✅ All required files are present!</strong></p>';
    }
    echo '</div>';
    
    // Check API.js content
    $apiJsPath = $baseDir . '/api/api.js';
    if (file_exists($apiJsPath)) {
        echo '<div class="container">';
        echo '<h2>🔌 API.js Configuration</h2>';
        $apiJsContent = file_get_contents($apiJsPath);
        
        if (strpos($apiJsContent, 'API_BASE_URL') !== false) {
            preg_match('/const API_BASE_URL = ([^;]+);/', $apiJsContent, $matches);
            if (!empty($matches[1])) {
                echo '<p><strong>API Base URL Logic:</strong></p>';
                echo '<pre>' . htmlspecialchars($matches[1]) . '</pre>';
            }
        }
        
        // Check if it has all API objects
        $hasAccountsAPI = strpos($apiJsContent, 'AccountsAPI') !== false;
        $hasBookingsAPI = strpos($apiJsContent, 'BookingsAPI') !== false;
        $hasMotorcyclesAPI = strpos($apiJsContent, 'MotorcyclesAPI') !== false;
        
        echo '<p>';
        echo ($hasAccountsAPI ? '✅' : '❌') . ' AccountsAPI<br>';
        echo ($hasBookingsAPI ? '✅' : '❌') . ' BookingsAPI<br>';
        echo ($hasMotorcyclesAPI ? '✅' : '❌') . ' MotorcyclesAPI<br>';
        echo '</p>';
        
        echo '</div>';
    }
    
    // Instructions
    echo '<div class="container">';
    echo '<h2>📋 Instructions</h2>';
    echo '<ol>';
    echo '<li><strong>If files are missing:</strong> Copy them from <code>c:\\Users\\ajbun\\</code> to <code>c:\\xampp\\htdocs\\motorent\\</code></li>';
    echo '<li><strong>API files:</strong> Should be in <code>c:\\xampp\\htdocs\\motorent\\api\\</code></li>';
    echo '<li><strong>Frontend files:</strong> Should be in <code>c:\\xampp\\htdocs\\motorent\\</code></li>';
    echo '<li><strong>After copying:</strong> Refresh this page to verify</li>';
    echo '</ol>';
    echo '</div>';
    ?>
</body>
</html>

