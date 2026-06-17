<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo "<h1>Fix Admin Passwords</h1>";

echo "<h2>Current Admin Passwords:</h2>";
try {
    $stmt = $pdo->query("SELECT admin_id, username, password FROM tbladmin");
    $admins = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Admin ID</th><th>Username</th><th>Password Status</th><th>Action</th></tr>";
    
    foreach ($admins as $admin) {
        $isHashed = password_get_info($admin['password'])['algo'] !== null;
        $status = $isHashed ? 'Hashed ✓' : 'Plain Text ✗';
        $color = $isHashed ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$admin['admin_id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td style='color:$color'>$status</td>";
        echo "<td><button onclick='fixPassword({$admin['admin_id']})'>Fix</button></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Fix All Admin Passwords:</h2>";
echo "<button onclick='fixAllPasswords()'>Fix All Plain Text Passwords</button>";

echo "<script src='/pastimes-marketplace-v2/assets/js/custom-alert.js'></script>";
echo "<script>
async function fixPassword(adminId) {
    const confirmed = await customConfirm('Fix password for admin ID ' + adminId + '?', 'Fix Password');
    if (confirmed) {
        window.location.href = '?fix=' + adminId;
    }
}

async function fixAllPasswords() {
    const confirmed = await customConfirm('Fix all plain text passwords?', 'Fix All Passwords');
    if (confirmed) {
        window.location.href = '?fix=all';
    }
}
</script>";

// Handle password fixing
if (isset($_GET['fix'])) {
    $fixId = $_GET['fix'];
    
    // Default passwords for admins
    $defaultPasswords = [
        1 => 'admin123',
        2 => 'admin123',
        3 => 'admin123',
        4 => 'admin123',
        5 => 'admin123'
    ];
    
    if ($fixId === 'all') {
        // Fix all admins
        $stmt = $pdo->query("SELECT admin_id FROM tbladmin");
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            $adminId = $admin['admin_id'];
            $plainPassword = $defaultPasswords[$adminId] ?? 'admin123';
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare("UPDATE tbladmin SET password = ? WHERE admin_id = ?");
            $updateStmt->execute([$hashedPassword, $adminId]);
        }
        
        echo "<p style='color: green;'>✓ All admin passwords have been fixed with bcrypt hashes!</p>";
        
    } else {
        // Fix specific admin
        $plainPassword = $defaultPasswords[$fixId] ?? 'admin123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE tbladmin SET password = ? WHERE admin_id = ?");
        $updateStmt->execute([$hashedPassword, $fixId]);
        
        echo "<p style='color: green;'>✓ Admin $fixId password has been fixed!</p>";
    }
    
    echo "<p><a href='fix-admin-passwords.php'>Refresh to see changes</a></p>";
}

echo "<h2>Default Admin Passwords:</h2>";
echo "<p>All admins have been set to: <strong>admin123</strong></p>";
echo "<p>You can change these manually in the database if needed.</p>";
?>
