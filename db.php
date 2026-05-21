<?php
// ============================================
// includes/db.php - Database Connection
// ============================================
// BUG FIX: Removed duplicate DB_* constant definitions that conflict with config.php.
// This file now only provides a mysqli $conn for legacy functions.php usage.
// All new code should use getDB() from config.php (PDO).

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'expense_tracker');
    define('DB_PORT', 3306);
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error .
                     '<br><br><strong>Please check your database settings in includes/db.php</strong>'
    ]));
}

$conn->set_charset('utf8mb4');

// Currency symbols
if (!defined('CURRENCY_SYMBOLS')) {
    define('CURRENCY_SYMBOLS', [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'AED' => 'د.إ',
        'AUD' => 'A$',
        'CAD' => 'C$',
    ]);
}

function getCurrencySymbol($code) {
    $symbols = CURRENCY_SYMBOLS;
    return $symbols[$code] ?? $code;
}
