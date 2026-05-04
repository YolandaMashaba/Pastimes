<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use absolute path to ensure we load our auth.php
require_once __DIR__ . '/auth.php';
$_nav_user = current_user();
$_nav_is_admin = is_admin();
$_nav_name = $_nav_user['name'] ?? '';
?>
<nav class="navbar">
  <div class="container nav-content">
    <a href="/pastimes-marketplace-v2/index.php" class="logo">Pastimes</a>

    <div class="nav-links">
      <a href="/pastimes-marketplace-v2/index.php">Home</a>
      <a href="/pastimes-marketplace-v2/pages/gallery.php">Gallery</a>

      <?php if (!$_nav_user): ?>
        <!-- Guest -->
        <a href="/pastimes-marketplace-v2/pages/register.php">Register</a>
        <a href="/pastimes-marketplace-v2/pages/login.php" class="nav-cta">Login</a>
        <a href="/pastimes-marketplace-v2/pages/admin-login.php" class="nav-admin-btn">Admin</a>

      <?php elseif ($_nav_is_admin): ?>
        <!-- Admin -->
        <span class="nav-badge nav-badge-admin">Admin</span>
        <a href="/pastimes-marketplace-v2/pages/admin-dashboard.php">Moderation Hub</a>
        <a href="/pastimes-marketplace-v2/pages/logout.php" class="nav-logout">Logout</a>

      <?php else: ?>
        <!-- Regular user -->
        <a href="/pastimes-marketplace-v2/pages/profile.php">My Profile</a>
        <?php if (in_array($_nav_user['role'] ?? '', ['seller', 'both'])): ?>
          <a href="/pastimes-marketplace-v2/pages/dashboard.php">My Hub</a>
        <?php endif; ?>
        <?php if (($_nav_user['verification_status'] ?? '') === 'pending'): ?>
          <span class="nav-badge nav-badge-pending">Pending Approval</span>
        <?php endif; ?>
        <a href="/pastimes-marketplace-v2/pages/logout.php" class="nav-logout">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
