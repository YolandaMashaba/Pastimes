<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use absolute path to ensure we load our auth.php and db.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
$_nav_user = current_user();
$_nav_is_admin = is_admin();
$_nav_name = $_nav_user['name'] ?? '';
?>
<nav class="navbar">
  <div class="container nav-content">
    <a href="/pastimes-marketplace-v2/index.php" class="logo">Pastimes</a>

    <div class="nav-center">
      <a href="/pastimes-marketplace-v2/pages/gallery.php" class="nav-link">Shop All</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php?category=tops" class="nav-link">Tops</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php?category=bottoms" class="nav-link">Bottoms</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php?category=dresses" class="nav-link">Dresses</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php?category=outerwear" class="nav-link">Outerwear</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php?category=more" class="nav-link">More</a>
    </div>

    <div class="nav-icons">
      <?php if ($_nav_is_admin): ?>
        <a href="/pastimes-marketplace-v2/pages/admin-dashboard.php" class="nav-icon" title="Admin Dashboard">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
            <line x1="3" x2="21" y1="9" y2="9"></line>
            <line x1="9" x2="9" y1="21" y2="9"></line>
          </svg>
        </a>
      <?php endif; ?>
      <?php if ($_nav_user && in_array($_nav_user['role'] ?? '', ['seller', 'both'])): ?>
        <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=seller" class="nav-icon" title="Seller Hub">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3v18h18"></path>
            <path d="M18.7 8l-5.1 5.2a2.1 2.1 0 0 1-3 0L9 12"></path>
            <path d="M14.5 8.5l-2.9 2.9"></path>
          </svg>
        </a>
      <?php endif; ?>
      <?php if ($_nav_user): ?>
        <?php
        $unread_count = 0;
        try {
          $user_id = $_nav_user['user_id'] ?? 0;
          if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblmessages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            $unread_count = $stmt->fetchColumn();
          }
        } catch (Exception $e) {
          $unread_count = 0;
        }
        ?>
        <a href="/pastimes-marketplace-v2/pages/messages.php" class="nav-icon" title="Messages">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <?php if ($unread_count > 0): ?>
            <span class="cart-count"><?php echo $unread_count; ?></span>
          <?php endif; ?>
        </a>
        <?php
        $notification_count = 0;
        try {
          $user_id = $_nav_user['user_id'] ?? 0;
          if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblnotifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            $notification_count = $stmt->fetchColumn();
          }
        } catch (PDOException $e) {
          // Table doesn't exist yet, notification count will be 0
          $notification_count = 0;
        }
        ?>
        <a href="/pastimes-marketplace-v2/pages/notifications.php" class="nav-icon" title="Notifications">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          <?php if ($notification_count > 0): ?>
            <span class="cart-count"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <a href="/pastimes-marketplace-v2/pages/dashboard.php" class="nav-icon nav-cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
          <path d="M3 6h18"></path>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <?php if ($_nav_user): ?>
          <?php
          $cart_count = 0;
          if (in_array($_nav_user['role'] ?? '', ['buyer', 'both'])) {
            try {
              $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblcart WHERE user_id = ?");
              $stmt->execute([$_nav_user['user_id']]);
              $cart_count = $stmt->fetchColumn();
            } catch (Exception $e) {
              $cart_count = 0;
            }
          }
          ?>
          <?php if ($cart_count > 0): ?>
            <span class="cart-count"><?php echo $cart_count; ?></span>
          <?php endif; ?>
        <?php endif; ?>
      </a>
      <?php if ($_nav_user): ?>
        <a href="/pastimes-marketplace-v2/pages/profile.php" class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </a>
      <?php else: ?>
        <a href="/pastimes-marketplace-v2/pages/login.php" class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
