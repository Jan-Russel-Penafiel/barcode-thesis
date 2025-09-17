<?php
header('Content-Type: application/json');
require_once 'data_helper.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if (!isset($_GET['barcode']) || empty($_GET['barcode'])) {
        throw new Exception('Barcode parameter is missing.');
    }

    $barcode = filter_var($_GET['barcode'], FILTER_SANITIZE_STRING);
    $data = load_data();
    $barcodes = isset($data['barcodes']) ? $data['barcodes'] : [];

    $barcodeData = null;
    foreach ($barcodes as $item) {
        if ($item['barcode'] === $barcode) {
            $barcodeData = $item;
            break;
        }
    }

    if (!$barcodeData) {
        throw new Exception('Barcode not found.');
    }

    $barcodeFile = BASE_DIR . "/barcodes/$barcode.png";
    if (!file_exists($barcodeFile)) {
        throw new Exception('Barcode image not found.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jerichoandres2023@gmail.com';
    $mail->Password = 'fuiaasemcrquiaeq'; // Update with new App Password if needed
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('jerichoandres2023@gmail.com', 'Barcode Attendance');
    $mail->addAddress($barcodeData['email'], $barcodeData['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Your Attendance Barcode';
    $mail->Body = "Dear {$barcodeData['name']},<br><br>Your attendance barcode is attached.<br>Name: {$barcodeData['name']}<br>Course: {$barcodeData['course']}<br>Year: {$barcodeData['course_year']}<br><br>Barcode Attendance Team";
    $mail->AltBody = "Dear {$barcodeData['name']},\n\nYour attendance barcode is attached.\nName: {$barcodeData['name']}\nCourse: {$barcodeData['course']}\nYear: {$barcodeData['course_year']}\n\nBarcode Attendance Team";
    $mail->addAttachment($barcodeFile, "$barcode.png");
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Email sent successfully.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>