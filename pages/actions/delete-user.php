<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['user_id'])) {
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$user_id = (int) $_POST['user_id'];

$stmt = $pdo->prepare("DELETE FROM tbluser WHERE user_id = ?");
$stmt->execute([$user_id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'User deleted successfully.');
} else {
    set_flash('error', 'Could not delete user. Please try again.');
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
