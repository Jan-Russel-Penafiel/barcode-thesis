<?php
// Script to add all Grade 10 students with barcodes
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Grade 10 students
$students = [
    // MALE
    ['name' => 'ABALA, Joshua Zoilo G.', 'gender' => 'Male'],
    ['name' => 'ADORADA, Cyrex John', 'gender' => 'Male'],
    ['name' => 'ANGGANTA, Sammy Jr. D.', 'gender' => 'Male'],
    ['name' => 'ARELLANO, Kent Xyrel D.', 'gender' => 'Male'],
    ['name' => 'AROBINTO, Al-Faiz C.', 'gender' => 'Male'],
    ['name' => 'BARBOSA, Carlos Miguel B.', 'gender' => 'Male'],
    ['name' => 'BEDIA, Jan Franz B.', 'gender' => 'Male'],
    ['name' => 'BRACAMONTE, Rovic Jess c.', 'gender' => 'Male'],
    ['name' => 'CANTOR, Joar Jr. P.', 'gender' => 'Male'],
    ['name' => 'CASTARDO, Cris Danielle D.', 'gender' => 'Male'],
    ['name' => 'CASTILLON, Artheod Vance B.', 'gender' => 'Male'],
    ['name' => 'CORNELIO, Levy James C.', 'gender' => 'Male'],
    ['name' => 'DACAYANAN, Xian C.', 'gender' => 'Male'],
    ['name' => 'FLORES, Armand Joseph R.', 'gender' => 'Male'],
    ['name' => 'GONZALES, Charles Jade V.', 'gender' => 'Male'],
    ['name' => 'GUINSAYAO, Jan Rafael G.', 'gender' => 'Male'],
    ['name' => 'GUINSAYAW, Renante Jr. P.', 'gender' => 'Male'],
    ['name' => 'KUMAN, Dornie John A.', 'gender' => 'Male'],
    ['name' => 'LIBO-ON, Trickz A.', 'gender' => 'Male'],
    ['name' => 'LLANTO, Cyrus Lee T.', 'gender' => 'Male'],
    ['name' => 'LUMATAO, Christian Dave B.', 'gender' => 'Male'],
    ['name' => 'MACARAEG, Rinjie L.', 'gender' => 'Male'],
    ['name' => 'MIRABUENO, Vincent S.', 'gender' => 'Male'],
    ['name' => 'NASAYSAYAN, Jasper D.', 'gender' => 'Male'],
    ['name' => 'OBRADOR, Kenny E.', 'gender' => 'Male'],
    ['name' => 'OPEÑIANO, Michael Angelo C.', 'gender' => 'Male'],
    ['name' => 'PENUELA, John Carlo S.', 'gender' => 'Male'],
    ['name' => 'POMADO, Zheanne Rex E.', 'gender' => 'Male'],
    ['name' => 'PORNEA, Ivant Jay P.', 'gender' => 'Male'],
    ['name' => 'RIO. Symon Josh B.', 'gender' => 'Male'],
    ['name' => 'ROJO, Cresenciano S.', 'gender' => 'Male'],
    ['name' => 'ROJO, Mark Angelo M.', 'gender' => 'Male'],
    ['name' => 'TUMALE, Jonel O.', 'gender' => 'Male'],
    // FEMALE
    ['name' => 'BAÑAS. Pia E.', 'gender' => 'Female'],
    ['name' => 'CALIB, Sittie Baimonina L.', 'gender' => 'Female'],
    ['name' => 'CAPADA, Angela R.', 'gender' => 'Female'],
    ['name' => 'CARGO, Jolina Mae A.', 'gender' => 'Female'],
    ['name' => 'CASIPLE, Victoria Anne P.', 'gender' => 'Female'],
    ['name' => 'CAYETANO, Patricia Mae D.', 'gender' => 'Female'],
    ['name' => 'DAMANDAMAN, Laryzlyn D.', 'gender' => 'Female'],
    ['name' => 'EBANADA, Rizelle Mariz P.', 'gender' => 'Female'],
    ['name' => 'GLENDRO, Richelle Mae B.', 'gender' => 'Female'],
    ['name' => 'LADEMORA, Danica Kim P.', 'gender' => 'Female'],
    ['name' => 'LAJO, Jasmine Joy G.', 'gender' => 'Female'],
    ['name' => 'MAGARO, Eunica Briana P.', 'gender' => 'Female'],
    ['name' => 'NONO, Ayesha Yuri', 'gender' => 'Female'],
    ['name' => 'ORO, Krystal Joy P.', 'gender' => 'Female'],
    ['name' => 'PAMPOSA, Rhea Nicole L.', 'gender' => 'Female'],
    ['name' => 'PAUNON, Juana P.', 'gender' => 'Female'],
    ['name' => 'REALINGO, Shaquelle D.', 'gender' => 'Female'],
    ['name' => 'RUZ, Ronaleen Mae R.', 'gender' => 'Female'],
    ['name' => 'SINSAO, Elsanor L.', 'gender' => 'Female'],
    ['name' => 'SUSAS, Bribie B.', 'gender' => 'Female'],
    ['name' => 'UKOM, Jona Faye K.', 'gender' => 'Female'],
    ['name' => 'VIDAL, Angel Mae E.', 'gender' => 'Female'],
    ['name' => 'VIDAL, Erica Mae M.', 'gender' => 'Female']
];

$course = 'Grade 10';
$course_year = '10';

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
    echo "<html><head><title>Grade 10 Students Added</title>";
    echo "<link href='tailwind.min.css' rel='stylesheet'></head>";
    echo "<body class='bg-gray-100 p-6'>";
    echo "<div class='container mx-auto max-w-3xl bg-white p-8 rounded-lg shadow-md'>";
    echo "<h2 class='text-3xl font-bold text-green-600 mb-4'>✓ Success!</h2>";
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-4'>";
    echo "<p class='text-lg text-gray-800 mb-2'><strong>Added {$added_count} Grade 10 students with barcodes.</strong></p>";
    echo "<p class='text-gray-700'>• 33 Male students</p>";
    echo "<p class='text-gray-700'>• 23 Female students</p>";
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
