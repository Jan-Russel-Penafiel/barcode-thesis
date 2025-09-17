<?php
// edit_barcode.php
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$course_year = isset($_POST['course_year']) ? trim($_POST['course_year']) : '';

if (!$barcode || !$name || !$course || !$course_year) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

$data = load_data();
$barcodes = &$data['barcodes'];
$index = array_search($barcode, array_column($barcodes, 'barcode'));

if ($index === false) {
    echo json_encode(['success' => false, 'error' => 'Barcode not found']);
    exit();
}

// Update barcode record
$barcodes[$index]['name'] = $name;
$barcodes[$index]['course'] = $course;
$barcodes[$index]['course_year'] = $course_year;

// Update associated attendance records
foreach ($data['attendance'] as &$record) {
    if ($record['barcode'] === $barcode) {
        $record['name'] = $name;
        $record['course'] = $course;
        $record['course_year'] = $course_year;
    }
}

if (!save_data($data)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save data']);
    exit();
}

echo json_encode(['success' => true, 'barcode' => $barcode, 'name' => $name, 'course' => $course, 'course_year' => $course_year]);
?>