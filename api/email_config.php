<?php
/**
 * Email Configuration for Gmail SMTP
 * 
 * Instructions:
 * 1. Enable "Less secure app access" in your Google Account settings
 *    OR use App Password (recommended):
 *    - Go to Google Account > Security > 2-Step Verification
 *    - Generate App Password for "Mail"
 *    - Use that password below
 * 
 * 2. Update the credentials below with your Gmail account
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ajbunielwork@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'qhba mhve aimw jwlm'); // Your Gmail password or App Password (spaces will be removed automatically)
define('SMTP_FROM_EMAIL', 'ajbunielwork@gmail.com');
define('SMTP_FROM_NAME', 'MOTORENT Admin');

// Enable/Disable email notifications
define('EMAIL_ENABLED', true);

// Test email function
function testEmailConfig() {
    if (empty(SMTP_PASSWORD)) {
        return ['success' => false, 'error' => 'SMTP password not configured. Please set SMTP_PASSWORD in email_config.php'];
    }
    return ['success' => true];
}

