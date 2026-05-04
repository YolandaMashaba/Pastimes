<?php
/**
 * Authentication & session helpers.
 * session_start() must already be called by the calling page.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/** Redirect to login if visitor is not authenticated. */
function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: /pastimes-marketplace-v2/pages/login.php');
        exit;
    }
}

/**
 * Ensure the logged-in user has the expected role.
 * 'admin'  → redirect to admin-login if not admin
 * 'seller' → redirect away if admin (admin can't use seller hub)
 */
function require_role(string $role): void
{
    require_login();
    $user = $_SESSION['user'];

    if ($role === 'admin' && (!isset($user['role']) || $user['role'] !== 'super_admin')) {
        header('Location: /pastimes-marketplace-v2/pages/admin-login.php');
        exit;
    }

    if ($role === 'seller' && !in_array($user['role'] ?? '', ['seller', 'both'])) {
        set_flash('error', 'You need a seller account to access this page.');
        header('Location: /pastimes-marketplace-v2/pages/gallery.php');
        exit;
    }
}

/** Returns true when any user (or admin) is logged in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

/** Returns true specifically when an admin is logged in. */
function is_admin(): bool
{
    return !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'super_admin';
}

/** Returns the current user array or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Store a one-time flash message. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear the flash message. */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
