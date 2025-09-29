<?php
// scan.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

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
    <title>Scan Barcode - Barcode Attendance</title>
    <link href="tailwind.min.css" rel="stylesheet">
    <style>
        .option-box {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-box:hover, .option-box.selected {
            background-color: #3b82f6; /* bg-blue-500 */
            color: white;
        }
        #video-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            position: relative;
        }
        #video {
            width: 100%;
            height: auto;
            display: none; /* Hidden until camera starts */
        }
        
        #barcodeModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10001;
            justify-content: center;
            align-items: center;
        }
        
        #barcodeModal .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            text-align: center;
            position: relative;
        }
        
        #barcodeModal .enlarged-barcode {
            max-width: 75%; /* Optimized for MP2300 scanner visibility */
            width: 400px; /* Optimal size for MP2300 */
            height: auto;
            border: 2px solid #374151; /* Dark border for better contrast */
            border-radius: 4px;
            margin: 20px auto;
            display: block;
            min-height: 120px; /* Optimal height for MP2300 */
            background: white;
            padding: 15px; /* Optimal padding for MP2300 */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            image-rendering: pixelated;
            image-rendering: -moz-crisp-edges;
            image-rendering: crisp-edges;
        }
        
        #barcodeModal .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #666;
        }
        
        #barcodeModal .close-btn:hover {
            color: #000;
        }
        
        .barcode-clickable {
            cursor: pointer;
            transition: transform 0.2s;
            max-width: 280px;
            min-height: 70px; /* Optimized for MP2300 scanner */
            min-width: 180px; /* Optimal width for MP2300 */
            height: auto;
            background: white;
            padding: 8px; /* Reduced padding for better scan area */
            border: 1px solid #d1d5db;
            border-radius: 2px;
            image-rendering: pixelated;
            image-rendering: -moz-crisp-edges;
            image-rendering: crisp-edges;
        }
        
        .barcode-clickable:hover {
            transform: scale(1.05);
        }
        
        .scanner-status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .scanner-ready {
            background-color: #e7f5e7;
            color: #2d5a2d;
            border: 1px solid #4caf50;
        }
        
        .scanner-scanning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .scanner-success {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #17a2b8;
        }
        
        /* Main scanning area status */
        .main-scanner-status {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .main-scanner-ready {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 2px solid #4caf50;
        }
        
        .main-scanner-scanning {
            background-color: #fff8e1;
            color: #f57c00;
            border: 2px solid #ff9800;
            animation: pulse 1.5s infinite;
        }
        
        .main-scanner-success {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 2px solid #2196f3;
        }
        
        .main-scanner-error {
            background-color: #ffebee;
            color: #c62828;
            border: 2px solid #f44336;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .scanner-input-display {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 2px;
            color: #333;
            min-height: 30px;
            padding: 10px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Bar -->
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

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <!-- Hidden input for barcode scanner compatibility -->
        <input type="text" id="hiddenScannerInput" style="position: absolute; left: -9999px; opacity: 0;" autocomplete="off">
        
        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Scan Barcode</h2>
            <div id="video-container">
                <video id="video" autoplay playsinline></video>
            </div>
            <form id="scan-form" action="process_scan.php" method="POST" class="space-y-6">
                <input type="hidden" id="barcode" name="barcode">
                <input type="hidden" id="action" name="action" value="time_in">
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
                <button type="submit" id="submit-btn" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 hidden">Submit</button>
            </form>
            
            <!-- Main Scanner Status -->
            <div id="mainScannerStatus" class="main-scanner-status main-scanner-ready">
                🔍 GOOJPRT MP2300 Scanner Ready - Use your physical barcode scanner (optimal distance: 2-6 inches, slight angle)
            </div>
            <div id="scannerInputDisplay" class="scanner-input-display">
                <span class="text-gray-500">Scanned barcode will appear here...</span>
            </div>
            
            <div id="message" class="mt-4 text-center"></div>
            <p class="mt-4 text-gray-600 text-center">Please scan a barcode to record attendance.</p>
            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-xs text-blue-700 text-center">
                    💡 MP2300 Tip: Hold scanner at slight angle (15-30°) for better reading. Use 2-6 inches distance for optimal scanning.
                </p>
                <div class="mt-2 text-center">
                    <button id="toggleDebug" class="text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600">
                        Enable Scanner Debug
                    </button>
                    <a href="scanner_diagnostic.php" class="text-xs bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 ml-2">
                        🔧 Open Diagnostic Tool
                    </a>
                </div>
                <div id="debugOutput" class="mt-2 text-xs bg-gray-800 text-green-400 p-2 rounded max-h-32 overflow-y-auto font-mono hidden"></div>
            </div>
        </div>
        
        <!-- Barcode Display Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mt-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">Available Barcodes</h3>
            <?php if (empty($barcodes)): ?>
                <p class="text-center text-gray-600">No barcodes available.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Student Name</th>
                                <th class="py-3 px-6 text-left">Strand</th>
                                <th class="py-3 px-6 text-left">Year Level</th>
                                <th class="py-3 px-6 text-center">Barcode</th>
                                <th class="py-3 px-6 text-center">Quick Scan</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            <?php foreach ($barcodes as $barcode): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100" style="height: 100px;"> <!-- Added minimum row height for better visibility -->
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['name']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['course']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['course_year']); ?></td>
                                    <td class="py-3 px-6 text-center">
                                        <?php
                                        $barcodeFile = "barcodes/{$barcode['barcode']}.png";
                                        if (file_exists($barcodeFile)):
                                        ?>
                                            <img src="<?php echo $barcodeFile; ?>" alt="Barcode" class="mx-auto barcode-clickable"
                                                 data-barcode-src="<?php echo $barcodeFile; ?>"
                                                 data-barcode-id="<?php echo htmlspecialchars($barcode['barcode']); ?>"
                                                 data-barcode-name="<?php echo htmlspecialchars($barcode['name']); ?>"
                                                 data-barcode-course="<?php echo htmlspecialchars($barcode['course']); ?>"
                                                 data-barcode-year="<?php echo htmlspecialchars($barcode['course_year']); ?>"
                                                 title="Click to enlarge for GOOJPRT scanner">
                                        <?php else: ?>
                                            <span class="text-red-500">Barcode image not found</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <button class="quick-scan-btn bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600"
                                                data-barcode="<?php echo htmlspecialchars($barcode['barcode']); ?>">
                                            Quick Scan
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barcode Enlargement Modal -->
    <div id="barcodeModal">
        <div class="modal-content">
            <button class="close-btn" id="closeBarcodeModal">&times;</button>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Barcode Details</h3>
            <div id="barcodeDetails">
                <p><strong>Student Name:</strong> <span id="modalName"></span></p>
                <p><strong>Strand:</strong> <span id="modalCourse"></span></p>
                <p><strong>Year Level:</strong> <span id="modalYear"></span></p>
                <p><strong>Barcode ID:</strong> <span id="modalBarcodeId"></span></p>
            </div>
            <img id="enlargedBarcode" class="enlarged-barcode" src="" alt="Enlarged Barcode">
            <div class="scanner-status scanner-ready" id="scannerStatus">
                📱 GOOJPRT MP2300 Scanner Ready - Point your scanner at the barcode above (2-6 inches distance, slight angle)
            </div>
            <div class="mt-4">
                <button id="scanFromModal" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 mr-2">Scan This Barcode</button>
                <button id="closeBarcodeModalBtn" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="jsqr.js"></script>
    <script src="quagga.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('scan-form');
            const barcodeInput = document.getElementById('barcode');
            const actionInput = document.getElementById('action');
            const messageDiv = document.getElementById('message');
            const optionBoxes = document.querySelectorAll('.option-box');
            const video = document.getElementById('video');
            const barcodeModal = document.getElementById('barcodeModal');
            const closeBarcodeModal = document.getElementById('closeBarcodeModal');
            const closeBarcodeModalBtn = document.getElementById('closeBarcodeModalBtn');
            const enlargedBarcode = document.getElementById('enlargedBarcode');
            const scannerStatus = document.getElementById('scannerStatus');
            const scanFromModal = document.getElementById('scanFromModal');
            const hiddenScannerInput = document.getElementById('hiddenScannerInput');
            
            // Debug logging for scanner troubleshooting
            const debugMode = localStorage.getItem('scannerDebug') === 'true';
            
            function debugLog(message, data = '') {
                if (debugMode) {
                    console.log(`[Scanner Debug] ${new Date().toLocaleTimeString()}: ${message}`, data);
                    
                    // Optional: Display debug info on page
                    const debugDiv = document.getElementById('debugOutput');
                    if (debugDiv) {
                        debugDiv.innerHTML += `<div>[${new Date().toLocaleTimeString()}] ${message}: ${JSON.stringify(data)}</div>`;
                        debugDiv.scrollTop = debugDiv.scrollHeight;
                    }
                }
            }
            
            debugLog('Scanner system initialized', {
                userAgent: navigator.userAgent,
                platform: navigator.platform,
                timestamp: new Date().toISOString()
            });
            
            // Main scanner elements
            const mainScannerStatus = document.getElementById('mainScannerStatus');
            const scannerInputDisplay = document.getElementById('scannerInputDisplay');

            // Keep hidden input focused for scanner compatibility
            function ensureHiddenInputFocus() {
                if (!isModalOpen && document.activeElement !== hiddenScannerInput) {
                    hiddenScannerInput.focus();
                }
            }

            // Focus hidden input initially and on window focus
            hiddenScannerInput.focus();
            window.addEventListener('focus', ensureHiddenInputFocus);
            document.addEventListener('click', () => {
                setTimeout(ensureHiddenInputFocus, 10);
            });

            // Debug toggle functionality
            const toggleDebugBtn = document.getElementById('toggleDebug');
            const debugOutput = document.getElementById('debugOutput');
            
            toggleDebugBtn.addEventListener('click', () => {
                const currentDebug = localStorage.getItem('scannerDebug') === 'true';
                const newDebug = !currentDebug;
                localStorage.setItem('scannerDebug', newDebug.toString());
                
                if (newDebug) {
                    toggleDebugBtn.textContent = 'Disable Scanner Debug';
                    toggleDebugBtn.className = 'text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600';
                    debugOutput.classList.remove('hidden');
                    debugLog('Debug mode enabled');
                } else {
                    toggleDebugBtn.textContent = 'Enable Scanner Debug';
                    toggleDebugBtn.className = 'text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600';
                    debugOutput.classList.add('hidden');
                    debugOutput.innerHTML = '';
                }
            });
            
            // Initialize debug state
            if (localStorage.getItem('scannerDebug') === 'true') {
                toggleDebugBtn.click();
            }

            // Scanner state management
            let scannerBuffer = '';
            let scannerTimeout = null;
            let isModalOpen = false;
            let currentBarcodeId = '';
            let mainScannerTimeout = null;
            let isScanning = false;

            // Barcode modal functionality
            function openBarcodeModal(imageSrc, barcodeId, name, course, year) {
                document.getElementById('modalName').textContent = name;
                document.getElementById('modalCourse').textContent = course;
                document.getElementById('modalYear').textContent = year;
                document.getElementById('modalBarcodeId').textContent = barcodeId;
                enlargedBarcode.src = imageSrc;
                currentBarcodeId = barcodeId;
                barcodeModal.style.display = 'flex';
                isModalOpen = true;
                updateScannerStatus('ready');
                
                // Focus on modal to capture keyboard events
                barcodeModal.focus();
            }

            function closeBarcodeModalFunc() {
                barcodeModal.style.display = 'none';
                isModalOpen = false;
                scannerBuffer = '';
                currentBarcodeId = '';
                if (scannerTimeout) {
                    clearTimeout(scannerTimeout);
                }
            }

            // Main scanner status update function
            function updateMainScannerStatus(status, message = '', scannedData = '') {
                const statusElement = mainScannerStatus;
                const displayElement = scannerInputDisplay;
                
                statusElement.className = 'main-scanner-status';
                
                switch (status) {
                    case 'ready':
                        statusElement.className += ' main-scanner-ready';
                        statusElement.innerHTML = '🔍 GOOJPRT MP2300 Scanner Ready - Use your physical barcode scanner (optimal distance: 2-6 inches, slight angle)';
                        displayElement.innerHTML = '<span class="text-gray-500">Scanned barcode will appear here...</span>';
                        break;
                    case 'scanning':
                        statusElement.className += ' main-scanner-scanning';
                        statusElement.innerHTML = '📱 Scanning... Reading barcode data';
                        displayElement.innerHTML = `<span class="text-orange-600">${scannedData}</span>`;
                        break;
                    case 'success':
                        statusElement.className += ' main-scanner-success';
                        statusElement.innerHTML = `✅ Barcode Scanned Successfully!`;
                        displayElement.innerHTML = `<span class="text-blue-600 font-bold">${scannedData}</span>`;
                        break;
                    case 'error':
                        statusElement.className += ' main-scanner-error';
                        statusElement.innerHTML = `❌ Error: ${message}`;
                        displayElement.innerHTML = `<span class="text-red-600">${scannedData || 'Invalid barcode'}</span>`;
                        break;
                    case 'processing':
                        statusElement.className += ' main-scanner-scanning';
                        statusElement.innerHTML = '⏳ Processing attendance...';
                        displayElement.innerHTML = `<span class="text-blue-600">${scannedData}</span>`;
                        break;
                }
            }

            // Enhanced main scanner input handling with debugging
            function handleMainScannerInput(char) {
                if (isModalOpen || isScanning) return;

                debugLog('Scanner input received', {
                    character: char,
                    charCode: char.charCodeAt(0),
                    bufferLength: scannerBuffer.length,
                    timestamp: Date.now()
                });

                // Clear previous timeout
                if (mainScannerTimeout) {
                    clearTimeout(mainScannerTimeout);
                }

                // Start scanning state if not already
                if (!scannerBuffer) {
                    isScanning = true;
                    updateMainScannerStatus('scanning', '', char);
                    debugLog('Started new scan session');
                } else {
                    updateMainScannerStatus('scanning', '', scannerBuffer + char);
                }

                // Add character to buffer
                scannerBuffer += char;

                // Set timeout to process the complete scan (optimized for MP2300)
                mainScannerTimeout = setTimeout(() => {
                    debugLog('Scanner timeout reached, processing data', {
                        buffer: scannerBuffer,
                        length: scannerBuffer.length
                    });
                    processMainScannerData(scannerBuffer.trim());
                }, 150); // Optimized for MP2300 scanner speed
            }

            function processMainScannerData(scannedData) {
                if (!scannedData) {
                    resetMainScanner();
                    return;
                }

                // Enhanced cleaning for GOOJPRT MP2300 scanner input
                scannedData = scannedData.replace(/[^a-zA-Z0-9]/g, '').trim();
                
                // GOOJPRT MP2300 scanners typically produce 13+ character barcodes
                if (!scannedData || scannedData.length < 8) {
                    updateMainScannerStatus('error', 'Barcode too short - Please rescan with GOOJPRT', scannedData);
                    setTimeout(resetMainScanner, 2000);
                    return;
                }

                console.log('GOOJPRT scanner - Processed data:', scannedData);
                updateMainScannerStatus('success', '', scannedData);
                
                // Set the barcode value and submit
                barcodeInput.value = scannedData;
                
                // Auto-submit after a short delay
                setTimeout(() => {
                    updateMainScannerStatus('processing', '', scannedData);
                    form.dispatchEvent(new Event('submit'));
                }, 500);
                
                // Reset scanner buffer
                scannerBuffer = '';
                isScanning = false;
            }

            function resetMainScanner() {
                scannerBuffer = '';
                isScanning = false;
                if (mainScannerTimeout) {
                    clearTimeout(mainScannerTimeout);
                }
                updateMainScannerStatus('ready');
            }

            function updateScannerStatus(status, message = '') {
                const statusElement = scannerStatus;
                statusElement.className = 'scanner-status';
                
                switch (status) {
                    case 'ready':
                        statusElement.className += ' scanner-ready';
                        statusElement.innerHTML = '📱 GOOJPRT MP2300 Scanner Ready - Point scanner at barcode above (2-6 inches distance, slight angle)';
                        break;
                    case 'scanning':
                        statusElement.className += ' scanner-scanning';
                        statusElement.innerHTML = '🔍 Scanning... Reading barcode data';
                        break;
                    case 'success':
                        statusElement.className += ' scanner-success';
                        statusElement.innerHTML = `✅ Success! Scanned: ${message}`;
                        break;
                    case 'match':
                        statusElement.className += ' scanner-success';
                        statusElement.innerHTML = `✅ Perfect Match! Barcode verified: ${message}`;
                        break;
                    case 'mismatch':
                        statusElement.className += ' scanner-scanning';
                        statusElement.innerHTML = `⚠️ Different barcode scanned: ${message} (Expected: ${currentBarcodeId})`;
                        break;
                }
            }

            // Handle barcode scanner input
            function handleScannerInput(char) {
                if (!isModalOpen) return;

                // Clear previous timeout
                if (scannerTimeout) {
                    clearTimeout(scannerTimeout);
                }

                // Add character to buffer
                scannerBuffer += char;
                updateScannerStatus('scanning');

                // Set timeout to process the complete scan
                scannerTimeout = setTimeout(() => {
                    processScannerData(scannerBuffer.trim());
                    scannerBuffer = '';
                }, 100); // 100ms delay to capture complete barcode
            }

            function processScannerData(scannedData) {
                if (!scannedData || !isModalOpen) return;

                // Enhanced cleaning for GOOJPRT scanner input
                scannedData = scannedData.replace(/[^a-zA-Z0-9]/g, '').trim();
                
                if (!scannedData || scannedData.length < 5) {
                    updateScannerStatus('ready');
                    return;
                }

                console.log('GOOJPRT modal scanner - Processed data:', scannedData);
                
                // Check if scanned data matches current barcode
                if (scannedData === currentBarcodeId) {
                    updateScannerStatus('match', scannedData);
                    // Use the scanned barcode for attendance
                    barcodeInput.value = scannedData;
                    closeBarcodeModalFunc();
                    form.dispatchEvent(new Event('submit'));
                } else {
                    updateScannerStatus('mismatch', scannedData);
                    // Auto-process different barcode after showing mismatch
                    setTimeout(() => {
                        // Still use the scanned barcode even if it doesn't match the modal
                        barcodeInput.value = scannedData;
                        closeBarcodeModalFunc();
                        form.dispatchEvent(new Event('submit'));
                    }, 1500);
                }
            }

            // Event listeners for barcode modal
            document.querySelectorAll('.barcode-clickable').forEach(img => {
                img.addEventListener('click', () => {
                    const src = img.dataset.barcodeSrc;
                    const id = img.dataset.barcodeId;
                    const name = img.dataset.barcodeName;
                    const course = img.dataset.barcodeCourse;
                    const year = img.dataset.barcodeYear;
                    openBarcodeModal(src, id, name, course, year);
                });
            });

            closeBarcodeModal.addEventListener('click', closeBarcodeModalFunc);
            closeBarcodeModalBtn.addEventListener('click', closeBarcodeModalFunc);

            // Scan from modal button
            scanFromModal.addEventListener('click', () => {
                if (currentBarcodeId) {
                    barcodeInput.value = currentBarcodeId;
                    closeBarcodeModalFunc();
                    form.dispatchEvent(new Event('submit'));
                }
            });

            // Close modal when clicking outside
            barcodeModal.addEventListener('click', (e) => {
                if (e.target === barcodeModal) {
                    closeBarcodeModalFunc();
                }
            });

            // Quick scan buttons
            document.querySelectorAll('.quick-scan-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const barcode = button.dataset.barcode;
                    barcodeInput.value = barcode;
                    form.dispatchEvent(new Event('submit'));
                });
            });

            // Handle option box selection
            optionBoxes.forEach(box => {
                box.addEventListener('click', () => {
                    optionBoxes.forEach(b => b.classList.remove('selected'));
                    box.classList.add('selected');
                    actionInput.value = box.dataset.action;
                });
            });

            // Keyboard scanner input (for hardware scanners)
            document.addEventListener('keydown', (e) => {
                if (isModalOpen) {
                    // Handle ESC key to close modal
                    if (e.key === 'Escape') {
                        closeBarcodeModalFunc();
                        return;
                    }

                    // Handle scanner input (most scanners send characters rapidly)
                    if (e.key.length === 1 || e.key === 'Enter') {
                        e.preventDefault();
                        
                        if (e.key === 'Enter') {
                            // Process complete scan on Enter
                            if (scannerBuffer) {
                                processScannerData(scannerBuffer.trim());
                                scannerBuffer = '';
                            }
                        } else {
                            // Add character to buffer
                            handleScannerInput(e.key);
                        }
                    }
                } else {
                    // Enhanced main scanner logic
                    if (e.key === 'Enter') {
                        // Process complete scan on Enter
                        if (scannerBuffer && scannerBuffer.trim()) {
                            processMainScannerData(scannerBuffer.trim());
                        }
                        e.preventDefault();
                    } else if (e.key.length === 1 && /[a-zA-Z0-9]/.test(e.key)) {
                        // Only accept alphanumeric characters
                        e.preventDefault();
                        handleMainScannerInput(e.key);
                    } else if (e.key === 'Escape') {
                        // Reset scanner on Escape
                        resetMainScanner();
                    }
                }
            });

            // Additional event listener for barcode scanner input (keypress for better compatibility)
            document.addEventListener('keypress', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return; // Don't interfere with form inputs
                }

                if (isModalOpen) {
                    if (e.key && e.key.length === 1) {
                        e.preventDefault();
                        handleScannerInput(e.key);
                    }
                } else {
                    if (e.key && e.key.length === 1 && /[a-zA-Z0-9]/.test(e.key)) {
                        e.preventDefault();
                        handleMainScannerInput(e.key);
                    }
                }
            });

            // Handle input event for better scanner compatibility
            document.addEventListener('input', (e) => {
                if (e.target.id === 'hiddenScannerInput') {
                    const value = e.target.value;
                    if (value && value.length > 5) { // Typical barcode length check
                        console.log('Hidden input captured:', value);
                        if (isModalOpen) {
                            processScannerData(value.trim());
                        } else {
                            processMainScannerData(value.trim());
                        }
                        e.target.value = ''; // Clear the hidden input
                    }
                }
            });

            // Add paste event handler for additional scanner compatibility
            document.addEventListener('paste', (e) => {
                if (e.target.id === 'hiddenScannerInput') {
                    setTimeout(() => {
                        const value = e.target.value;
                        if (value && value.length > 5) {
                            console.log('Paste captured:', value);
                            if (isModalOpen) {
                                processScannerData(value.trim());
                            } else {
                                processMainScannerData(value.trim());
                            }
                            e.target.value = '';
                        }
                    }, 10);
                }
            });

            // Form submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!barcodeInput.value) {
                    messageDiv.className = 'text-red-500';
                    messageDiv.textContent = 'Please scan a barcode';
                    updateMainScannerStatus('error', 'No barcode scanned');
                    return;
                }

                const formData = new FormData(form);
                const data = new URLSearchParams(formData);

                try {
                    const response = await fetch('process_scan.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: data
                    });
                    const result = await response.json();

                    messageDiv.className = result.success ? 'text-green-500' : 'text-red-500';
                    messageDiv.textContent = result.message;

                    if (result.success) {
                        updateMainScannerStatus('success', result.message, barcodeInput.value);
                        barcodeInput.value = '';
                        scannerBuffer = '';
                        isScanning = false;
                        // Redirect to index.php after successful attendance recording
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000); // Longer delay to show success message
                    } else {
                        updateMainScannerStatus('error', result.message, barcodeInput.value);
                        // Reset after showing error
                        setTimeout(() => {
                            resetMainScanner();
                        }, 3000);
                    }
                } catch (error) {
                    messageDiv.className = 'text-red-500';
                    messageDiv.textContent = 'Network error occurred';
                    updateMainScannerStatus('error', 'Network error occurred', barcodeInput.value);
                    setTimeout(() => {
                        resetMainScanner();
                    }, 3000);
                }
            });

            // Camera scanning with QuaggaJS (preserved original functionality)
            let stream = null;
            const startCameraBtn = document.getElementById('start-camera');
            
            if (startCameraBtn) {
                startCameraBtn.addEventListener('click', () => {
                    if (stream) {
                        // Stop camera
                        stream.getTracks().forEach(track => track.stop());
                        video.style.display = 'none';
                        startCameraBtn.textContent = 'Start Camera';
                        Quagga.stop();
                        stream = null;
                    } else {
                        // Start camera
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                            .then(mediaStream => {
                                stream = mediaStream;
                                video.srcObject = stream;
                                video.style.display = 'block';
                                startCameraBtn.textContent = 'Stop Camera';

                                // Initialize QuaggaJS
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
                                        readers: ['code_128_reader']
                                    },
                                    locator: {
                                        patchSize: 'medium',
                                        halfSample: true
                                    },
                                    numOfWorkers: 2,
                                    locate: true
                                }, (err) => {
                                    if (err) {
                                        messageDiv.className = 'text-red-500';
                                        messageDiv.textContent = 'Camera initialization failed: ' + err;
                                        return;
                                    }
                                    Quagga.start();
                                });

                                // Handle barcode detection
                                Quagga.onDetected((data) => {
                                    const code = data.codeResult.code;
                                    barcodeInput.value = code;
                                    form.dispatchEvent(new Event('submit'));
                                    Quagga.stop();
                                    stream.getTracks().forEach(track => track.stop());
                                    video.style.display = 'none';
                                    startCameraBtn.textContent = 'Start Camera';
                                    stream = null;
                                });
                            })
                            .catch(err => {
                                messageDiv.className = 'text-red-500';
                                messageDiv.textContent = 'Camera access denied: ' + err;
                            });
                    }
                });
            }
        });
    </script>
</body>
</html>