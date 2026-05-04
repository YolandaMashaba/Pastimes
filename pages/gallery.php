<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Fetch only active listings
$items = $pdo
    ->query("SELECT c.*, u.first_name AS seller_name, u.last_name AS seller_lastname
               FROM tblclothes c
               JOIN tbluser u ON c.seller_id = u.user_id
              WHERE c.status = 'active'
              ORDER BY c.created_at DESC")
    ->fetchAll();

// Initialize items array
$items = $items ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery — Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
  <h1 class="page-title">Marketplace Gallery</h1>

  <?php if (empty($items)): ?>
  <div class="empty-state" style="margin-top:1rem;">
    <p>No items listed yet. Check back soon, or
      <?php if (!is_logged_in()): ?>
        <a href="register.php">become a seller</a>
      <?php else: ?>
        <a href="dashboard.php">upload an item</a>
      <?php endif; ?>!
    </p>
  </div>
  <?php else: ?>
  <div class="gallery-grid">
    <?php foreach ($items as $item): ?>
    <div class="product-card">
      <?php if ($item['image_path']): ?>
        <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
             alt="<?php echo htmlspecialchars($item['title']); ?>">
      <?php else: ?>
        <img src="https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?q=80&w=600"
             alt="<?php echo htmlspecialchars($item['title']); ?>">
      <?php endif; ?>
      <div class="product-card-body">
        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
        <p style="color:var(--color-primary);font-weight:700;margin:.25rem 0;">
          R <?php echo number_format((float)$item['price'], 2); ?>
        </p>
        <p style="font-size:.82rem;color:var(--color-text-muted);margin:.25rem 0 .75rem;">
          by <?php echo htmlspecialchars(ucwords($item['seller_name']) . ' ' . ucwords($item['seller_lastname'])); ?>
        </p>
        <a href="product-view.php?clothes_id=<?php echo (int)$item['clothes_id']; ?>"
           class="btn btn-primary btn-sm">View Item</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</body>
</html>
