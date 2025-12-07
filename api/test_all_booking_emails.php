<?php
/**
 * Test Email Sending to All Booking Customers
 * Access via: http://localhost/motorent/api/test_all_booking_emails.php
 * 
 * This page allows you to:
 * - View all bookings and customer emails
 * - Send test emails to all customers
 * - Send emails to specific customers
 */

require_once 'config.php';
require_once 'email_config.php';
require_once 'send_email.php';

header('Content-Type: text/html; charset=utf-8');

// Get all bookings from database
$conn = getDBConnection();
$bookings = [];
$dbError = null;
$totalCount = 0;

if (!$conn) {
    $dbError = "Failed to connect to database";
} else {
    // Check if bookings table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // First, get total count
        $countResult = $conn->query("SELECT COUNT(*) as total FROM bookings");
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $totalCount = $countRow['total'];
        }
        
        // Get all bookings
        $sql = "SELECT id, owner_username, customer_name, customer_email, bike_name, pickup_datetime, return_datetime, pickup_location, total_price, booking_date FROM bookings ORDER BY booking_date DESC";
        $result = $conn->query($sql);
        
        if ($result === false) {
            $dbError = "Query failed: " . $conn->error;
        } elseif ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
        }
    } else {
        $dbError = "Bookings table does not exist in database";
    }
}

