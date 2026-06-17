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

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$category = trim($_POST['category'] ?? '');
$seller_id = (int)($_POST['seller_id'] ?? 0);
$image_path = null;

if (empty($title) || $price <= 0 || empty($category) || $seller_id <= 0) {
    set_flash('error', 'Please provide title, price, category, and seller ID.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

// Verify seller exists
$stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_id = ?");
$stmt->execute([$seller_id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Seller not found.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

// Handle optional image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);

    if (in_array($mime, $allowed_types, true)) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('item_', true) . '.' . strtolower($ext);
        $dest_dir = dirname(__DIR__, 2) . '/uploads/';
        $dest = $dest_dir . $filename;

        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $image_path = '/pastimes-marketplace-v2/uploads/' . $filename;
            chmod($dest, 0644);
        }
    }
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO tblclothes (seller_id, title, description, price, category, image_path, status)
         VALUES (?, ?, ?, ?, ?, ?, 'active')"
    );
    $stmt->execute([$seller_id, $title, $description, $price, $category, $image_path]);
    set_flash('success', 'Item added successfully.');
} catch (Exception $e) {
    set_flash('error', 'Failed to add item: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
exit;
