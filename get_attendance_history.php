<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in (match the session check used in index.php)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in to access attendance history']);
    exit;
}

require_once 'data_helper.php';

// Get barcode parameter
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Barcode parameter is required']);
    exit;
}

// Load all data
$data = load_data();
$attendance = isset($data['attendance']) ? $data['attendance'] : [];

// Filter attendance records for this specific barcode
$student_records = [];
foreach ($attendance as $record) {
    if ($record['barcode'] === $barcode) {
        $student_records[] = $record;
    }
}

// Remove duplicates for same date (merge time_in and time_out)
$cleaned_records = [];
$seen_dates = [];

foreach ($student_records as $record) {
    $key = $record['date'];
    
    if (!isset($seen_dates[$key])) {
        // First occurrence for this date
        $seen_dates[$key] = count($cleaned_records);
        $cleaned_records[] = $record;
    } else {
        // Duplicate date - merge time_in and time_out
        $existing_index = $seen_dates[$key];
        
        // Merge time_in if current record has it and existing doesn't
        if (empty($cleaned_records[$existing_index]['time_in']) && !empty($record['time_in'])) {
            $cleaned_records[$existing_index]['time_in'] = $record['time_in'];
        }
        
        // Merge time_out if current record has it and existing doesn't
        if (empty($cleaned_records[$existing_index]['time_out']) && !empty($record['time_out'])) {
            $cleaned_records[$existing_index]['time_out'] = $record['time_out'];
        }
        
        // Update day if it was empty
        if (empty($cleaned_records[$existing_index]['day']) && !empty($record['day'])) {
            $cleaned_records[$existing_index]['day'] = $record['day'];
        }
    }
}

// Sort by date descending (newest first)
usort($cleaned_records, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Return the records
echo json_encode($cleaned_records);
