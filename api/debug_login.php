<?php
require_once 'config.php';

header('Content-Type: application/json');

$conn = getDBConnection();

// Get username from query parameter or POST
$username = isset($_GET['username']) ? $_GET['username'] : (isset($_POST['username']) ? $_POST['username'] : '');

if (empty($username)) {
    echo json_encode([
        'error' => 'Username is required',
        'usage' => 'Add ?username=YOUR_USERNAME to the URL or POST username'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Get account info
$stmt = $conn->prepare("SELECT id, username, email, password, status, created_at FROM accounts WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $password_hash = $row['password'];
    $is_hashed = password_get_info($password_hash);
    
    $response = [
        'found' => true,
        'username' => $row['username'],
        'email' => $row['email'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'password_info' => [
            'is_hashed' => $is_hashed['algo'] !== null,
            'algorithm' => $is_hashed['algoName'] ?? 'Not hashed',
            'options' => $is_hashed['options'] ?? null,
            'hash_length' => strlen($password_hash),
            'hash_preview' => substr($password_hash, 0, 20) . '...'
        ]
    ];
    
    // Test password if provided
    if (isset($_POST['test_password'])) {
        $test_password = $_POST['test_password'];
        if ($is_hashed['algo'] !== null) {
            $response['test_password'] = [
                'tested' => true,
                'result' => password_verify($test_password, $password_hash)
            ];
        } else {
            $response['test_password'] = [
                'tested' => true,
                'result' => ($test_password === $password_hash),
                'note' => 'Password is not hashed (legacy account)'
            ];
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    // List all usernames for debugging
    $all_stmt = $conn->prepare("SELECT username, email, status FROM accounts ORDER BY username");
    $all_stmt->execute();
    $all_result = $all_stmt->get_result();
    $usernames = [];
    while ($all_row = $all_result->fetch_assoc()) {
        $usernames[] = [
            'username' => $all_row['username'],
            'email' => $all_row['email'],
            'status' => $all_row['status']
        ];
    }
    echo json_encode([
        'found' => false,
        'message' => 'Username not found in database',
        'all_usernames' => $usernames
    ], JSON_PRETTY_PRINT);
}

$stmt->close();
$conn->close();
?>

