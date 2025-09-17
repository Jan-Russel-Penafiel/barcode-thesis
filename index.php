<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'data_helper.php';
$data = load_data();
$attendance = isset($data['attendance']) ? $data['attendance'] : [];

// Initialize filter variables
$filter_course = isset($_GET['course']) && $_GET['course'] !== 'Course' ? $_GET['course'] : '';
$filter_course_year = isset($_GET['course_year']) && $_GET['course_year'] !== 'Course Year' ? $_GET['course_year'] : '';
$filter_date = isset($_GET['date']) && $_GET['date'] !== 'Date' ? $_GET['date'] : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

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
                    <option value="Course">Course</option>
                    <?php foreach (array_unique(array_column($attendance, 'course')) as $course): ?>
                        <option value="<?php echo htmlspecialchars($course); ?>" <?php if ($filter_course === $course) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($course); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="course_year" id="filter-course-year" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Course Year">Course Year</option>
                    <?php foreach (array_unique(array_column($attendance, 'course_year')) as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php if ($filter_course_year === $year) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="date" id="filter-date" class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Date">Date</option>
                    <?php foreach (array_unique(array_map(function($r) { return (new DateTime($r['date']))->format('F j, Y'); }, $attendance)) as $date): ?>
                        <option value="<?php echo htmlspecialchars($date); ?>" <?php if ($filter_date === $date) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($date); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" id="filter-search" placeholder="Search by name or barcode" value="<?php echo htmlspecialchars($filter_search); ?>"
                       class="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 col-span-1 sm:col-span-4">
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Attendance Records</h2>
            <?php if (empty($filtered_attendance)): ?>
                <p class="text-gray-600 text-center">No attendance records found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border" id="attendance-table">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Name</th>
                                <th class="py-3 px-6 text-left">Course</th>
                                <th class="py-3 px-6 text-left">Year</th>
                                <th class="py-3 px-6 text-left">Date</th>
                                <th class="py-3 px-6 text-left">Day</th>
                                <th class="py-3 px-6 text-left">Time In</th>
                                <th class="py-3 px-6 text-left">Time Out</th>
                                <th class="py-3 px-6 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            <?php foreach ($attendance as $record): ?>
                                <tr class="border-b hover:bg-gray-100"
                                    data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                    data-name="<?php echo htmlspecialchars($record['name']); ?>"
                                    data-course="<?php echo htmlspecialchars($record['course']); ?>"
                                    data-course-year="<?php echo htmlspecialchars($record['course_year']); ?>"
                                    data-date="<?php echo (new DateTime($record['date']))->format('F j, Y'); ?>">
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['course']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($record['course_year']); ?></td>
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
                                            $time_in = new DateTime($record['time_in']);
                                            echo $time_in->format('g:i A'); // e.g., 2:30 PM
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <?php
                                        if ($record['time_out']) {
                                            $time_out = new DateTime($record['time_out']);
                                            echo $time_out->format('g:i A'); // e.g., 2:45 PM
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <button class="delete-attendance bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"
                                                data-barcode="<?php echo htmlspecialchars($record['barcode']); ?>"
                                                data-date="<?php echo htmlspecialchars($record['date']); ?>">
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

    <!-- JavaScript for Automatic Filtering and Deletion -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('filter-form');
            const courseSelect = document.getElementById('filter-course');
            const courseYearSelect = document.getElementById('filter-course-year');
            const dateSelect = document.getElementById('filter-date');
            const searchInput = document.getElementById('filter-search');
            const table = document.getElementById('attendance-table');
            const rows = table.querySelectorAll('tbody tr');
            const deleteModal = document.getElementById('deleteModal');
            const errorModal = document.getElementById('errorModal');
            const deleteMessage = document.getElementById('delete-message');
            const errorMessage = document.getElementById('error-message');
            const cancelDelete = document.getElementById('cancel-delete');
            const confirmDelete = document.getElementById('confirm-delete');
            const closeError = document.getElementById('close-error');

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
                    if (course !== 'Course' && rowCourse !== course) {
                        matches = false;
                    }
                    if (courseYear !== 'Course Year' && rowCourseYear !== courseYear) {
                        matches = false;
                    }
                    if (date !== 'Date' && rowDate !== date) {
                        matches = false;
                    }
                    if (search && !rowName.includes(search) && !rowBarcode.includes(search)) {
                        matches = false;
                    }

                    row.style.display = matches ? '' : 'none';
                });

                const params = new URLSearchParams();
                if (course !== 'Course') params.set('course', course);
                if (courseYear !== 'Course Year') params.set('course_year', courseYear);
                if (date !== 'Date') params.set('date', date);
                if (search) params.set('search', search);
                const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
                history.replaceState(null, '', newUrl);
            }

            applyFilters();
            courseSelect.addEventListener('change', applyFilters);
            courseYearSelect.addEventListener('change', applyFilters);
            dateSelect.addEventListener('change', applyFilters);
            searchInput.addEventListener('input', applyFilters);

            table.addEventListener('click', async (e) => {
                if (e.target.classList.contains('delete-attendance')) {
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