<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['user_id'])) {
    set_flash('error', 'Invalid request.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$user_id = (int) $_POST['user_id'];

try {
    $stmt = $pdo->prepare("UPDATE tbluser SET verification_status = 'verified' WHERE user_id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Seller account approved successfully.');
    } else {
        set_flash('error', 'Could not approve seller. Please try again.');
    }
} catch (Exception $e) {
    set_flash('error', 'Failed to approve seller: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
