<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer.php';
require 'SMTP.php';
require 'Exception.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Your email SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'you@example.com';  // Your email
    $mail->Password   = 'yourpassword';     // Your password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('you@example.com', 'BillSimp');
    $mail->addAddress('client@example.com', 'Client Name');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Here is your invoice';
    $mail->Body    = 'This is your invoice sent from BillSimp.';

    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
?>
