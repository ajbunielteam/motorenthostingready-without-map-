<?php
/**
 * Check Database Connection and Data
 * Access via: http://localhost/motorent/api/check_database.php
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - MOTORENT</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .stat {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .stat strong {
            display: block;
            font-size: 24px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <h1>🔍 Database Connection & Data Check</h1>
    
    <?php
    // Test database connection
    $conn = getDBConnection();
    
    if (!$conn) {
        echo '<div class="container"><h2 class="error">❌ Database Connection Failed</h2>';
        echo '<p>Could not connect to database. Check config.php settings.</p></div>';
        exit;
    }
    
    echo '<div class="container">';
    echo '<h2 class="success">✅ Database Connected</h2>';
    echo '<p><strong>Database:</strong> ' . DB_NAME . '</p>';
    echo '<p><strong>Host:</strong> ' . DB_HOST . '</p>';
    echo '<p><strong>User:</strong> ' . DB_USER . '</p>';
    echo '</div>';
    
    // Check tables
    echo '<div class="container">';
    echo '<h2>📊 Database Tables</h2>';
    
    $tables = ['accounts', 'profiles', 'motorcycles', 'bookings', 'tickets'];
    $tableStats = [];
    
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as total FROM $table");
            $row = $count->fetch_assoc();
            $tableStats[$table] = $row['total'];
            echo '<div class="stat">';
            echo '<strong>' . $row['total'] . '</strong>';
            echo '<span>' . ucfirst($table) . '</span>';
            echo '</div>';
        } else {
            echo '<p class="error">❌ Table "' . $table . '" does not exist!</p>';
        }
    }
    echo '</div>';
    
    // Show accounts
    echo '<div class="container">';
    echo '<h2>👥 Accounts</h2>';
    $accounts = $conn->query("SELECT id, username, email, status, created_at FROM accounts ORDER BY created_at DESC LIMIT 20");
    if ($accounts && $accounts->num_rows > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Created</th></tr>';
        while ($row = $accounts->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . $row['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ No accounts found in database.</p>';
    }
    echo '</div>';
    
    // Show bookings
    echo '<div class="container">';
    echo '<h2>📅 Bookings</h2>';
    $bookings = $conn->query("SELECT id, owner_username, customer_name, customer_email, bike_name, total_price, booking_date FROM bookings ORDER BY booking_date DESC LIMIT 20");
    if ($bookings && $bookings->num_rows > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Owner</th><th>Customer</th><th>Email</th><th>Bike</th><th>Price</th><th>Date</th></tr>';
        while ($row = $bookings->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['owner_username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['customer_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['customer_email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['bike_name']) . '</td>';
            echo '<td>₱' . number_format($row['total_price'], 2) . '</td>';
            echo '<td>' . $row['booking_date'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ No bookings found in database.</p>';
    }
    echo '</div>';
    
    // Show motorcycles
    echo '<div class="container">';
    echo '<h2>🏍️ Motorcycles</h2>';
    $motorcycles = $conn->query("SELECT id, owner_username, name, category, available, created_at FROM motorcycles ORDER BY created_at DESC LIMIT 20");
    if ($motorcycles && $motorcycles->num_rows > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Owner</th><th>Name</th><th>Category</th><th>Available</th><th>Created</th></tr>';
        while ($row = $motorcycles->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['owner_username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['category']) . '</td>';
            echo '<td>' . $row['available'] . '</td>';
            echo '<td>' . $row['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ No motorcycles found in database.</p>';
    }
    echo '</div>';
    
    $conn->close();
    ?>
    
    <div class="container">
        <h2>🔧 Quick Actions</h2>
        <p>
            <a href="setup_database.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                Setup Database
            </a>
            <a href="test_all_booking_emails.php" style="padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                Test Booking Emails
            </a>
            <a href="check_email_logs.php" style="padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                Check Email Logs
            </a>
        </p>
    </div>
</body>
</html>

