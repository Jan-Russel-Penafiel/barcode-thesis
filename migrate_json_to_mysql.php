<?php
// migrate_json_to_mysql.php
$conn = new mysqli("localhost", "root", "", "barcode_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read JSON file
$json_data = file_get_contents("data.json");
$data = json_decode($json_data, true);

if (!$data) {
    die("Failed to parse data.json");
}

// Insert users
foreach ($data['users'] as $user) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $user['username'], $user['password']);
    $stmt->execute();
    $stmt->close();
}

// Insert barcodes
foreach ($data['barcodes'] as $barcode) {
    $stmt = $conn->prepare("INSERT IGNORE INTO barcodes (barcode, name, course, course_year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $barcode['barcode'], $barcode['name'], $barcode['course'], $barcode['course_year']);
    $stmt->execute();
    $stmt->close();
}

// Insert attendance
foreach ($data['attendance'] as $record) {
    $stmt = $conn->prepare("INSERT IGNORE INTO attendance (barcode, name, course, course_year, date, day, time_in, time_out) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $record['barcode'], $record['name'], $record['course'], $record['course_year'], $record['date'], $record['day'], $record['time_in'], $record['time_out']);
    $stmt->execute();
    $stmt->close();
}

echo "Data migrated from data.json to MySQL successfully.";
$conn->close();
?>