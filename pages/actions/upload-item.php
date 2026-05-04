<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('seller');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php');
    exit;
}

$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$price       = (float) ($_POST['price']   ?? 0);
$user        = current_user();
$seller_id   = (int) $user['user_id'];
$image_path  = null;

if ($title === '' || $price <= 0) {
    set_flash('error', 'Please provide a title and a valid price.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php');
    exit;
}

// Handle optional image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo         = new finfo(FILEINFO_MIME_TYPE);
    $mime          = $finfo->file($_FILES['image']['tmp_name']);

    if (in_array($mime, $allowed_types, true)) {
        $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('item_', true) . '.' . strtolower($ext);
        $dest_dir = dirname(__DIR__, 2) . '/uploads/';
        $dest     = $dest_dir . $filename;

        // Ensure upload directory exists with proper permissions
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }

        // Debug: Check if file was uploaded successfully
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $image_path = '/pastimes-marketplace-v2/uploads/' . $filename;
            // Ensure file is readable
            chmod($dest, 0644);
            // Debug: Log successful upload
            error_log('Image uploaded successfully: ' . $dest);
        } else {
            error_log('Failed to move uploaded file. Temp: ' . $_FILES['image']['tmp_name'] . ', Dest: ' . $dest);
        }
    } else {
        error_log('Invalid file type: ' . $mime);
    }
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    error_log('Upload error: ' . $_FILES['image']['error']);
}

$stmt = $pdo->prepare(
    "INSERT INTO tblclothes (seller_id, title, description, price, image_path, status)
     VALUES (?, ?, ?, ?, ?, 'active')"
);
$stmt->execute([$seller_id, $title, $description, $price, $image_path]);

set_flash('success', 'Item uploaded successfully and is now live in the gallery.');
header('Location: /pastimes-marketplace-v2/pages/dashboard.php');
exit;
