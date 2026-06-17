<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$user = current_user();
$flash = get_flash();

// Only sellers and both roles can access verification
if (!in_array($user['role'] ?? '', ['seller', 'both'])) {
    set_flash('error', 'Only sellers can access the verification page.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

$user_id = $user['user_id'] ?? 0;

// Fetch user's verification documents
$stmt = $pdo->prepare("
    SELECT * FROM tblverification_documents 
    WHERE user_id = ? 
    ORDER BY upload_date DESC
");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Check if user is verified
$is_verified = ($user['verification_status'] ?? '') === 'verified';
$verification_status = $user['verification_status'] ?? 'pending';

// Count documents by status
$pending_docs = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
$approved_docs = count(array_filter($documents, fn($d) => $d['status'] === 'approved'));
$rejected_docs = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));

// Check if required documents are uploaded
$required_doc_types = ['id_document', 'proof_of_address', 'bank_statement'];
$uploaded_doc_types = array_unique(array_column($documents, 'document_type'));
$missing_required_docs = array_diff($required_doc_types, $uploaded_doc_types);
$has_required_docs = empty($missing_required_docs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification — Pastimes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="verification-header">
        <h1><i class="fas fa-shield-alt"></i> Seller Verification</h1>
        <p>Upload your documents to verify your seller account. Multi-factor verification helps establish trust with buyers.</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>

    <!-- Verification Status Banner -->
    <div class="verification-status-banner status-<?php echo htmlspecialchars($verification_status); ?>">
        <?php if ($is_verified): ?>
            <div class="status-content">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Your account is verified!</strong>
                    <p>You can now sell items on the platform with full privileges.</p>
                </div>
            </div>
        <?php elseif ($verification_status === 'rejected'): ?>
            <div class="status-content">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>Verification Rejected</strong>
                    <p><?php echo htmlspecialchars($user['rejection_reason'] ?? 'Please contact support for more information.'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-content">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Verification Pending</strong>
                    <p>Your documents are under review. This usually takes 1-2 business days.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Required Documents Status -->
    <?php if (!$is_verified): ?>
    <div class="verification-status-banner status-<?php echo $has_required_docs ? 'success' : 'warning'; ?>">
        <div class="status-content">
            <i class="fas fa-<?php echo $has_required_docs ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <div>
                <strong>Required Documents Status</strong>
                <?php if ($has_required_docs): ?>
                    <p>All required documents have been uploaded and are under review.</p>
                <?php else: ?>
                    <p>Missing required documents: <?php echo implode(', ', array_map(fn($d) => str_replace('_', ' ', ucfirst($d)), $missing_required_docs)); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Verification Stats -->
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

    <!-- Upload Document Form -->
    <?php if (!$is_verified): ?>
    <section class="verification-section">
        <h2 class="section-title"><i class="fas fa-upload"></i> Upload Verification Documents</h2>
        <div class="upload-form-card">
            <form method="POST" action="actions/upload-document.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="document_type">
                        Document Type
                        <span class="field-required">*</span>
                    </label>
                    <select id="document_type" name="document_type" required>
                        <option value="">Select document type...</option>
                        <option value="id_document">ID Document (Passport/ID Card)</option>
                        <option value="proof_of_address">Proof of Address (Utility Bill/Bank Statement)</option>
                        <option value="bank_statement">Bank Statement</option>
                        <option value="business_registration">Business Registration (if applicable)</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document">
                        Document File
                        <span class="field-required">*</span>
                    </label>
                    <input
                        type="file"
                        id="document"
                        name="document"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="file-input"
                        required
                    >
                    <small class="form-hint">Accepted formats: PDF, JPG, PNG. Max file size: 5MB</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <!-- Uploaded Documents -->
    <section class="verification-section">
        <h2 class="section-title"><i class="fas fa-folder-open"></i> My Documents</h2>
        
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
                        Uploaded: <?php echo date('M j, Y', strtotime($doc['upload_date'])); ?>
                    </p>
                    <span class="badge badge-<?php echo htmlspecialchars($doc['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($doc['status'])); ?>
                    </span>
                    <?php if ($doc['status'] === 'rejected' && $doc['rejection_reason']): ?>
                    <p class="rejection-reason">
                        <strong>Reason:</strong> <?php echo htmlspecialchars($doc['rejection_reason']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Verification Requirements -->
    <section class="verification-section">
        <h2 class="section-title"><i class="fas fa-info-circle"></i> Verification Requirements</h2>
        <div class="requirements-list">
            <div class="requirement-item">
                <i class="fas fa-check"></i>
                <div>
                    <strong>ID Document</strong>
                    <p>A clear photo of your passport or national ID card</p>
                </div>
            </div>
            <div class="requirement-item">
                <i class="fas fa-check"></i>
                <div>
                    <strong>Proof of Address</strong>
                    <p>Utility bill or bank statement from the last 3 months</p>
                </div>
            </div>
            <div class="requirement-item">
                <i class="fas fa-check"></i>
                <div>
                    <strong>Bank Statement</strong>
                    <p>Shows your name and account details for payments</p>
                </div>
            </div>
            <div class="requirement-item">
                <i class="fas fa-check"></i>
                <div>
                    <strong>Business Registration (Optional)</strong>
                    <p>If selling as a business, provide registration documents</p>
                </div>
            </div>
        </div>
    </section>
</div>

</body>
</html>
