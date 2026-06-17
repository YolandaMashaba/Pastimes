<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=seller');
    exit;
}

// Check if user is already a seller or both
if (!in_array($user['role'] ?? '', ['seller', 'both'])) {
    set_flash('error', 'You need to register as a seller first.');
    header('Location: /pastimes-marketplace-v2/pages/profile.php');
    exit;
}

// Check if already verified
if (($user['verification_status'] ?? '') === 'verified') {
    set_flash('success', 'You are already approved to sell.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=seller');
    exit;
}

// Update verification status to pending
try {
    $stmt = $pdo->prepare("UPDATE tbluser SET verification_status = 'pending' WHERE user_id = ?");
    $stmt->execute([$user_id]);
    set_flash('success', 'Your request to sell has been submitted. Wait for admin approval.');
} catch (Exception $e) {
    set_flash('error', 'Failed to submit request: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=seller');
exit;
