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

// Fetch user's orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM tblorder_items WHERE order_id = o.order_id) as item_count,
           (SELECT GROUP_CONCAT(cl.title SEPARATOR ', ') 
            FROM tblorder_items oi 
            JOIN tblclothes cl ON oi.clothes_id = cl.clothes_id 
            WHERE oi.order_id = o.order_id) as items_summary
    FROM tblorder o 
    WHERE o.buyer_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — Pastimes</title>
    <link rel="stylesheet" href="/pastimes-marketplace-v2/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 2rem auto;
        }
        .orders-header {
            margin-bottom: 2rem;
        }
        .orders-header h1 {
            margin-bottom: 0.5rem;
        }
        .order-card {
            background: var(--color-bg-secondary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--color-border);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--color-border);
        }
        .order-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color-primary);
        }
        .order-date {
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
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
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .order-detail-item {
            display: flex;
            justify-content: space-between;
        }
        .order-detail-label {
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
        .order-detail-value {
            font-weight: 500;
        }
        .order-items {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
        }
        .order-items h4 {
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        .order-items-summary {
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
        .order-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
            display: flex;
            gap: 1rem;
        }
        .btn-view-details {
            padding: 0.5rem 1rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-view-details:hover {
            background: var(--color-primary-dark);
        }
        .empty-orders {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-muted);
        }
        .empty-orders i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="orders-container">
        <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer" class="back-link">← Back to Dashboard</a>
        
        <div class="orders-header">
            <h1><i class="fas fa-box"></i> My Orders</h1>
            <p>Track and manage your orders</p>
        </div>

        <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-shopping-bag"></i>
            <h3>No orders yet</h3>
            <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
            <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-primary" style="margin-top: 1rem;">Start Shopping</a>
        </div>
        <?php else: ?>
        
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-number">Order #<?php echo $order['order_id']; ?></div>
                    <div class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </div>
            </div>

            <div class="order-details">
                <div class="order-detail-item">
                    <span class="order-detail-label">Items:</span>
                    <span class="order-detail-value"><?php echo $order['item_count']; ?> items</span>
                </div>
                <div class="order-detail-item">
                    <span class="order-detail-label">Total:</span>
                    <span class="order-detail-value">R<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="order-detail-item">
                    <span class="order-detail-label">Payment:</span>
                    <span class="order-detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                </div>
                <div class="order-detail-item">
                    <span class="order-detail-label">Payment Status:</span>
                    <span class="order-detail-value"><?php echo ucfirst($order['payment_status']); ?></span>
                </div>
            </div>

            <div class="order-items">
                <h4>Items in this order:</h4>
                <div class="order-items-summary">
                    <?php echo htmlspecialchars($order['items_summary'] ?? 'No items'); ?>
                </div>
            </div>

            <div class="order-actions">
                <button class="btn-view-details" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                    <i class="fas fa-eye"></i> View Details
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    window.location.href = '/pastimes-marketplace-v2/pages/order-details.php?order_id=' + orderId;
}
</script>

</body>
</html>
