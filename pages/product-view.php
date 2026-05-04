<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

$item_id = (int)($_GET['clothes_id'] ?? 0);

if ($item_id <= 0) {
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

// Fetch the approved item with seller info
$stmt = $pdo->prepare(
    "SELECT c.*, u.first_name AS seller_name, u.last_name AS seller_lastname, u.email AS seller_email
       FROM tblclothes c
       JOIN tbluser u ON c.seller_id = u.user_id
      WHERE c.clothes_id = ? AND c.status = 'active'
      LIMIT 1"
);
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

$message_sent = false;
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        // Placeholder: in a real app, store the message in a messages table
        $message_sent = true;
    } else {
        $message_error = 'Please enter a message before sending.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($item['title']); ?> — Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
  <a href="gallery.php" style="display:inline-block;margin-bottom:1.5rem;color:var(--color-text-muted);text-decoration:none;font-size:.9rem;">
    ← Back to Gallery
  </a>

  <div class="product-view-grid">
    <!-- Image -->
    <div>
      <?php if ($item['image_path']): ?>
        <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
             alt="<?php echo htmlspecialchars($item['title']); ?>"
             class="product-view-img">
      <?php else: ?>
        <img src="https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800"
             alt="<?php echo htmlspecialchars($item['title']); ?>"
             class="product-view-img">
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="product-view-details">
      <h1 class="product-view-title"><?php echo htmlspecialchars($item['title']); ?></h1>
      <p class="product-view-price">R <?php echo number_format((float)$item['price'], 2); ?></p>
      <p class="product-view-seller">Listed by <strong><?php echo htmlspecialchars($item['seller_name'] . ' ' . $item['seller_lastname']); ?></strong></p>

      <?php if ($item['description']): ?>
      <p class="product-view-desc"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
      <?php endif; ?>

      <div class="product-view-divider"></div>

      <h3 style="margin:0 0 1rem;font-size:1rem;">Contact Seller</h3>

      <?php if ($message_sent): ?>
        <div class="success">Message sent! The seller will be in touch.</div>
      <?php elseif (!is_logged_in()): ?>
        <div class="warning-banner" style="margin-bottom:0;">
          <span class="warning-icon"></span>
          <div>
            <strong>Want to contact this seller?</strong>
            <p><a href="login.php">Log in</a> or <a href="register.php">register</a> to send a message.</p>
          </div>
        </div>
      <?php else: ?>
        <?php if ($message_error): ?>
        <div class="error"><?php echo htmlspecialchars($message_error); ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="form-group">
            <textarea name="message" rows="4"
                      placeholder="Hi! Is this item still available?"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
