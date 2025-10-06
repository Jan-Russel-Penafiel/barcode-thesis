<?php
// Script to add all Grade 12 Cookery students with barcodes
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Grade 12 Cookery students
$students = [
    // MALE
    ['name' => 'AMPUYAS, DANIEL', 'gender' => 'Male'],
    ['name' => 'ARE CHARLES BENEDICT P.', 'gender' => 'Male'],
    // FEMALE
    ['name' => 'DACAYANAN, LARA C.', 'gender' => 'Female'],
    ['name' => 'GUINSAYAO, PRINCESS ANN T.', 'gender' => 'Female'],
    ['name' => 'KUTA, Alza G.', 'gender' => 'Female'],
    ['name' => 'MENDOZA, KRISTYL M.', 'gender' => 'Female'],
    ['name' => 'OBAS, Rachelle E.', 'gender' => 'Female'],
    ['name' => 'ORBITA, LYN LYN L', 'gender' => 'Female'],
    ['name' => 'PABULAR, QUEENIE ANN D.', 'gender' => 'Female'],
    ['name' => 'PAMPOSA, LYKA V.', 'gender' => 'Female'],
    ['name' => 'TOMENLACO, ATHENA SOFIA N.', 'gender' => 'Female']
];

$course = 'Grade 12 - Cookery';
$course_year = '12';
$strand = 'Cookery'; // TVL Track - Cookery

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
        "gender" => $student['gender'],
        "strand" => $strand
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
    echo "Added {$added_count} Grade 12 Cookery students successfully!\n";
} else {
    echo "Error saving Grade 12 Cookery students.\n";
}
?>
