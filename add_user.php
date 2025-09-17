<?php
// add_user.php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'data_helper.php';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Basic validation
        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            // Load data from data.json
            $data = load_data();
            $users = $data['users'] ?? [];

            // Check if username already exists
            foreach ($users as $user) {
                if ($user['username'] === $username) {
                    $error = "Username already exists.";
                    break;
                }
            }

            if (!$error) {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Add new user
                $data['users'][] = [
                    "username" => $username,
                    "password" => $hashed_password
                ];

                // Save updated data
                save_data($data);
                $success = "User '$username' added successfully.";
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
    <title>Add User - Barcode Attendance</title>
   <link href="tailwind.min.css" rel="stylesheet">
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
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Add New User</h2>
            <?php if ($error): ?>
                <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" action="add_user.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 mb-2">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600"
                >
                    Add User
                </button>
            </form>
        </div>
    </div>
</body>
</html>