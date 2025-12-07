<?php
/**
 * Email Notification Service
 * Handles sending emails for various events
 */

require_once 'config.php';
require_once 'send_email.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'account_approved':
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Email address required']);
                exit;
            }
            
            $subject = 'Your MOTORENT Account Has Been Approved!';
            $message = getEmailTemplate('account_approved', [
                'username' => $username,
                'login_url' => 'http://localhost/motorent/'
            ]);
            
            $result = sendEmailAdvanced($email, $subject, $message);
            echo json_encode($result);
            break;
            
        case 'account_denied':
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Email address required']);
                exit;
            }
            
            $subject = 'MOTORENT Account Application Status';
            $message = getEmailTemplate('account_denied', [
                'username' => $username,
                'admin_email' => SMTP_FROM_EMAIL
            ]);
            
            $result = sendEmailAdvanced($email, $subject, $message);
            echo json_encode($result);
            break;
            
        case 'booking_confirmation':
            $customerEmail = $data['customer_email'] ?? '';
            $customerName = $data['customer_name'] ?? '';
            $bikeName = $data['bike_name'] ?? '';
            $pickupDateTime = $data['pickup_datetime'] ?? '';
            $returnDateTime = $data['return_datetime'] ?? '';
            $pickupLocation = $data['pickup_location'] ?? '';
            $totalPrice = $data['total_price'] ?? '';
            
            if (empty($customerEmail)) {
                echo json_encode(['success' => false, 'error' => 'Customer email required']);
                exit;
            }
            
            $subject = 'Your Motorcycle Rental Booking is Confirmed!';
            $message = getEmailTemplate('booking_confirmation', [
                'customer_name' => $customerName,
                'bike_name' => $bikeName,
                'pickup_datetime' => $pickupDateTime,
                'return_datetime' => $returnDateTime,
                'pickup_location' => $pickupLocation,
                'total_price' => number_format($totalPrice, 2)
            ]);
            
            $result = sendEmailAdvanced($customerEmail, $subject, $message);
            echo json_encode($result);
            break;
            
        case 'new_booking_notification':
            $providerEmail = $data['provider_email'] ?? '';
            $providerName = $data['provider_name'] ?? '';
            $bikeName = $data['bike_name'] ?? '';
            $customerName = $data['customer_name'] ?? '';
            $customerEmail = $data['customer_email'] ?? '';
            $pickupDateTime = $data['pickup_datetime'] ?? '';
            $returnDateTime = $data['return_datetime'] ?? '';
            $totalPrice = $data['total_price'] ?? '';
            
            if (empty($providerEmail)) {
                echo json_encode(['success' => false, 'error' => 'Provider email required']);
                exit;
            }
            
            $subject = 'New Booking Received - ' . $bikeName;
            $message = getEmailTemplate('new_booking_notification', [
                'provider_name' => $providerName,
                'bike_name' => $bikeName,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'pickup_datetime' => $pickupDateTime,
                'return_datetime' => $returnDateTime,
                'total_price' => number_format($totalPrice, 2),
                'dashboard_url' => 'http://localhost/motorent/'
            ]);
            
            $result = sendEmailAdvanced($providerEmail, $subject, $message);
            echo json_encode($result);
            break;
            
        case 'ticket_submitted':
            $name = $data['name'] ?? '';
            $email = $data['email'] ?? '';
            $subject = $data['subject'] ?? '';
            $messageText = $data['message'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Email address required']);
                exit;
            }
            
            $emailSubject = 'Support Ticket Received - ' . $subject;
            $message = getEmailTemplate('ticket_submitted', [
                'name' => $name,
                'subject' => $subject,
                'message' => nl2br(htmlspecialchars($messageText))
            ]);
            
            $result = sendEmailAdvanced($email, $emailSubject, $message);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Helper function to send notification email (can be called directly from other PHP files)
function sendNotificationEmail($action, $data) {
    switch ($action) {
        case 'account_approved':
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            
            if (empty($email)) {
                return ['success' => false, 'error' => 'Email address required'];
            }
            
            $subject = 'Your MOTORENT Account Has Been Approved!';
            $message = getEmailTemplate('account_approved', [
                'username' => $username,
                'login_url' => 'http://localhost/motorent/'
            ]);
            
            return sendEmailAdvanced($email, $subject, $message);
            
        case 'account_denied':
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            
            if (empty($email)) {
                return ['success' => false, 'error' => 'Email address required'];
            }
            
            $subject = 'MOTORENT Account Application Status';
            $message = getEmailTemplate('account_denied', [
                'username' => $username,
                'admin_email' => SMTP_FROM_EMAIL
            ]);
            
            return sendEmailAdvanced($email, $subject, $message);
            
        case 'booking_confirmation':
            $customerEmail = $data['customer_email'] ?? '';
            $customerName = $data['customer_name'] ?? '';
            $bikeName = $data['bike_name'] ?? '';
            $pickupDateTime = $data['pickup_datetime'] ?? '';
            $returnDateTime = $data['return_datetime'] ?? '';
            $pickupLocation = $data['pickup_location'] ?? '';
            $totalPrice = $data['total_price'] ?? '';
            
            if (empty($customerEmail)) {
                return ['success' => false, 'error' => 'Customer email required'];
            }
            
            $subject = 'Your Motorcycle Rental Booking is Confirmed!';
            $message = getEmailTemplate('booking_confirmation', [
                'customer_name' => $customerName,
                'bike_name' => $bikeName,
                'pickup_datetime' => $pickupDateTime,
                'return_datetime' => $returnDateTime,
                'pickup_location' => $pickupLocation,
                'total_price' => number_format($totalPrice, 2)
            ]);
            
            return sendEmailAdvanced($customerEmail, $subject, $message);
            
        case 'new_booking_notification':
            $providerEmail = $data['provider_email'] ?? '';
            $providerName = $data['provider_name'] ?? '';
            $bikeName = $data['bike_name'] ?? '';
            $customerName = $data['customer_name'] ?? '';
            $customerEmail = $data['customer_email'] ?? '';
            $pickupDateTime = $data['pickup_datetime'] ?? '';
            $returnDateTime = $data['return_datetime'] ?? '';
            $totalPrice = $data['total_price'] ?? '';
            
            if (empty($providerEmail)) {
                return ['success' => false, 'error' => 'Provider email required'];
            }
            
            $subject = 'New Booking Received - ' . $bikeName;
            $message = getEmailTemplate('new_booking_notification', [
                'provider_name' => $providerName,
                'bike_name' => $bikeName,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'pickup_datetime' => $pickupDateTime,
                'return_datetime' => $returnDateTime,
                'total_price' => number_format($totalPrice, 2),
                'dashboard_url' => 'http://localhost/motorent/'
            ]);
            
            return sendEmailAdvanced($providerEmail, $subject, $message);
            
        case 'ticket_submitted':
            $name = $data['name'] ?? '';
            $email = $data['email'] ?? '';
            $subject = $data['subject'] ?? '';
            $messageText = $data['message'] ?? '';
            
            if (empty($email)) {
                return ['success' => false, 'error' => 'Email address required'];
            }
            
            $emailSubject = 'Support Ticket Received - ' . $subject;
            $message = getEmailTemplate('ticket_submitted', [
                'name' => $name,
                'subject' => $subject,
                'message' => nl2br(htmlspecialchars($messageText))
            ]);
            
            return sendEmailAdvanced($email, $emailSubject, $message);
            
        default:
            return ['success' => false, 'error' => 'Invalid action'];
    }
}