// Handle email sending
$sendResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_all') {
        // Send to all customers
        foreach ($bookings as $booking) {
            if (!empty($booking['customer_email'])) {
                $pickupFormatted = date('F j, Y g:i A', strtotime($booking['pickup_datetime']));
                $returnFormatted = date('F j, Y g:i A', strtotime($booking['return_datetime']));
                
                $subject = 'Your Motorcycle Rental Booking is Confirmed!';
                $message = getEmailTemplate('booking_confirmation', [
                    'customer_name' => $booking['customer_name'],
                    'bike_name' => $booking['bike_name'],
                    'pickup_datetime' => $pickupFormatted,
                    'return_datetime' => $returnFormatted,
                    'pickup_location' => $booking['pickup_location'],
                    'total_price' => number_format($booking['total_price'], 2)
                ]);
                
                $result = sendEmailAdvanced($booking['customer_email'], $subject, $message);
                $sendResults[] = [
                    'email' => $booking['customer_email'],
                    'name' => $booking['customer_name'],
                    'bike' => $booking['bike_name'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
            }
        }
    } elseif ($action === 'send_selected') {
        // Send to selected customers
        $selectedIds = $_POST['selected_ids'] ?? [];
        foreach ($selectedIds as $bookingId) {
            $booking = array_filter($bookings, function($b) use ($bookingId) {
                return $b['id'] == $bookingId;
            });
            
            if (!empty($booking)) {
                $booking = array_values($booking)[0];
                if (!empty($booking['customer_email'])) {
                    $pickupFormatted = date('F j, Y g:i A', strtotime($booking['pickup_datetime']));
                    $returnFormatted = date('F j, Y g:i A', strtotime($booking['return_datetime']));
                    
                    $subject = 'Your Motorcycle Rental Booking is Confirmed!';
                    $message = getEmailTemplate('booking_confirmation', [
                        'customer_name' => $booking['customer_name'],
                        'bike_name' => $booking['bike_name'],
                        'pickup_datetime' => $pickupFormatted,
                        'return_datetime' => $returnFormatted,
                        'pickup_location' => $booking['pickup_location'],
                        'total_price' => number_format($booking['total_price'], 2)
                    ]);
                    
                    $result = sendEmailAdvanced($booking['customer_email'], $subject, $message);
                    $sendResults[] = [
                        'email' => $booking['customer_email'],
                        'name' => $booking['customer_name'],
                        'bike' => $booking['bike_name'],
                        'success' => $result['success'],
                        'error' => $result['error'] ?? null
                    ];
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Booking Emails - MOTORENT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .stat-card strong {
            display: block;
            font-size: 24px;
            color: #667eea;
        }
        
        .actions {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .email-cell {
            font-family: monospace;
            color: #2196F3;
        }
        
        .success {
            color: #4caf50;
            font-weight: bold;
        }
        
        .error {
            color: #f44336;
            font-weight: bold;
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .result-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .result-item.success {
            background: #d4edda;
            border-color: #4caf50;
        }
        
        .result-item.error {
            background: #f8d7da;
            border-color: #f44336;
        }
        
        .checkbox-cell {
            text-align: center;
        }
        
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .config-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .config-info strong {
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Booking Emails</h1>
        <p class="subtitle">Send confirmation emails to all booking customers</p>
        
        <div class="config-info">
            <strong>Email Configuration:</strong><br>
            SMTP Host: <?php echo SMTP_HOST; ?><br>
            SMTP Port: <?php echo SMTP_PORT; ?><br>
            From Email: <?php echo SMTP_FROM_EMAIL; ?><br>
            Email Enabled: <?php echo EMAIL_ENABLED ? '✅ Yes' : '❌ No'; ?><br>
            Password Set: <?php echo !empty(SMTP_PASSWORD) ? '✅ Yes' : '❌ No'; ?><br>
            <br>
            <strong>Database Status:</strong><br>
            <?php if ($dbError): ?>
                <span style="color: red;">❌ Error: <?php echo htmlspecialchars($dbError); ?></span><br>
            <?php else: ?>
                <span style="color: green;">✅ Connected</span><br>
                Database: <?php echo defined('DB_NAME') ? DB_NAME : 'Unknown'; ?><br>
            <?php endif; ?>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <strong><?php echo $totalCount > 0 ? $totalCount : count($bookings); ?></strong>
                <span>Total Bookings in DB</span>
            </div>
            <div class="stat-card">
                <strong><?php echo count($bookings); ?></strong>
                <span>Bookings Loaded</span>
            </div>
            <div class="stat-card">
                <strong><?php echo count(array_filter($bookings, function($b) { return !empty($b['customer_email']); })); ?></strong>
                <span>Customers with Email</span>
            </div>
            <div class="stat-card">
                <strong><?php echo count(array_unique(array_column($bookings, 'customer_email'))); ?></strong>
                <span>Unique Email Addresses</span>
            </div>
        </div>
        
        <?php if (!empty($sendResults)): ?>
        <div class="results">
            <h2>Email Send Results</h2>
            <?php
            $successCount = count(array_filter($sendResults, function($r) { return $r['success']; }));
            $failCount = count($sendResults) - $successCount;
            ?>
            <p><strong>Sent: <?php echo $successCount; ?> | Failed: <?php echo $failCount; ?></strong></p>
            
            <?php foreach ($sendResults as $result): ?>
            <div class="result-item <?php echo $result['success'] ? 'success' : 'error'; ?>">
                <strong><?php echo htmlspecialchars($result['name']); ?></strong> 
                (<?php echo htmlspecialchars($result['email']); ?>) - 
                <?php echo htmlspecialchars($result['bike']); ?><br>
                <?php if ($result['success']): ?>
                    <span class="success">✅ Email sent successfully</span>
                <?php else: ?>
                    <span class="error">❌ Failed: <?php echo htmlspecialchars($result['error']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="emailForm">
            <input type="hidden" name="action" id="formAction" value="">
            <input type="hidden" name="selected_ids" id="selectedIds" value="">
            
            <div class="actions">
                <button type="button" class="btn btn-primary" onclick="sendToAll()">
                    📧 Send to All Customers
                </button>
                <button type="button" class="btn btn-success" onclick="sendToSelected()">
                    ✅ Send to Selected
                </button>
                <button type="button" class="btn btn-danger" onclick="clearSelection()">
                    🗑️ Clear Selection
                </button>
                <a href="debug_booking_email.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
                    🔧 Test Single Email
                </a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAll" onchange="toggleAll()">
                        </th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Motorcycle</th>
                        <th>Owner</th>
                        <th>Pickup Date</th>
                        <th>Total Price</th>
                        <th>Booking Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                            <?php if ($dbError): ?>
                                <strong style="color: red;">Database Error:</strong><br>
                                <?php echo htmlspecialchars($dbError); ?><br><br>
                                <small>Please check your database connection in config.php</small>
                            <?php elseif ($totalCount > 0 && count($bookings) == 0): ?>
                                <strong style="color: orange;">⚠️ Warning:</strong><br>
                                Database shows <?php echo $totalCount; ?> booking(s) but query returned 0 results.<br><br>
                                <small>There may be a data format issue. <a href="bookings.php" target="_blank">Check bookings API</a></small>
                            <?php else: ?>
                                No bookings found in the database.<br><br>
                                <small>If you see bookings in the admin panel, check: 
                                <a href="bookings.php" target="_blank">bookings API</a> | 
                                <a href="?refresh=1">Refresh Page</a></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td class="checkbox-cell">
                            <input type="checkbox" class="booking-checkbox" value="<?php echo $booking['id']; ?>" 
                                   data-email="<?php echo htmlspecialchars($booking['customer_email']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                        <td class="email-cell">
                            <?php 
                            if (!empty($booking['customer_email'])) {
                                echo htmlspecialchars($booking['customer_email']);
                            } else {
                                echo '<span style="color: #999;">No email</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($booking['bike_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($booking['owner_username'] ?? 'N/A'); ?></small></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($booking['pickup_datetime'])); ?></td>
                        <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
    
    <script>
        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.booking-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
        }
        
        function clearSelection() {
            document.getElementById('selectAll').checked = false;
            document.querySelectorAll('.booking-checkbox').forEach(cb => {
                cb.checked = false;
            });
        }
        
        function getSelectedIds() {
            const selected = [];
            document.querySelectorAll('.booking-checkbox:checked').forEach(cb => {
                const email = cb.getAttribute('data-email');
                if (email && email.trim() !== '') {
                    selected.push(cb.value);
                }
            });
            return selected;
        }
        
        function sendToAll() {
            if (confirm('Send confirmation emails to ALL customers? This may take a while.')) {
                document.getElementById('formAction').value = 'send_all';
                document.getElementById('emailForm').submit();
            }
        }
        
        function sendToSelected() {
            const selected = getSelectedIds();
            if (selected.length === 0) {
                alert('Please select at least one customer with an email address.');
                return;
            }
            
            if (confirm(`Send confirmation emails to ${selected.length} selected customer(s)?`)) {
                document.getElementById('formAction').value = 'send_selected';
                document.getElementById('selectedIds').value = JSON.stringify(selected);
                document.getElementById('emailForm').submit();
            }
        }
        
        // Disable checkboxes for rows without email
        document.querySelectorAll('.booking-checkbox').forEach(cb => {
            const email = cb.getAttribute('data-email');
            if (!email || email.trim() === '') {
                cb.disabled = true;
                cb.title = 'No email address';
            }
        });
    </script>
</body>
</html>

