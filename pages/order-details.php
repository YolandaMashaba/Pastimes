<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/login.php';</script>";
    exit;
}

$user = current_user();
$user_id = $user['user_id'] ?? 0;
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id === 0) {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/orders.php';</script>";
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.* 
    FROM tblorder o 
    WHERE o.order_id = ? AND o.buyer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/orders.php';</script>";
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, cl.title, cl.image_path
    FROM tblorder_items oi
    JOIN tblclothes cl ON oi.clothes_id = cl.clothes_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details — Pastimes</title>
    <link rel="stylesheet" href="/pastimes-marketplace-v2/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 2rem auto;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }
        .order-header {
            background: var(--color-bg-secondary);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .order-header h1 {
            margin-bottom: 0.5rem;
        }
        .order-meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .order-meta-item {
            display: flex;
            flex-direction: column;
        }
        .order-meta-label {
            font-size: 0.85rem;
            color: var(--color-text-muted);
            margin-bottom: 0.25rem;
        }
        .order-meta-value {
            font-weight: 600;
        }
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .order-items-section {
            background: var(--color-bg-secondary);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .order-items-section h2 {
            margin-bottom: 1.5rem;
        }
        .order-item {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--color-border);
            align-items: center;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .order-item-img-placeholder {
            width: 100px;
            height: 100px;
            background: var(--color-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted);
        }
        .order-item-details {
            flex: 1;
        }
        .order-item-details h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        .order-item-details p {
            margin: 0.25rem 0;
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
        .order-item-price {
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.2rem;
        }
        .order-summary-section {
            background: var(--color-bg-secondary);
            padding: 2rem;
            border-radius: 8px;
        }
        .order-summary-section h2 {
            margin-bottom: 1.5rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--color-border);
        }
        .summary-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 600;
            padding-top: 1rem;
            border-top: 2px solid var(--color-border);
        }
        .tracking-timeline {
            margin-top: 2rem;
        }
        .tracking-timeline h3 {
            margin-bottom: 1rem;
        }
        .timeline-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--color-border);
        }
        .timeline-item:last-child {
            border-bottom: none;
        }
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .timeline-content {
            flex: 1;
        }
        .timeline-content h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
        }
        .timeline-content p {
            margin: 0;
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="order-details-container">
        <a href="/pastimes-marketplace-v2/pages/orders.php" class="back-link">← Back to Orders</a>
        
        <div class="order-header">
            <h1>Order #<?php echo $order['order_id']; ?></h1>
            <p>Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
            
            <div class="order-meta">
                <div class="order-meta-item">
                    <span class="order-meta-label">Status</span>
                    <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </div>
                <div class="order-meta-item">
                    <span class="order-meta-label">Payment Method</span>
                    <span class="order-meta-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                </div>
                <div class="order-meta-item">
                    <span class="order-meta-label">Payment Status</span>
                    <span class="order-meta-value"><?php echo ucfirst($order['payment_status']); ?></span>
                </div>
            </div>
        </div>

        <div class="order-items-section">
            <h2>Order Items</h2>
            <?php foreach ($order_items as $item): ?>
            <div class="order-item">
                <?php if (!empty($item['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="order-item-img">
                <?php else: ?>
                <div class="order-item-img-placeholder">No Image</div>
                <?php endif; ?>
                <div class="order-item-details">
                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    <p>Quantity: <?php echo (int)$item['quantity']; ?></p>
                    <p>Price per item: R<?php echo number_format($item['price_at_purchase'], 2); ?></p>
                </div>
                <div class="order-item-price">
                    R<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="order-summary-section">
            <h2>Order Summary</h2>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>R<?php echo number_format($order['total_amount'] - $order['shipping_cost'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span>R<?php echo number_format($order['shipping_cost'], 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span>R<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>

            <div class="tracking-timeline">
                <h3>Order Tracking</h3>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Order Placed</h4>
                        <p><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
                <?php if ($order['order_status'] === 'processing'): ?>
                <div class="timeline-item">
                    <div class="timeline-icon" style="background: #ccc;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Processing</h4>
                        <p>Your order is being prepared for shipment</p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($order['order_status'] === 'shipped' || $order['order_status'] === 'delivered'): ?>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Shipped</h4>
                        <p>Your order has been shipped and is on its way</p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($order['order_status'] === 'delivered'): ?>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Delivered</h4>
                        <p>Your order has been delivered successfully</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
