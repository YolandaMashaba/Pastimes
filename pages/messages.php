<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$user = current_user();
$flash = get_flash();
$user_id = $user['user_id'] ?? 0;

// Get conversation partner (if specified)
$partner_id = (int)($_GET['user_id'] ?? 0);
$item_id = (int)($_GET['item_id'] ?? 0);

// Fetch all conversations (unique users we've messaged with)
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END as other_user_id,
        u.first_name,
        u.last_name,
        u.username,
        MAX(m.created_at) as last_message_time
    FROM tblmessages m
    JOIN tbluser u ON (
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END = u.user_id
    )
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, u.first_name, u.last_name, u.username
    ORDER BY last_message_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Fetch messages for selected conversation
$messages = [];
$partner_name = '';
if ($partner_id > 0) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u_sender.first_name as sender_first_name,
               u_sender.last_name as sender_last_name,
               u_receiver.first_name as receiver_first_name,
               u_receiver.last_name as receiver_last_name
        FROM tblmessages m
        JOIN tbluser u_sender ON m.sender_id = u_sender.user_id
        JOIN tbluser u_receiver ON m.receiver_id = u_receiver.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $partner_id, $partner_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE tblmessages 
        SET is_read = 1, read_at = NOW()
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id, $partner_id]);

    // Get partner name
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM tbluser WHERE user_id = ?");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch();
    $partner_name = $partner ? ($partner['first_name'] . ' ' . $partner['last_name']) : 'Unknown';
}

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tblmessages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_result = $stmt->fetch();
$unread_count = $unread_result['count'] ?? 0;

// Get item details if item_id is specified
$item_details = null;
if ($item_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tblclothes WHERE clothes_id = ?");
    $stmt->execute([$item_id]);
    $item_details = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — Pastimes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="messages-header">
        <h1><i class="fas fa-envelope"></i> Messages</h1>
        <p>Communicate with sellers and buyers about items, negotiations, and general questions.</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>

    <div class="messages-layout">
        <!-- Conversations List -->
        <div class="conversations-sidebar">
            <h2>Conversations</h2>
            <?php if ($unread_count > 0): ?>
            <div class="unread-badge"><?php echo $unread_count; ?> unread</div>
            <?php endif; ?>
            
            <?php if (empty($conversations)): ?>
            <div class="empty-state">
                <p>No conversations yet.</p>
                <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-primary">Browse Items</a>
            </div>
            <?php else: ?>
            <div class="conversations-list">
                <?php foreach ($conversations as $conv): ?>
                <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                   class="conversation-item <?php echo $partner_id == $conv['other_user_id'] ? 'active' : ''; ?>">
                    <div class="conversation-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="conversation-info">
                        <h4><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h4>
                        <p class="conversation-time">
                            <?php echo date('M j, g:i A', strtotime($conv['last_message_time'])); ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Message View -->
        <div class="messages-main">
            <?php if ($partner_id > 0): ?>
                <div class="message-view-header">
                    <h3>
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($partner_name); ?>
                    </h3>
                    <?php if ($item_details): ?>
                    <div class="message-item-context">
                        <span>About: <?php echo htmlspecialchars($item_details['title']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="messages-container">
                    <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received'; ?>">
                        <div class="message-content">
                            <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                            <span class="message-time">
                                <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                <?php if ($msg['sender_id'] == $user_id): ?>
                                    <?php echo $msg['is_read'] ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>'; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="message-form">
                    <form method="POST" action="actions/send-message.php">
                        <input type="hidden" name="receiver_id" value="<?php echo $partner_id; ?>">
                        <?php if ($item_id > 0): ?>
                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                        <?php endif; ?>
                        <div class="message-input-group">
                            <textarea 
                                name="message_text" 
                                rows="3" 
                                placeholder="Type your message..." 
                                required
                            ></textarea>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Select a conversation to view messages, or start a new conversation from an item page.</p>
                    <a href="/pastimes-marketplace-v2/pages/gallery.php" class="btn btn-primary">Browse Items</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
