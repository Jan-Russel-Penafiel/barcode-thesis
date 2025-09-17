<?php
// read.php
require_once 'data_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barcode = $_POST['barcode'];
    $data = load_data();
    list($message, $success, $message_text) = log_attendance($barcode, $data);
    echo $message;
    exit();
}
?>