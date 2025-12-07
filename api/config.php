<?php
// Database Configuration for Hostinger
// ⚠️ IMPORTANT: Update these values with your Hostinger database credentials
define('DB_HOST', 'localhost'); // Usually 'localhost' on Hostinger
define('DB_USER', 'u291171953_motorent'); // Your full database username from Hostinger
define('DB_PASS', 'Aerolaerol123'); // ⚠️ REPLACE THIS with your actual database password
define('DB_NAME', 'u291171953_motorent'); // Your full database name from Hostinger

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die(json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $conn->connect_error
            ]));
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]));
    }
}

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

