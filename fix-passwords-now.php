<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo "<h1>Fix User Passwords Now</h1>";

echo "<h2>Current User Passwords:</h2>";
try {
    $stmt = $pdo->query("SELECT user_id, username, password FROM tbluser");
    $users = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Password Status</th><th>Action</th></tr>";
    
    foreach ($users as $user) {
        $isHashed = password_get_info($user['password'])['algo'] !== null;
        $status = $isHashed ? 'Hashed ✓' : 'Plain Text ✗';
        $color = $isHashed ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td style='color:$color'>$status</td>";
        echo "<td><button onclick='fixPassword({$user['user_id']})'>Fix</button></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Fix All Passwords:</h2>";
echo "<button onclick='fixAllPasswords()'>Fix All Plain Text Passwords</button>";

echo "<script>
function fixPassword(userId) {
    if (confirm('Fix password for user ID ' + userId + '?')) {
        window.location.href = '?fix=' + userId;
    }
}

function fixAllPasswords() {
    if (confirm('Fix all plain text passwords?')) {
        window.location.href = '?fix=all';
    }
}
</script>";

// Handle password fixing
if (isset($_GET['fix'])) {
    $fixId = $_GET['fix'];
    
    // Default passwords for users
    $defaultPasswords = [
        1 => 'password123',
        2 => 'password123', 
        3 => 'password123',
        4 => 'password123',
        5 => 'password123',
        6 => 'password123'
    ];
    
    if ($fixId === 'all') {
        // Fix all users
        $stmt = $pdo->query("SELECT user_id FROM tbluser");
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            $userId = $user['user_id'];
            $plainPassword = $defaultPasswords[$userId] ?? 'password123';
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $userId]);
        }
        
        echo "<p style='color: green;'>✓ All user passwords have been fixed with bcrypt hashes!</p>";
        
    } else {
        // Fix specific user
        $plainPassword = $defaultPasswords[$fixId] ?? 'password123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
        $updateStmt->execute([$hashedPassword, $fixId]);
        
        echo "<p style='color: green;'>✓ User $fixId password has been fixed!</p>";
    }
    
    echo "<p><a href='fix-passwords-now.php'>Refresh to see changes</a></p>";
}

echo "<h2>Default Passwords:</h2>";
echo "<p>All users have been set to: <strong>password123</strong></p>";
echo "<p>You can change these manually in the database if needed.</p>";
?>
