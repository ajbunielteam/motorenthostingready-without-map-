<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

switch ($method) {
    case 'GET':
        // Get tickets
        $read_status = isset($_GET['read']) ? intval($_GET['read']) : null;
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : null;
        
        $sql = "SELECT * FROM tickets WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($read_status !== null) {
            $sql .= " AND read_status = ?";
            $params[] = $read_status;
            $types .= "i";
        }
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        
        while ($row = $result->fetch_assoc()) {
            $ticket = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'subject' => $row['subject'],
                'message' => $row['message'],
                'status' => $row['status'],
                'read' => $row['read_status'] == 1,
                'createdAt' => $row['created_at']
            ];
            $tickets[] = $ticket;
        }
        
        echo json_encode(['success' => true, 'data' => $tickets]);
        $stmt->close();
        break;
        
    case 'POST':
        // Create new ticket
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = $conn->real_escape_string($data['name']);
        $email = $conn->real_escape_string($data['email']);
        $subject = $conn->real_escape_string($data['subject']);
        $message = $conn->real_escape_string($data['message']);
        
        $stmt = $conn->prepare("INSERT INTO tickets (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $ticketId = $conn->insert_id;
            
            // Send confirmation email to ticket submitter
            $notificationData = [
                'action' => 'ticket_submitted',
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ];
            
            // Send email asynchronously
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/motorent/api/notifications.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_exec($ch);
            curl_close($ch);
            
            echo json_encode(['success' => true, 'message' => 'Ticket submitted successfully', 'id' => $ticketId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to submit ticket: ' . $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'PUT':
        // Update ticket (mark as read, etc.)
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        
        if (isset($data['read'])) {
            $read = intval($data['read']);
            $stmt = $conn->prepare("UPDATE tickets SET read_status = ? WHERE id = ?");
            $stmt->bind_param("ii", $read, $id);
        } else {
            echo json_encode(['success' => false, 'error' => 'No field to update']);
            $conn->close();
            exit;
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update ticket']);
        }
        $stmt->close();
        break;
        
    case 'DELETE':
        // Delete ticket or delete all read tickets
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['delete_all_read']) && $data['delete_all_read'] === true) {
            // Delete all read tickets
            $stmt = $conn->prepare("DELETE FROM tickets WHERE read_status = 1");
            if ($stmt->execute()) {
                $deletedCount = $stmt->affected_rows;
                echo json_encode(['success' => true, 'message' => "Deleted $deletedCount read ticket(s) successfully"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete read tickets']);
            }
            $stmt->close();
        } else {
            // Delete single ticket
            $id = intval($data['id']);
            $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete ticket']);
            }
            $stmt->close();
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();

