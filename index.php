<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'data_helper.php';
$data = load_data();
$attendance = isset($data['attendance']) ? $data['attendance'] : [];

// Store ALL attendance records for history (don't modify the original data)
$all_attendance_records = $attendance;

// CLEANUP: Remove duplicate attendance records (same barcode + date)
// Keep only the most complete record per date
$cleaned_by_date = [];
$seen_date_records = [];

foreach ($attendance as $record) {
    $key = $record['barcode'] . '|' . $record['date'];
    
    if (!isset($seen_date_records[$key])) {
        // First occurrence - keep it
        $seen_date_records[$key] = count($cleaned_by_date);
        $cleaned_by_date[] = $record;
    } else {
        // Duplicate found - merge the data (keep non-empty values)
        $existing_index = $seen_date_records[$key];
        
        // Merge time_in if current record has it and existing doesn't
        if (empty($cleaned_by_date[$existing_index]['time_in']) && !empty($record['time_in'])) {
            $cleaned_by_date[$existing_index]['time_in'] = $record['time_in'];
        }
        
        // Merge time_out if current record has it and existing doesn't
        if (empty($cleaned_by_date[$existing_index]['time_out']) && !empty($record['time_out'])) {
            $cleaned_by_date[$existing_index]['time_out'] = $record['time_out'];
        }
        
        // Update day if it was empty
        if (empty($cleaned_by_date[$existing_index]['day']) && !empty($record['day'])) {
            $cleaned_by_date[$existing_index]['day'] = $record['day'];
        }
    }
}

// For DISPLAY in main table: Show only the LATEST record per student
// Group by barcode and keep only the most recent date
$latest_attendance = [];
$student_latest = [];

foreach ($cleaned_by_date as $record) {
    $barcode = $record['barcode'];
    $record_date = strtotime($record['date']);
    
    if (!isset($student_latest[$barcode])) {
        // First record for this student
        $student_latest[$barcode] = [
            'index' => count($latest_attendance),
            'date' => $record_date
        ];
        $latest_attendance[] = $record;
    } else {
        // Check if this record is more recent
        if ($record_date > $student_latest[$barcode]['date']) {
            // Replace with newer record
            $index = $student_latest[$barcode]['index'];
            $latest_attendance[$index] = $record;
            $student_latest[$barcode]['date'] = $record_date;
        }
    }
}

// Use latest_attendance for display, but keep all_attendance_records for history
$attendance = $latest_attendance;

// Save cleaned data back if duplicates were found
if (count($cleaned_by_date) < count($data['attendance'])) {
    $data['attendance'] = $cleaned_by_date;
    save_data($data);
}

// Initialize filter variables
$filter_course = isset($_GET['course']) && $_GET['course'] !== '' ? $_GET['course'] : '';
$filter_course_year = isset($_GET['course_year']) && $_GET['course_year'] !== '' ? $_GET['course_year'] : '';
$filter_date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

// Extract unique strands for filtering
$strands = [];
foreach ($attendance as $record) {
    $strand = isset($record['course']) ? trim($record['course']) : 'Unknown';
    if (!in_array($strand, $strands)) {
        $strands[] = $strand;
    }
}
sort($strands);

