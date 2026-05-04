<?php
/**
 * One-time admin seeder.
 * Visit this URL once after importing database.sql, then DELETE this file.
 * http://localhost/pastimes-marketplace-v2/setup/create-admin.php
 */
require_once '../includes/db.php';

$name     = 'Admin';
$email    = 'admin@pastimes.com';
$password = 'admin12345';

// Prevent duplicate seeding
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    echo '<p style="font-family:sans-serif;color:#b91c1c;">Admin already exists. Delete this file.</p>';
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password, role, is_verified_seller) VALUES (?, ?, ?, 'admin', 1)"
);
$stmt->execute([$name, $email, $hash]);

echo '<p style="font-family:sans-serif;color:#065f46;">
    Admin created.<br>
    Email: <strong>' . htmlspecialchars($email) . '</strong><br>
    Password: <strong>' . htmlspecialchars($password) . '</strong><br><br>
    <strong> Delete this file now!</strong>
</p>';
