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

if ($document_id === 0 || $user_id === 0) {
    set_flash('error', 'Invalid document or user ID. Document ID: ' . $document_id . ', User ID: ' . $user_id);
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Update document status (set reviewed_by to NULL to avoid foreign key constraint)
    $stmt = $pdo->prepare("
        UPDATE tblverification_documents
        SET status = 'approved', reviewed_by = NULL, reviewed_at = NOW()
        WHERE document_id = ?
    ");
    $stmt->execute([$document_id]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        set_flash('error', 'Document not found or already processed.');
        header('Location: /pastimes-marketplace-v2/pages/admin-verify.php?user_id=' . $user_id);
        exit;
    }

    $pdo->commit();
    set_flash('success', 'Document approved successfully.');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to approve document: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/admin-verify.php?user_id=' . $user_id);
exit;
