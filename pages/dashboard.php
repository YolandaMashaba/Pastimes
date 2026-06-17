<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/auth.php';

require_login();

$user = current_user();

$flash = get_flash();

// Safe defaults to prevent undefined array warnings
$user_name = $user['name']
    ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

$user_name = trim($user_name);

$user_id = $user['user_id'] ?? 0;
$user_role = $user['role'] ?? 'buyer';

$is_verified_seller =
    ($user['verification_status'] ?? '') === 'verified';

// Dashboard tabs
$active_tab = $_GET['tab'] ?? ($user_role === 'buyer' ? 'buyer' : 'seller');

// Fetch seller listings if user is seller or both
$seller_listings = [];
if (in_array($user_role, ['seller', 'both'])) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tblclothes
        WHERE seller_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $seller_listings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
}

// Fetch buyer orders if user is buyer or both
$buyer_orders = [];
if (in_array($user_role, ['buyer', 'both'])) {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.order_item_id) as item_count
        FROM tblorder o
        LEFT JOIN tblorder_items oi ON o.order_id = oi.order_id
        WHERE o.buyer_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $buyer_orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
}

// Fetch buyer cart items
$cart_items = [];
if (in_array($user_role, ['buyer', 'both'])) {
    $stmt = $pdo->prepare("
        SELECT c.*, cl.title, cl.price, cl.image_path
        FROM tblcart c
        JOIN tblclothes cl ON c.clothes_id = cl.clothes_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
}

// Seller Stats
$totalListings = count($seller_listings);
$activeListings = count(array_filter($seller_listings, fn($l) => ($l['status'] ?? '') === 'active'));
$soldListings = count(array_filter($seller_listings, fn($l) => ($l['status'] ?? '') === 'sold'));

// Buyer Stats
$totalOrders = count($buyer_orders);
$pendingOrders = count(array_filter($buyer_orders, fn($o) => ($o['order_status'] ?? '') === 'pending'));
$completedOrders = count(array_filter($buyer_orders, fn($o) => ($o['order_status'] ?? '') === 'delivered'));
$cartCount = count($cart_items);
$cartTotal = array_sum(array_map(fn($c) => ($c['price'] ?? 0) * ($c['quantity'] ?? 1), $cart_items));
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard — Pastimes</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

</head>

<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">

    <!-- DASHBOARD WELCOME -->

    <div class="seller-welcome">

        <h1><?php echo $active_tab === 'seller' ? 'Seller Hub' : 'Cart'; ?></h1>

        <p>
            Welcome back,
            <?php echo htmlspecialchars($user_name); ?>!
            <?php if ($user_role === 'both'): ?>
            Manage your buying and selling activities in one place.
            <?php elseif ($user_role === 'seller'): ?>
            Manage your listings and grow your business.
            <?php else: ?>
            Browse items and manage your orders.
            <?php endif; ?>
        </p>

    </div>

    <!-- DASHBOARD TABS -->

    <?php if ($user_role === 'both'): ?>
    <div class="dashboard-tabs">
        <a href="?tab=buyer" class="tab-btn <?php echo $active_tab === 'buyer' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Buyer Dashboard
        </a>
        <a href="?tab=seller" class="tab-btn <?php echo $active_tab === 'seller' ? 'active' : ''; ?>">
            <i class="fas fa-store"></i> Seller Dashboard
        </a>
    </div>
    <?php endif; ?>

    <!-- BUYER DASHBOARD -->

    <?php if ($active_tab === 'buyer' && in_array($user_role, ['buyer', 'both'])): ?>

    <div class="seller-stats">

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $cartCount; ?>
            </div>

            <div class="seller-stat-label">
                Cart Items
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                R<?php echo number_format($cartTotal, 2); ?>
            </div>

            <div class="seller-stat-label">
                Cart Total
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $totalOrders; ?>
            </div>

            <div class="seller-stat-label">
                Total Orders
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $completedOrders; ?>
            </div>

            <div class="seller-stat-label">
                Completed
            </div>

        </div>

    </div>

    <!-- SHOPPING CART -->

    <section class="dashboard-section">

        <h2 class="section-title">
            Shopping Cart
            <span class="badge-count"><?php echo $cartCount; ?></span>
        </h2>

        <?php if (empty($cart_items)): ?>

        <div class="empty-state">

            <p>
                Your cart is empty.
                <br>
                <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-primary">Browse Items</a>
            </p>

        </div>

        <?php else: ?>

        <div class="cart-grid">

            <?php foreach ($cart_items as $item): ?>

            <div class="cart-item">

                <?php if (!empty($item['image_path'])): ?>

                <img
                    src="<?php echo htmlspecialchars($item['image_path']); ?>"
                    alt="<?php echo htmlspecialchars($item['title'] ?? 'Item'); ?>"
                    class="cart-item-img"
                >

                <?php else: ?>

                <div class="cart-item-img-placeholder">

                    No Image

                </div>

                <?php endif; ?>

                <div class="cart-item-info">

                    <h4><?php echo htmlspecialchars($item['title'] ?? 'Untitled Item'); ?></h4>

                    <p class="cart-item-price">

                        R<?php echo number_format((float)($item['price'] ?? 0), 2); ?>

                    </p>

                    <p class="cart-item-quantity">Quantity: <?php echo (int)($item['quantity'] ?? 1); ?></p>

                    <div class="cart-item-actions">

                        <form method="POST" action="actions/update-cart.php" style="display:inline;">

                            <input type="hidden" name="cart_id" value="<?php echo (int)($item['cart_id'] ?? 0); ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="number" name="quantity" value="<?php echo (int)($item['quantity'] ?? 1); ?>" min="1" max="10" class="quantity-input" style="width: 60px; padding: 0.3rem; margin-right: 0.5rem;">

                            <button type="submit" class="btn btn-sm btn-primary">Update</button>

                        </form>

                        <form method="POST" action="actions/update-cart.php" style="display:inline;">

                            <input type="hidden" name="cart_id" value="<?php echo (int)($item['cart_id'] ?? 0); ?>">
                            <input type="hidden" name="action" value="remove">

                            <button type="submit" class="btn btn-sm btn-danger remove-cart-btn">

                                Remove

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <div class="cart-summary">

            <div class="cart-total">

                <strong>Total: R<?php echo number_format($cartTotal, 2); ?></strong>

            </div>

            <div class="cart-actions">
                <a href="/pastimes-marketplace-v2/pages/orders.php" class="btn btn-secondary">View Orders</a>
                <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-secondary">Continue Shopping</a>
                <a href="/pastimes-marketplace-v2/pages/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>

        </div>

        <?php endif; ?>

    </section>

    <?php endif; ?>

    <!-- SELLER DASHBOARD -->

    <?php if ($active_tab === 'seller' && in_array($user_role, ['seller', 'both'])): ?>

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
                <?php echo $soldListings; ?>
            </div>

            <div class="seller-stat-label">
                Sold Items
            </div>

        </div>

        <div class="seller-stat">

            <div class="seller-stat-number">
                <?php echo $is_verified_seller ? '✓' : '○'; ?>
            </div>

            <div class="seller-stat-label">
                Verified
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
                Verification Required
            </strong>

            <p>
                You need to upload verification documents to sell items. Once approved by the admin, you can start selling.
                <a href="verification.php" class="btn btn-sm btn-primary">Upload Documents</a>
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

        <?php if (empty($seller_listings)): ?>

        <div class="empty-state">

            <p>
                You haven't uploaded any items yet.
                <br>
                Use the form above to get started!
            </p>

        </div>

        <?php else: ?>

        <div class="listings-grid">

            <?php foreach ($seller_listings as $item): ?>

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
                              class="delete-item-form">

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

    <?php endif; ?>

</div>

<script src="/pastimes-marketplace-v2/assets/js/custom-alert.js"></script>
<script>
// Handle remove from cart
document.querySelectorAll('.remove-cart-btn').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirm('Remove this item from cart?', 'Remove Item');
    if (confirmed) {
      this.closest('form').submit();
    }
  });
});

// Handle delete item
document.querySelectorAll('.delete-item-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirmDanger('Are you sure you want to delete this item?', 'Delete Item');
    if (confirmed) {
      form.submit();
    }
  });
});
</script>

</body>
</html>