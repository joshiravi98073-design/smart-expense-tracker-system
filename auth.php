<?php
// ============================================
// includes/auth.php - Authentication Helpers
// ============================================
// BUG FIX: Replaced absolute /login.php and /dashboard.php redirects with
// relative paths so the app works in any subdirectory (e.g. /expense-tracker/).
// BUG FIX: Removed duplicate function definitions (isLoggedIn, isAdmin,
// requireLogin, requireAdmin, sanitize) that conflict with config.php.
// All those functions already live in config.php; we only define extras here.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Only define if NOT already defined by config.php ---
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            // Relative path fix: go up until we reach index.php
            $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
            $prefix = str_repeat('../', max(0, $depth - 1));
            header('Location: ' . $prefix . 'index.php');
            exit;
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
            $prefix = str_repeat('../', max(0, $depth - 1));
            header('Location: ' . $prefix . 'user/dashboard.php');
            exit;
        }
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['name']     ?? ($_SESSION['user_name']  ?? ''),
        'email'    => $_SESSION['email']    ?? ($_SESSION['user_email'] ?? ''),
        'role'     => $_SESSION['role'],
        'currency' => $_SESSION['currency'] ?? 'INR',
        'theme'    => $_SESSION['theme']    ?? 'light',
    ];
}

function loginUser($user) {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['name']      = $user['name'];   // used by layout.php
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['currency']  = $user['currency'] ?? 'INR';
    $_SESSION['theme']     = $user['theme']    ?? 'light';
    session_regenerate_id(true);
}

function logoutUser() {
    $_SESSION = [];
    session_destroy();
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}
