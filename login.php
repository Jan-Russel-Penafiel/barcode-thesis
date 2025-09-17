<?php
// login.php
session_start();

// Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit();
}

require_once 'data_helper.php';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        // Load data from data.json
        $data = load_data();
        $users = $data['users'] ?? [];

        // Check user credentials
        $user_found = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $user_found = true;
                // Debug: Check if password hash is valid
                if (strlen($user['password']) < 60) {
                    $error = "Password hash is corrupted. Please contact administrator.";
                    break;
                }
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
                break;
            }
        }
        
        if (!$user_found) {
            $error = "Username not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Attendance</title>
   <link href="tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: url('scmcstiii.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #2d3436;
        }
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: -10%;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Barcode Attendance</h1>
            <div class="space-x-4">
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="login-container">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Barcode Attendance</h2>
            <?php if ($error): ?>
                <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php">
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
                    Login
                </button>
            </form>
            <div class="mt-4 text-center text-sm text-gray-600">
                <p>Having login issues? <a href="debug_login.php" class="text-blue-500 hover:underline">Debug Login</a> | <a href="fix_admin_password.php" class="text-blue-500 hover:underline">Fix Admin Password</a></p>
            </div>
        </div>
    </div>
</body>
</html>