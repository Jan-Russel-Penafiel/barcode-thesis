git <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner Diagnostic Tool</title>
    <link href="tailwind.min.css" rel="stylesheet">
    <style>
        .debug-panel {
            background: #1f2937;
            color: #f9fafb;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .debug-entry {
            margin-bottom: 5px;
            padding: 2px 0;
            border-bottom: 1px solid #374151;
        }
        
        .debug-timestamp {
            color: #9ca3af;
            font-size: 11px;
        }
        
        .debug-event {
            color: #fbbf24;
            font-weight: bold;
        }
        
        .debug-data {
            color: #10b981;
        }
        
        .scanner-test-input {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 2px;
            padding: 15px;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            margin: 10px 0;
            width: 100%;
            background: #f8fafc;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-success { background-color: #10b981; }
        .status-warning { background-color: #f59e0b; }
        .status-error { background-color: #ef4444; }
        .status-info { background-color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto">
            <h1 class="text-xl font-bold">🔧 Barcode Scanner Diagnostic Tool</h1>
            <p class="text-sm">GOOJPRT MP2300 Scanner Troubleshooting</p>
            <div class="mt-2 text-xs bg-green-700 text-white p-2 rounded">
                <strong>Universal Compatibility Checklist:</strong>
                <ul class="list-disc list-inside mt-1">
                    <li>Scanner is plugged in and recognized as a keyboard (HID) device</li>
                    <li>Scanner is set to <b>Keyboard Emulation</b> mode (not Serial/COM)</li>
                    <li>Test in Notepad: Scanned barcode appears as text</li>
                    <li>Try in multiple browsers (Chrome, Edge, Firefox)</li>
                    <li>Disable browser extensions if scanner input is not detected</li>
                    <li>Ensure browser window and input field are focused before scanning</li>
                    <li>Check for carriage return/line feed after scan (scanner config)</li>
                    <li>Use English (US) keyboard layout for best results</li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- System Information -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">System Information</h2>
            <div id="systemInfo" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><strong>Browser:</strong> <span id="browserInfo">Detecting...</span></div>
                <div><strong>Platform:</strong> <span id="platformInfo">Detecting...</span></div>
                <div><strong>User Agent:</strong> <span id="userAgent" class="text-xs break-all">Detecting...</span></div>
                <div><strong>Timestamp:</strong> <span id="currentTime">Detecting...</span></div>
            </div>
        </div>

        <!-- Scanner Connection Test -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">
                <span class="status-indicator status-info"></span>
                Scanner Connection Test
            </h2>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Test Scanner Input (Click here and scan a barcode):</label>
                <input type="text" id="scannerTestInput" class="scanner-test-input" 
                       placeholder="Click here, then scan a barcode with your GOOJPRT MP2300..." 
                       autocomplete="off">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <strong>Scanner Status:</strong> 
                    <span id="scannerStatus" class="font-mono">
                        <span class="status-indicator status-warning"></span>Waiting for input...
                    </span>
                </div>
                <div>
                    <strong>Last Input:</strong> 
                    <span id="lastInput" class="font-mono text-green-600">None</span>
                </div>
                <div>
                    <strong>Input Length:</strong> 
                    <span id="inputLength" class="font-mono">0</span>
                </div>
            </div>
        </div>

        <!-- Event Monitoring -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">
                <span class="status-indicator status-info"></span>
                Keyboard Event Monitor
            </h2>
            <div class="mb-4">
                <button id="clearEvents" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Clear Event Log
                </button>
                <button id="startMonitoring" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">
                    Start Monitoring
                </button>
                <button id="stopMonitoring" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 ml-2">
                    Stop Monitoring
                </button>
            </div>
            <div id="eventLog" class="debug-panel">
                <div class="debug-entry">
                    <span class="debug-timestamp">[Ready]</span> 
                    <span class="debug-event">EVENT MONITOR:</span> 
                    <span class="debug-data">Click "Start Monitoring" and scan a barcode</span>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Guide -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">
                <span class="status-indicator status-info"></span>
                Troubleshooting Guide
            </h2>
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border-l-4 border-blue-500">
                    <h3 class="font-semibold text-blue-800">Step 1: Hardware Check</h3>
                    <ul class="list-disc list-inside text-sm text-blue-700 mt-2">
                        <li>Ensure GOOJPRT MP2300 is properly connected via USB</li>
                        <li>Check if Windows recognizes the device (Device Manager)</li>
                        <li>Look for a flashing LED on the scanner indicating power</li>
                        <li>Try different USB ports</li>
                    </ul>
                </div>
                
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500">
                    <h3 class="font-semibold text-yellow-800">Step 2: Driver Test</h3>
                    <ul class="list-disc list-inside text-sm text-yellow-700 mt-2">
                        <li>Open Notepad and scan a barcode - does it appear as text?</li>
                        <li>If yes: Scanner hardware is working, issue is with web app</li>
                        <li>If no: Driver or USB connection issue</li>
                    </ul>
                </div>
                
                <div class="p-4 bg-green-50 border-l-4 border-green-500">
                    <h3 class="font-semibold text-green-800">Step 3: Browser Test</h3>
                    <ul class="list-disc list-inside text-sm text-green-700 mt-2">
                        <li>Try different browsers (Chrome, Firefox, Edge)</li>
                        <li>Disable browser extensions temporarily</li>
                        <li>Clear browser cache and cookies</li>
                        <li>Check if JavaScript is enabled</li>
                    </ul>
                </div>
                
                <div class="p-4 bg-red-50 border-l-4 border-red-500">
                    <h3 class="font-semibold text-red-800">Step 4: Scanner Configuration</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 mt-2">
                        <li>Some scanners have multiple modes (keyboard emulation, COM port)</li>
                        <li>Ensure scanner is in "Keyboard Emulation" mode</li>
                        <li>Check if scanner adds carriage return/line feed after scan</li>
                        <li>Verify scanner is configured for Code 128 barcodes</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Test Section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4">
                <span class="status-indicator status-success"></span>
                Quick Scanner Test
            </h2>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm mb-4">
                    <strong>Instructions:</strong> Click the test input field above, then scan any barcode. 
                    The results will show if your scanner is working properly.
                </p>
                <div id="quickTestResults" class="text-sm">
                    <div class="font-mono text-gray-600">Waiting for scanner input...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // System Detection
        document.addEventListener('DOMContentLoaded', function() {
            // Populate system information
            document.getElementById('browserInfo').textContent = navigator.appName + ' ' + navigator.appVersion.split(' ')[0];
            document.getElementById('platformInfo').textContent = navigator.platform;
            document.getElementById('userAgent').textContent = navigator.userAgent;
            document.getElementById('currentTime').textContent = new Date().toLocaleString();

            // Event monitoring variables
            let isMonitoring = false;
            let eventBuffer = [];
            let scannerBuffer = '';
            let scannerTimeout = null;
            const eventLog = document.getElementById('eventLog');
            const scannerTestInput = document.getElementById('scannerTestInput');

            // Logging function
            function log(event, data = '', category = 'info') {
                const timestamp = new Date().toLocaleTimeString();
                const entry = document.createElement('div');
                entry.className = 'debug-entry';
                entry.innerHTML = `
                    <span class="debug-timestamp">[${timestamp}]</span> 
                    <span class="debug-event">${event}:</span> 
                    <span class="debug-data">${data}</span>
                `;
                eventLog.appendChild(entry);
                eventLog.scrollTop = eventLog.scrollHeight;
                
                // Keep only last 100 entries
                while (eventLog.children.length > 100) {
                    eventLog.removeChild(eventLog.firstChild);
                }
            }

            // Scanner input processing
            function processScannerInput(input) {
                document.getElementById('lastInput').textContent = input;
                document.getElementById('inputLength').textContent = input.length;
                
                if (input.length > 5) {
                    document.getElementById('scannerStatus').innerHTML = 
                        '<span class="status-indicator status-success"></span>Scanner Working!';
                    
                    document.getElementById('quickTestResults').innerHTML = `
                        <div class="text-green-600 font-semibold">✅ Scanner Test PASSED</div>
                        <div class="text-sm mt-2">
                            <strong>Scanned Data:</strong> <code class="bg-gray-200 px-2 py-1 rounded">${input}</code><br>
                            <strong>Length:</strong> ${input.length} characters<br>
                            <strong>Type:</strong> ${/^[0-9]+$/.test(input) ? 'Numeric' : 'Mixed'}<br>
                            <strong>Status:</strong> Scanner is working correctly with this laptop!
                        </div>
                    `;
                } else {
                    document.getElementById('scannerStatus').innerHTML = 
                        '<span class="status-indicator status-warning"></span>Partial input detected';
                }
                
                log('SCANNER_INPUT', `"${input}" (${input.length} chars)`, 'success');
            }

            // Event listeners for scanner test input
            scannerTestInput.addEventListener('input', function(e) {
                const value = e.target.value;
                if (isMonitoring) {
                    log('INPUT_EVENT', `Value: "${value}"`, 'info');
                }
                
                if (value.length > 0) {
                    clearTimeout(scannerTimeout);
                    scannerTimeout = setTimeout(() => {
                        processScannerInput(value);
                        e.target.value = ''; // Clear for next scan
                    }, 200);
                }
            });

            scannerTestInput.addEventListener('keydown', function(e) {
                if (isMonitoring) {
                    log('KEYDOWN', `Key: "${e.key}" Code: ${e.keyCode} CharCode: ${e.which}`, 'info');
                }
            });

            scannerTestInput.addEventListener('keypress', function(e) {
                if (isMonitoring) {
                    log('KEYPRESS', `Key: "${e.key}" Code: ${e.keyCode} CharCode: ${e.which}`, 'info');
                }
            });

            scannerTestInput.addEventListener('keyup', function(e) {
                if (isMonitoring) {
                    log('KEYUP', `Key: "${e.key}" Code: ${e.keyCode}`, 'info');
                }
            });

            // Global event monitoring
            document.addEventListener('keydown', function(e) {
                if (isMonitoring && document.activeElement !== scannerTestInput) {
                    log('GLOBAL_KEYDOWN', `Key: "${e.key}" Code: ${e.keyCode} Target: ${e.target.tagName}`, 'warning');
                }
            });

            // Control buttons
            document.getElementById('clearEvents').addEventListener('click', function() {
                eventLog.innerHTML = '<div class="debug-entry"><span class="debug-timestamp">[Cleared]</span> <span class="debug-event">EVENT LOG:</span> <span class="debug-data">Log cleared</span></div>';
            });

            document.getElementById('startMonitoring').addEventListener('click', function() {
                isMonitoring = true;
                log('MONITOR', 'Event monitoring STARTED', 'success');
                this.disabled = true;
                document.getElementById('stopMonitoring').disabled = false;
            });

            document.getElementById('stopMonitoring').addEventListener('click', function() {
                isMonitoring = false;
                log('MONITOR', 'Event monitoring STOPPED', 'warning');
                this.disabled = true;
                document.getElementById('startMonitoring').disabled = false;
            });

            // Focus the input field for immediate testing
            scannerTestInput.focus();
            log('SYSTEM', 'Diagnostic tool ready. Scanner test input focused.', 'success');
        });
    </script>
</body>
</html>