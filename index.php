<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Use absolute path to ensure we load our auth.php
require_once __DIR__ . '/includes/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes — Preloved Fashion Marketplace</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<?php if ($user): ?>
<div class="container">
  <div class="login-notice">
    <span><i class="fas fa-hand-wave"></i> Welcome back, <strong><?php echo htmlspecialchars($user['name']); ?></strong>!</span>
    <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-sm btn-primary">Browse Gallery →</a>
  </div>
</div>
<?php endif; ?>

<section class="hero">
  <div class="hero-content">
    <h1>Buy &amp; Sell Preloved Fashion</h1>
    <p>Minimalist sustainable fashion marketplace.</p>
    <?php if (!$user): ?>
    <div class="hero-actions">
      <a href="pages/register.php" class="btn btn-primary">Get Started</a>
      <a href="pages/login.php" class="btn btn-outline">Login</a>
    </div>
    <?php else: ?>
    <div class="hero-actions">
      <a href="pages/gallery.php" class="btn btn-primary">Browse Gallery</a>
      <?php if (is_admin()): ?>
      <a href="pages/admin-dashboard.php" class="btn btn-outline">Moderation Hub</a>
      <?php else: ?>
      <a href="pages/profile.php" class="btn btn-outline">My Profile</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Features Section -->
<section class="features">
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon"><i class="fas fa-shopping-bag"></i></div>
      <h3>Buy Sustainable</h3>
      <p>Discover preloved fashion treasures while supporting sustainable shopping practices and reducing fashion waste.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
      <h3>Sell Easily</h3>
      <p>Turn your closet into cash with our simple listing process. Upload items in minutes and reach buyers nationwide.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="fas fa-handshake"></i></div>
      <h3>Trusted Community</h3>
      <p>Join a verified community of fashion enthusiasts. All sellers and items are reviewed for quality and authenticity.</p>
    </div>
  </div>
</section>

</body>
</html>
