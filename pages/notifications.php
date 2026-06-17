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

// Fetch user's notifications (with error handling for missing table)
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.*,
               CASE
                   WHEN n.type = 'order_update' THEN 'Order Update'
                   WHEN n.type = 'message' THEN 'New Message'
                   WHEN n.type = 'promotion' THEN 'Promotion'
                   ELSE 'Notification'
               END as type_label
        FROM tblnotifications n
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    // Mark all notifications as read
    $stmt = $pdo->prepare("UPDATE tblnotifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
} catch (PDOException $e) {
    // Table doesn't exist yet, notifications will be empty
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Pastimes</title>
    <link rel="stylesheet" href="/pastimes-marketplace-v2/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .notifications-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notifications-header h1 {
            margin-bottom: 0.5rem;
        }
        .notification-card {
            background: var(--color-bg-secondary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--color-border);
            border-left: 4px solid var(--color-primary);
            transition: transform 0.2s;
        }
        .notification-card:hover {
            transform: translateX(5px);
        }
        .notification-card.unread {
            background: var(--color-bg);
            border-left-color: var(--color-primary);
        }
        .notification-card.read {
            border-left-color: var(--color-border);
            opacity: 0.8;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        .notification-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .type-order-update {
            background: #d1ecf1;
            color: #0c5460;
        }
        .type-message {
            background: #d4edda;
            color: #155724;
        }
        .type-promotion {
            background: #fff3cd;
            color: #856404;
        }
        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .notification-message {
            color: var(--color-text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .notification-time {
            font-size: 0.85rem;
            color: var(--color-text-muted);
            margin-top: 0.75rem;
        }
        .notification-action {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
        }
        .notification-action a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }
        .notification-action a:hover {
            text-decoration: underline;
        }
        .empty-notifications {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-muted);
        }
        .empty-notifications i {
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
        .mark-all-read {
            padding: 0.5rem 1rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="notifications-container">
        <a href="/pastimes-marketplace-v2/pages/dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="notifications-header">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p>Stay updated with your orders, messages, and promotions</p>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="empty-notifications">
            <i class="fas fa-bell-slash"></i>
            <h3>No notifications</h3>
            <p>You don't have any notifications yet.</p>
        </div>
        <?php else: ?>
        
        <?php foreach ($notifications as $notification): ?>
        <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
            <div class="notification-header">
                <span class="notification-type type-<?php echo str_replace('_', '-', $notification['type']); ?>">
                    <?php echo htmlspecialchars($notification['type_label']); ?>
                </span>
                <span class="notification-time">
                    <?php echo time_elapsed_string($notification['created_at']); ?>
                </span>
            </div>
            <div class="notification-title">
                <?php echo htmlspecialchars($notification['title']); ?>
            </div>
            <div class="notification-message">
                <?php echo htmlspecialchars($notification['message']); ?>
            </div>
            <?php if (!empty($notification['action_link'])): ?>
            <div class="notification-action">
                <a href="<?php echo htmlspecialchars($notification['action_link']); ?>">
                    <?php echo htmlspecialchars($notification['action_text'] ?? 'View Details'); ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php
function time_elapsed_string($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('F j, Y', $time);
    }
}
?>

</body>
</html>
