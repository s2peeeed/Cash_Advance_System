<?php
// Include PHPMailer files directly
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'juandelacruz12212000@gmail.com';    // ⚠️ Replace with your Gmail address
    $mail->Password   = 'hrxm tvhj sgbo qurk';     // ⚠️ Replace with your 16-character App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->SMTPDebug  = 2;                         // Enable verbose debug output
    $mail->Debugoutput = 'html';

    // Recipients
    $mail->setFrom('juandelacruz12212000@gmail.com', 'LGU Liquidation System');  // ⚠️ Replace with your Gmail
    $mail->addAddress('jhnvlntnstrd@gmail.com', 'Recipient Name');      // ⚠️ Replace with recipient's email

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'MANGULI NATA';
    $mail->Body    = 'MGA BIOT mo tanan.';

    $mail->send();
    echo 'Email has been sent successfully';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
} 