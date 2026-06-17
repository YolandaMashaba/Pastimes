<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$user = current_user();
$admin_id = $user['admin_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$document_id = (int)($_POST['document_id'] ?? 0);
$user_id = (int)($_POST['user_id'] ?? 0);
$rejection_reason = $_POST['rejection_reason'] ?? '';

if ($document_id === 0 || $user_id === 0 || empty($rejection_reason)) {
    set_flash('error', 'Invalid input. Please provide all required fields.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Update document status (set reviewed_by to NULL to avoid foreign key constraint)
    $stmt = $pdo->prepare("
        UPDATE tblverification_documents
        SET status = 'rejected', reviewed_by = NULL, reviewed_at = NOW(), rejection_reason = ?
        WHERE document_id = ?
    ");
    $stmt->execute([$rejection_reason, $document_id]);

    $pdo->commit();
    set_flash('success', 'Document rejected successfully.');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to reject document: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/admin-verify.php?user_id=' . $user_id);
exit;
