<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Create student_pictures directory if it doesn't exist
$pictures_dir = __DIR__ . '/student_pictures';
if (!is_dir($pictures_dir)) {
    mkdir($pictures_dir, 0755, true);
}

// Check if a file was uploaded
if (!isset($_FILES['picture'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['picture'];
$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;

// Validate barcode
if (empty($barcode)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Barcode is required']);
    exit();
}

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit();
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images allowed']);
    exit();
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Generate filename based on barcode
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $barcode . '.' . $file_extension;
$filepath = $pictures_dir . '/' . $filename;

// Delete old picture if exists
if (is_file($filepath)) {
    unlink($filepath);
}

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Return the relative path for accessing the image
    $relative_path = 'student_pictures/' . $filename;
    
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Picture uploaded successfully',
        'picture_path' => $relative_path,
        'picture_url' => $relative_path
    ]);
} else {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save picture']);
}
?>
