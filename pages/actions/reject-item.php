<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['item_id'])) {
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$item_id = (int) $_POST['item_id'];

$stmt = $pdo->prepare(
    "UPDATE tblclothes SET status = 'flagged' WHERE clothes_id = ?"
);
$stmt->execute([$item_id]);

if ($stmt->rowCount() > 0) {
    set_flash('error', 'Item has been rejected.');
} else {
    set_flash('error', 'Could not reject item. Please try again.');
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
