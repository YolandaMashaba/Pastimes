<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['seller_id'])) {
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$seller_id = (int) $_POST['seller_id'];

$stmt = $pdo->prepare("UPDATE tbluser SET verification_status = 'verified', is_verified = 1 WHERE user_id = ?");
$stmt->execute([$seller_id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Seller account approved successfully.');
} else {
    set_flash('error', 'Could not approve seller. Please try again.');
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
