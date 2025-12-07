<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'login') {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            $conn->close();
            exit;
        }
        
        $username_escaped = $conn->real_escape_string($username);
        
        $stmt = $conn->prepare("SELECT id, username, email, password, status, location, license, valid_id, created_at FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username_escaped);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Check if account is blocked
            if ($row['status'] === 'blocked') {
                echo json_encode(['success' => false, 'error' => 'Your account has been blocked. Please contact support.']);
            } elseif ($row['status'] === 'pending' || $row['status'] === 'denied') {
                echo json_encode(['success' => false, 'error' => 'Your account is pending approval or has been denied.']);
            } else {
                // Check if password is hashed
                $password_hash = $row['password'];
                $password_info = password_get_info($password_hash);
                $is_hashed = $password_info['algo'] !== null;
                
                // Verify password
                $password_valid = false;
                if ($is_hashed) {
                    // Password is hashed, use password_verify
                    $password_valid = password_verify($password, $password_hash);
                } else {
                    // Password is not hashed (legacy account), do plain comparison
                    // This should only happen for accounts created before password hashing was implemented
                    $password_valid = ($password === $password_hash);
                    
                    // If password matches but is not hashed, re-hash it for security
                    if ($password_valid) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE username = ?");
                        $update_stmt->bind_param("ss", $new_hash, $username_escaped);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                
                if ($password_valid) {
                    // Remove password from response
                    unset($row['password']);
                    // Convert field names to camelCase for consistency
                    $user = [
                        'id' => $row['id'],
                        'username' => $row['username'],
                        'email' => $row['email'],
                        'status' => $row['status'],
                        'location' => $row['location'],
                        'license' => $row['license'],
                        'validId' => $row['valid_id'],
                        'valid_id' => $row['valid_id'],
                        'createdAt' => $row['created_at'],
                        'created_at' => $row['created_at']
                    ];
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
        $stmt->close();
    } elseif ($action === 'check_username') {
        $username = $conn->real_escape_string($data['username']);
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo json_encode(['exists' => $result->num_rows > 0]);
        $stmt->close();
    } elseif ($action === 'check_email') {
        $email = $conn->real_escape_string($data['email']);
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo json_encode(['exists' => $result->num_rows > 0]);
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

