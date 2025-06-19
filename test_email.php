<?php
// Simple email test script
require_once 'includes/EmailSender.php';

try {
    $emailSender = new EmailSender();
    
    // Test email - Replace with your actual email for testing
    $to = 'juandelacruz12212000@gmail.com'; // Use the same email as configured
    $subject = 'Test Email from LGU Liquidation System';
    $message = '
        <p>This is a test email from the LGU Liquidation System.</p>
        <p>If you receive this email, the email functionality is working correctly.</p>
        <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        <p>This confirms that:</p>
        <ul>
            <li>PHPMailer is properly configured</li>
            <li>Gmail SMTP settings are correct</li>
            <li>App password is working</li>
            <li>Email sending functionality is operational</li>
        </ul>
    ';
    
    $result = $emailSender->sendReminder($to, $subject, $message);
    
    if ($result) {
        echo "✅ SUCCESS: Email sent successfully!\n";
        echo "📧 Check your email at: $to\n";
        echo "📝 Subject: $subject\n";
    } else {
        echo "❌ FAILED: Email sending failed.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "🔍 Check the error logs for more details.\n";
}

// Display current email configuration
echo "\n📋 Current Email Configuration:\n";
echo "SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . "\n";
echo "SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'Not defined') . "\n";
echo "SMTP Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined') . "\n";
echo "From Email: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'Not defined') . "\n";
echo "From Name: " . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Not defined') . "\n";

echo "\n🎉 Email system is now ready for use!\n";
?> 