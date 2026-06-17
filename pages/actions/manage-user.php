<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$cellphone = trim($_POST['cellphone'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');
$verification_status = trim($_POST['verification_status'] ?? '');

if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($cellphone) || empty($role) || empty($verification_status)) {
    set_flash('error', 'Please fill in all required fields.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

try {
    if ($user_id > 0) {
        // Update existing user
        $stmt = $pdo->prepare("
            UPDATE tbluser 
            SET first_name = ?, last_name = ?, username = ?, email = ?, cellphone = ?, role = ?, verification_status = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$first_name, $last_name, $username, $email, $cellphone, $role, $verification_status, $user_id]);
        set_flash('success', 'User updated successfully.');
    } else {
        // Add new user
        if (empty($password)) {
            set_flash('error', 'Password is required for new users.');
            header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
            exit;
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            set_flash('error', 'Username or email already exists.');
            header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
            exit;
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO tbluser (first_name, last_name, username, email, cellphone, password, role, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$first_name, $last_name, $username, $email, $cellphone, $hashed_password, $role, $verification_status]);
        set_flash('success', 'User added successfully.');
    }
} catch (Exception $e) {
    set_flash('error', 'Failed to save user: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
