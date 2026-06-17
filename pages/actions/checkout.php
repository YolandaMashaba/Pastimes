<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

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
    set_flash('error', 'Your cart is empty.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
}

// Calculate total
$total_amount = array_sum(array_map(fn($c) => ($c['price'] ?? 0) * ($c['quantity'] ?? 1), $cart_items));
$shipping_cost = 60.00;
$grand_total = $total_amount + $shipping_cost;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = trim($_POST['card_number'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');

    if (empty($shipping_address) || empty($payment_method)) {
        $error = 'Please fill in all required fields.';
    } elseif ($payment_method === 'credit_card' && (empty($card_number) || empty($card_expiry) || empty($card_cvv))) {
        $error = 'Please fill in all credit card details.';
    } else {
        try {
            $pdo->beginTransaction();

            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO tblorder (buyer_id, total_amount, shipping_cost, shipping_address, payment_method, payment_status, order_status)
                VALUES (?, ?, ?, ?, ?, 'paid', 'processing')
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

            $pdo->commit();
            set_flash('success', 'Order placed successfully! Order #' . $order_id);
            header('Location: /pastimes-marketplace-v2/pages/order-confirmation.php?order_id=' . $order_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Pastimes</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
        .order-summary, .checkout-form {
            background: var(--color-bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
        }
        .cart-items-list {
            margin-bottom: 1.5rem;
        }
        .cart-item-summary {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            align-items: center;
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
        .cart-item-img-placeholder {
            width: 80px;
            height: 80px;
            background: var(--color-bg);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted);
        }
        .cart-item-details h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }
        .cart-item-details p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }
        .cart-item-details .price {
            color: var(--color-primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .order-totals {
            border-top: 2px solid var(--color-border);
            padding-top: 1rem;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        .total-row-grand {
            font-size: 1.2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
        }
        .checkout-form h2 {
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 1rem;
        }
        .payment-details {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--color-bg);
            border-radius: 6px;
        }
        .payment-details.active {
            display: block;
        }
        .payment-details .form-group {
            margin-bottom: 0.75rem;
        }
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            width: 100%;
        }
        .checkout-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .checkout-header h1 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="container">
    <div class="checkout-header">
        <h1><i class="fas fa-shopping-cart"></i> Checkout</h1>
        <p>Review your order and complete your purchase.</p>
    </div>

    <?php if (isset($error)): ?>
    <div class="flash flash-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item-summary">
                    <?php if (!empty($item['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-item-img">
                    <?php else: ?>
                    <div class="cart-item-img-placeholder">No Image</div>
                    <?php endif; ?>
                    <div class="cart-item-details">
                        <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                        <p>Quantity: <?php echo (int)$item['quantity']; ?></p>
                        <p class="price">R<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>R<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>R<?php echo number_format($shipping_cost, 2); ?></span>
                </div>
                <div class="total-row total-row-grand">
                    <strong>Total:</strong>
                    <strong>R<?php echo number_format($grand_total, 2); ?></strong>
                </div>
            </div>
        </div>

        <!-- Checkout Form -->
        <div class="checkout-form">
            <h2>Shipping & Payment</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="shipping_address">
                        Shipping Address
                        <span class="field-required">*</span>
                    </label>
                    <textarea 
                        id="shipping_address" 
                        name="shipping_address" 
                        rows="3" 
                        required
                        placeholder="Enter your full shipping address..."
                    ><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="payment_method">
                        Payment Method
                        <span class="field-required">*</span>
                    </label>
                    <select id="payment_method" name="payment_method" required onchange="togglePaymentDetails()">
                        <option value="">Select payment method...</option>
                        <option value="credit_card" <?php echo (($_POST['payment_method'] ?? '') === 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="eft" <?php echo (($_POST['payment_method'] ?? '') === 'eft' ? 'selected' : ''; ?>>EFT (Electronic Funds Transfer)</option>
                        <option value="cash_on_delivery" <?php echo (($_POST['payment_method'] ?? '') === 'cash_on_delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                    </select>
                </div>

                <!-- Credit Card Details -->
                <div id="credit_card_details" class="payment-details">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_expiry">Expiry Date</label>
                        <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="card_cvv">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="3">
                    </div>
                </div>

                <!-- EFT Details -->
                <div id="eft_details" class="payment-details">
                    <p style="margin-bottom: 1rem;"><strong>Bank Details:</strong></p>
                    <p>Bank: First National Bank</p>
                    <p>Account Name: Pastimes Marketplace</p>
                    <p>Account Number: 1234567890</p>
                    <p>Branch Code: 250655</p>
                    <p style="margin-top: 1rem; color: var(--color-text-muted);">Reference: Your Order ID</p>
                </div>

                <!-- Cash on Delivery Details -->
                <div id="cod_details" class="payment-details">
                    <p><strong>Cash on Delivery</strong></p>
                    <p style="color: var(--color-text-muted);">Please have the exact amount ready when your order is delivered.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-lock"></i> Place Order
                </button>

                <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer" class="btn btn-outline" style="display: block; text-align: center; margin-top: 1rem;">Back to Cart</a>
            </form>
        </div>
    </div>
</div>

<script>
function togglePaymentDetails() {
    const paymentMethod = document.getElementById('payment_method').value;
    const creditCardDetails = document.getElementById('credit_card_details');
    const eftDetails = document.getElementById('eft_details');
    const codDetails = document.getElementById('cod_details');

    creditCardDetails.classList.remove('active');
    eftDetails.classList.remove('active');
    codDetails.classList.remove('active');

    if (paymentMethod === 'credit_card') {
        creditCardDetails.classList.add('active');
    } else if (paymentMethod === 'eft') {
        eftDetails.classList.add('active');
    } else if (paymentMethod === 'cash_on_delivery') {
        codDetails.classList.add('active');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', togglePaymentDetails);
</script>

</body>
</html>