// Filter attendance records (server-side for initial load)
$filtered_attendance = array_filter($attendance, function($record) use ($filter_course, $filter_course_year, $filter_date, $filter_search) {
    $matches = true;
    if ($filter_course && $record['course'] !== $filter_course) {
        $matches = false;
    }
    if ($filter_course_year && $record['course_year'] !== $filter_course_year) {
        $matches = false;
    }
    if ($filter_date) {
        $record_date = (new DateTime($record['date']))->format('F j, Y');
        if ($record_date !== $filter_date) {
            $matches = false;
        }
    }
    if ($filter_search) {
        $search_lower = strtolower($filter_search);
        if (strpos(strtolower($record['name']), $search_lower) === false && strpos(strtolower($record['barcode']), $search_lower) === false) {
            $matches = false;
        }
    }
    return $matches;
});
$filtered_attendance = array_values($filtered_attendance); // Reindex array
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barcode Attendance</title>
    <link href="tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #deleteModal, #errorModal {
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
        #deleteModal .modal-content, #errorModal .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        #deleteModal .modal-content {
            text-align: center;
        }
        #deleteModal .modal-content p {
            margin-bottom: 1.5rem;
            color: #4b5563;
        }
        #deleteModal .modal-content .btn-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        #errorModal .modal-content {
            background: #ef4444;
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
        #errorModal .modal-content p {
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        /* Barcode Modal Styles */
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
            max-width: 80%;
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

        /* View and Edit Modal Styles */
        #viewModal, #editModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10002;
            justify-content: center;
            align-items: center;
        }

        #viewModal .modal-content {
            background: white;
            padding: 16px;
            border-radius: 8px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #editModal .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 95%;
            max-width: 800px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #374151;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }

        .detail-value {
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
            flex-shrink: 0;
            width: 100px;
        }

        .detail-value {
            color: #6b7280;
            text-align: right;
            flex-grow: 1;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #111827;
            text-align: center;
            margin-bottom: 10px;
        }

        .view-barcode-container {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .view-barcode-image {
            max-width: 280px;
            height: auto;
            margin: 8px 0;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            display: block;
        }

        .barcode-id {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
            font-weight: 500;
        }

        /* Action Button Styles */
        .action-btn {
            padding: 8px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            position: relative;
            text-decoration: none;
        }

        .action-btn i {
            pointer-events: none;
            width: 14px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .action-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-view {
            background-color: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background-color: #2563eb;
        }

        .btn-edit {
            background-color: #10b981;
            color: white;
        }

        .btn-edit:hover {
            background-color: #059669;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background-color: #dc2626;
        }

        .barcode-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Compact Strand filter tabs */
        .compact-strand-container {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .compact-strand-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }
        
        .compact-strand-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .compact-strand-tab {
            padding: 6px 14px;
            border: 1.5px solid #3b82f6;
            border-radius: 6px;
            background: white;
            color: #3b82f6;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .compact-strand-tab:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .compact-strand-tab.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.4);
        }
        
        .attendance-row {
            transition: opacity 0.2s ease;
        }
        
        .attendance-row.hidden {
            display: none;
        }

        .grade-section {
            transition: opacity 0.2s ease;
        }
        
        .grade-section.hidden {
            display: none;
        }

        /* Modal action boxes for Time In/Time Out */
        .modal-action-box {
            transition: all 0.3s ease;
        }
        
        .modal-action-box:hover {
            background-color: #3b82f6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        
        .modal-action-box.selected {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.5);
        }

        /* Attendance History Table */
        #historyTable {
            border-collapse: collapse;
        }
        
        #historyTable thead {
            background: #f3f4f6;
        }
        
        #historyTable tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        
        #historyTable tbody tr:hover {
            background-color: #f9fafb;
        }
        
        #historyTable tbody tr:last-child {
            border-bottom: none;
        }
        
        #historyTable th,
        #historyTable td {
            padding: 8px 12px;
            text-align: left;
        }
        
        #historyTableContainer {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
        }
        
        .history-date-today {
            background-color: #dbeafe !important;
            font-weight: 600;
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
        <!-- Filter Attendance -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Attendance</h3>
            <form id="filter-form" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <select name="course" id="filter-course" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Strand</option>
                    <?php foreach (array_unique(array_column($attendance, 'course')) as $course): ?>
                        <option value="<?php echo htmlspecialchars($course); ?>" <?php if ($filter_course === $course) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($course); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="course_year" id="filter-course-year" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Year Level</option>
                    <?php foreach (array_unique(array_column($attendance, 'course_year')) as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php if ($filter_course_year === $year) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="date" id="filter-date" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Date</option>
                    <?php foreach (array_unique(array_map(function($r) { return (new DateTime($r['date']))->format('F j, Y'); }, $attendance)) as $date): ?>
                        <option value="<?php echo htmlspecialchars($date); ?>" <?php if ($filter_date === $date) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($date); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" id="filter-search" placeholder="Search by student name or barcode" value="<?php echo htmlspecialchars($filter_search); ?>"
                       class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 col-span-1 sm:col-span-4">
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Attendance Records</h2>
            <?php if (empty($filtered_attendance)): ?>
                <p class="text-gray-600 text-center">No attendance records found.</p>
            <?php else: ?>
                <!-- Total Students Display -->
                <div class="mb-6 text-center">
                    <div class="inline-block bg-blue-500 text-white px-6 py-3 rounded-lg shadow-md">
                        <span class="text-sm font-semibold uppercase tracking-wide">Total Students</span>
                        <div class="text-3xl font-bold mt-1"><?php echo count($attendance); ?></div>
                    </div>
                </div>
                <?php
                // Sort attendance alphabetically by student name
                usort($attendance, function($a, $b) {
                    $nameA = isset($a['name']) ? strtolower(trim($a['name'])) : '';
                    $nameB = isset($b['name']) ? strtolower(trim($b['name'])) : '';
                    return strcmp($nameA, $nameB);
                });
                
                // Group attendance by grade level (course_year)
                $grouped_by_grade = [];
                foreach ($attendance as $record) {
                    $grade = $record['course_year'];
                    if (!isset($grouped_by_grade[$grade])) {
                        $grouped_by_grade[$grade] = [];
                    }
                    $grouped_by_grade[$grade][] = $record;
                }
                ksort($grouped_by_grade); // Sort by grade level
                ?>
                
                <!-- Grade Level Summary -->
                <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($grouped_by_grade as $grade => $records): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($grade); ?></div>
                            <div class="text-sm text-gray-600">Grade Level</div>
                            <div class="text-xl font-semibold text-gray-800 mt-2"><?php echo count($records); ?></div>
                            <div class="text-xs text-gray-500">Students</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Compact Strand Filter Tabs -->
                <div class="compact-strand-container">
                    <span class="compact-strand-label">📚 Filter by Strand:</span>
                    <div class="compact-strand-tabs">
                        <button class="compact-strand-tab active" data-strand="all">
                            All (<?php echo count($attendance); ?>)
                        </button>
                        <?php foreach ($strands as $strand): 
                            $strandCount = count(array_filter($attendance, function($r) use ($strand) {
                                return isset($r['course']) && trim($r['course']) === $strand;
                            }));
                        ?>
                            <button class="compact-strand-tab" data-strand="<?php echo htmlspecialchars($strand); ?>">
                                <?php echo htmlspecialchars($strand); ?> (<?php echo $strandCount; ?>)
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <?php foreach ($grouped_by_grade as $grade => $grade_records): ?>
                        <div class="grade-section" data-grade="<?php echo htmlspecialchars($grade); ?>">
                            <!-- Grade Level Header -->
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-t-lg mt-6 flex justify-between items-center">
                                <h3 class="text-lg font-bold">Grade <?php echo htmlspecialchars($grade); ?></h3>
                                <span class="bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo count($grade_records); ?> Students
                                </span>
                            </div>
                            
                            <table class="min-w-full bg-white border mb-6" id="attendance-table-grade-<?php echo htmlspecialchars($grade); ?>">
                            <thead>
                                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-center">Barcode</th>
                                    <th class="py-3 px-6 text-left">Student Name</th>
                                    <th class="py-3 px-6 text-left">Strand/Section</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                    <th class="py-3 px-6 text-left">Day</th>
                                    <th class="py-3 px-6 text-left">Time In</th>
                                    <th class="py-3 px-6 text-left">Time Out</th>
                                    <th class="py-3 px-6 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm">
                                <?php foreach ($grade_records as $record): ?>
                                <tr class="attendance-row border-b hover:bg-gray-100"
                                    data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                    data-name="<?php echo htmlspecialchars($record['name']); ?>"
                                    data-course="<?php echo htmlspecialchars($record['course']); ?>"
                                    data-strand="<?php echo htmlspecialchars(isset($record['course']) ? trim($record['course']) : 'Unknown'); ?>"
                                    data-course-year="<?php echo htmlspecialchars($record['course_year']); ?>"
                                    data-date="<?php echo (new DateTime($record['date']))->format('F j, Y'); ?>">
                                    <td class="py-3 px-6 text-center">
                                        <?php
                                        $barcodeFile = "barcodes/{$record['barcode']}.png";
                                        if (file_exists($barcodeFile)):
                                        ?>
                                            <img src="<?php echo $barcodeFile; ?>" alt="Barcode: <?php echo htmlspecialchars($record['barcode']); ?>" 
                                                 class="mx-auto max-w-32 h-auto cursor-pointer barcode-clickable"
                                                 title="Barcode: <?php echo htmlspecialchars($record['barcode']); ?>"
                                                 data-barcode-src="<?php echo $barcodeFile; ?>"
                                                 data-barcode-id="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                 data-barcode-name="<?php echo htmlspecialchars($record['name']); ?>"
                                                 data-barcode-course="<?php echo htmlspecialchars($record['course']); ?>"
                                                 data-barcode-year="<?php echo htmlspecialchars($record['course_year']); ?>">
                                        <?php else: ?>
                                            <div class="text-center">
                                                <span class="text-red-500 text-xs block mb-1">Barcode image missing</span>
                                                <span class="text-gray-500 text-xs block mb-2"><?php echo htmlspecialchars($record['barcode']); ?></span>
                                                <button class="regenerate-barcode bg-blue-500 text-white text-xs px-2 py-1 rounded hover:bg-blue-600"
                                                        data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                        data-name="<?php echo htmlspecialchars($record['name']); ?>"
                                                        data-course="<?php echo htmlspecialchars($record['course']); ?>"
                                                        data-course-year="<?php echo htmlspecialchars($record['course_year']); ?>">
                                                    Regenerate
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['course']); ?></td>
                                    <td class="py-3 px-6">
                                        <?php
                                        $date = new DateTime($record['date']);
                                        echo $date->format('F j, Y'); // e.g., May 11, 2025
                                        ?>
                                    </td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['day']); ?></td>
                                    <td class="py-3 px-6">
                                        <?php
                                        if ($record['time_in']) {
                                            // Handle time-only format (H:i:s) by creating from format
                                            $time_in = DateTime::createFromFormat('H:i:s', $record['time_in']);
                                            if ($time_in === false) {
                                                // Fallback: try to parse as full datetime
                                                $time_in = new DateTime($record['time_in']);
                                            }
                                            echo $time_in->format('g:i A'); // e.g., 1:03 PM
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <?php
                                        if ($record['time_out']) {
                                            // Handle time-only format (H:i:s) by creating from format
                                            $time_out = DateTime::createFromFormat('H:i:s', $record['time_out']);
                                            if ($time_out === false) {
                                                // Fallback: try to parse as full datetime
                                                $time_out = new DateTime($record['time_out']);
                                            }
                                            echo $time_out->format('g:i A'); // e.g., 1:45 PM
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <div class="flex space-x-2">
                                            <button class="view-attendance action-btn btn-view"
                                                    data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                    data-name="<?php echo htmlspecialchars($record['name']); ?>"
                                                    data-course="<?php echo htmlspecialchars($record['course']); ?>"
                                                    data-course-year="<?php echo htmlspecialchars($record['course_year']); ?>"
                                                    data-date="<?php echo htmlspecialchars($record['date']); ?>"
                                                    data-day="<?php echo htmlspecialchars($record['day']); ?>"
                                                    data-time-in="<?php echo htmlspecialchars($record['time_in']); ?>"
                                                    data-time-out="<?php echo htmlspecialchars($record['time_out']); ?>"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="edit-attendance action-btn btn-edit"
                                                    data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                    data-name="<?php echo htmlspecialchars($record['name']); ?>"
                                                    data-course="<?php echo htmlspecialchars($record['course']); ?>"
                                                    data-course-year="<?php echo htmlspecialchars($record['course_year']); ?>"
                                                    data-date="<?php echo htmlspecialchars($record['date']); ?>"
                                                    data-day="<?php echo htmlspecialchars($record['day']); ?>"
                                                    data-time-in="<?php echo htmlspecialchars($record['time_in']); ?>"
                                                    data-time-out="<?php echo htmlspecialchars($record['time_out']); ?>"
                                                    title="Edit Record">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="delete-attendance action-btn btn-delete"
                                                    data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                    data-date="<?php echo htmlspecialchars($record['date']); ?>"
                                                    title="Delete Record">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal">
        <div class="modal-content">
            <p id="delete-message"></p>
            <div class="btn-container">
                <button id="cancel-delete" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                <button id="confirm-delete" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>
            </div>
        </div>
    </div>
    <!-- Error Message Modal -->
    <div id="errorModal">
        <div class="modal-content">
            <p id="error-message"></p>
            <button id="close-error" class="bg-white text-red-500 px-4 py-2 rounded hover:bg-gray-100">Close</button>
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
            
            <!-- Time In / Time Out Selection -->
            <div class="mt-4 mb-4">
                <p class="text-sm font-semibold text-gray-600 mb-2 text-center">Select Action:</p>
                <div class="flex justify-center space-x-4">
                    <div class="modal-action-box border-2 border-blue-500 text-blue-500 p-3 rounded-lg cursor-pointer font-semibold selected w-32 text-center transition-all"
                         data-action="time_in">
                        Time In
                    </div>
                    <div class="modal-action-box border-2 border-blue-500 text-blue-500 p-3 rounded-lg cursor-pointer font-semibold w-32 text-center transition-all"
                         data-action="time_out">
                        Time Out
                    </div>
                </div>
            </div>
            
            <div class="mt-4 flex justify-center space-x-2">
                <button id="scanBarcodeFromModal" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 transition-colors">
                    📝 Record Attendance
                </button>
                <button id="closeBarcodeModalBtn" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition-colors">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for attendance submission -->
    <form id="attendance-scan-form" action="process_scan.php" method="POST" style="display: none;">
        <input type="hidden" id="scan-barcode" name="barcode">
        <input type="hidden" id="scan-action" name="action" value="time_in">
    </form>

    <!-- View Attendance Modal -->
    <div id="viewModal">
        <div class="modal-content">
            <div class="modal-header" style="padding-bottom: 10px; margin-bottom: 12px;">
                <h3 style="font-size: 16px; font-weight: 600;">Attendance Details</h3>
            </div>
            <div id="viewContent">
                <div class="view-barcode-container" style="margin-bottom: 10px;">
                    <img id="viewBarcodeImage" class="view-barcode-image" src="" alt="Student Barcode" style="max-width: 180px; height: auto;">
                    <div class="barcode-id" id="viewBarcodeId" style="font-size: 10px; margin-top: 4px;"></div>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Name:</span>
                    <span class="detail-value" id="viewName"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Strand:</span>
                    <span class="detail-value" id="viewCourse"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Year:</span>
                    <span class="detail-value" id="viewCourseYear"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Date:</span>
                    <span class="detail-value" id="viewDate"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Day:</span>
                    <span class="detail-value" id="viewDay"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Time In:</span>
                    <span class="detail-value" id="viewTimeIn"></span>
                </div>
                <div class="detail-row" style="padding: 4px 0; font-size: 13px;">
                    <span class="detail-label" style="font-weight: 600; color: #6b7280; min-width: 70px;">Time Out:</span>
                    <span class="detail-value" id="viewTimeOut"></span>
                </div>
            </div>
            
            <!-- Attendance History Section -->
            <div id="attendanceHistory" style="margin-top: 12px;">
                <h4 style="color: #374151; margin-bottom: 8px; font-size: 13px; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">📅 History</h4>
                <div id="historyTableContainer" style="max-height: 180px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <table class="min-w-full" id="historyTable" style="font-size: 11px;">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="py-1 px-2 text-left text-xs font-semibold text-gray-600">Date</th>
                                <th class="py-1 px-2 text-left text-xs font-semibold text-gray-600">Day</th>
                                <th class="py-1 px-2 text-left text-xs font-semibold text-gray-600">In</th>
                                <th class="py-1 px-2 text-left text-xs font-semibold text-gray-600">Out</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- History records will be inserted here -->
                        </tbody>
                    </table>
                </div>
                <div id="noHistoryMessage" style="display: none; padding: 12px; text-align: center; color: #6b7280; font-size: 12px;">
                    No history found
                </div>
            </div>
            
            <div class="modal-buttons" style="margin-top: 12px;">
                <button id="closeViewModal" class="bg-gray-500 text-white px-3 py-1.5 rounded text-sm hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Attendance Record</h3>
            </div>
            <form id="editForm">
                <input type="hidden" id="editOriginalBarcode">
                <input type="hidden" id="editOriginalDate">
                
                <!-- Student Information Section -->
                <div style="margin-bottom: 25px;">
                    <h4 style="color: #374151; margin-bottom: 15px; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">Student Information</h4>
                    
                    <div class="form-group">
                        <label for="editBarcode">Barcode ID:</label>
                        <input type="text" id="editBarcode" readonly class="bg-gray-100">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editName">Student Name:</label>
                            <input type="text" id="editName" required>
                        </div>
                        <div class="form-group">
                            <label for="editCourse">Strand:</label>
                            <input type="text" id="editCourse" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCourseYear">Year Level:</label>
                        <input type="text" id="editCourseYear" required>
                    </div>
                </div>
                
                <!-- Attendance Details Section -->
                <div style="margin-bottom: 25px;">
                    <h4 style="color: #374151; margin-bottom: 15px; font-size: 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">Attendance Details</h4>
                    
                    <div class="form-group">
                        <label for="editDate">Date:</label>
                        <input type="date" id="editDate" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editTimeIn">Time In:</label>
                            <input type="time" id="editTimeIn" required>
                            <small class="text-gray-500">Use 24-hour format (e.g., 13:30 for 1:30 PM)</small>
                        </div>
                        <div class="form-group">
                            <label for="editTimeOut">Time Out:</label>
                            <input type="time" id="editTimeOut">
                            <small class="text-gray-500">Use 24-hour format (e.g., 17:45 for 5:45 PM)</small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" id="cancelEdit" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Automatic Filtering and Deletion -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('filter-form');
            const courseSelect = document.getElementById('filter-course');
            const courseYearSelect = document.getElementById('filter-course-year');
            const dateSelect = document.getElementById('filter-date');
            const searchInput = document.getElementById('filter-search');
            // Get all tables (one per grade level)
            const tables = document.querySelectorAll('table[id^="attendance-table-grade-"]');
            const rows = [];
            tables.forEach(table => {
                const tableRows = table.querySelectorAll('tbody tr');
                rows.push(...tableRows);
            });
            const deleteModal = document.getElementById('deleteModal');
            const errorModal = document.getElementById('errorModal');
            const deleteMessage = document.getElementById('delete-message');
            const errorMessage = document.getElementById('error-message');
            const cancelDelete = document.getElementById('cancel-delete');
            const confirmDelete = document.getElementById('confirm-delete');
            const closeError = document.getElementById('close-error');

            // Barcode modal elements
            const barcodeModal = document.getElementById('barcodeModal');
            const closeBarcodeModal = document.getElementById('closeBarcodeModal');
            const closeBarcodeModalBtn = document.getElementById('closeBarcodeModalBtn');
            const enlargedBarcode = document.getElementById('enlargedBarcode');

            // View modal elements
            const viewModal = document.getElementById('viewModal');
            const closeViewModal = document.getElementById('closeViewModal');

            // Edit modal elements
            const editModal = document.getElementById('editModal');
            const editForm = document.getElementById('editForm');
            const cancelEdit = document.getElementById('cancelEdit');

            // Barcode modal functionality
            let currentModalBarcodeId = '';
            const modalActionBoxes = document.querySelectorAll('.modal-action-box');
            const scanBarcodeFromModal = document.getElementById('scanBarcodeFromModal');
            const attendanceScanForm = document.getElementById('attendance-scan-form');
            const scanBarcodeInput = document.getElementById('scan-barcode');
            const scanActionInput = document.getElementById('scan-action');
            
            function openBarcodeModal(imageSrc, barcodeId, name, course, year) {
                document.getElementById('modalName').textContent = name;
                document.getElementById('modalCourse').textContent = course;
                document.getElementById('modalYear').textContent = year;
                document.getElementById('modalBarcodeId').textContent = barcodeId;
                enlargedBarcode.src = imageSrc;
                currentModalBarcodeId = barcodeId;
                barcodeModal.style.display = 'flex';
                
                // Reset to Time In by default
                modalActionBoxes.forEach(box => box.classList.remove('selected'));
                modalActionBoxes[0].classList.add('selected');
                scanActionInput.value = 'time_in';
            }

            function closeBarcodeModalFunc() {
                barcodeModal.style.display = 'none';
                currentModalBarcodeId = '';
            }
            
            // Handle Time In / Time Out selection in modal
            modalActionBoxes.forEach(box => {
                box.addEventListener('click', () => {
                    modalActionBoxes.forEach(b => b.classList.remove('selected'));
                    box.classList.add('selected');
                    scanActionInput.value = box.dataset.action;
                });
            });
            
            // Handle scan/record attendance from modal
            scanBarcodeFromModal.addEventListener('click', async () => {
                if (!currentModalBarcodeId) return;
                
                scanBarcodeInput.value = currentModalBarcodeId;
                const action = scanActionInput.value;
                
                // Disable button and show loading state
                scanBarcodeFromModal.disabled = true;
                scanBarcodeFromModal.textContent = 'Processing...';
                
                try {
                    const formData = new FormData(attendanceScanForm);
                    const response = await fetch('process_scan.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: new URLSearchParams(formData)
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        alert(`✅ ${result.message}`);
                        closeBarcodeModalFunc();
                        // Reload page to show updated attendance
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert(`❌ ${result.message}`);
                        // Re-enable button
                        scanBarcodeFromModal.disabled = false;
                        scanBarcodeFromModal.textContent = '📝 Record Attendance';
                    }
                } catch (error) {
                    alert('❌ Network error occurred');
                    // Re-enable button
                    scanBarcodeFromModal.disabled = false;
                    scanBarcodeFromModal.textContent = '📝 Record Attendance';
                }
            });

            // View modal functions
            async function openViewModal(data) {
                // Set barcode image
                const barcodeImage = document.getElementById('viewBarcodeImage');
                const barcodeFile = `barcodes/${data.barcode}.png`;
                barcodeImage.src = barcodeFile;
                barcodeImage.onerror = function() {
                    this.style.display = 'none';
                    document.getElementById('viewBarcodeId').innerHTML = `<span style="color: #ef4444;">Barcode image not found</span><br>${data.barcode}`;
                };
                barcodeImage.onload = function() {
                    this.style.display = 'block';
                };
                
                document.getElementById('viewBarcodeId').textContent = data.barcode;
                document.getElementById('viewName').textContent = data.name;
                document.getElementById('viewCourse').textContent = data.course;
                document.getElementById('viewCourseYear').textContent = data.courseYear;
                document.getElementById('viewDate').textContent = new Date(data.date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('viewDay').textContent = data.day;
                document.getElementById('viewTimeIn').textContent = data.timeIn ? new Date('1970-01-01T' + data.timeIn).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : 'Not recorded';
                document.getElementById('viewTimeOut').textContent = data.timeOut ? new Date('1970-01-01T' + data.timeOut).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : 'Not recorded';
                
                // Load attendance history for this student
                await loadAttendanceHistory(data.barcode, data.date);
                
                viewModal.style.display = 'flex';
            }
            
            // Load and display attendance history
            async function loadAttendanceHistory(barcode, currentDate) {
                const historyTableBody = document.getElementById('historyTableBody');
                const noHistoryMessage = document.getElementById('noHistoryMessage');
                const historyTableContainer = document.getElementById('historyTableContainer');
                
                // Clear previous history
                historyTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-gray-500">Loading history...</td></tr>';
                
                try {
                    // Fetch attendance history from server
                    const response = await fetch(`get_attendance_history.php?barcode=${encodeURIComponent(barcode)}`, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Server response:', errorText);
                        throw new Error(`Failed to fetch history: ${response.status}`);
                    }
                    
                    const studentHistory = await response.json();
                    
                    if (!Array.isArray(studentHistory) || studentHistory.length === 0) {
                        historyTableContainer.style.display = 'none';
                        noHistoryMessage.style.display = 'block';
                        return;
                    }
                    
                    historyTableContainer.style.display = 'block';
                    noHistoryMessage.style.display = 'none';
                    
                    // Build table rows
                    let historyHTML = '';
                    const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    
                    studentHistory.forEach((record, index) => {
                        const recordDate = new Date(record.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                        const isToday = recordDate === currentDate || recordDate === today;
                        const rowClass = isToday ? 'history-date-today' : '';
                        
                        const timeInDisplay = record.time_in ? new Date('1970-01-01T' + record.time_in).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        }) : '-';
                        
                        const timeOutDisplay = record.time_out ? new Date('1970-01-01T' + record.time_out).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        }) : '-';
                        
                        historyHTML += `
                            <tr class="${rowClass}" style="border-bottom: 1px solid #f3f4f6;">
                                <td class="py-1 px-2 text-gray-700" style="font-size: 11px;">${recordDate}${isToday ? ' <span class="text-blue-600" style="font-size: 10px; font-weight: 600;">(Today)</span>' : ''}</td>
                                <td class="py-1 px-2 text-gray-600" style="font-size: 11px;">${record.day}</td>
                                <td class="py-1 px-2 text-gray-700" style="font-size: 11px;">${timeInDisplay}</td>
                                <td class="py-1 px-2 text-gray-700" style="font-size: 11px;">${timeOutDisplay}</td>
                            </tr>
                        `;
                    });
                    
                    historyTableBody.innerHTML = historyHTML;
                    
                } catch (error) {
                    console.error('Error loading attendance history:', error);
                    historyTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-red-500">Error loading history</td></tr>';
                }
            }

            function closeViewModalFunc() {
                viewModal.style.display = 'none';
            }

            // Edit modal functions
            function openEditModal(data) {
                document.getElementById('editOriginalBarcode').value = data.barcode;
                document.getElementById('editOriginalDate').value = data.date;
                document.getElementById('editBarcode').value = data.barcode;
                document.getElementById('editName').value = data.name;
                document.getElementById('editCourse').value = data.course;
                document.getElementById('editCourseYear').value = data.courseYear;
                document.getElementById('editDate').value = data.date;
                document.getElementById('editTimeIn').value = data.timeIn ? data.timeIn.substring(0, 5) : '';
                document.getElementById('editTimeOut').value = data.timeOut ? data.timeOut.substring(0, 5) : '';
                editModal.style.display = 'flex';
            }

            function closeEditModalFunc() {
                editModal.style.display = 'none';
                editForm.reset();
            }

            // Event listeners for barcode modal
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('barcode-clickable')) {
                    const src = e.target.dataset.barcodeSrc;
                    const id = e.target.dataset.barcodeId;
                    const name = e.target.dataset.barcodeName;
                    const course = e.target.dataset.barcodeCourse;
                    const year = e.target.dataset.barcodeYear;
                    openBarcodeModal(src, id, name, course, year);
                }
            });

            closeBarcodeModal.addEventListener('click', closeBarcodeModalFunc);
            closeBarcodeModalBtn.addEventListener('click', closeBarcodeModalFunc);
            closeViewModal.addEventListener('click', closeViewModalFunc);
            cancelEdit.addEventListener('click', closeEditModalFunc);

            // Close modals when clicking outside
            barcodeModal.addEventListener('click', (e) => {
                if (e.target === barcodeModal) {
                    closeBarcodeModalFunc();
                }
            });

            viewModal.addEventListener('click', (e) => {
                if (e.target === viewModal) {
                    closeViewModalFunc();
                }
            });

            editModal.addEventListener('click', (e) => {
                if (e.target === editModal) {
                    closeEditModalFunc();
                }
            });

            function showError(message) {
                errorMessage.textContent = message;
                errorModal.style.display = 'flex';
                setTimeout(() => {
                    errorModal.style.display = 'none';
                }, 3000);
            }

            function applyFilters() {
                const course = courseSelect.value;
                const courseYear = courseYearSelect.value;
                const date = dateSelect.value;
                const search = searchInput.value.trim().toLowerCase();

                rows.forEach(row => {
                    const rowCourse = row.dataset.course;
                    const rowCourseYear = row.dataset.courseYear;
                    const rowDate = row.dataset.date;
                    const rowName = row.dataset.name.toLowerCase();
                    const rowBarcode = row.dataset.barcode.toLowerCase();

                    let matches = true;
                    if (course && course !== '' && rowCourse !== course) {
                        matches = false;
                    }
                    if (courseYear && courseYear !== '' && rowCourseYear !== courseYear) {
                        matches = false;
                    }
                    if (date && date !== '' && rowDate !== date) {
                        matches = false;
                    }
                    if (search && !rowName.includes(search) && !rowBarcode.includes(search)) {
                        matches = false;
                    }

                    row.style.display = matches ? '' : 'none';
                });

                const params = new URLSearchParams();
                if (course && course !== '') params.set('course', course);
                if (courseYear && courseYear !== '') params.set('course_year', courseYear);
                if (date && date !== '') params.set('date', date);
                if (search) params.set('search', search);
                const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
                history.replaceState(null, '', newUrl);
            }

            applyFilters();
            courseSelect.addEventListener('change', applyFilters);
            courseYearSelect.addEventListener('change', applyFilters);
            dateSelect.addEventListener('change', applyFilters);
            searchInput.addEventListener('input', applyFilters);

            // Compact strand filter tabs functionality
            const strandTabs = document.querySelectorAll('.compact-strand-tab');
            const attendanceRows = document.querySelectorAll('.attendance-row');
            const gradeSections = document.querySelectorAll('.grade-section');
            
            strandTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    strandTabs.forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    tab.classList.add('active');
                    
                    const selectedStrand = tab.dataset.strand;
                    
                    // Filter attendance rows
                    attendanceRows.forEach(row => {
                        if (selectedStrand === 'all') {
                            row.classList.remove('hidden');
                        } else {
                            if (row.dataset.strand === selectedStrand) {
                                row.classList.remove('hidden');
                            } else {
                                row.classList.add('hidden');
                            }
                        }
                    });

                    // Hide/show grade sections based on whether they have visible rows
                    gradeSections.forEach(section => {
                        const sectionRows = section.querySelectorAll('.attendance-row');
                        const hasVisibleRows = Array.from(sectionRows).some(row => !row.classList.contains('hidden'));
                        
                        if (hasVisibleRows) {
                            section.classList.remove('hidden');
                        } else {
                            section.classList.add('hidden');
                        }
                    });
                });
            });

            // Add click listeners to all tables
            tables.forEach(table => {
                table.addEventListener('click', async (e) => {
                if (e.target.classList.contains('view-attendance')) {
                    const data = {
                        barcode: e.target.dataset.barcode,
                        name: e.target.dataset.name,
                        course: e.target.dataset.course,
                        courseYear: e.target.dataset.courseYear,
                        date: e.target.dataset.date,
                        day: e.target.dataset.day,
                        timeIn: e.target.dataset.timeIn,
                        timeOut: e.target.dataset.timeOut
                    };
                    openViewModal(data);
                } else if (e.target.classList.contains('edit-attendance')) {
                    const data = {
                        barcode: e.target.dataset.barcode,
                        name: e.target.dataset.name,
                        course: e.target.dataset.course,
                        courseYear: e.target.dataset.courseYear,
                        date: e.target.dataset.date,
                        day: e.target.dataset.day,
                        timeIn: e.target.dataset.timeIn,
                        timeOut: e.target.dataset.timeOut
                    };
                    openEditModal(data);
                } else if (e.target.classList.contains('delete-attendance')) {
                    const barcode = e.target.dataset.barcode;
                    const date = e.target.dataset.date;
                    deleteMessage.textContent = `Are you sure you want to delete attendance for barcode ${barcode} on ${date}?`;
                    deleteModal.style.display = 'flex';

                    confirmDelete.onclick = async () => {
                        deleteModal.style.display = 'none';
                        try {
                            const response = await fetch('delete_attendance.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `barcode=${encodeURIComponent(barcode)}&date=${encodeURIComponent(date)}`
                            });
                            const result = await response.json();
                            if (result.success) {
                                e.target.closest('tr').remove();
                                applyFilters();
                            } else {
                                showError(`Error: ${result.error}`);
                            }
                        } catch (error) {
                            showError('Failed to delete attendance: Network error');
                        }
                    };
                } else if (e.target.classList.contains('regenerate-barcode')) {
                    const button = e.target;
                    const originalText = button.textContent;
                    button.disabled = true;
                    button.textContent = 'Regenerating...';
                    button.classList.add('opacity-50');
                    
                    try {
                        const formData = new FormData();
                        formData.append('barcode_id', button.dataset.barcode);
                        formData.append('name', button.dataset.name);
                        formData.append('course', button.dataset.course);
                        formData.append('course_year', button.dataset.courseYear);
                        
                        const response = await fetch('regenerate_barcode.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Reload the page to show the regenerated barcode
                            location.reload();
                        } else {
                            showError(result.message || 'Failed to regenerate barcode');
                            button.disabled = false;
                            button.textContent = originalText;
                            button.classList.remove('opacity-50');
                        }
                    } catch (error) {
                        showError('Network error occurred while regenerating barcode');
                        button.disabled = false;
                        button.textContent = originalText;
                        button.classList.remove('opacity-50');
                    }
                }
                });
            }); // End forEach for tables

            // Edit form submission
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('original_barcode', document.getElementById('editOriginalBarcode').value);
                formData.append('original_date', document.getElementById('editOriginalDate').value);
                formData.append('name', document.getElementById('editName').value);
                formData.append('course', document.getElementById('editCourse').value);
                formData.append('course_year', document.getElementById('editCourseYear').value);
                formData.append('date', document.getElementById('editDate').value);
                formData.append('time_in', document.getElementById('editTimeIn').value);
                formData.append('time_out', document.getElementById('editTimeOut').value);

                try {
                    const response = await fetch('edit_attendance.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        closeEditModalFunc();
                        // Reload the page to show updated data
                        window.location.reload();
                    } else {
                        showError(`Error: ${result.error}`);
                    }
                } catch (error) {
                    showError('Failed to update attendance: Network error');
                }
            });

            cancelDelete.addEventListener('click', () => {
                deleteModal.style.display = 'none';
            });

            closeError.addEventListener('click', () => {
                errorModal.style.display = 'none';
            });

            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });

            errorModal.addEventListener('click', (e) => {
                if (e.target === errorModal) {
                    errorModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>