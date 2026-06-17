<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
}

$cart_id = (int)($_POST['cart_id'] ?? 0);
$action = $_POST['action'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 1);

if ($cart_id === 0 || empty($action)) {
    set_flash('error', 'Invalid request.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
}

// Verify cart item belongs to user
$stmt = $pdo->prepare("SELECT cart_id FROM tblcart WHERE cart_id = ? AND user_id = ?");
$stmt->execute([$cart_id, $user_id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Cart item not found.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
}

try {
    if ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM tblcart WHERE cart_id = ?");
        $stmt->execute([$cart_id]);
        set_flash('success', 'Item removed from cart.');
    } elseif ($action === 'update') {
        if ($quantity < 1) {
            set_flash('error', 'Quantity must be at least 1.');
        } else {
            $stmt = $pdo->prepare("UPDATE tblcart SET quantity = ? WHERE cart_id = ?");
            $stmt->execute([$quantity, $cart_id]);
            set_flash('success', 'Cart updated successfully.');
        }
    } else {
        set_flash('error', 'Invalid action.');
    }
} catch (Exception $e) {
    set_flash('error', 'Failed to update cart: ' . $e->getMessage());
}

header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
exit;
