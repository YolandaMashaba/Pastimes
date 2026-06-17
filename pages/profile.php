<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$user = current_user();
$flash = get_flash();

// Fetch user's listings if they're a seller
$user_listings = [];
if (in_array($user['role'], ['seller', 'both'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM tblclothes WHERE seller_id = ? ORDER BY created_at DESC"
    );
    $stmt->execute([$user['user_id']]);
    $user_listings = $stmt->fetchAll();
}

// Fetch buyer orders
$buyer_orders = [];
if (in_array($user['role'], ['buyer', 'both'])) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.order_item_id) as item_count
        FROM tblorder o
        LEFT JOIN tblorder_items oi ON o.order_id = oi.order_id
        WHERE o.buyer_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user['user_id']]);
    $buyer_orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
}

// Fetch user data with created_at field
$user_id = $user['user_id'] ?? 0;
if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbluser WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    if ($user_data) {
        $user = array_merge($user, $user_data);
    }
}

// Initialize variables
$user_listings = $user_listings ?? [];
$user_id = $user['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">

  <!-- Profile Header -->
  <div class="profile-header">
    <div class="profile-avatar">
      <div class="avatar-circle">
        <i class="fas fa-user"></i>
      </div>
    </div>
    <div class="profile-info">
      <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
      <p class="profile-subtitle"><i class="fas fa-at"></i> <?php echo htmlspecialchars($user['username']); ?></p>
      <div class="profile-badges">
        <span class="badge badge-<?php echo htmlspecialchars($user['role']); ?>">
          <i class="fas fa-<?php echo $user['role'] === 'buyer' ? 'shopping-cart' : ($user['role'] === 'seller' ? 'store' : 'user-tag'); ?>"></i>
          <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
        </span>
        <span class="badge badge-<?php echo htmlspecialchars($user['verification_status']); ?>">
          <i class="fas fa-<?php echo $user['verification_status'] === 'verified' ? 'check-circle' : 'clock'; ?>"></i>
          <?php echo ucfirst(htmlspecialchars($user['verification_status'])); ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
    <?php echo htmlspecialchars($flash['message']); ?>
  </div>
  <?php endif; ?>

  <!-- Profile Details -->
  <div class="profile-sections">
    <div class="profile-section">
      <h2>Account Information</h2>
      <div class="info-grid">
        <div class="info-item">
          <label><i class="fas fa-user"></i> Full Name</label>
          <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        </div>
        <div class="info-item">
          <label><i class="fas fa-id-badge"></i> Username</label>
          <p><?php echo htmlspecialchars($user['username']); ?></p>
        </div>
        <div class="info-item">
          <label><i class="fas fa-envelope"></i> Email Address</label>
          <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="info-item">
          <label><i class="fas fa-phone"></i> Phone Number</label>
          <p><?php echo htmlspecialchars($user['cellphone'] ?? 'Not provided'); ?></p>
        </div>
        <div class="info-item">
          <label><i class="fas fa-user-tag"></i> Account Type</label>
          <p><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
        </div>
        <div class="info-item">
          <label><i class="fas fa-calendar"></i> Member Since</label>
          <p><?php echo date('F j, Y', strtotime($user['created_at'] ?? 'now')); ?></p>
        </div>
      </div>
    </div>

    <?php if (!empty($user_listings)): ?>
    <div class="profile-section">
      <h2>My Listings</h2>
      <div class="listings-grid">
        <?php foreach ($user_listings as $item): ?>
        <div class="listing-card">
          <?php if ($item['image_path']): ?>
            <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                 class="listing-img">
          <?php else: ?>
            <div class="listing-img-placeholder">No Image</div>
          <?php endif; ?>

          <div class="listing-info">
            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
            <p class="listing-price">R <?php echo number_format((float)$item['price'], 2); ?></p>
            <span class="badge badge-<?php echo htmlspecialchars($item['status']); ?>">
              <?php echo ucfirst($item['status']); ?>
            </span>
            
            <!-- Delete Button -->
            <div class="listing-actions">
              <form method="POST" action="actions/delete-item.php" class="delete-item-form">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($buyer_orders)): ?>
    <div class="profile-section">
      <h2>My Orders</h2>
      <div class="orders-list">
        <?php foreach ($buyer_orders as $order): ?>
        <div class="order-card">
          <div class="order-header">
            <h4>Order #<?php echo (int)($order['order_id'] ?? 0); ?></h4>
            <span class="badge badge-<?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?>">
              <?php echo ucfirst(htmlspecialchars($order['order_status'] ?? 'pending')); ?>
            </span>
          </div>
          <div class="order-details">
            <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'] ?? 'now')); ?></p>
            <p><strong>Total:</strong> R<?php echo number_format((float)($order['total_amount'] ?? 0), 2); ?></p>
            <p><strong>Items:</strong> <?php echo (int)($order['item_count'] ?? 0); ?></p>
            <?php if (!empty($order['tracking_number'])): ?>
            <p><strong>Tracking:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="profile-section">
      <h2>Account Actions</h2>
      <div class="action-buttons">
        <?php if (in_array($user['role'], ['seller', 'both'])): ?>
          <a href="/pastimes-marketplace-v2/pages/dashboard.php" class="btn btn-primary"><i class="fas fa-store"></i> Go to Seller Hub</a>
        <?php endif; ?>
        <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-outline"><i class="fas fa-images"></i> Browse Gallery</a>
        <a href="/pastimes-marketplace-v2/pages/logout.php" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>

</div>

<script src="/pastimes-marketplace-v2/assets/js/custom-alert.js"></script>
<script>
document.querySelectorAll('.delete-item-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirmDanger('Are you sure you want to delete this item? This action cannot be undone.', 'Delete Item');
    if (confirmed) {
      form.submit();
    }
  });
});
</script>

</body>
</html>
