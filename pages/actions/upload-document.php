<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

// Only sellers can upload documents
if (!in_array($user['role'] ?? '', ['seller', 'both'])) {
    set_flash('error', 'Only sellers can upload verification documents.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/verification.php');
    exit;
}

$document_type = $_POST['document_type'] ?? '';
$file = $_FILES['document'] ?? null;

// Validate inputs
if (empty($document_type) || empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
    set_flash('error', 'Please select a document type and upload a file.');
    header('Location: /pastimes-marketplace-v2/pages/verification.php');
    exit;
}

// Validate file type
$allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    set_flash('error', 'Invalid file type. Please upload PDF, JPG, or PNG files only.');
    header('Location: /pastimes-marketplace-v2/pages/verification.php');
    exit;
}

// Validate file size (5MB max)
$max_size = 5 * 1024 * 1024;
if ($file['size'] > $max_size) {
    set_flash('error', 'File size exceeds 5MB limit.');
    header('Location: /pastimes-marketplace-v2/pages/verification.php');
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = '../../uploads/verification/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'doc_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    set_flash('error', 'Failed to upload file. Please try again.');
    header('Location: /pastimes-marketplace-v2/pages/verification.php');
    exit;
}

// Save to database
$stmt = $pdo->prepare("
    INSERT INTO tblverification_documents 
    (user_id, document_type, document_path, document_name, status)
    VALUES (?, ?, ?, ?, 'pending')
");
$stmt->execute([
    $user_id,
    $document_type,
    'uploads/verification/' . $filename,
    $file['name']
]);

set_flash('success', 'Document uploaded successfully. It will be reviewed by an admin.');
header('Location: /pastimes-marketplace-v2/pages/verification.php');
exit;
