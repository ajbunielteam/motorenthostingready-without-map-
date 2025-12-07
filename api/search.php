<?php
/**
 * Universal Search API
 * Search across all tables in the database
 * 
 * Usage:
 * GET /api/search.php?q=searchterm&table=accounts|motorcycles|bookings|tickets
 */

require_once 'config.php';

$conn = getDBConnection();
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$table = isset($_GET['table']) ? $_GET['table'] : 'all';

if (empty($searchTerm)) {
    echo json_encode(['success' => false, 'error' => 'Search term required']);
    exit;
}

$search = $conn->real_escape_string($searchTerm);
$results = [];

// Search in accounts
if ($table === 'all' || $table === 'accounts') {
    $stmt = $conn->prepare("SELECT 'account' as type, id, username as title, email as subtitle, status, created_at 
                           FROM accounts 
                           WHERE username LIKE ? OR email LIKE ? OR location LIKE ?
                           LIMIT 10");
    $searchPattern = "%$search%";
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

// Search in motorcycles
if ($table === 'all' || $table === 'motorcycles') {
    $stmt = $conn->prepare("SELECT 'motorcycle' as type, m.id, m.name as title, m.owner_username as subtitle, 
                           m.available, m.created_at 
                           FROM motorcycles m
                           WHERE m.name LIKE ? OR m.category LIKE ? OR m.description LIKE ?
                           LIMIT 10");
    $searchPattern = "%$search%";
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

// Search in bookings
if ($table === 'all' || $table === 'bookings') {
    $stmt = $conn->prepare("SELECT 'booking' as type, b.id, b.customer_name as title, b.bike_name as subtitle, 
                           b.total_price, b.booking_date as created_at
                           FROM bookings b
                           WHERE b.customer_name LIKE ? OR b.customer_email LIKE ? OR b.bike_name LIKE ?
                           LIMIT 10");
    $searchPattern = "%$search%";
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

// Search in tickets
if ($table === 'all' || $table === 'tickets') {
    $stmt = $conn->prepare("SELECT 'ticket' as type, t.id, t.subject as title, t.name as subtitle, 
                           t.read_status, t.created_at
                           FROM tickets t
                           WHERE t.name LIKE ? OR t.email LIKE ? OR t.subject LIKE ? OR t.message LIKE ?
                           LIMIT 10");
    $searchPattern = "%$search%";
    $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'query' => $searchTerm,
    'table' => $table,
    'count' => count($results),
    'data' => $results
]);

$conn->close();
?>

