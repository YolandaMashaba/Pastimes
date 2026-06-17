<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$user_id = (int)($_GET['user_id'] ?? 0);
$flash = get_flash();

if ($user_id === 0) {
    set_flash('error', 'Invalid user ID.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM tbluser WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User not found.');
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

// Fetch user's verification documents
$stmt = $pdo->prepare("
    SELECT * FROM tblverification_documents 
    WHERE user_id = ? 
    ORDER BY upload_date DESC
");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Check if required documents are uploaded
$required_doc_types = ['id_document', 'proof_of_address', 'bank_statement'];
$uploaded_doc_types = array_unique(array_column($documents, 'document_type'));
$missing_required_docs = array_diff($required_doc_types, $uploaded_doc_types);
$has_required_docs = empty($missing_required_docs);

// Count documents by status
$pending_docs = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
$approved_docs = count(array_filter($documents, fn($d) => $d['status'] === 'approved'));
$rejected_docs = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Verification — Pastimes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Review Seller Verification</h1>
        <a href="/pastimes-marketplace-v2/pages/admin-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>

    <!-- User Info -->
    <div class="admin-section">
        <h2>Seller Information</h2>
        <div class="user-info-card">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['cellphone']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
            <p><strong>Verification Status:</strong> <span class="badge badge-<?php echo htmlspecialchars($user['verification_status']); ?>"><?php echo ucfirst(htmlspecialchars($user['verification_status'])); ?></span></p>
        </div>
    </div>

    <!-- Verification Status -->
    <div class="admin-section">
        <h2>Verification Status</h2>
        <div class="verification-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_docs; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $approved_docs; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $rejected_docs; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <?php if (!$has_required_docs): ?>
        <div class="warning-banner">
            <strong>Missing Required Documents:</strong>
            <?php echo implode(', ', array_map(fn($d) => str_replace('_', ' ', ucfirst($d)), $missing_required_docs)); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Documents List -->
    <div class="admin-section">
        <h2>Uploaded Documents</h2>
        
        <?php if (empty($documents)): ?>
        <div class="empty-state">
            <p>No documents uploaded yet.</p>
        </div>
        <?php else: ?>
        <div class="documents-grid">
            <?php foreach ($documents as $doc): ?>
            <div class="document-card">
                <div class="document-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="document-info">
                    <h4><?php echo htmlspecialchars($doc['document_name']); ?></h4>
                    <p class="document-type">
                        <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($doc['document_type']))); ?>
                    </p>
                    <p class="document-date">
                        Uploaded: <?php echo date('M j, Y g:i A', strtotime($doc['upload_date'])); ?>
                    </p>
                    <span class="badge badge-<?php echo htmlspecialchars($doc['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($doc['status'])); ?>
                    </span>
                    <a href="/pastimes-marketplace-v2/<?php echo htmlspecialchars($doc['document_path']); ?>" target="_blank" class="btn btn-sm btn-outline">View Document</a>
                </div>
                <?php if ($doc['status'] === 'pending'): ?>
                <div class="document-actions">
                    <form method="POST" action="actions/approve-document.php" style="display:inline;">
                        <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="POST" action="actions/reject-document.php" style="display:inline;">
                        <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <input type="text" name="rejection_reason" placeholder="Reason for rejection" required style="width: 200px; padding: 0.3rem; margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Final Approval -->
    <?php if ($has_required_docs && $approved_docs >= 3): ?>
    <div class="admin-section">
        <h2>Final Verification Decision</h2>
        <div class="verification-decision">
            <p>All required documents have been approved. You can now verify this seller.</p>
            <form method="POST" action="actions/approve-seller.php">
                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                <button type="submit" class="btn btn-success">Approve Seller Account</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
