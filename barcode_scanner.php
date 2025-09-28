<?php
// barcode_scanner.php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'data_helper.php';
$data = load_data();
$barcodes = isset($data['barcodes']) ? $data['barcodes'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Barcode Scanner - Barcode Attendance</title>
    <link href="tailwind.min.css" rel="stylesheet">
    <style>
        .scanner-container {
            position: relative;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin: 20px auto;
            max-width: 500px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .scanner-container.active {
            border-color: #007bff;
            background: #e3f2fd;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.2);
        }
        
        .scanner-container.success {
            border-color: #28a745;
            background: #e8f5e9;
        }
        
        .scanner-container.error {
            border-color: #dc3545;
            background: #ffebee;
        }
        
        #video-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            border-radius: 8px;
            overflow: hidden;
            background: #000;
        }
        
        #video {
            width: 100%;
            height: auto;
            display: none;
        }
        
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 150px;
            border: 2px solid #fff;
            border-radius: 8px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }
        
        .scanner-overlay::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border: 2px solid #007bff;
            border-radius: 8px;
            animation: scanline 2s linear infinite;
        }
        
        @keyframes scanline {
            0% { border-color: #007bff; transform: scale(1); }
            50% { border-color: #0056b3; transform: scale(1.02); }
            100% { border-color: #007bff; transform: scale(1); }
        }
        
        .scanner-status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .status-ready {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        
        .status-scanning {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ff9800;
            animation: pulse 1.5s infinite;
        }
        
        .status-success {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #2196f3;
        }
        
        .status-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.02); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .input-display {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 2px;
            color: #333;
            min-height: 50px;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .input-display.active {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .scanner-modes {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .mode-btn {
            padding: 10px 20px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .mode-btn.active {
            background: #007bff;
            color: white;
            border-color: #0056b3;
        }
        
        .mode-btn:hover {
            border-color: #007bff;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .manual-input {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .manual-input input {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
        
        .manual-input input:focus {
            border-color: #007bff;
            outline: none;
        }
        
        .scanner-history {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .history-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .option-box {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-box:hover, .option-box.selected {
            background-color: #3b82f6; /* bg-blue-500 */
            color: white;
        }
        
        .hidden-scanner-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
            width: 1px;
            height: 1px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Scan Barcode</h2>

            
            <div id="video-container">
                <video id="video" autoplay playsinline></video>
                <div class="scanner-overlay" id="scannerOverlay" style="display: none;"></div>
            </div>
            
            <div class="action-buttons mt-4">
                <button class="action-btn btn-primary" id="startCameraBtn">
                    Start Camera
                </button>
                <button class="action-btn btn-warning" id="stopCameraBtn" style="display: none;">
                    Stop Camera
                </button>
            </div>
            
            <form id="scanForm" action="process_scan.php" method="POST" class="space-y-6">
                <input type="hidden" id="scannedBarcode" name="barcode">
                <input type="hidden" id="attendanceAction" name="action" value="time_in">
                
                <div class="flex justify-center space-x-4 mt-4">
                    <div class="option-box border-2 border-blue-500 text-blue-500 p-4 rounded-lg w-1/2 text-center font-semibold selected"
                         data-action="time_in">
                        Time In
                    </div>
                    <div class="option-box border-2 border-blue-500 text-blue-500 p-4 rounded-lg w-1/2 text-center font-semibold"
                         data-action="time_out">
                        Time Out
                    </div>
                </div>
            </form>
            
            <div id="messageDisplay" class="mt-4 text-center"></div>
            <p class="mt-4 text-gray-600 text-center">Please scan a barcode to record attendance.</p>
        </div>
        
        <!-- Quick Reference Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mt-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">Available Barcodes</h3>
            <?php if (empty($barcodes)): ?>
                <p class="text-center text-gray-600">No barcodes available. <a href="generate.php" class="text-blue-500 hover:underline">Generate one here</a>.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($barcodes as $barcode): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($barcode['name']); ?></h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($barcode['course']); ?> - Year <?php echo htmlspecialchars($barcode['course_year']); ?></p>
                            <p class="text-xs text-gray-500 font-mono mt-2">ID: <?php echo htmlspecialchars($barcode['barcode']); ?></p>
                            <?php
                            $barcodeFile = "barcodes/{$barcode['barcode']}.png";
                            if (file_exists($barcodeFile)):
                            ?>
                                <img src="<?php echo $barcodeFile; ?>" alt="Barcode" class="mt-2 mx-auto max-w-full h-12 object-contain">
                                <button class="quick-scan-btn w-full mt-2 bg-blue-500 text-white py-1 px-3 rounded text-sm hover:bg-blue-600"
                                        data-barcode="<?php echo htmlspecialchars($barcode['barcode']); ?>">
                                    Quick Scan
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="jsqr.js"></script>
    <script src="quagga.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const videoContainer = document.getElementById('video-container');
            const video = document.getElementById('video');
            const scannerOverlay = document.getElementById('scannerOverlay');
            const startCameraBtn = document.getElementById('startCameraBtn');
            const stopCameraBtn = document.getElementById('stopCameraBtn');
            const messageDisplay = document.getElementById('messageDisplay');
            const scanForm = document.getElementById('scanForm');
            const scannedBarcode = document.getElementById('scannedBarcode');
            const attendanceAction = document.getElementById('attendanceAction');
            
            // Scanner State
            let cameraStream = null;
            let isProcessing = false;
            
            // Initialize
            init();
            
            function init() {
                setupCameraScanner();
                setupQuickScanButtons();
                setupActionSelection();
                
                // Show camera controls by default
                videoContainer.style.display = 'block';
            }
            

            

            

            
            function setupCameraScanner() {
                startCameraBtn.addEventListener('click', startCamera);
                stopCameraBtn.addEventListener('click', stopCamera);
            }
            
            function startCamera() {
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    } 
                })
                .then(stream => {
                    cameraStream = stream;
                    video.srcObject = stream;
                    video.style.display = 'block';
                    scannerOverlay.style.display = 'block';
                    startCameraBtn.style.display = 'none';
                    stopCameraBtn.style.display = 'block';
                    
                    updateStatus('scanning', '📷 Camera Active - Point at barcode within the frame');
                    
                    // Initialize Quagga for barcode detection
                    initializeQuagga();
                })
                .catch(err => {
                    updateStatus('error', '❌ Camera access denied or not available');
                    console.error('Camera error:', err);
                });
            }
            
            function stopCamera() {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                    cameraStream = null;
                }
                
                video.style.display = 'none';
                scannerOverlay.style.display = 'none';
                startCameraBtn.style.display = 'block';
                stopCameraBtn.style.display = 'none';
                
                if (typeof Quagga !== 'undefined') {
                    Quagga.stop();
                }
                
                updateStatus('ready', '📷 Camera Scanner Ready - Click "Start Camera" to begin');
            }
            
            function initializeQuagga() {
                if (typeof Quagga === 'undefined') {
                    updateStatus('error', '❌ Barcode detection library not available');
                    return;
                }
                
                Quagga.init({
                    inputStream: {
                        name: 'Live',
                        type: 'LiveStream',
                        target: video,
                        constraints: {
                            facingMode: 'environment'
                        }
                    },
                    decoder: {
                        readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'code_39_reader']
                    },
                    locator: {
                        patchSize: 'medium',
                        halfSample: true
                    },
                    numOfWorkers: 2,
                    locate: true
                }, (err) => {
                    if (err) {
                        updateStatus('error', '❌ Camera initialization failed');
                        console.error('Quagga init error:', err);
                        return;
                    }
                    
                    Quagga.start();
                    
                    // Handle barcode detection
                    Quagga.onDetected((result) => {
                        const code = result.codeResult.code;
                        if (code && code.length > 5) {
                            processScannedData(code);
                            stopCamera(); // Auto-stop after successful scan
                        }
                    });
                });
            }
            

            
            function setupQuickScanButtons() {
                document.querySelectorAll('.quick-scan-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const barcode = btn.dataset.barcode;
                        processScannedData(barcode);
                    });
                });
            }
            
            function setupActionSelection() {
                const optionBoxes = document.querySelectorAll('.option-box');
                optionBoxes.forEach(box => {
                    box.addEventListener('click', () => {
                        optionBoxes.forEach(b => b.classList.remove('selected'));
                        box.classList.add('selected');
                        attendanceAction.value = box.dataset.action;
                    });
                });
            }
            
            function processScannedData(barcode) {
                if (isProcessing || !barcode) return;
                
                isProcessing = true;
                barcode = barcode.replace(/[^a-zA-Z0-9]/g, '').trim();
                
                if (!barcode || barcode.length < 5) {
                    updateStatus('error', '❌ Invalid barcode format');
                    setTimeout(() => {
                        isProcessing = false;
                        updateStatus('ready', 'Please scan a barcode to record attendance.');
                    }, 2000);
                    return;
                }
                
                updateStatus('success', `✅ Barcode Scanned: ${barcode}`);
                
                // Set form data and submit
                scannedBarcode.value = barcode;
                
                // Submit to process_scan.php
                submitAttendance(barcode);
            }
            
            async function submitAttendance(barcode) {
                const action = attendanceAction.value;
                
                updateStatus('scanning', '⏳ Processing attendance...');
                
                try {
                    const formData = new FormData();
                    formData.append('barcode', barcode);
                    formData.append('action', action);
                    
                    const response = await fetch('process_scan.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        updateStatus('success', `✅ ${result.message}`);
                        showMessage(result.message, 'success');
                        
                        // Reset scanner after successful scan for continuous scanning
                        setTimeout(() => {
                            isProcessing = false;
                            updateStatus('ready', 'Please scan a barcode to record attendance.');
                        }, 2000);
                    } else {
                        updateStatus('error', `❌ ${result.message}`);
                        showMessage(result.message, 'error');
                        setTimeout(() => {
                            isProcessing = false;
                            updateStatus('ready', 'Please scan a barcode to record attendance.');
                        }, 3000);
                    }
                } catch (error) {
                    updateStatus('error', '❌ Network error occurred');
                    showMessage('Network error occurred', 'error');
                    setTimeout(() => {
                        isProcessing = false;
                        updateStatus('ready', 'Please scan a barcode to record attendance.');
                    }, 3000);
                }
            }
            
            function updateStatus(type, message) {
                const statusClass = type === 'success' ? 'text-green-600' : 
                                  type === 'error' ? 'text-red-600' : 
                                  type === 'scanning' ? 'text-orange-600' : 'text-blue-600';
                                  
                messageDisplay.className = `mt-4 text-center font-semibold ${statusClass}`;
                messageDisplay.textContent = message;
            }
            
            function showMessage(message, type) {
                messageDisplay.className = `mt-4 text-center font-semibold ${type === 'success' ? 'text-green-600' : 'text-red-600'}`;
                messageDisplay.textContent = message;
                
                setTimeout(() => {
                    messageDisplay.textContent = '';
                }, 5000);
            }
            
            // Prevent form submission
            scanForm.addEventListener('submit', (e) => {
                e.preventDefault();
            });
            
            console.log('Barcode Scanner initialized successfully');
        });
    </script>
</body>
</html>