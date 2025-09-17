<?php
// process_scan.php
require_once 'data_helper.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : 'time_in';

if (!$barcode) {
    echo json_encode(['success' => false, 'message' => 'Barcode is required']);
    exit();
}

$data = load_data();
$barcodes = isset($data['barcodes']) ? $data['barcodes'] : [];
$attendance = isset($data['attendance']) ? $data['attendance'] : [];

// Find barcode details
$barcode_data = null;
foreach ($barcodes as $b) {
    if ($b['barcode'] === $barcode) {
        $barcode_data = $b;
        break;
    }
}

if (!$barcode_data) {
    echo json_encode(['success' => false, 'message' => 'Invalid barcode']);
    exit();
}

// Current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$current_day = date('l');

// Find existing attendance record for today
$record_index = -1;
foreach ($attendance as $index => $record) {
    if ($record['barcode'] === $barcode && $record['date'] === $current_date) {
        $record_index = $index;
        break;
    }
}

if ($action === 'time_in') {
    if ($record_index !== -1 && !empty($attendance[$record_index]['time_in'])) {
        echo json_encode(['success' => false, 'message' => 'Time In already recorded for today']);
        exit();
    }

    $new_record = [
        'barcode' => $barcode,
        'name' => $barcode_data['name'],
        'course' => $barcode_data['course'],
        'course_year' => $barcode_data['course_year'],
        'date' => $current_date,
        'day' => $current_day,
        'time_in' => $current_time,
        'time_out' => ''
    ];

    if ($record_index === -1) {
        $attendance[] = $new_record;
    } else {
        $attendance[$record_index] = $new_record;
    }

    $data['attendance'] = $attendance;
    save_data($data);
    echo json_encode(['success' => true, 'message' => 'Time In recorded successfully']);
} elseif ($action === 'time_out') {
    if ($record_index === -1 || empty($attendance[$record_index]['time_in'])) {
        echo json_encode(['success' => false, 'message' => 'No Time In recorded for today']);
        exit();
    }

    if (!empty($attendance[$record_index]['time_out'])) {
        echo json_encode(['success' => false, 'message' => 'Time Out already recorded for today']);
        exit();
    }

    $attendance[$record_index]['time_out'] = $current_time;
    $data['attendance'] = $attendance;
    save_data($data);
    echo json_encode(['success' => true, 'message' => 'Time Out recorded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>