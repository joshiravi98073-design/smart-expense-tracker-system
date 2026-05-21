<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change to your MySQL username
define('DB_PASS', '');              // Change to your MySQL password
define('DB_NAME', 'expense_tracker');

// APP SETTINGS
define('APP_NAME', 'ExpenseTracker Pro');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', 'assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// SESSION CONFIG
session_name('ET_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE CONNECTION (PDO)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // BUG FIX: Return a proper JSON error instead of dying mid-page.
            http_response_code(500);
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Database connection failed. Please check your config.php settings.']));
        }
    }
    return $pdo;
}

// HELPER FUNCTIONS
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        // BUG FIX: Use relative path based on depth, not absolute /index.php
        $depth = substr_count($_SERVER['PHP_SELF'] ?? '', '/');
        $prefix = $depth > 2 ? '../' : '';
        header('Location: ' . $prefix . 'index.php');
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        $depth = substr_count($_SERVER['PHP_SELF'] ?? '', '/');
        $prefix = $depth > 2 ? '../' : '';
        header('Location: ' . $prefix . 'index.php');
        exit();
    }
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function formatCurrency($amount, $currency = 'INR') {
    $symbols = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format((float)$amount, 2);
}
