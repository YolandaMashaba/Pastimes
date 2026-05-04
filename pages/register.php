<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    header('Location: /pastimes-marketplace-v2/index.php');
    exit;
}

$error   = '';
$success = '';
$sticky  = ['firstname' => '', 'lastname' => '', 'email' => '', 'username' => '', 'phone' => '', 'user_type' => 'buyer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sticky['firstname'] = $firstname = trim($_POST['firstname'] ?? '');
    $sticky['lastname']  = $lastname  = trim($_POST['lastname']  ?? '');
    $sticky['email']     = $email     = trim($_POST['email']     ?? '');
    $sticky['username']  = $username  = trim($_POST['username']  ?? '');
    $sticky['phone']     = $phone     = trim($_POST['phone']     ?? '');
    $sticky['user_type'] = $user_type = $_POST['user_type'] ?? 'buyer';
    $password = $_POST['password'] ?? '';

    // Server-side validation (HTML5 handles client-side)
    if (!$firstname || !$lastname || !$email || !$username || !$phone || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!in_array($user_type, ['buyer', 'seller', 'both'])) {
        $error = 'Please select a valid user type.';
    } else {
        // Check for duplicate email or username
        $check = $pdo->prepare("SELECT user_id FROM tbluser WHERE email = ? OR username = ? LIMIT 1");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $error = 'An account with that email address or username already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO tbluser (first_name, last_name, email, username, password, cellphone, role, verification_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$firstname, $lastname, $email, $username, $hash, $phone, $user_type]);
            $success = true;
            $sticky  = ['firstname' => '', 'lastname' => '', 'email' => '', 'username' => '', 'phone' => '', 'user_type' => 'buyer'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="auth-container" style="align-items:flex-start;padding-top:2rem;">
  <div class="auth-card" style="max-width:560px;">
    <h2>Create Account</h2>
    <p class="auth-subtitle">Register to buy &amp; sell on Pastimes.</p>

    <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success">
      <strong>Registration submitted!</strong><br>
      Your account is <strong>pending admin verification</strong>. You will be able to log in once an administrator approves your account. Thank you for registering!
    </div>
    <div class="auth-switch">
      <a href="login.php" class="btn btn-outline" style="margin-top:.75rem;">Back to Login</a>
    </div>

    <?php else: ?>
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label for="firstname">First Name <span class="field-required">*</span></label>
          <input type="text" id="firstname" name="firstname" required placeholder="Jane"
                 value="<?php echo htmlspecialchars($sticky['firstname']); ?>">
        </div>
        <div class="form-group">
          <label for="lastname">Last Name <span class="field-required">*</span></label>
          <input type="text" id="lastname" name="lastname" required placeholder="Doe"
                 value="<?php echo htmlspecialchars($sticky['lastname']); ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="username">Username <span class="field-required">*</span></label>
          <input type="text" id="username" name="username" required placeholder="janedoe"
                 value="<?php echo htmlspecialchars($sticky['username']); ?>">
        </div>
        <div class="form-group">
          <label for="phone">Phone <span class="field-required">*</span></label>
          <input type="tel" id="phone" name="phone" required placeholder="0821234567"
                 value="<?php echo htmlspecialchars($sticky['phone']); ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="field-required">*</span></label>
        <input type="email" id="email" name="email" required placeholder="you@example.com"
               value="<?php echo htmlspecialchars($sticky['email']); ?>">
      </div>

      <div class="form-group">
        <label for="user_type">Account Type <span class="field-required">*</span></label>
        <select id="user_type" name="user_type" required>
          <option value="buyer"  <?php echo $sticky['user_type'] === 'buyer'  ? 'selected' : ''; ?>>Buyer — I want to shop</option>
          <option value="seller" <?php echo $sticky['user_type'] === 'seller' ? 'selected' : ''; ?>>Seller — I want to sell</option>
          <option value="both"   <?php echo $sticky['user_type'] === 'both'   ? 'selected' : ''; ?>>Both — Buy &amp; Sell</option>
        </select>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="field-required">*</span> <span class="field-hint">(min. 8 characters)</span></label>
        <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••">
      </div>

      <button class="btn btn-primary btn-block" type="submit">Create Account</button>
    </form>

    <div class="auth-switch">Already have an account? <a href="login.php">Login</a></div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
