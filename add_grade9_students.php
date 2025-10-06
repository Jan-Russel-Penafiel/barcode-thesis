<?php
// Script to add all Grade 9 students with barcodes
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Grade 9 students
$students = [
    // MALE
    ['name' => 'AROBINTO, Mohammad Arham C.', 'gender' => 'Male'],
    ['name' => 'BEROL, Alexis Jr. C.', 'gender' => 'Male'],
    ['name' => 'DASMARIÑAS, Marc Gabriel G.', 'gender' => 'Male'],
    ['name' => 'DELICANA, Prince Yael G.', 'gender' => 'Male'],
    ['name' => 'DELOS SANTOS, Dexter D.', 'gender' => 'Male'],
    ['name' => 'DIGNADICE, Juvan A.', 'gender' => 'Male'],
    ['name' => 'DOLENDRES, Joey A.', 'gender' => 'Male'],
    ['name' => 'EBANADA, Charles Henrex P.', 'gender' => 'Male'],
    ['name' => 'FERMO, Ezekiel Leo Jhenvir C.', 'gender' => 'Male'],
    ['name' => 'FLORES, Marko B.', 'gender' => 'Male'],
    ['name' => 'GREGORIO, Art John L.', 'gender' => 'Male'],
    ['name' => 'LADUA, Aj B.', 'gender' => 'Male'],
    ['name' => 'LAMIÑARES, Jomel J.', 'gender' => 'Male'],
    ['name' => 'MANALOTO, Jan Michael L.', 'gender' => 'Male'],
    ['name' => 'MANLAPAO, Jun Rey Jr. F.', 'gender' => 'Male'],
    ['name' => 'MERJA, Jay Marc L.', 'gender' => 'Male'],
    ['name' => 'MILLENDEZ, Vaughn Jayecob D.', 'gender' => 'Male'],
    ['name' => 'ORO, John Carlo B.', 'gender' => 'Male'],
    ['name' => 'PORRAS, Bryan M.', 'gender' => 'Male'],
    ['name' => 'PORRAS, Tim Fred S.', 'gender' => 'Male'],
    ['name' => 'ROA, Christian Dave B.', 'gender' => 'Male'],
    ['name' => 'SABADO, Renz Yuuri P.', 'gender' => 'Male'],
    ['name' => 'SANTOS, Harvey Lian G.', 'gender' => 'Male'],
    ['name' => 'SOLINAP, Jace Mckaine D.', 'gender' => 'Male'],
    // FEMALE
    ['name' => 'AGUSAN, Sophia T.', 'gender' => 'Female'],
    ['name' => 'CASTILLO, Kylie Nicole S.', 'gender' => 'Female'],
    ['name' => 'DELOS SANTOS, Judy Ann P.', 'gender' => 'Female'],
    ['name' => 'DIONEDA, Reyelle P.', 'gender' => 'Female'],
    ['name' => 'DOCE. Angel L.', 'gender' => 'Female'],
    ['name' => 'DOCUSIN, Shiela Miles', 'gender' => 'Female'],
    ['name' => 'ERALINO, Stephanie Louise D.', 'gender' => 'Female'],
    ['name' => 'FELONGCO, Princess Justine M.', 'gender' => 'Female'],
    ['name' => 'HOFILEÑA, Shaira Llaine B.', 'gender' => 'Female'],
    ['name' => 'JUNIO, Angel Kyle T.', 'gender' => 'Female'],
    ['name' => 'LAJO, Bea Angela D.', 'gender' => 'Female'],
    ['name' => 'MARTINEZ, Rezel Mae H.', 'gender' => 'Female'],
    ['name' => 'SALISE, April Rose B.', 'gender' => 'Female'],
    ['name' => 'SIEGA, Angela Marie M.', 'gender' => 'Female'],
    ['name' => 'TUAN, Precious Mae P.', 'gender' => 'Female'],
    ['name' => 'UMAYAN, Thiana Citrine Amethyst M.', 'gender' => 'Female'],
    ['name' => 'WAMAR, Erica Shane J.', 'gender' => 'Female']
];

