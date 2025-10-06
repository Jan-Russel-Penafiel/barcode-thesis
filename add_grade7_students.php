<?php
// Script to add all Grade 7 - St. Joseph students with barcodes
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Grade 7 - St. Joseph students
$students = [
    // FEMALE
    ['name' => 'ARELLANO, Rhea Jane S.', 'gender' => 'Female'],
    ['name' => 'ARPOCIA, Jezzabell F.', 'gender' => 'Female'],
    ['name' => 'CARBOLIDO, Kayce Jane D.', 'gender' => 'Female'],
    ['name' => 'CRESENCIA, Princess D.', 'gender' => 'Female'],
    ['name' => 'DACAYANAN, Lexi C', 'gender' => 'Female'],
    ['name' => 'DRILON Annie Lou G.', 'gender' => 'Female'],
    ['name' => 'ESTRERA, Jahneen Cleo F.', 'gender' => 'Female'],
    ['name' => 'GALLO, Angel Mae G.', 'gender' => 'Female'],
    ['name' => 'KARTIL, Bailanie C.', 'gender' => 'Female'],
    ['name' => 'LAWAN, Marcedes A.', 'gender' => 'Female'],
    ['name' => 'OBRADOR, Althea E.', 'gender' => 'Female'],
    ['name' => 'RIO, Angel Mae C.', 'gender' => 'Female'],
    ['name' => 'RIO, Angelica C.', 'gender' => 'Female'],
    ['name' => 'SALING, Jessa May B.', 'gender' => 'Female'],
    ['name' => 'SANDOVAL, Lovely P.', 'gender' => 'Female'],
    ['name' => 'TUAN, Rhea Jean E.', 'gender' => 'Female'],
    // MALE
    ['name' => 'AGUSAN, France T.', 'gender' => 'Male'],
    ['name' => 'ALABADO, Joshua Kylle N.', 'gender' => 'Male'],
    ['name' => 'CASAÑA, Jeffrey Jr. C.', 'gender' => 'Male'],
    ['name' => 'DEMO, Kenth Jude', 'gender' => 'Male'],
    ['name' => 'DIMASINSIL, Fahad A.', 'gender' => 'Male'],
    ['name' => 'ELICAÑA, Joebert Jr. F.', 'gender' => 'Male'],
    ['name' => 'EMBAJADOR. Cyriel John L.', 'gender' => 'Male'],
    ['name' => 'GUILLANO, Rayden G.', 'gender' => 'Male'],
    ['name' => 'JARANDILLA, Rey Joseph V.', 'gender' => 'Male'],
    ['name' => 'LEGADA, John Angelo D.', 'gender' => 'Male'],
    ['name' => 'MALAPAJO, John Ellian D.', 'gender' => 'Male'],
    ['name' => 'MINANDANG, Nhasmin M.', 'gender' => 'Male'],
    ['name' => 'ORO, Julian P.', 'gender' => 'Male'],
    ['name' => 'SALISE, Roy B.', 'gender' => 'Male'],
    ['name' => 'SARCON, Jasper John Jr. E.', 'gender' => 'Male'],
    ['name' => 'SORIANO, Phil Marc B', 'gender' => 'Male'],
    ['name' => 'SUDAN, Kurt Kevin G.', 'gender' => 'Male'],
    ['name' => 'SUMALI, Dan Angelo R', 'gender' => 'Male'],
    ['name' => 'WICAS, Jhanhart P.', 'gender' => 'Male']
];

$course = 'Grade 7 - St. Joseph';
$course_year = '7';

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
    echo "<h2>Success!</h2>";
    echo "<p>Added {$added_count} Grade 7 - St. Joseph students with barcodes.</p>";
    echo "<p>16 Female students and 19 Male students have been registered.</p>";
    echo "<p>Attendance records created for today ({$today}).</p>";
    
    if (!empty($errors)) {
        echo "<h3>Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }
    
    echo "<br><a href='index.php'>Go to Dashboard</a>";
    echo " | <a href='qr_table.php'>View All Barcodes</a>";
} else {
    echo "<h2>Error!</h2>";
    echo "<p>Failed to save data to data.json.</p>";
    if (!empty($errors)) {
        echo "<h3>Additional Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }
}
?>
