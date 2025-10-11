<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'data_helper.php';
$data = load_data();
$barcodes = isset($data['barcodes']) ? $data['barcodes'] : [];

$filter_course = isset($_GET['course']) && $_GET['course'] !== '' ? $_GET['course'] : '';
$filter_course_year = isset($_GET['course_year']) && $_GET['course_year'] !== '' ? $_GET['course_year'] : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

$filtered_barcodes = array_filter($barcodes, function($barcode) use ($filter_course, $filter_course_year, $filter_search) {
    $matches = true;
    if ($filter_course && $barcode['course'] !== $filter_course) {
        $matches = false;
    }
    if ($filter_course_year && $barcode['course_year'] !== $filter_course_year) {
        $matches = false;
    }
    if ($filter_search) {
        $search_lower = strtolower($filter_search);
        if (strpos(strtolower($barcode['barcode']), $search_lower) === false && strpos(strtolower($barcode['name']), $search_lower) === false) {
            $matches = false;
        }
    }
    return $matches;
});
$filtered_barcodes = array_values($filtered_barcodes);

$courses = array_unique(array_column($barcodes, 'course'));
sort($courses);
$course_years = array_unique(array_column($barcodes, 'course_year'));
sort($course_years);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Table - Barcode Attendance</title>
        <link href="tailwind.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading .spinner {
            display: inline-block;
        }
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }
        #editModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        #editModal .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
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
            max-width: 60%;
            width: 400px;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 20px auto;
            display: block;
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
    </style>
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
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Barcodes</h3>
            <form id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <select name="course" id="filter-course" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Strand/Year Level & Section</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course); ?>" <?php if ($filter_course === $course) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($course); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="course_year" id="filter-course-year" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Year Levels</option>
                    <?php foreach ($course_years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php if ($filter_course_year === $year) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" id="filter-search" placeholder="Search by barcode or student name" value="<?php echo htmlspecialchars($filter_search); ?>"
                       class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </form>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Barcode Table</h2>
        <?php if (empty($data['barcodes'])): ?>
            <p class="text-center text-gray-600">No barcodes available.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg shadow-md">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Student Name</th>
                            <th class="py-3 px-6 text-left">Strand/Year Level & Section</th>
                            <th class="py-3 px-6 text-left">Year Level</th>
                            <th class="py-3 px-6 text-center">Barcode</th>
                            <th class="py-3 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php foreach ($data['barcodes'] as $barcode): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100"
                                data-barcode="<?php echo htmlspecialchars($barcode['barcode']); ?>"
                                data-name="<?php echo htmlspecialchars($barcode['name']); ?>"
                                data-course="<?php echo htmlspecialchars($barcode['course']); ?>"
                                data-course-year="<?php echo htmlspecialchars($barcode['course_year']); ?>">
                                <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['name']); ?></td>
                                <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['course']); ?></td>
                                <td class="py-3 px-6"><?php echo htmlspecialchars($barcode['course_year']); ?></td>
                                <td class="py-3 px-6 text-center">
                                    <?php
                                    $barcodeFile = "barcodes/{$barcode['barcode']}.png";
                                    if (file_exists($barcodeFile)):
                                    ?>
                                        <img src="<?php echo $barcodeFile; ?>" alt="Barcode" class="mx-auto barcode-clickable" style="margin-top: 20px; padding-left: 40px; padding-right: 40px;"
                                             data-barcode-src="<?php echo $barcodeFile; ?>"
                                             data-barcode-id="<?php echo htmlspecialchars($barcode['barcode']); ?>"
                                             data-barcode-name="<?php echo htmlspecialchars($barcode['name']); ?>"
                                             data-barcode-course="<?php echo htmlspecialchars($barcode['course']); ?>"
                                             data-barcode-year="<?php echo htmlspecialchars($barcode['course_year']); ?>">
                                    <?php else: ?>
                                        <span class="text-red-500">Barcode image not found</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6 text-center flex justify-center space-x-2">
                                    <button class="edit-barcode bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600"
                                            data-barcode="<?php echo htmlspecialchars($barcode['barcode']); ?>"
                                            data-name="<?php echo htmlspecialchars($barcode['name']); ?>"
                                            data-course="<?php echo htmlspecialchars($barcode['course']); ?>"
                                            data-course-year="<?php echo htmlspecialchars($barcode['course_year']); ?>">
                                        Edit
                                    </button>
                                    <button class="delete-barcode bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                            data-barcode="<?php echo htmlspecialchars($barcode['barcode']); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div id="editModal">
        <div class="modal-content">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Edit Barcode</h3>
            <form id="edit-barcode-form">
                <input type="hidden" id="edit-barcode" name="barcode">
                <div class="mb-4">
                    <label for="edit-name" class="block text-gray-600">Student Name</label>
                    <input type="text" id="edit-name" name="name" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit-course" class="block text-gray-600">Strand/Year Level & Section</label>
                    <input type="text" id="edit-course" name="course" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit-course-year" class="block text-gray-600">Year Level</label>
                    <input type="text" id="edit-course-year" name="course_year" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancel-edit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Barcode Enlargement Modal -->
    <div id="barcodeModal">
        <div class="modal-content">
            <button class="close-btn" id="closeBarcodeModal">&times;</button>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Barcode Details</h3>
            <div id="barcodeDetails">
                <p><strong>Student Name:</strong> <span id="modalName"></span></p>
                <p><strong>Strand/Year Level & Section:</strong> <span id="modalCourse"></span></p>
                <p><strong>Year Level:</strong> <span id="modalYear"></span></p>
                <p><strong>Barcode ID:</strong> <span id="modalBarcodeId"></span></p>
            </div>
            <img id="enlargedBarcode" class="enlarged-barcode" src="" alt="Enlarged Barcode">
            <div class="scanner-status scanner-ready" id="scannerStatus">
                📱 Scanner Ready - Point your GGOJPRT scanner at the barcode above
            </div>
            <div class="mt-4">
                <button id="closeBarcodeModalBtn" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editModal');
            const editForm = document.getElementById('edit-barcode-form');
            const cancelEdit = document.getElementById('cancel-edit');
            const barcodeModal = document.getElementById('barcodeModal');
            const closeBarcodeModal = document.getElementById('closeBarcodeModal');
            const closeBarcodeModalBtn = document.getElementById('closeBarcodeModalBtn');
            const enlargedBarcode = document.getElementById('enlargedBarcode');
            const scannerStatus = document.getElementById('scannerStatus');
            const form = document.getElementById('filter-form');
            const courseSelect = document.getElementById('filter-course');
            const courseYearSelect = document.getElementById('filter-course-year');
            const searchInput = document.getElementById('filter-search');
            const table = document.querySelector('table');
            const rows = table ? table.querySelectorAll('tbody tr') : [];

            // Scanner state management
            let scannerBuffer = '';
            let scannerTimeout = null;
            let isModalOpen = false;
            let currentBarcodeId = '';

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

            function updateScannerStatus(status, message = '') {
                const statusElement = scannerStatus;
                statusElement.className = 'scanner-status';
                
                switch (status) {
                    case 'ready':
                        statusElement.className += ' scanner-ready';
                        statusElement.innerHTML = '📱 Scanner Ready - Point your GGOJPRT scanner at the barcode above';
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

                console.log('Scanned data:', scannedData);
                
                // Check if scanned data matches current barcode
                if (scannedData === currentBarcodeId) {
                    updateScannerStatus('match', scannedData);
                    // Auto-close modal after successful match (optional)
                    setTimeout(() => {
                        closeBarcodeModalFunc();
                    }, 2000);
                } else {
                    updateScannerStatus('mismatch', scannedData);
                    // Reset status after 3 seconds
                    setTimeout(() => {
                        updateScannerStatus('ready');
                    }, 3000);
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

            // Close modal when clicking outside
            barcodeModal.addEventListener('click', (e) => {
                if (e.target === barcodeModal) {
                    closeBarcodeModalFunc();
                }
            });

            // Keyboard event listener for scanner input
            document.addEventListener('keydown', (e) => {
                if (!isModalOpen) return;

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
            });

            // Original filter functionality
            function applyFilters() {
                const course = courseSelect.value;
                const courseYear = courseYearSelect.value;
                const search = searchInput.value.trim().toLowerCase();

                rows.forEach(row => {
                    const rowCourse = row.dataset.course;
                    const rowCourseYear = row.dataset.courseYear;
                    const rowBarcode = row.dataset.barcode.toLowerCase();
                    const rowName = row.dataset.name.toLowerCase();

                    let matches = true;
                    if (course && course !== '' && rowCourse !== course) {
                        matches = false;
                    }
                    if (courseYear && courseYear !== '' && rowCourseYear !== courseYear) {
                        matches = false;
                    }
                    if (search && !rowBarcode.includes(search) && !rowName.includes(search)) {
                        matches = false;
                    }

                    row.style.display = matches ? '' : 'none';
                });

                const params = new URLSearchParams();
                if (course && course !== '') params.set('course', course);
                if (courseYear && courseYear !== '') params.set('course_year', courseYear);
                if (search) params.set('search', search);
                const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
                history.replaceState(null, '', newUrl);
            }

            if (rows.length > 0) {
                applyFilters();
                courseSelect.addEventListener('change', applyFilters);
                courseYearSelect.addEventListener('change', applyFilters);
                searchInput.addEventListener('input', applyFilters);
            }

            // Delete barcode functionality
            document.querySelectorAll('.delete-barcode').forEach(button => {
                button.addEventListener('click', async () => {
                    const barcode = button.dataset.barcode;
                    if (!confirm(`Are you sure you want to delete barcode ${barcode}?`)) {
                        return;
                    }
                    try {
                        const response = await fetch('delete_barcode.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `barcode=${encodeURIComponent(barcode)}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            button.closest('tr').remove();
                        } else {
                            alert(`Error: ${result.error}`);
                        }
                    } catch (error) {
                        alert('Failed to delete barcode: Network error');
                    }
                });
            });

            // Edit barcode functionality
            document.querySelectorAll('.edit-barcode').forEach(button => {
                button.addEventListener('click', () => {
                    const barcode = button.dataset.barcode;
                    document.getElementById('edit-barcode').value = barcode;
                    document.getElementById('edit-name').value = button.dataset.name;
                    document.getElementById('edit-course').value = button.dataset.course;
                    document.getElementById('edit-course-year').value = button.dataset.courseYear;
                    editModal.style.display = 'flex';
                });
            });

            cancelEdit.addEventListener('click', () => {
                editModal.style.display = 'none';
            });

            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(editForm);
                const data = new URLSearchParams(formData);
                try {
                    const response = await fetch('edit_barcode.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: data
                    });
                    const result = await response.json();
                    if (result.success) {
                        const row = document.querySelector(`tr[data-barcode="${result.barcode}"]`);
                        if (row) {
                            const cells = row.children;
                            cells[0].textContent = result.name;
                            cells[1].textContent = result.course;
                            cells[2].textContent = result.course_year;
                            row.dataset.name = result.name;
                            row.dataset.course = result.course;
                            row.dataset.courseYear = result.course_year;
                            const editButton = row.querySelector('.edit-barcode');
                            editButton.dataset.name = result.name;
                            editButton.dataset.course = result.course;
                            editButton.dataset.courseYear = result.course_year;
                            
                            // Update barcode image data attributes
                            const barcodeImg = row.querySelector('.barcode-clickable');
                            if (barcodeImg) {
                                barcodeImg.dataset.barcodeName = result.name;
                                barcodeImg.dataset.barcodeCourse = result.course;
                                barcodeImg.dataset.barcodeYear = result.course_year;
                            }
                        }
                        editModal.style.display = 'none';
                    } else {
                        alert(`Error: ${result.error}`);
                    }
                } catch (error) {
                    alert('Failed to update barcode: Network error');
                }
            });
        });
    </script>
</body>
</html>