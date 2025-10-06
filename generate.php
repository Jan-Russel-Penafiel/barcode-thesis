<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

if (!file_exists('vendor/autoload.php')) {
    die('Error: Composer autoloader not found. Run "composer install".');
}
require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;
$success = null;
$error = null;
$generated_barcode = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $course = filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS);
    $course_year = filter_input(INPUT_POST, 'course_year', FILTER_SANITIZE_SPECIAL_CHARS);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS);
    if (empty($name) || empty($course) || empty($course_year)) {
        $error = "All fields are required.";
    } else {
        $data = load_data();
        $barcode = time() . rand(1000, 9999);
        $generator = new BarcodeGeneratorPNG();
        // Optimized settings specifically for GOOJPRT MP2300 2D Barcode Scanner
        // Width factor: 3 (optimal for MP2300), Height: 80 (standard readable height)
        // Quiet zone padding and high contrast for better scan reliability
        $barcodeImage = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 3, 80, [0, 0, 0]);
        
        if (!file_exists(BASE_DIR . '/barcodes')) {
            mkdir(BASE_DIR . '/barcodes', 0777, true);
        }
        $barcodeFile = BASE_DIR . "/barcodes/$barcode.png";
        if (!file_put_contents($barcodeFile, $barcodeImage)) {
            $error = "Failed to save barcode image.";
        } else {
                $data['barcodes'][] = [
                    "barcode" => $barcode,
                    "name" => $name,
                    "course" => $course,
                    "course_year" => $course_year,
                    "gender" => $gender ?? 'Not specified'
                ];
                
                // Automatically create an attendance record for today (without time in)
                $today = date('Y-m-d');
                $dayName = date('l'); // Full day name (e.g., Monday, Tuesday)
                
                // Check if attendance record already exists for this barcode and date
                $attendanceExists = false;
                if (isset($data['attendance'])) {
                    foreach ($data['attendance'] as $record) {
                        if ($record['barcode'] === $barcode && $record['date'] === $today) {
                            $attendanceExists = true;
                            break;
                        }
                    }
                }
                
                // Create attendance record if it doesn't exist (without time_in)
                if (!$attendanceExists) {
                    if (!isset($data['attendance'])) {
                        $data['attendance'] = [];
                    }
                    
                    $data['attendance'][] = [
                        "barcode" => $barcode,
                        "name" => $name,
                        "course" => $course,
                        "course_year" => $course_year,
                        "date" => $today,
                        "day" => $dayName,
                        "time_in" => null,  // Empty - will be filled when they scan
                        "time_out" => null
                    ];
                }
                
                if (!save_data($data)) {
                    $error = "Failed to save data to data.json.";
                } else {
                    $success = "Barcode $barcode generated and saved. Attendance record created for today.";
                    $generated_barcode = [
                        "barcode" => $barcode,
                        "name" => $name,
                        "course" => $course,
                        "course_year" => $course_year,
                        "file" => "barcodes/$barcode.png"
                    ];
                }
            }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Barcode - Barcode Attendance</title>
    <link href="tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Barcode Attendance</h1>
            <div class="space-x-4">
                <a href="index.php" class="hover:underline">Dashboard</a>
                <a href="generate.php" class="hover:underline">Generate Barcode</a>
                <a href="scan.php" class="hover:underline">Scan Barcode</a>
                <a href="add_user.php" class="hover:underline">Add User</a>
                <a href="qr_table.php" class="hover:underline">Barcodes</a>
                <a href="logout.php" class="hover:underline">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6">
        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto relative">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Generate Barcode</h2>
            <?php if (isset($success)): ?>
                <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p class="text-red-500 mb-4 text-center"><?php echo $error; ?></p>
            <?php endif; ?>
            <form id="barcodeForm" method="POST">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 mb-2">Student Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter student name" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="course" class="block text-gray-700 mb-2">Strand</label>
                    <input type="text" name="course" id="course" placeholder="Enter strand" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="course_year" class="block text-gray-700 mb-2">Year Level</label>
                    <input type="text" name="course_year" id="course_year" placeholder="Enter year level" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="gender" class="block text-gray-700 mb-2">Gender</label>
                    <select name="gender" id="gender" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select gender (optional)</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <button type="submit" id="submitButton" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 flex items-center justify-center">
                    <span id="buttonText">Generate and Save Barcode</span>
                    <svg id="loader" class="animate-spin h-5 w-5 mr-2 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
        <?php if (isset($generated_barcode)): ?>
        <div id="barcodePopup" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Generated Barcode</h3>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">
                        <strong>Student:</strong> <?php echo htmlspecialchars($generated_barcode['name']); ?>
                    </p>
                    <p class="text-sm text-gray-600 mb-2">
                        <strong>Strand:</strong> <?php echo htmlspecialchars($generated_barcode['course']); ?>
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        <strong>Year:</strong> <?php echo htmlspecialchars($generated_barcode['course_year']); ?>
                    </p>
                </div>
                <img src="<?php echo htmlspecialchars($generated_barcode['file']); ?>" alt="Generated Barcode" class="mb-4 mx-auto w-full max-w-md object-contain" style="min-height: 100px; max-width: 100%; background: white; padding: 15px; border: 1px solid #d1d5db; border-radius: 4px; image-rendering: pixelated; image-rendering: -moz-crisp-edges; image-rendering: crisp-edges;">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        📋 Attendance record created for today
                    </p>
                    <p class="text-xs text-blue-600 mt-1">
                        Scan the barcode to record Time In/Out
                    </p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-green-800 font-semibold">
                        📱 GOOJPRT MP2300 Scanner Ready
                    </p>
                    <p class="text-xs text-green-600 mt-1">
                        Optimized for GOOJPRT MP2300 2D scanner. Hold scanner 2-6 inches from barcode at slight angle for best results.
                    </p>
                </div>
                <button id="closePopup" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                    Close
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById('barcodeForm').addEventListener('submit', function() {
            const submitButton = document.getElementById('submitButton');
            const buttonText = document.getElementById('buttonText');
            const loader = document.getElementById('loader');
            submitButton.disabled = true;
            buttonText.textContent = 'Generating...';
            loader.classList.remove('hidden');
        });
        <?php if (isset($success)): ?>
            setTimeout(function() {
                const submitButton = document.getElementById('submitButton');
                const buttonText = document.getElementById('buttonText');
                const loader = document.getElementById('loader');
                submitButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                submitButton.classList.add('bg-green-500', 'hover:bg-green-600');
                buttonText.textContent = 'Saved';
                loader.classList.add('hidden');
                submitButton.disabled = false;
            }, 2000);
        <?php endif; ?>
        const closePopup = document.getElementById('closePopup');
        
        if (closePopup) {
            closePopup.addEventListener('click', function() {
                document.getElementById('barcodePopup').remove();
                document.getElementById('barcodeForm').reset(); // Reset the form fields
            });
        }
    </script>
</body>
</html>