<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$user = current_user();
$flash = get_flash();

// Fetch all pending verification documents
$stmt = $pdo->prepare("
    SELECT vd.*, u.first_name, u.last_name, u.email, u.username
    FROM tblverification_documents vd
    JOIN tbluser u ON vd.user_id = u.user_id
    WHERE vd.status = 'pending'
    ORDER BY vd.upload_date DESC
");
$stmt->execute();
$pending_documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Fetch all documents (for history)
$stmt = $pdo->prepare("
    SELECT vd.*, u.first_name, u.last_name, u.email, u.username
    FROM tblverification_documents vd
    JOIN tbluser u ON vd.user_id = u.user_id
    ORDER BY vd.upload_date DESC
");
$stmt->execute();
$all_documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Stats
$pending_count = count(array_filter($all_documents, fn($d) => $d['status'] === 'pending'));
$approved_count = count(array_filter($all_documents, fn($d) => $d['status'] === 'approved'));
$rejected_count = count(array_filter($all_documents, fn($d) => $d['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Verification Management | Pastimes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Verification Management</h1>
        <p class="page-subtitle">Review and approve seller verification documents</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $pending_count; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $approved_count; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $rejected_count; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($all_documents); ?></div>
            <div class="stat-label">Total Documents</div>
        </div>
    </div>

    <!-- Pending Documents Section -->
    <?php if ($pending_count > 0): ?>
    <div class="admin-section">
        <h2><i class="fas fa-clock"></i> Pending Documents</h2>
        <div class="documents-grid">
            <?php foreach ($pending_documents as $doc): ?>
            <div class="document-card admin-document-card">
                <div class="document-header">
                    <div class="document-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="document-user">
                        <h4><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></h4>
                        <p><?php echo htmlspecialchars($doc['email']); ?></p>
                    </div>
                </div>
                <div class="document-details">
                    <p><strong>Document Type:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($doc['document_type']))); ?></p>
                    <p><strong>File Name:</strong> <?php echo htmlspecialchars($doc['document_name']); ?></p>
                    <p><strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($doc['upload_date'])); ?></p>
                </div>
                <div class="document-actions">
                    <a href="<?php echo htmlspecialchars($doc['document_path']); ?>" target="_blank" class="btn btn-outline">
                        <i class="fas fa-eye"></i> View Document
                    </a>
                    <form method="POST" action="actions/approve-document.php" style="display:inline;" class="approve-doc-form">
                        <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $doc['user_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?php echo $doc['document_id']; ?>, <?php echo $doc['user_id']; ?>)">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>No pending documents to review.</p>
    </div>
    <?php endif; ?>

    <!-- All Documents History -->
    <div class="admin-section">
        <h2><i class="fas fa-history"></i> Document History</h2>
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_documents as $doc): ?>
                    <tr>
                        <td><?php echo $doc['document_id']; ?></td>
                        <td><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($doc['document_type']))); ?></td>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($doc['upload_date'])); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($doc['status']); ?>"><?php echo ucfirst(htmlspecialchars($doc['status'])); ?></span></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($doc['document_path']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Document Modal -->
<div id="rejectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeRejectModal()">&times;</span>
        <h3>Reject Document</h3>
        <form method="POST" action="actions/reject-document.php">
            <input type="hidden" name="document_id" id="reject_document_id">
            <input type="hidden" name="user_id" id="reject_user_id">
            <div class="form-group">
                <label for="rejection_reason">Rejection Reason</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Please explain why this document is being rejected..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Reject Document</button>
            <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openRejectModal(documentId, userId) {
    document.getElementById('reject_document_id').value = documentId;
    document.getElementById('reject_user_id').value = userId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<script src="/pastimes-marketplace-v2/assets/js/custom-alert.js"></script>
<script>
// Handle approve document
document.querySelectorAll('.approve-doc-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirm('Approve this document?', 'Approve Document');
    if (confirmed) {
      form.submit();
    }
  });
});
</script>

</body>
</html>
