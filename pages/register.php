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
            $user_id = $pdo->lastInsertId();

            // Handle document uploads for sellers/both
            if (in_array($user_type, ['seller', 'both'])) {
                $upload_dir = __DIR__ . '/../uploads/verification/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $required_docs = ['id_document', 'proof_of_address', 'bank_statement'];
                $uploaded_count = 0;

                foreach ($required_docs as $doc_type) {
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$doc_type];

                        // Validate file type
                        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        if (!in_array($mime_type, $allowed_types)) {
                            $error = "Invalid file type for $doc_type. Please upload PDF, JPG, or PNG files only.";
                            break;
                        }

                        // Validate file size (5MB max)
                        $max_size = 5 * 1024 * 1024;
                        if ($file['size'] > $max_size) {
                            $error = "File size exceeds 5MB limit for $doc_type.";
                            break;
                        }

                        // Generate unique filename
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'doc_' . $user_id . '_' . $doc_type . '_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = $upload_dir . $filename;

                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $stmt = $pdo->prepare("
                                INSERT INTO tblverification_documents
                                (user_id, document_type, document_path, document_name, status)
                                VALUES (?, ?, ?, ?, 'pending')
                            ");
                            $stmt->execute([
                                $user_id,
                                $doc_type,
                                'uploads/verification/' . $filename,
                                $file['name']
                            ]);
                            $uploaded_count++;
                        } else {
                            $error = "Failed to upload $doc_type. Please try again.";
                            break;
                        }
                    }
                }

                if ($uploaded_count < 3) {
                    $error = 'Please upload all 3 required documents (ID Document, Proof of Address, Bank Statement).';
                } else {
                    $success = true;
                    $sticky  = ['firstname' => '', 'lastname' => '', 'email' => '', 'username' => '', 'phone' => '', 'user_type' => 'buyer'];
                }
            } else {
                $success = true;
                $sticky  = ['firstname' => '', 'lastname' => '', 'email' => '', 'username' => '', 'phone' => '', 'user_type' => 'buyer'];
            }
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
      <a href="/pastimes-marketplace-v2/pages/login.php" class="btn btn-outline" style="margin-top:.75rem;">Back to Login</a>
    </div>

    <?php else: ?>
    <form method="POST" enctype="multipart/form-data">
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
        <select id="user_type" name="user_type" required onchange="toggleDocumentUpload()">
          <option value="buyer"  <?php echo $sticky['user_type'] === 'buyer'  ? 'selected' : ''; ?>>Buyer — I want to shop</option>
          <option value="seller" <?php echo $sticky['user_type'] === 'seller' ? 'selected' : ''; ?>>Seller — I want to sell</option>
          <option value="both"   <?php echo $sticky['user_type'] === 'both'   ? 'selected' : ''; ?>>Both — Buy &amp; Sell</option>
        </select>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="field-required">*</span> <span class="field-hint">(min. 8 characters)</span></label>
        <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••">
      </div>

      <!-- Document Upload Section for Sellers/Both -->
      <div id="document-upload-section" style="display: <?php echo in_array($sticky['user_type'], ['seller', 'both']) ? 'block' : 'none'; ?>;">
        <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.1rem; color: var(--color-dark);">Verification Documents (Required for Sellers)</h3>
        <p style="margin-bottom: 1rem; color: var(--color-text-muted);">Please upload the following documents to verify your seller account:</p>

        <div class="form-group">
          <label for="id_document">ID Document (Passport/ID Card) <span class="field-required">*</span></label>
          <input type="file" id="id_document" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required>
          <small class="form-hint">Accepted formats: PDF, JPG, PNG. Max file size: 5MB</small>
        </div>

        <div class="form-group">
          <label for="proof_of_address">Proof of Address (Utility Bill/Bank Statement) <span class="field-required">*</span></label>
          <input type="file" id="proof_of_address" name="proof_of_address" accept=".pdf,.jpg,.jpeg,.png" required>
          <small class="form-hint">Accepted formats: PDF, JPG, PNG. Max file size: 5MB</small>
        </div>

        <div class="form-group">
          <label for="bank_statement">Bank Statement <span class="field-required">*</span></label>
          <input type="file" id="bank_statement" name="bank_statement" accept=".pdf,.jpg,.jpeg,.png" required>
          <small class="form-hint">Accepted formats: PDF, JPG, PNG. Max file size: 5MB</small>
        </div>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Create Account</button>
    </form>

    <div class="auth-switch">Already have an account? <a href="/pastimes-marketplace-v2/pages/login.php">Login</a></div>

    <script>
    function toggleDocumentUpload() {
      const userType = document.getElementById('user_type').value;
      const docSection = document.getElementById('document-upload-section');
      const docInputs = docSection.querySelectorAll('input[type="file"]');

      if (userType === 'seller' || userType === 'both') {
        docSection.style.display = 'block';
        docInputs.forEach(input => input.required = true);
      } else {
        docSection.style.display = 'none';
        docInputs.forEach(input => input.required = false);
      }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', toggleDocumentUpload);
    </script>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
