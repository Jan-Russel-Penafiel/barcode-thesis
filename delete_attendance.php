<?php
// delete_attendance.php
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';

if (!$barcode || !$date) {
    echo json_encode(['success' => false, 'error' => 'Barcode and date are required']);
    exit();
}

$data = load_data();
$attendance = &$data['attendance'];
$index = array_search(true, array_map(function($record) use ($barcode, $date) {
    return $record['barcode'] === $barcode && $record['date'] === $date;
}, $attendance));

if ($index === false) {
    echo json_encode(['success' => false, 'error' => 'Attendance record not found']);
    exit();
}

array_splice($attendance, $index, 1);
save_data($data);
echo json_encode(['success' => true]);
?>