$course = 'Grade 9';
$course_year = '9';

// Load existing data
$data = load_data();

// Prepare barcode generator
$generator = new BarcodeGeneratorPNG();

// Create barcodes directory if it doesn't exist
if (!file_exists(BASE_DIR . '/barcodes')) {
    mkdir(BASE_DIR . '/barcodes', 0777, true);
}

$today = date('Y-m-d');
$dayName = date('l');

$added_count = 0;
$errors = [];

foreach ($students as $student) {
    // Generate unique barcode
    $barcode = time() . rand(1000, 9999);
    
    // Generate barcode image with optimized settings for GOOJPRT MP2300
    $barcodeImage = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 3, 80, [0, 0, 0]);
    
    $barcodeFile = BASE_DIR . "/barcodes/$barcode.png";
    if (!file_put_contents($barcodeFile, $barcodeImage)) {
        $errors[] = "Failed to save barcode image for {$student['name']}";
        continue;
    }
    
    // Add to barcodes array
    $data['barcodes'][] = [
        "barcode" => $barcode,
        "name" => $student['name'],
        "course" => $course,
        "course_year" => $course_year,
        "gender" => $student['gender']
    ];
    
    // Create attendance record for today (without time in/out)
    if (!isset($data['attendance'])) {
        $data['attendance'] = [];
    }
    
    $data['attendance'][] = [
        "barcode" => $barcode,
        "name" => $student['name'],
        "course" => $course,
        "course_year" => $course_year,
        "date" => $today,
        "day" => $dayName,
        "time_in" => null,
        "time_out" => null
    ];
    
    $added_count++;
    
    // Small delay to ensure unique timestamps
    usleep(100000); // 0.1 second delay
}

// Save data
if (save_data($data)) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Grade 9 Students Added</title>";
    echo "<link href='tailwind.min.css' rel='stylesheet'></head>";
    echo "<body class='bg-gray-100 p-6'>";
    echo "<div class='container mx-auto max-w-3xl bg-white p-8 rounded-lg shadow-md'>";
    echo "<h2 class='text-3xl font-bold text-green-600 mb-4'>✓ Success!</h2>";
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-4'>";
    echo "<p class='text-lg text-gray-800 mb-2'><strong>Added {$added_count} Grade 9 students with barcodes.</strong></p>";
    echo "<p class='text-gray-700'>• 24 Male students</p>";
    echo "<p class='text-gray-700'>• 17 Female students</p>";
    echo "</div>";
    echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6'>";
    echo "<p class='text-gray-800'><strong>📅 Attendance records created for today:</strong> {$today} ({$dayName})</p>";
    echo "<p class='text-sm text-gray-600 mt-2'>Students can now scan their barcodes to record Time In/Out</p>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-6'>";
        echo "<h3 class='text-lg font-semibold text-red-700 mb-2'>Errors:</h3>";
        echo "<ul class='list-disc list-inside text-red-600'>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<div class='flex space-x-4'>";
    echo "<a href='index.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 font-semibold'>Go to Dashboard</a>";
    echo "<a href='qr_table.php' class='bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 font-semibold'>View All Barcodes</a>";
    echo "</div>";
    echo "</div></body></html>";
} else {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error</title>";
    echo "<link href='tailwind.min.css' rel='stylesheet'></head>";
    echo "<body class='bg-gray-100 p-6'>";
    echo "<div class='container mx-auto max-w-3xl bg-white p-8 rounded-lg shadow-md'>";
    echo "<h2 class='text-3xl font-bold text-red-600 mb-4'>✗ Error!</h2>";
    echo "<p class='text-gray-800 mb-4'>Failed to save data to data.json.</p>";
    
    if (!empty($errors)) {
        echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-6'>";
        echo "<h3 class='text-lg font-semibold text-red-700 mb-2'>Additional Errors:</h3>";
        echo "<ul class='list-disc list-inside text-red-600'>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<a href='index.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 font-semibold inline-block'>Go to Dashboard</a>";
    echo "</div></body></html>";
}
?>
