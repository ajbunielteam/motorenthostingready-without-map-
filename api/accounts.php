<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Get all accounts or specific account
        if (isset($_GET['username'])) {
            $username = $conn->real_escape_string($_GET['username']);
            $stmt = $conn->prepare("SELECT id, username, email, location, license, valid_id, status, created_at FROM accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Account not found']);
            }
            $stmt->close();
        } else {
            $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : null;
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : null;
            
            // Build query with search
            $sql = "SELECT id, username, email, location, license, valid_id, status, created_at FROM accounts WHERE 1=1";
            $params = [];
            $types = "";
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            if ($search) {
                $sql .= " AND (username LIKE ? OR email LIKE ? OR location LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $accounts = [];
            
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $accounts]);
            $stmt->close();
        }
        break;
        
    case 'POST':
        // Create new account
        $data = json_decode(file_get_contents('php://input'), true);
        
        $username = $conn->real_escape_string($data['username']);
        $email = $conn->real_escape_string($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $location = isset($data['location']) ? $conn->real_escape_string($data['location']) : '';
        $license = isset($data['license']) ? $data['license'] : '';
        $valid_id = isset($data['validId']) ? $data['validId'] : '';
        $status = isset($data['status']) ? $conn->real_escape_string($data['status']) : 'pending';
        
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM accounts WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
            $check->close();
            break;
        }
        $check->close();
        
        $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, location, license, valid_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $password, $location, $license, $valid_id, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Account created successfully', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create account: ' . $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'PUT':
        // Update account
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $conn->real_escape_string($data['status']);
            $types .= 's';
        }
        
        if (isset($data['location'])) {
            $updates[] = "location = ?";
            $params[] = $conn->real_escape_string($data['location']);
            $types .= 's';
        }
        
        if (count($updates) > 0) {
            $params[] = $id;
            $types .= 'i';
            $sql = "UPDATE accounts SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Send email notification if status changed
                if (isset($data['status'])) {
                    $status = $data['status'];
                    // Get account email
                    $emailStmt = $conn->prepare("SELECT username, email FROM accounts WHERE id = ?");
                    $emailStmt->bind_param("i", $id);
                    $emailStmt->execute();
                    $emailResult = $emailStmt->get_result();
                    if ($emailRow = $emailResult->fetch_assoc()) {
                        // Send email notification if status is approved or denied
                        if ($status === 'approved' || $status === 'denied') {
                            // Send email notification directly
                            require_once __DIR__ . '/send_email.php';
                            
                            if ($status === 'approved') {
                                $subject = 'Your MOTORENT Account Has Been Approved!';
                                $message = getEmailTemplate('account_approved', [
                                    'username' => $emailRow['username'],
                                    'login_url' => 'http://localhost/motorent/'
                                ]);
                            } else {
                                $subject = 'MOTORENT Account Application Status';
                                $message = getEmailTemplate('account_denied', [
                                    'username' => $emailRow['username'],
                                    'admin_email' => SMTP_FROM_EMAIL
                                ]);
                            }
                            
                            // Send email (don't fail account update if email fails)
                            $emailResult = sendEmailAdvanced($emailRow['email'], $subject, $message);
                            if (!$emailResult['success']) {
                                error_log('Email notification failed for ' . $emailRow['email'] . ': ' . ($emailResult['error'] ?? 'Unknown error'));
                            }
                        }
                    }
                    $emailStmt->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update account']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
        }
        break;
        
    case 'DELETE':
        // Delete account
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $conn->real_escape_string($data['username']);
        
        $stmt = $conn->prepare("DELETE FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete account']);
        }
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

