<?php
// debug_login.php
// This script helps debug login issues

require_once 'data_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login Debug Results</h2>";
    echo "<p><strong>Username entered:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password entered:</strong> " . str_repeat('*', strlen($password)) . " (" . strlen($password) . " characters)</p>";
    
    // Load data
    $data = load_data();
    $users = $data['users'] ?? [];
    
    echo "<h3>Available Users:</h3>";
    foreach ($users as $user) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
        echo "<strong>Username:</strong> " . htmlspecialchars($user['username']) . "<br>";
        echo "<strong>Password Hash:</strong> " . htmlspecialchars($user['password']) . "<br>";
        echo "<strong>Hash Length:</strong> " . strlen($user['password']) . " characters<br>";
        
        // Check if this is the user trying to login
        if ($user['username'] === $username) {
            echo "<strong style='color: blue;'>👤 This is the user you're trying to login as</strong><br>";
            
            // Test password verification
            $verify_result = password_verify($password, $user['password']);
            echo "<strong>Password Verification:</strong> " . ($verify_result ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
            
            // Additional debugging
            echo "<strong>Hash Algorithm Info:</strong><br>";
            $info = password_get_info($user['password']);
            echo "- Algorithm: " . $info['algoName'] . "<br>";
            echo "- Options: " . json_encode($info['options']) . "<br>";
            
            // Test with common passwords
            $common_passwords = ['admin', 'admin123', 'password', '123456'];
            echo "<strong>Testing common passwords:</strong><br>";
            foreach ($common_passwords as $test_pass) {
                $test_result = password_verify($test_pass, $user['password']);
                echo "- '$test_pass': " . ($test_result ? '✅ MATCH' : '❌ No match') . "<br>";
            }
        }
        echo "</div>";
    }
    
    // Check if user exists
    $user_found = false;
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $user_found = true;
            break;
        }
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>User exists: " . ($user_found ? '✅ Yes' : '❌ No') . "</p>";
    
    if ($user_found) {
        $stored_user = null;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $stored_user = $user;
                break;
            }
        }
        
        $password_correct = password_verify($password, $stored_user['password']);
        echo "<p>Password correct: " . ($password_correct ? '✅ Yes' : '❌ No') . "</p>";
        
        if ($password_correct) {
            echo "<p style='color: green; font-weight: bold;'>✅ Login should work! If it doesn't, check for other issues like CSRF tokens or session problems.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Password is incorrect. Try using the 'Fix Admin Password' tool.</p>";
        }
    }
    
    echo "<br><a href='debug_login.php'>Test Again</a> | <a href='fix_admin_password.php'>Fix Admin Password</a> | <a href='login.php'>Go to Login</a>";
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #005a8b; }
    </style>
</head>
<body>
    <h1>Login Debug Tool</h1>
    <p>This tool helps debug login issues by showing detailed information about the authentication process.</p>
    
    <form method="POST" action="debug_login.php">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Debug Login</button>
    </form>
    
    <hr style="margin: 30px 0;">
    <p><a href="fix_admin_password.php">Fix Admin Password</a> | <a href="login.php">Go to Login Page</a></p>
</body>
</html>
<?php
}
?>