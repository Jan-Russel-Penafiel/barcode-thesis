<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require 'vendor/autoload.php';
require_once 'data_helper.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['barcode_id'])) {
    $barcodeId = $_POST['barcode_id'];
    $name = $_POST['name'] ?? 'Unknown';
    $course = $_POST['course'] ?? 'Unknown';
    $courseYear = $_POST['course_year'] ?? 'Unknown';
    
    try {
        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = $generator->getBarcode($barcodeId, $generator::TYPE_CODE_128, 3, 80);
        
        if (!file_exists(BASE_DIR . '/barcodes')) {
            mkdir(BASE_DIR . '/barcodes', 0777, true);
        }
        
        $barcodeFile = BASE_DIR . "/barcodes/$barcodeId.png";
        
        if (file_put_contents($barcodeFile, $barcodeImage)) {
            // Update the data.json to include this barcode if it doesn't exist
            $data = load_data();
            
            // Check if barcode already exists in barcodes array
            $barcodeExists = false;
            foreach ($data['barcodes'] as $existingBarcode) {
                if ($existingBarcode['barcode'] === $barcodeId) {
                    $barcodeExists = true;
                    break;
                }
            }
            
            // Add barcode to data if it doesn't exist
            if (!$barcodeExists) {
                $data['barcodes'][] = [
                    "barcode" => $barcodeId,
                    "name" => $name,
                    "course" => $course,
                    "course_year" => $courseYear
                ];
                save_data($data);
            }
            
            echo json_encode(['success' => true, 'message' => 'Barcode image regenerated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save barcode image']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error generating barcode: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>