<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2; // Detailed debug output
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jerichoandres2023@gmail.com'; // Replace with your Gmail address
    $mail->Password = 'fuiaasemcrquiaeq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('jerichoandres2023@gmail.com', 'Test');
    $mail->addAddress('test@example.com');
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email.';
    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
?>