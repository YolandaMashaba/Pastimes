<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer';</script>";
    exit;
}

$shipping_address = trim($_POST['shipping_address'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';

if (empty($shipping_address) || empty($payment_method)) {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/checkout.php?error=missing_fields';</script>";
    exit;
}

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.*, cl.title, cl.price, cl.seller_id, cl.image_path
    FROM tblcart c
    JOIN tblclothes cl ON c.clothes_id = cl.clothes_id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

if (empty($cart_items)) {
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer';</script>";
    exit;
}

// Calculate total
$total_amount = array_sum(array_map(fn($c) => ($c['price'] ?? 0) * ($c['quantity'] ?? 1), $cart_items));
$shipping_cost = 60.00;
$grand_total = $total_amount + $shipping_cost;

try {
    $pdo->beginTransaction();

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO tblorder (buyer_id, total_amount, shipping_cost, shipping_address, payment_method, payment_status, order_status)
        VALUES (?, ?, ?, ?, ?, 'paid', 'pending')
    ");
    $stmt->execute([$user_id, $grand_total, $shipping_cost, $shipping_address, $payment_method]);
    $order_id = $pdo->lastInsertId();

    // Add order items
    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO tblorder_items (order_id, clothes_id, quantity, price_at_purchase)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['clothes_id'],
            $item['quantity'],
            $item['price']
        ]);

        // Update item status to sold
        $stmt = $pdo->prepare("UPDATE tblclothes SET status = 'sold' WHERE clothes_id = ?");
        $stmt->execute([$item['clothes_id']]);
    }

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM tblcart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Create notification for order placed (skip if table doesn't exist)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tblnotifications (user_id, type, title, message, action_link, action_text)
            VALUES (?, 'order_update', 'Order Placed Successfully', 'Your order #' . ? . ' has been placed successfully and is being processed.', '/pastimes-marketplace-v2/pages/order-details.php?order_id=' . ?, 'View Order')
        ");
        $stmt->execute([$user_id, $order_id, $order_id]);
    } catch (Exception $e) {
        // Notification table might not exist, continue anyway
    }

    $pdo->commit();
    echo "<script>window.location.href='/pastimes-marketplace-v2/pages/order-confirmation.php?order_id=" . $order_id . "';</script>";
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    // Log error for debugging
    error_log("Checkout error: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='/pastimes-marketplace-v2/pages/checkout.php?error=processing_failed';</script>";
    exit;
}
