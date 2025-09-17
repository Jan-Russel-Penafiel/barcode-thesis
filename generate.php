<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
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
    if (empty($name) || empty($course) || empty($course_year)) {
        $error = "All fields are required.";
    } else {
        $data = load_data();
        $barcode = time() . rand(1000, 9999);
        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 50);
        $barcodeResource = imagecreatefromstring($barcodeImage);
        if (!$barcodeResource) {
            $error = "Failed to generate barcode image.";
        } else {
            $barcodeWidth = imagesx($barcodeResource);
            $barcodeHeight = imagesy($barcodeResource);
            $fontSize = 4;
            $fontWidth = imagefontwidth($fontSize);
            $sidePadding = 20;
            $textLines = ["Name: $name", "Course: $course", "Year: $course_year"];
            $maxTextWidth = 0;
            foreach ($textLines as $text) {
                $textPixelWidth = $fontWidth * strlen($text);
                $maxTextWidth = max($maxTextWidth, $textPixelWidth);
            }
            $imageWidth = max($barcodeWidth, $maxTextWidth) + 2 * $sidePadding;
            $topMargin = 10;
            $textHeight = 60;
            $newHeight = $topMargin + $barcodeHeight + $textHeight;
            $newImage = imagecreatetruecolor($imageWidth, $newHeight);
            $white = imagecolorallocate($newImage, 255, 255, 255);
            $black = imagecolorallocate($newImage, 0, 0, 0);
            imagefill($newImage, 0, 0, $white);
            $barcodeX = (int)(($imageWidth - $barcodeWidth) / 2);
            imagecopy($newImage, $barcodeResource, $barcodeX, $topMargin, 0, 0, $barcodeWidth, $barcodeHeight);
            $textY = $topMargin + $barcodeHeight + 5;
            foreach ($textLines as $text) {
                $textPixelWidth = $fontWidth * strlen($text);
                $textX = (int)(($imageWidth - $textPixelWidth) / 2);
                imagestring($newImage, $fontSize, $textX, $textY, $text, $black);
                $textY += 15;
            }
            if (!file_exists(BASE_DIR . '/barcodes')) {
                mkdir(BASE_DIR . '/barcodes', 0777, true);
            }
            $barcodeFile = BASE_DIR . "/barcodes/$barcode.png";
            if (!imagepng($newImage, $barcodeFile, 9)) {
                $error = "Failed to save barcode image.";
            } else {
                $data['barcodes'][] = [
                    "barcode" => $barcode,
                    "name" => $name,
                    "course" => $course,
                    "course_year" => $course_year
                ];
                if (!save_data($data)) {
                    $error = "Failed to save data to data.json.";
                } else {
                    $success = "Barcode $barcode generated and saved.";
                    $generated_barcode = [
                        "barcode" => $barcode,
                        "name" => $name,
                        "course" => $course,
                        "course_year" => $course_year,
                        "file" => "barcodes/$barcode.png"
                    ];
                }
            }
            imagedestroy($barcodeResource);
            imagedestroy($newImage);
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
                    <label for="name" class="block text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter your name" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="course" class="block text-gray-700 mb-2">Course</label>
                    <input type="text" name="course" id="course" placeholder="Enter course" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="course_year" class="block text-gray-700 mb-2">Year</label>
                    <input type="text" name="course_year" id="course_year" placeholder="Enter course year" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                <img src="<?php echo htmlspecialchars($generated_barcode['file']); ?>" alt="Generated Barcode" class="mb-4 mx-auto w-full max-w-xs object-contain">
                <button id="closePopup" class="mt-4 w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Close</button>
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