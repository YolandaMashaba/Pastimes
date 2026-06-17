<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_login();

$user = current_user();
$user_id = $user['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}

$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message_text = trim($_POST['message_text'] ?? '');
$item_id = (int)($_POST['item_id'] ?? 0);

if ($receiver_id === 0 || empty($message_text)) {
    set_flash('error', 'Please select a recipient and enter a message.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}

if ($receiver_id === $user_id) {
    set_flash('error', 'You cannot send messages to yourself.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}

// Verify receiver exists
$stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_id = ?");
$stmt->execute([$receiver_id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Recipient not found.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}

// Verify sender exists in tbluser (for admin accounts)
$stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_id = ?");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Sender account not found in user table. Admin accounts cannot send messages.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO tblmessages (sender_id, receiver_id, item_id, message_text, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$user_id, $receiver_id, $item_id ?: null, $message_text]);
    
    set_flash('success', 'Message sent successfully.');
    header('Location: /pastimes-marketplace-v2/pages/messages.php?user_id=' . $receiver_id . ($item_id > 0 ? '&item_id=' . $item_id : ''));
    exit;
} catch (Exception $e) {
    set_flash('error', 'Failed to send message: ' . $e->getMessage());
    header('Location: /pastimes-marketplace-v2/pages/messages.php');
    exit;
}
