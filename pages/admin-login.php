<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Already logged in as admin → go straight to dashboard
if (is_admin()) {
    header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
    exit;
}

$error  = '';
$sticky = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password =       $_POST['password'] ?? '';

    $sticky['username'] = $username;

    if ($username === '' || $password === '') {
        $error = 'Please enter your admin username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tbladmin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Fetch corresponding user_id from tbluser if exists
            $user_id = null;
            if (!empty($admin['user_id'])) {
                $user_id = $admin['user_id'];
            } else {
                // Try to find user by username
                $stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE username = ? LIMIT 1");
                $stmt->execute([$admin['username']]);
                $user_record = $stmt->fetch();
                if ($user_record) {
                    $user_id = $user_record['user_id'];
                }
            }

            $_SESSION['user'] = [
                'id'                 => (int) $admin['admin_id'],
                'user_id'            => $user_id, // Add user_id for messaging
                'admin_id'           => (int) $admin['admin_id'],
                'first_name'         => $admin['full_name'],
                'last_name'          => '',
                'name'               => $admin['full_name'],
                'username'           => $admin['username'],
                'email'              => $admin['email'],
                'role'               => $admin['role'],
                'admin_role'         => $admin['role'],
                'verification_status'=> 'verified',
                'is_verified'        => 1,
                'created_at'         => $admin['created_at']
            ];
            header('Location: /pastimes-marketplace-v2/pages/admin-dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="auth-container">
  <div class="auth-card admin-login-card">
    <div class="admin-login-icon"></div>
    <h2>Administrator Login</h2>
    <p class="auth-subtitle">Restricted access. Admin credentials required.</p>

    <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Admin Username</label>
        <input type="text" id="username" name="username" required placeholder="admin_username"
               value="<?php echo htmlspecialchars($sticky['username']); ?>">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>

      <button class="btn btn-secondary btn-block" type="submit">Login as Administrator</button>
    </form>

    <div class="auth-switch">
      Not an admin? <a href="/pastimes-marketplace-v2/pages/login.php">User Login</a>
    </div>
  </div>
</div>

</body>
</html>
