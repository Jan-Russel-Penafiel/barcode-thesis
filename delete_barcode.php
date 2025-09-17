<?php
// delete_barcode.php
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';

if (!$barcode) {
    echo json_encode(['success' => false, 'error' => 'Barcode is required']);
    exit();
}

$data = load_data();
$barcodes = &$data['barcodes'];
$index = array_search($barcode, array_column($barcodes, 'barcode'));

if ($index === false) {
    echo json_encode(['success' => false, 'error' => 'Barcode not found']);
    exit();
}

// Delete QR code image
$qr_file = 'barcodes/' . $barcode . '.png';
if (file_exists($qr_file)) {
    unlink($qr_file);
}

// Remove barcode record
array_splice($barcodes, $index, 1);

// Remove associated attendance records
$data['attendance'] = array_filter($data['attendance'], function($record) use ($barcode) {
    return $record['barcode'] !== $barcode;
});
$data['attendance'] = array_values($data['attendance']);

save_data($data);
echo json_encode(['success' => true]);
?>