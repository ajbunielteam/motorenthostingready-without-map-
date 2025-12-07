<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Get motorcycles
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : null;
        
        if (isset($_GET['username'])) {
            // Get motorcycles for specific owner
            $username = $conn->real_escape_string($_GET['username']);
            $sql = "SELECT * FROM motorcycles WHERE owner_username = ?";
            if ($search) {
                $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
                $searchTerm = "%$search%";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $username, $searchTerm, $searchTerm, $searchTerm);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
            }
        } elseif (isset($_GET['approved_only'])) {
            // Get motorcycles from approved accounts only
            $sql = "SELECT m.* FROM motorcycles m 
                   INNER JOIN accounts a ON m.owner_username = a.username 
                   WHERE a.status = 'approved'";
            if ($search) {
                $sql .= " AND (m.name LIKE ? OR m.category LIKE ? OR m.description LIKE ?)";
                $searchTerm = "%$search%";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            } else {
                $stmt = $conn->prepare($sql);
            }
            $sql .= " ORDER BY m.id DESC";
        } else {
            // Get all motorcycles
            $sql = "SELECT * FROM motorcycles WHERE 1=1";
            if ($search) {
                $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
                $searchTerm = "%$search%";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            } else {
                $stmt = $conn->prepare($sql);
            }
        }
        
        if (!isset($stmt)) {
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $motorcycles = [];
        
        while ($row = $result->fetch_assoc()) {
            $motorcycles[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $motorcycles]);
        $stmt->close();
        break;
        
    case 'POST':
        // Create new motorcycle
        $data = json_decode(file_get_contents('php://input'), true);
        
        $owner_username = $conn->real_escape_string($data['owner']);
        $name = $conn->real_escape_string($data['name']);
        $category = isset($data['category']) ? $conn->real_escape_string($data['category']) : '';
        $transmission = isset($data['transmission']) ? $conn->real_escape_string($data['transmission']) : 'manual';
        $engine = isset($data['engine']) ? $conn->real_escape_string($data['engine']) : '';
        $power = isset($data['power']) ? $conn->real_escape_string($data['power']) : '';
        $top_speed = isset($data['topSpeed']) ? $conn->real_escape_string($data['topSpeed']) : '';
        $description = isset($data['description']) ? $conn->real_escape_string($data['description']) : '';
        $image = isset($data['image']) ? $data['image'] : '';
        $available = intval($data['available'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO motorcycles (owner_username, name, category, transmission, engine, power, top_speed, description, image, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssi", $owner_username, $name, $category, $transmission, $engine, $power, $top_speed, $description, $image, $available);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Motorcycle added successfully', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add motorcycle: ' . $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'PUT':
        // Update motorcycle
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        
        $name = $conn->real_escape_string($data['name']);
        $category = isset($data['category']) ? $conn->real_escape_string($data['category']) : '';
        $transmission = isset($data['transmission']) ? $conn->real_escape_string($data['transmission']) : 'manual';
        $engine = isset($data['engine']) ? $conn->real_escape_string($data['engine']) : '';
        $power = isset($data['power']) ? $conn->real_escape_string($data['power']) : '';
        $top_speed = isset($data['topSpeed']) ? $conn->real_escape_string($data['topSpeed']) : '';
        $description = isset($data['description']) ? $conn->real_escape_string($data['description']) : '';
        $image = isset($data['image']) ? $data['image'] : '';
        $available = intval($data['available'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE motorcycles SET name = ?, category = ?, transmission = ?, engine = ?, power = ?, top_speed = ?, description = ?, image = ?, available = ? WHERE id = ?");
        $stmt->bind_param("ssssssssii", $name, $category, $transmission, $engine, $power, $top_speed, $description, $image, $available, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Motorcycle updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update motorcycle']);
        }
        $stmt->close();
        break;
        
    case 'DELETE':
        // Delete motorcycle
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        
        $stmt = $conn->prepare("DELETE FROM motorcycles WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Motorcycle deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete motorcycle']);
        }
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

