<?php
require_once 'includes/db.php';

// Check if admin exists in tbluser
$stmt = $pdo->query("SELECT * FROM tbluser WHERE role='admin'");
$admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Admin users in tbluser: " . count($admin_users) . "\n";

if (empty($admin_users)) {
    echo "No admin users found in tbluser. Checking tbladmin...\n";
    
    // Check tbladmin table
    $stmt = $pdo->query("SELECT * FROM tbladmin LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Found admin in tbladmin: " . $admin['username'] . "\n";
        echo "Admin ID: " . $admin['admin_id'] . "\n";
        
        // Create corresponding user record (use 'both' role since admin is not in ENUM)
        $stmt = $pdo->prepare("
            INSERT INTO tbluser (first_name, last_name, username, password, email, cellphone, role, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, 'both', 'verified')
        ");
        $stmt->execute([
            $admin['first_name'] ?? 'Admin',
            $admin['last_name'] ?? 'User',
            $admin['username'],
            $admin['password'],
            $admin['email'] ?? 'admin@pastimes.com',
            $admin['cellphone'] ?? '0000000000'
        ]);
        
        $new_user_id = $pdo->lastInsertId();
        echo "Created user record with ID: " . $new_user_id . "\n";
        
        // Update tbladmin to reference the new user_id
        $stmt = $pdo->prepare("UPDATE tbladmin SET user_id = ? WHERE admin_id = ?");
        $stmt->execute([$new_user_id, $admin['admin_id']]);
        
        echo "Updated tbladmin to reference user_id: " . $new_user_id . "\n";
        echo "Admin messaging should now work!\n";
        echo "IMPORTANT: Please log out and log back in as admin to update your session with the new user_id.\n";
    } else {
        echo "No admin found in tbladmin either.\n";
    }
} else {
    echo "Admin users already exist in tbluser:\n";
    foreach ($admin_users as $u) {
        echo "- User ID: " . $u['user_id'] . ", Username: " . $u['username'] . "\n";
    }
}
?>
