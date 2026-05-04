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

// Fetch user data with created_at field
$stmt = $pdo->prepare("SELECT * FROM tbluser WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$user_data = $stmt->fetch();
if ($user_data) {
    $user = array_merge($user, $user_data);
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
          <p><?php echo htmlspecialchars($user['cellphone']); ?></p>
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
              <form method="POST" action="actions/delete-item.php" 
                    onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
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

    <div class="profile-section">
      <h2>Account Actions</h2>
      <div class="action-buttons">
        <?php if (in_array($user['role'], ['seller', 'both'])): ?>
          <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-store"></i> Go to Seller Hub</a>
        <?php endif; ?>
        <a href="gallery.php" class="btn btn-outline"><i class="fas fa-images"></i> Browse Gallery</a>
        <a href="logout.php" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>

</div>

</body>
</html>
