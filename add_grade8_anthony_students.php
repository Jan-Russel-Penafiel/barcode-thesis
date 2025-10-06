<?php
// Script to add all Grade 8 - St. Anthony students with barcodes
// Adviser: ALJAY LAPITAN
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Grade 8 - St. Anthony students
$students = [
    // MALE
    ['name' => 'AGUSTIN, Xian Brix S.', 'gender' => 'Male'],
    ['name' => 'ARELLANO, Clent Rovei J.', 'gender' => 'Male'],
    ['name' => 'ARELLANO, Earl Siegfred Jr. D.', 'gender' => 'Male'],
    ['name' => 'CABALO, Andrew John G.', 'gender' => 'Male'],
    ['name' => 'CATALUÑA, Ryan C.', 'gender' => 'Male'],
    ['name' => 'CATEDRAL, Arvy Jay D.', 'gender' => 'Male'],
    ['name' => 'CELESTIAL, Ronmark E.', 'gender' => 'Male'],
    ['name' => 'DELA CRUZ, James S.', 'gender' => 'Male'],
    ['name' => 'EDER, Frenz Chester S.', 'gender' => 'Male'],
    ['name' => 'GRANADA, Jeremiah Rhyll V.', 'gender' => 'Male'],
    ['name' => 'JAVIER, Michael Angelo A.', 'gender' => 'Male'],
    ['name' => 'MANZANO, Bryan D. Jr.', 'gender' => 'Male'],
    ['name' => 'MELLOMIDA, Edrian J.', 'gender' => 'Male'],
    ['name' => 'PADILLA Kert Zeann J.', 'gender' => 'Male'],
    ['name' => 'PAMPOSA, Alexander F.', 'gender' => 'Male'],
    ['name' => 'RIO, Steven Andrew B.', 'gender' => 'Male'],
    ['name' => 'SEPI, King Javier A.', 'gender' => 'Male'],
    ['name' => 'SUAREZ, Kheden Cyril E.', 'gender' => 'Male'],
    ['name' => 'VILLANUEVA, Jerard Angelo A.', 'gender' => 'Male'],
    // FEMALE
    ['name' => 'ABAT, Golde Marie C.', 'gender' => 'Female'],
    ['name' => 'ABOGADIL, Kyla A.', 'gender' => 'Female'],
    ['name' => 'CAYETANO, Pamela D.', 'gender' => 'Female'],
    ['name' => 'DEMO. Merrycris C.', 'gender' => 'Female'],
    ['name' => 'FACTO, Rheon Unice P.', 'gender' => 'Female'],
    ['name' => 'GALLO, Angel Mery G.', 'gender' => 'Female'],
    ['name' => 'LADEMORA, Zairain Phele P.', 'gender' => 'Female'],
    ['name' => 'LAVALLE, Tresha Mae L.', 'gender' => 'Female'],
    ['name' => 'NOTO, Julliane Mae C.', 'gender' => 'Female'],
    ['name' => 'OBRADOR, Rhiane Kate E.', 'gender' => 'Female'],
    ['name' => 'PATRICIO, Jamea Chienell R.', 'gender' => 'Female'],
    ['name' => 'PORRAS, Princess Nicole P.', 'gender' => 'Female'],
    ['name' => 'TALNILAS, Keasha Andria F.', 'gender' => 'Female'],
    ['name' => 'UMAYAN, Thaeniel Cynthia Ashanti M.', 'gender' => 'Female'],
    ['name' => 'VIDAL, Cassandra Mae M.', 'gender' => 'Female'],
    ['name' => 'VILLAR, Lovelyn Jean M.', 'gender' => 'Female']
];

$course = 'Grade 8 - St. Anthony';
$course_year = '8';
$adviser = 'ALJAY LAPITAN';

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
    echo "<html><head><title>Grade 8 - St. Anthony Students Added</title>";
    echo "<link href='tailwind.min.css' rel='stylesheet'></head>";
    echo "<body class='bg-gray-100 p-6'>";
    echo "<div class='container mx-auto max-w-3xl bg-white p-8 rounded-lg shadow-md'>";
    echo "<h2 class='text-3xl font-bold text-green-600 mb-4'>✓ Success!</h2>";
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-4'>";
    echo "<p class='text-lg text-gray-800 mb-2'><strong>Added {$added_count} Grade 8 - St. Anthony students with barcodes.</strong></p>";
    echo "<p class='text-gray-700'>• 19 Male students</p>";
    echo "<p class='text-gray-700'>• 16 Female students</p>";
    echo "<p class='text-sm text-gray-600 mt-2'>Adviser: {$adviser}</p>";
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
