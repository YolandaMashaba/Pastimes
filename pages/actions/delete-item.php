<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('seller');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['item_id'])) {
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php');
    exit;
}

$item_id = (int) $_POST['item_id'];
$user = current_user();

// Verify that the item belongs to the current user
$stmt = $pdo->prepare("SELECT seller_id, image_path FROM tblclothes WHERE clothes_id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item || $item['seller_id'] != $user['id']) {
    set_flash('error', 'You can only delete your own items.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php');
    exit;
}

// Delete the image file if it exists
if ($item['image_path']) {
    $image_file = dirname(__DIR__, 2) . str_replace('/pastimes-marketplace-v2', '', $item['image_path']);
    if (file_exists($image_file)) {
        unlink($image_file);
    }
}

// Delete the item from database
$stmt = $pdo->prepare("DELETE FROM tblclothes WHERE clothes_id = ?");
$stmt->execute([$item_id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Item deleted successfully.');
} else {
    set_flash('error', 'Could not delete item. Please try again.');
}

// Redirect back to the referring page or dashboard
$referer = $_SERVER['HTTP_REFERER'] ?? '/pastimes-marketplace-v2/pages/dashboard.php';
header('Location: ' . $referer);
exit;
?>
