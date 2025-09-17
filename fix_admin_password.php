<?php
// fix_admin_password.php
// This script fixes the admin user's password in data.json

require_once 'data_helper.php';

echo "<h2>Password Fix Utility</h2>";

// Load current data
$data = load_data();
$users = $data['users'] ?? [];

echo "<h3>Current Users:</h3>";
foreach ($users as $index => $user) {
    echo "Username: " . htmlspecialchars($user['username']) . "<br>";
    echo "Password Hash: " . htmlspecialchars($user['password']) . "<br>";
    echo "Hash Length: " . strlen($user['password']) . " characters<br>";
    echo "Is Valid Bcrypt: " . (strlen($user['password']) == 60 && strpos($user['password'], '$2y$10$') === 0 ? 'Yes' : 'No') . "<br><br>";
}

// Fix the admin password if it's truncated
$admin_found = false;
foreach ($users as $index => $user) {
    if ($user['username'] === 'admin') {
        $admin_found = true;
        if (strlen($user['password']) < 60 || $user['password'] === '$2y$10$...') {
            // Generate a new hash for password "admin123"
            $new_password = 'admin123';
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            echo "<h3>Fixing Admin Password:</h3>";
            echo "Old hash: " . htmlspecialchars($user['password']) . "<br>";
            echo "New hash: " . htmlspecialchars($new_hash) . "<br>";
            echo "New password: " . $new_password . "<br>";
            
            // Update the password
            $data['users'][$index]['password'] = $new_hash;
            
            if (save_data($data)) {
                echo "<p style='color: green;'>✅ Admin password fixed successfully!</p>";
                echo "<p><strong>You can now login with:</strong></p>";
                echo "<p>Username: admin</p>";
                echo "<p>Password: admin123</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to save data</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ Admin password hash looks correct</p>";
        }
        break;
    }
}

if (!$admin_found) {
    echo "<h3>Creating New Admin User:</h3>";
    $new_password = 'admin123';
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    $data['users'][] = [
        'username' => 'admin',
        'password' => $new_hash
    ];
    
    if (save_data($data)) {
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        echo "<p><strong>You can now login with:</strong></p>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: admin123</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to save data</p>";
    }
}

echo "<br><a href='login.php'>Go to Login Page</a>";
?>