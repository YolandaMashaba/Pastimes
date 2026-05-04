<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/auth.php';

require_login();
require_role('seller');

$user = current_user();

$flash = get_flash();

// Safe defaults to prevent undefined array warnings
$user_name = $user['name']
    ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

$user_name = trim($user_name);

$user_id = $user['user_id'] ?? 0;

$is_verified_seller =
    ($user['verification_status'] ?? '') === 'verified';

// Fetch seller listings
$stmt = $pdo->prepare("
    SELECT *
    FROM tblclothes
    WHERE seller_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);

$listings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Stats
$totalListings = count($listings);

$activeListings = count(array_filter(
    $listings,
    fn($l) => ($l['status'] ?? '') === 'active'
));

$pendingListings = count(array_filter(
    $listings,
    fn($l) => ($l['status'] ?? '') === 'pending'
));

$rejectedListings = count(array_filter(
    $listings,
    fn($l) => ($l['status'] ?? '') === 'rejected'
));
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Seller Hub — Pastimes</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

</head>

<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">

    <!-- SELLER WELCOME -->

    <div class="seller-welcome">

        <h1>Seller Hub</h1>

        <p>
            Welcome back,
            <?php echo htmlspecialchars($user_name); ?>!
            Manage your listings and grow your business.
        </p>

    </div>

    <!-- SELLER STATS -->

    <div class="seller-stats">

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $totalListings; ?>
            </div>

            <div class="seller-stat-label">
                Total Listings
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $activeListings; ?>
            </div>

            <div class="seller-stat-label">
                Active Items
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $pendingListings; ?>
            </div>

            <div class="seller-stat-label">
                Pending Review
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $rejectedListings; ?>
            </div>

            <div class="seller-stat-label">
                Rejected
            </div>

        </div>

    </div>

    <!-- FLASH MESSAGE -->

    <?php if ($flash): ?>

    <div class="<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?>">

        <?php echo htmlspecialchars($flash['message'] ?? ''); ?>

    </div>

    <?php endif; ?>

    <!-- ACCOUNT VERIFICATION WARNING -->

    <?php if (!$is_verified_seller): ?>

    <div class="warning-banner">

        <div>

            <strong>
                Account Pending Admin Approval
            </strong>

            <p>
                Your seller account is awaiting verification.
                You may upload items now, but they won't be
                publicly visible until both your account
                and items are approved.
            </p>

        </div>

    </div>

    <?php endif; ?>

    <!-- UPLOAD ITEM -->

    <section class="dashboard-section">

        <h2 class="section-title">
            Upload New Item
        </h2>

        <div class="upload-form-card">

            <form method="POST"
                  action="/pastimes-marketplace-v2/pages/actions/upload-item.php"
                  enctype="multipart/form-data">

                <div class="form-row">

                    <div class="form-group">

                        <label for="title">
                            Item Title
                            <span class="field-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            required
                            placeholder="e.g. Vintage Denim Jacket"
                        >

                    </div>

                    <div class="form-group">

                        <label for="price">
                            Price (R)
                            <span class="field-required">*</span>
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            required
                            min="1"
                            step="0.01"
                            placeholder="450.00"
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Describe the condition, size, and style..."
                    ></textarea>

                </div>

                <div class="form-group">

                    <label for="image">
                        Item Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        class="file-input"
                    >

                </div>

                <button type="submit"
                        class="btn btn-primary">

                    Upload Item

                </button>

            </form>

        </div>

    </section>

    <!-- MY LISTINGS -->

    <section class="dashboard-section">

        <div class="section-header-row">

            <h2 class="section-title">
                My Listings
            </h2>

            <span class="badge-count">
                <?php echo $totalListings; ?>
            </span>

        </div>

        <?php if (empty($listings)): ?>

        <div class="empty-state">

            <p>
                You haven't uploaded any items yet.
                <br>
                Use the form above to get started!
            </p>

        </div>

        <?php else: ?>

        <div class="listings-grid">

            <?php foreach ($listings as $item): ?>

            <div class="listing-card">

                <?php if (!empty($item['image_path'])): ?>

                <img
                    src="<?php echo htmlspecialchars($item['image_path']); ?>"
                    alt="<?php echo htmlspecialchars($item['title'] ?? 'Item'); ?>"
                    class="listing-img"
                >

                <?php else: ?>

                <div class="listing-img-placeholder">

                    No Image

                </div>

                <?php endif; ?>

                <div class="listing-info">

                    <h4>
                        <?php echo htmlspecialchars($item['title'] ?? 'Untitled Item'); ?>
                    </h4>

                    <p class="listing-price">

                        R
                        <?php echo number_format(
                            (float)($item['price'] ?? 0),
                            2
                        ); ?>

                    </p>

                    <?php
                    $status = $item['status'] ?? 'pending';
                    ?>

                    <span class="badge badge-<?php echo htmlspecialchars($status); ?>">

                        <?php echo ucfirst($status); ?>

                    </span>

                    <?php if ($status === 'pending'): ?>

                    <p class="status-note">

                        Pending Admin Approval

                    </p>

                    <?php elseif ($status === 'rejected'): ?>

                    <p class="status-note status-note-danger">

                        Rejected by admin.

                    </p>

                    <?php endif; ?>

                    <!-- DELETE BUTTON -->

                    <div class="listing-actions">

                        <form method="POST"
                              action="actions/delete-item.php"
                              onsubmit="return confirm('Are you sure you want to delete this item?');">

                            <input
                                type="hidden"
                                name="item_id"
                                value="<?php echo (int)($item['clothes_id'] ?? 0); ?>"
                            >

                            <button type="submit"
                                    class="btn btn-sm btn-danger">

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </section>

</div>

</body>
</html>