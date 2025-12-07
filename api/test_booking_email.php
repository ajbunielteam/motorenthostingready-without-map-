<?php
/**
 * Test Booking Email Notification
 * This script tests if booking confirmation emails are being sent correctly
 */

require_once 'config.php';
require_once 'send_email.php';

header('Content-Type: application/json');

// Test data (similar to what would be sent from a booking)
$testData = [
    'customer_email' => isset($_GET['email']) ? $_GET['email'] : 'test@gmail.com',
    'customer_name' => 'Test Customer',
    'bike_name' => 'Test Motorcycle',
    'pickup_datetime' => 'December 3, 2025 1:45 PM',
    'return_datetime' => 'December 3, 2025 11:45 PM',
    'pickup_location' => 'Test Location',
    'total_price' => '700.00'
];

// Format dates for email
$pickupFormatted = $testData['pickup_datetime'];
$returnFormatted = $testData['return_datetime'];

// Send confirmation email to customer
$customerSubject = 'Your Motorcycle Rental Booking is Confirmed!';
$customerMessage = getEmailTemplate('booking_confirmation', [
    'customer_name' => $testData['customer_name'],
    'bike_name' => $testData['bike_name'],
    'pickup_datetime' => $pickupFormatted,
    'return_datetime' => $returnFormatted,
    'pickup_location' => $testData['pickup_location'],
    'total_price' => $testData['total_price']
]);

$result = sendEmailAdvanced($testData['customer_email'], $customerSubject, $customerMessage);

echo json_encode([
    'test' => 'booking_confirmation_email',
    'to' => $testData['customer_email'],
    'result' => $result,
    'config' => [
        'email_enabled' => EMAIL_ENABLED,
        'smtp_host' => SMTP_HOST,
        'smtp_port' => SMTP_PORT,
        'smtp_username' => SMTP_USERNAME,
        'smtp_from_email' => SMTP_FROM_EMAIL,
        'has_password' => !empty(SMTP_PASSWORD)
    ]
], JSON_PRETTY_PRINT);

