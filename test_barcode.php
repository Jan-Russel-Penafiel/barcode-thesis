<?php
// Test script to verify improved barcode generation
require 'vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

try {
    $generator = new BarcodeGeneratorPNG();
    
    // Test with old parameters (small)
    $oldBarcodeImage = $generator->getBarcode('17588856338564', $generator::TYPE_CODE_128, 2, 50);
    
    // Test with new parameters (improved)
    $newBarcodeImage = $generator->getBarcode('17588856338564', $generator::TYPE_CODE_128, 3, 80);
    
    // Save both for comparison
    file_put_contents('test_barcode_old.png', $oldBarcodeImage);
    file_put_contents('test_barcode_new.png', $newBarcodeImage);
    
    echo "Test barcodes generated successfully!<br>";
    echo "Old parameters (2x50): <img src='test_barcode_old.png' style='display:block; margin:10px 0;'><br>";
    echo "New parameters (3x80): <img src='test_barcode_new.png' style='display:block; margin:10px 0;'><br>";
    echo "The new barcode should be larger and more scanner-friendly.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>