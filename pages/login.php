<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Redirect already-logged-in users
if (is_logged_in()) {

    $user = current_user();

    if (is_admin()) {

        $dest = '/pastimes-marketplace-v2/pages/admin-dashboard.php';

    } elseif (in_array($user['role'] ?? '', ['seller', 'both'])) {

        $dest = '/pastimes-marketplace-v2/pages/dashboard.php';

    } else {

        $dest = '/pastimes-marketplace-v2/pages/gallery.php';
    }

    header('Location: ' . $dest);
    exit;
}

$error = '';
$logged_in_user = null;

$sticky = [
    'username' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sticky['username'] = $username;

    if ($username === '' || $password === '') {

        $error = 'Please fill in all fields.';

    } else {

        // Authenticate user against tbluser
        $stmt = $pdo->prepare("
            SELECT 
                user_id,
                first_name,
                last_name,
                email,
                username,
                cellphone,
                password,
                role,
                is_verified,
                verification_status,
                created_at
            FROM tbluser
            WHERE username = ?
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $error = 'No account found with that username.';

        } elseif (
            $user['verification_status'] === 'pending'
        ) {

            $error = 'Your account is pending admin verification.';

        } elseif (
            password_verify($password, $user['password'])
        ) {

            // Ensure account is verified
            if ($user['verification_status'] !== 'verified') {

                $error = 'Your account is not verified yet.';

            } else {

                // Store session
                $_SESSION['user'] = [

                    'id' => $user['user_id'],

                    'user_id' => $user['user_id'],

                    'first_name' => $user['first_name'],

                    'last_name' => $user['last_name'],

                    'name' => $user['first_name'] . ' ' . $user['last_name'],

                    'email' => $user['email'],

                    'username' => $user['username'],

                    'cellphone' => $user['cellphone'],

                    'role' => $user['role'],

                    'is_verified' => $user['is_verified'],

                    'verification_status' => $user['verification_status'],

                    'created_at' => $user['created_at']
                ];

                $logged_in_user = $user;
            }

        } else {

            $error = 'Incorrect password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login — Pastimes</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

</head>

<body>

<?php include '../includes/navbar.php'; ?>

<?php if ($logged_in_user): ?>

<!-- LOGIN SUCCESS -->

<div class="login-success-banner">

    <strong>
        User
        <?php echo htmlspecialchars(
            $logged_in_user['first_name'] . ' ' .
            $logged_in_user['last_name']
        ); ?>
        is logged in
    </strong>

</div>

<div class="container">

    <div class="user-data-card">

        <div class="user-data-card-header">

            <h2>Account Details</h2>

            <p class="page-subtitle">
                Your information retrieved from the database
            </p>

        </div>

        <div class="table-scroll">

            <table class="user-data-table">

                <thead>

                    <tr>

                        <?php
                        $display_cols = [
                            'user_id',
                            'first_name',
                            'last_name',
                            'email',
                            'username',
                            'cellphone',
                            'role',
                            'verification_status',
                            'created_at'
                        ];

                        foreach ($display_cols as $col):
                        ?>

                        <th>
                            <?php echo htmlspecialchars($col); ?>
                        </th>

                        <?php endforeach; ?>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <?php foreach ($display_cols as $col): ?>

                        <td>
                            <?php echo htmlspecialchars(
                                (string)($logged_in_user[$col] ?? '')
                            ); ?>
                        </td>

                        <?php endforeach; ?>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="user-data-actions">

            <a href="/pastimes-marketplace-v2/pages/gallery.php"
               class="btn btn-primary">

               Browse Gallery →

            </a>

            <?php if (
                in_array(
                    $logged_in_user['role'],
                    ['seller', 'both']
                )
            ): ?>

            <a href="/pastimes-marketplace-v2/pages/dashboard.php"
               class="btn btn-outline">

               Go to Seller Hub

            </a>

            <?php endif; ?>

            <a href="/pastimes-marketplace-v2/pages/logout.php"
               class="btn btn-secondary">

               Logout

            </a>

        </div>

    </div>

</div>

<?php else: ?>

<!-- LOGIN FORM -->

<div class="auth-container">

    <div class="auth-card">

        <h2>Welcome Back</h2>

        <p class="auth-subtitle">
            Sign in to your Pastimes account.
        </p>

        <?php if ($error): ?>

        <div class="error">

            <?php echo htmlspecialchars($error); ?>

        </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    placeholder="your_username"
                    value="<?php echo htmlspecialchars($sticky['username']); ?>"
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                >

            </div>

            <button
                class="btn btn-primary btn-block"
                type="submit">

                Login

            </button>

        </form>

        <div class="auth-switch">

            Don't have an account?

            <a href="register.php">
                Register here
            </a>

        </div>

        <div class="auth-switch"
             style="margin-top:.5rem;">

            Administrator?

            <a href="admin-login.php">
                Admin Login
            </a>

        </div>

    </div>

</div>

<?php endif; ?>

</body>
</html>