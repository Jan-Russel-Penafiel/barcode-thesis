<?php
// edit_attendance.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$original_barcode = isset($_POST['original_barcode']) ? trim($_POST['original_barcode']) : '';
$original_date = isset($_POST['original_date']) ? trim($_POST['original_date']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$course_year = isset($_POST['course_year']) ? trim($_POST['course_year']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time_in = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
$time_out = isset($_POST['time_out']) ? trim($_POST['time_out']) : '';

if (!$original_barcode || !$original_date) {
    echo json_encode(['success' => false, 'error' => 'Original barcode and date are required']);
    exit();
}

if (!$name || !$course || !$course_year || !$date) {
    echo json_encode(['success' => false, 'error' => 'Name, course, course year, and date are required']);
    exit();
}

$data = load_data();
$attendance = isset($data['attendance']) ? $data['attendance'] : [];

// Find the attendance record to edit
$record_index = -1;
foreach ($attendance as $index => $record) {
    if ($record['barcode'] === $original_barcode && $record['date'] === $original_date) {
        $record_index = $index;
        break;
    }
}

if ($record_index === -1) {
    echo json_encode(['success' => false, 'error' => 'Attendance record not found']);
    exit();
}

// Calculate day from date
$date_obj = new DateTime($date);
$day = $date_obj->format('l'); // Full day name (e.g., Monday)

// Update the record
$attendance[$record_index] = [
    'barcode' => $original_barcode, // Keep original barcode
    'name' => $name,
    'course' => $course,
    'course_year' => $course_year,
    'date' => $date,
    'day' => $day,
    'time_in' => $time_in,
    'time_out' => $time_out
];

// Save the updated data
$data['attendance'] = $attendance;
if (save_data($data)) {
    echo json_encode(['success' => true, 'message' => 'Attendance record updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save attendance data']);
}
?>