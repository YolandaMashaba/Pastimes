<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

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

header('Content-Type: application/json');
echo json_encode(['items' => $cart_items]);
