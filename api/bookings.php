<?php
// Set error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Get bookings
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : null;
        
        if (isset($_GET['username'])) {
            $username = $conn->real_escape_string($_GET['username']);
            $returned = isset($_GET['returned']) ? intval($_GET['returned']) : null;
            
            $sql = "SELECT * FROM bookings WHERE owner_username = ?";
            $params = [$username];
            $types = "s";
            
            if ($returned !== null) {
                $sql .= " AND returned = ?";
                $params[] = $returned;
                $types .= "i";
            }
            
            if ($search) {
                $sql .= " AND (customer_name LIKE ? OR customer_email LIKE ? OR bike_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $sql .= " ORDER BY booking_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else {
            $sql = "SELECT * FROM bookings WHERE 1=1";
            $params = [];
            $types = "";
            
            if ($search) {
                $sql .= " AND (customer_name LIKE ? OR customer_email LIKE ? OR bike_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types = "sss";
            }
            
            $sql .= " ORDER BY booking_date DESC";
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            // Convert database fields to match frontend format
            $booking = [
                'id' => $row['id'],
                'bike' => $row['bike_name'],
                'customer' => [
                    'name' => $row['customer_name'],
                    'email' => $row['customer_email'],
                    'address' => $row['customer_address'],
                    'idPhoto' => $row['customer_id_photo']
                ],
                'pickupLocation' => $row['pickup_location'],
                'pickupDateTime' => $row['pickup_datetime'],
                'returnDateTime' => $row['return_datetime'],
                'days' => intval($row['days']),
                'hours' => floatval($row['hours']),
                'totalPrice' => floatval($row['total_price']),
                'started' => $row['started'] == 1,
                'startedDate' => $row['started_date'],
                'returned' => $row['returned'] == 1,
                'returnedDate' => $row['returned_date'],
                'bookingDate' => $row['booking_date']
            ];
            $bookings[] = $booking;
        }
        
        echo json_encode(['success' => true, 'data' => $bookings]);
        $stmt->close();
        break;
        
    case 'POST':
        // Create new booking
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Check if JSON decode failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg(), 'input' => substr($input, 0, 200)]);
            $conn->close();
            exit;
        }
        
        // Validate required fields
        if (!isset($data['owner']) || !isset($data['bike']) || !isset($data['customer']) || !isset($data['pickupDateTime']) || !isset($data['returnDateTime']) || !isset($data['totalPrice'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields', 'received' => array_keys($data ?? [])]);
            $conn->close();
            exit;
        }
        
        $owner_username = $conn->real_escape_string($data['owner']);
        $bike_name = $conn->real_escape_string($data['bike']);
        $customer_name = isset($data['customer']['name']) ? $conn->real_escape_string($data['customer']['name']) : '';
        $customer_email = isset($data['customer']['email']) ? $conn->real_escape_string($data['customer']['email']) : '';
        $customer_address = isset($data['customer']['address']) ? $conn->real_escape_string($data['customer']['address']) : '';
        $customer_id_photo = isset($data['customer']['idPhoto']) ? $data['customer']['idPhoto'] : '';
        $pickup_location = isset($data['pickupLocation']) ? $conn->real_escape_string($data['pickupLocation']) : '';
        $pickup_datetime = $conn->real_escape_string($data['pickupDateTime']);
        $return_datetime = $conn->real_escape_string($data['returnDateTime']);
        $days = intval($data['days'] ?? 0);
        $hours = floatval($data['hours'] ?? 0);
        $total_price = floatval($data['totalPrice']);
        
        $stmt = $conn->prepare("INSERT INTO bookings (owner_username, bike_name, customer_name, customer_email, customer_address, customer_id_photo, pickup_location, pickup_datetime, return_datetime, days, hours, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssidd", $owner_username, $bike_name, $customer_name, $customer_email, $customer_address, $customer_id_photo, $pickup_location, $pickup_datetime, $return_datetime, $days, $hours, $total_price);
        
        if ($stmt->execute()) {
            $bookingId = $conn->insert_id;
            
            // Send email notifications directly
            require_once __DIR__ . '/email_config.php';
            require_once __DIR__ . '/send_email.php';
            
            // Format dates for email
            $pickupFormatted = date('F j, Y g:i A', strtotime($pickup_datetime));
            $returnFormatted = date('F j, Y g:i A', strtotime($return_datetime));
            
            $emailErrors = [];
            
            // 1. Send confirmation email to customer
            $customerSubject = 'Your Motorcycle Rental Booking is Confirmed!';
            $customerMessage = getEmailTemplate('booking_confirmation', [
                'customer_name' => $customer_name,
                'bike_name' => $bike_name,
                'pickup_datetime' => $pickupFormatted,
                'return_datetime' => $returnFormatted,
                'pickup_location' => $pickup_location,
                'total_price' => number_format($total_price, 2)
            ]);
            
            // Send email to customer
            $customerEmailResult = sendEmailAdvanced($customer_email, $customerSubject, $customerMessage);
            if (!$customerEmailResult['success']) {
                $errorMsg = 'Customer booking confirmation email failed for ' . $customer_email . ': ' . ($customerEmailResult['error'] ?? 'Unknown error');
                error_log($errorMsg);
                $emailErrors[] = $errorMsg;
                // Also log to a file for easier debugging
                file_put_contents(__DIR__ . '/email_errors.log', date('Y-m-d H:i:s') . ' - Customer Email Error: ' . $errorMsg . PHP_EOL, FILE_APPEND);
            } else {
                error_log('Customer booking confirmation email sent successfully to ' . $customer_email);
                file_put_contents(__DIR__ . '/email_success.log', date('Y-m-d H:i:s') . ' - Customer Email Sent: ' . $customer_email . PHP_EOL, FILE_APPEND);
            }
            
            // 2. Get provider email and send notification
            $providerStmt = $conn->prepare("SELECT email FROM accounts WHERE username = ?");
            $providerStmt->bind_param("s", $owner_username);
            $providerStmt->execute();
            $providerResult = $providerStmt->get_result();
            if ($providerRow = $providerResult->fetch_assoc()) {
                $providerSubject = 'New Booking Received - ' . $bike_name;
                $providerMessage = getEmailTemplate('new_booking_notification', [
                    'provider_name' => $owner_username,
                    'bike_name' => $bike_name,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'pickup_datetime' => $pickupFormatted,
                    'return_datetime' => $returnFormatted,
                    'total_price' => number_format($total_price, 2),
                    'dashboard_url' => 'http://localhost/motorent/'
                ]);
                
                $providerEmailResult = sendEmailAdvanced($providerRow['email'], $providerSubject, $providerMessage);
                if (!$providerEmailResult['success']) {
                    $errorMsg = 'Provider booking notification email failed for ' . $providerRow['email'] . ': ' . ($providerEmailResult['error'] ?? 'Unknown error');
                    error_log($errorMsg);
                    $emailErrors[] = $errorMsg;
                    file_put_contents(__DIR__ . '/email_errors.log', date('Y-m-d H:i:s') . ' - Provider Email Error: ' . $errorMsg . PHP_EOL, FILE_APPEND);
                } else {
                    error_log('Provider booking notification email sent successfully to ' . $providerRow['email']);
                    file_put_contents(__DIR__ . '/email_success.log', date('Y-m-d H:i:s') . ' - Provider Email Sent: ' . $providerRow['email'] . PHP_EOL, FILE_APPEND);
                }
            } else {
                error_log('Provider email not found for username: ' . $owner_username);
            }
            $providerStmt->close();
            
            // Return success even if emails fail (booking is still created)
            $response = [
                'success' => true, 
                'message' => 'Booking created successfully', 
                'id' => $bookingId,
                'email_sent' => empty($emailErrors),
                'customer_email_sent' => isset($customerEmailResult) && $customerEmailResult['success'],
                'provider_email_sent' => isset($providerEmailResult) && $providerEmailResult['success']
            ];
            if (!empty($emailErrors)) {
                $response['email_warnings'] = $emailErrors;
                $response['email_errors'] = $emailErrors;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create booking: ' . $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'PUT':
        // Update booking (start, confirm return, etc.)
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        $action = $data['action'] ?? '';
        
        if ($action === 'start') {
            $stmt = $conn->prepare("UPDATE bookings SET started = 1, started_date = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($action === 'confirm_return') {
            $stmt = $conn->prepare("UPDATE bookings SET returned = 1, returned_date = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            $conn->close();
            exit;
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update booking']);
        }
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

