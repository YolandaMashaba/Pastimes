<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;
$user_role = $user['role'] ?? 'buyer';

// Only buyers can add to cart
if (!in_array($user_role, ['buyer', 'both'])) {
    set_flash('error', 'Only buyers can add items to cart.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

$clothes_id = (int)($_POST['clothes_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($clothes_id === 0 || $quantity < 1) {
    set_flash('error', 'Invalid item or quantity.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

// Verify item exists and is active
$stmt = $pdo->prepare("SELECT clothes_id, seller_id, status FROM tblclothes WHERE clothes_id = ?");
$stmt->execute([$clothes_id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('error', 'Item not found.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

if ($item['status'] !== 'active') {
    set_flash('error', 'This item is not available for purchase.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

if ($item['seller_id'] === $user_id) {
    set_flash('error', 'You cannot add your own item to cart.');
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}

try {
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT cart_id, quantity FROM tblcart WHERE user_id = ? AND clothes_id = ?");
    $stmt->execute([$user_id, $clothes_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update quantity
        $stmt = $pdo->prepare("UPDATE tblcart SET quantity = quantity + ? WHERE cart_id = ?");
        $stmt->execute([$quantity, $existing['cart_id']]);
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO tblcart (user_id, clothes_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $clothes_id, $quantity]);
    }

    set_flash('success', 'Item added to cart successfully.');
    header('Location: /pastimes-marketplace-v2/pages/dashboard.php?tab=buyer');
    exit;
} catch (Exception $e) {
    set_flash('error', 'Failed to add item to cart: ' . $e->getMessage());
    header('Location: /pastimes-marketplace-v2/pages/gallery.php');
    exit;
}
