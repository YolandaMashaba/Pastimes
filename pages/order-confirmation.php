<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id === 0) {
    set_flash('error', 'Invalid order ID.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM tblorder_items WHERE order_id = o.order_id) as item_count
    FROM tblorder o 
    WHERE o.order_id = ? AND o.buyer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

// Set default created_at if not present
if ($order && !isset($order['created_at'])) {
    $order['created_at'] = date('Y-m-d H:i:s');
}

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
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
    <title>Order Confirmation — Pastimes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
        .confirmation-header h1 {
            margin-bottom: 0.5rem;
            color: var(--color-primary);
        }
        .order-number {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0;
            color: var(--color-dark);
        }
        .confirmation-details {
            background: var(--color-bg-secondary);
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--color-border);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: var(--color-text-muted);
        }
        .detail-value {
            font-weight: 600;
        }
        .order-items-preview {
            margin-top: 1.5rem;
        }
        .order-items-preview h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .item-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--color-bg);
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }
        .item-preview img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .item-preview-info {
            flex: 1;
        }
        .item-preview-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }
        .item-preview-info p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }
        .next-steps {
            background: var(--color-bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: left;
        }
        .next-steps h3 {
            margin-bottom: 1rem;
        }
        .next-steps ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .next-steps li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        .next-steps li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--color-primary);
            font-weight: bold;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .action-buttons .btn {
            min-width: 150px;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="confirmation-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <div class="confirmation-header">
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order has been successfully placed.</p>
        </div>

        <div class="order-number">
            Order #<?php echo $order_id; ?>
        </div>

        <div class="confirmation-details">
            <h2>Order Details</h2>
            
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value" style="color: var(--color-primary);">Paid</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Order Status:</span>
                <span class="detail-value"><?php echo ucfirst($order['order_status']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">R<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>

            <div class="order-items-preview">
                <h3>Items Ordered (<?php echo count($order_items); ?>)</h3>
                <?php foreach ($order_items as $item): ?>
                <div class="item-preview">
                    <?php if (!empty($item['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php else: ?>
                    <div style="width: 50px; height: 50px; background: var(--color-border); border-radius: 4px;"></div>
                    <?php endif; ?>
                    <div class="item-preview-info">
                        <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                        <p>Qty: <?php echo (int)$item['quantity']; ?> × R<?php echo number_format($item['price_at_purchase'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="next-steps">
            <h3>What Happens Next?</h3>
            <ul>
                <li>You will receive an email confirmation with your order details</li>
                <li>The seller will be notified and will prepare your items for shipping</li>
                <li>You can track your order status from your dashboard</li>
                <li>Estimated delivery time: 3-5 business days</li>
                <li>Contact support if you have any questions about your order</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> View Orders
            </a>
            <a href="/pastimes-marketplace-v2/index.php" class="btn btn-outline">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

</body>
</html>
