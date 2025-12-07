<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Get profile
        if (isset($_GET['username'])) {
            $username = $conn->real_escape_string($_GET['username']);
            $stmt = $conn->prepare("SELECT * FROM profiles WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'data' => null]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Username required']);
        }
        break;
        
    case 'POST':
    case 'PUT':
        // Create or update profile
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $conn->real_escape_string($data['username']);
        $photo = isset($data['photo']) ? $data['photo'] : '';
        $business_name = isset($data['businessName']) ? $conn->real_escape_string($data['businessName']) : '';
        $contact_number = isset($data['contactNumber']) ? $conn->real_escape_string($data['contactNumber']) : '';
        $description = isset($data['description']) ? $conn->real_escape_string($data['description']) : '';
        
        // Check if profile exists
        $check = $conn->prepare("SELECT id FROM profiles WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
        
        if ($exists) {
            // Update
            $stmt = $conn->prepare("UPDATE profiles SET photo = ?, business_name = ?, contact_number = ?, description = ? WHERE username = ?");
            $stmt->bind_param("sssss", $photo, $business_name, $contact_number, $description, $username);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO profiles (username, photo, business_name, contact_number, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $photo, $business_name, $contact_number, $description);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save profile']);
        }
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

