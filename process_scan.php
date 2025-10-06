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

// Enhanced validation for GOOJPRT scanner input
if (!$barcode) {
    echo json_encode(['success' => false, 'message' => 'No barcode detected - Please scan with GOOJPRT scanner']);
    exit();
}

// Clean barcode data (remove any non-alphanumeric characters that might be added by scanner)
$barcode = preg_replace('/[^a-zA-Z0-9]/', '', $barcode);

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
    echo json_encode(['success' => false, 'message' => 'Invalid barcode - Please rescan with GOOJPRT scanner']);
    exit();
}

// Current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$current_day = date('l');

// CLEANUP: Remove any duplicate records for the same barcode and date
// Keep only the first record and merge data if needed
$cleaned_attendance = [];
$seen_records = [];

foreach ($attendance as $record) {
    $key = $record['barcode'] . '|' . $record['date'];
    
    if (!isset($seen_records[$key])) {
        // First occurrence - keep it
        $seen_records[$key] = count($cleaned_attendance);
        $cleaned_attendance[] = $record;
    } else {
        // Duplicate found - merge the data (keep non-empty values)
        $existing_index = $seen_records[$key];
        if (empty($cleaned_attendance[$existing_index]['time_in']) && !empty($record['time_in'])) {
            $cleaned_attendance[$existing_index]['time_in'] = $record['time_in'];
        }
        if (empty($cleaned_attendance[$existing_index]['time_out']) && !empty($record['time_out'])) {
            $cleaned_attendance[$existing_index]['time_out'] = $record['time_out'];
        }
    }
}

// Replace with cleaned attendance
$attendance = $cleaned_attendance;

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

    // If record exists but time_in is empty, update it
    if ($record_index !== -1) {
        $attendance[$record_index]['time_in'] = $current_time;
    } else {
        // Create new record if none exists
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
        $attendance[] = $new_record;
    }

    $data['attendance'] = $attendance;
    save_data($data);
    echo json_encode(['success' => true, 'message' => 'Time In recorded successfully! GOOJPRT scan verified ✓']);
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
    echo json_encode(['success' => true, 'message' => 'Time Out recorded successfully! GOOJPRT scan verified ✓']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>