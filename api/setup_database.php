<?php
/**
 * MOTORENT Database Setup Script
 * Run this file once to create the database and all tables
 * 
 * Instructions:
 * 1. Make sure XAMPP MySQL is running
 * 2. Open this file in your browser: http://localhost/motorent/api/setup_database.php
 * 3. The script will create the database and all tables automatically
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Change if you have a MySQL password
$db_name = 'motorent_db';

// Create connection without database (to create the database)
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br><br>Make sure MySQL is running in XAMPP!");
}

echo "<h1>MOTORENT Database Setup</h1>";
echo "<p>Setting up database...</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Database '$db_name' created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating database: " . $conn->error . "</p>";
    $conn->close();
    exit;
}

// Select the database
$conn->select_db($db_name);
$conn->set_charset("utf8mb4");

// Create tables
$tables = [];

// Table: accounts
$tables['accounts'] = "CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location TEXT,
    license TEXT,
    valid_id TEXT,
    status ENUM('pending', 'approved', 'denied', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Table: profiles
$tables['profiles'] = "CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    photo TEXT,
    business_name VARCHAR(255),
    contact_number VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES accounts(username) ON DELETE CASCADE,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Table: motorcycles
$tables['motorcycles'] = "CREATE TABLE IF NOT EXISTS motorcycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_username VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    transmission ENUM('automatic', 'manual') DEFAULT 'manual',
    engine VARCHAR(100),
    power VARCHAR(100),
    top_speed VARCHAR(100),
    description TEXT,
    image TEXT,
    available INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_username) REFERENCES accounts(username) ON DELETE CASCADE,
    INDEX idx_owner (owner_username),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Table: bookings
$tables['bookings'] = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_username VARCHAR(100) NOT NULL,
    bike_name VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_address TEXT,
    customer_id_photo TEXT,
    pickup_location TEXT,
    pickup_datetime DATETIME NOT NULL,
    return_datetime DATETIME NOT NULL,
    days INT DEFAULT 0,
    hours DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,
    started BOOLEAN DEFAULT FALSE,
    started_date DATETIME,
    returned BOOLEAN DEFAULT FALSE,
    returned_date DATETIME,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_username) REFERENCES accounts(username) ON DELETE CASCADE,
    INDEX idx_owner (owner_username),
    INDEX idx_returned (returned),
    INDEX idx_started (started)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Table: tickets
$tables['tickets'] = "CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_read_status (read_status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create each table
$errors = [];
$success = [];

foreach ($tables as $tableName => $sql) {
    if ($conn->query($sql) === TRUE) {
        $success[] = $tableName;
        echo "<p style='color: green;'>✓ Table '$tableName' created successfully</p>";
    } else {
        $errors[] = $tableName;
        echo "<p style='color: red;'>✗ Error creating table '$tableName': " . $conn->error . "</p>";
    }
}

// Summary
echo "<hr>";
echo "<h2>Setup Summary</h2>";
echo "<p><strong>Database:</strong> $db_name</p>";
echo "<p><strong>Tables created:</strong> " . count($success) . " / " . count($tables) . "</p>";

if (count($errors) > 0) {
    echo "<p style='color: red;'><strong>Errors:</strong> " . implode(', ', $errors) . "</p>";
}

if (count($success) === count($tables)) {
    echo "<h2 style='color: green;'>✓ Database setup completed successfully!</h2>";
    echo "<p>You can now use the MOTORENT application.</p>";
    echo "<p><a href='../index.html'>Go to Application</a></p>";
} else {
    echo "<h2 style='color: orange;'>⚠ Setup completed with some errors</h2>";
    echo "<p>Please check the errors above and try again.</p>";
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #333;
    }
    h2 {
        color: #667eea;
        margin-top: 30px;
    }
    p {
        line-height: 1.6;
    }
    a {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }
    a:hover {
        background: #5568d3;
    }
</style>

