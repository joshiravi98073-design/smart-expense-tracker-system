<?php
// ============================================
// includes/functions.php - Utility Functions (MySQLi-based, for legacy use)
// ============================================
// NOTE: All new API code uses getDB() (PDO) from config.php.
// This file provides helper functions that use the $conn MySQLi connection
// from db.php, used primarily in older/direct PHP pages.

require_once __DIR__ . '/db.php';

// BUG FIX: getCurrencySymbol() was defined in db.php but not always available.
// Ensured it's defined here as a fallback.
if (!function_exists('getCurrencySymbol')) {
    function getCurrencySymbol($code) {
        $symbols = ['INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£','JPY'=>'¥','AED'=>'د.إ','AUD'=>'A$','CAD'=>'C$'];
        return $symbols[$code] ?? $code;
    }
}

function getTransactions($conn, $user_id, $filters = []) {
    $where  = ["t.user_id = ?"];
    $params = [$user_id];
    $types  = "i";

    if (!empty($filters['type']) && in_array($filters['type'], ['income','expense'])) {
        $where[]  = "t.type = ?";
        $params[] = $filters['type'];
        $types   .= "s";
    }
    if (!empty($filters['category_id'])) {
        $where[]  = "t.category_id = ?";
        $params[] = intval($filters['category_id']);
        $types   .= "i";
    }
    if (!empty($filters['date_from'])) {
        $where[]  = "t.date >= ?";
        $params[] = $filters['date_from'];
        $types   .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where[]  = "t.date <= ?";
        $params[] = $filters['date_to'];
        $types   .= "s";
    }
    if (!empty($filters['search'])) {
        $where[]  = "(t.description LIKE ? OR c.name LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
        $types   .= "ss";
    }

    $whereClause = implode(" AND ", $where);
    $limit = isset($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "";
    $sql = "SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE $whereClause
            ORDER BY t.date DESC, t.created_at DESC
            $limit";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllTransactions($conn, $filters = []) {
    $where  = ["1=1"];
    $params = [];
    $types  = "";

    if (!empty($filters['user_id'])) {
        $where[]  = "t.user_id = ?";
        $params[] = intval($filters['user_id']);
        $types   .= "i";
    }
    if (!empty($filters['type']) && in_array($filters['type'], ['income','expense'])) {
        $where[]  = "t.type = ?";
        $params[] = $filters['type'];
        $types   .= "s";
    }
    if (!empty($filters['date_from'])) {
        $where[]  = "t.date >= ?";
        $params[] = $filters['date_from'];
        $types   .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where[]  = "t.date <= ?";
        $params[] = $filters['date_to'];
        $types   .= "s";
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT t.*, c.name AS category_name, c.icon AS category_icon,
                   u.name AS user_name, u.email AS user_email
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE $whereClause
            ORDER BY t.date DESC, t.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getSummary($conn, $user_id, $month = null, $year = null) {
    $month = $month ?? date('n');
    $year  = $year  ?? date('Y');
    $sql = "SELECT
                SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
            FROM transactions
            WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $result  = $stmt->get_result()->fetch_assoc();
    $income  = $result['total_income']  ?? 0;
    $expense = $result['total_expense'] ?? 0;
    return ['income' => $income, 'expense' => $expense, 'balance' => $income - $expense];
}

function getYearlySummary($conn, $user_id, $year = null) {
    $year = $year ?? date('Y');
    $sql  = "SELECT
                SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
            FROM transactions WHERE user_id = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $year);
    $stmt->execute();
    $result  = $stmt->get_result()->fetch_assoc();
    $income  = $result['total_income']  ?? 0;
    $expense = $result['total_expense'] ?? 0;
    return ['income' => $income, 'expense' => $expense, 'balance' => $income - $expense];
}

function getAllTimeSummary($conn, $user_id) {
    $sql  = "SELECT
                SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
            FROM transactions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result  = $stmt->get_result()->fetch_assoc();
    $income  = $result['total_income']  ?? 0;
    $expense = $result['total_expense'] ?? 0;
    return ['income' => $income, 'expense' => $expense, 'balance' => $income - $expense];
}

function getBudget($conn, $user_id, $month = null, $year = null) {
    $month = $month ?? date('n');
    $year  = $year  ?? date('Y');
    $stmt  = $conn->prepare("SELECT * FROM budgets WHERE user_id=? AND month=? AND year=?");
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getCategories($conn, $type = null) {
    // BUG FIX: Original code called $stmt->execute() before $stmt->bind_param()
    // when $type was set, causing a fatal error. Fixed ordering.
    if ($type) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE type=? OR type='both' ORDER BY name");
        $stmt->bind_param("s", $type);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY type, name");
        $stmt->execute();
    }
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMonthlyChartData($conn, $user_id, $year = null) {
    $year = $year ?? date('Y');
    $sql  = "SELECT MONTH(date) as m,
                   SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS income,
                   SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense
            FROM transactions
            WHERE user_id=? AND YEAR(date)=?
            GROUP BY MONTH(date) ORDER BY m";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $year);
    $stmt->execute();
    $rows   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $months = array_fill(1, 12, ['income' => 0, 'expense' => 0]);
    foreach ($rows as $r) $months[(int)$r['m']] = ['income' => $r['income'], 'expense' => $r['expense']];
    $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $inc = $exp = [];
    for ($i = 1; $i <= 12; $i++) { $inc[] = $months[$i]['income']; $exp[] = $months[$i]['expense']; }
    return ['labels' => $labels, 'income' => $inc, 'expense' => $exp];
}

function getCategoryChartData($conn, $user_id, $month = null, $year = null) {
    $month = $month ?? date('n');
    $year  = $year  ?? date('Y');
    $sql   = "SELECT c.name, c.color, SUM(t.amount) AS total
              FROM transactions t
              LEFT JOIN categories c ON t.category_id = c.id
              WHERE t.user_id=? AND t.type='expense' AND MONTH(t.date)=? AND YEAR(t.date)=?
              GROUP BY t.category_id ORDER BY total DESC";
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getSpendingInsights($conn, $user_id) {
    $sql  = "SELECT c.name, c.icon, SUM(t.amount) AS total
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id=? AND t.type='expense' AND MONTH(t.date)=MONTH(NOW()) AND YEAR(t.date)=YEAR(NOW())
             GROUP BY t.category_id ORDER BY total DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $top  = $stmt->get_result()->fetch_assoc();
    $cur  = getSummary($conn, $user_id);
    $last = getSummary($conn, $user_id, date('n', strtotime('-1 month')), date('Y', strtotime('-1 month')));
    $diff = $cur['expense'] - $last['expense'];
    $pct  = $last['expense'] > 0 ? round(abs($diff) / $last['expense'] * 100, 1) : 0;
    return [
        'top_category' => $top,
        'expense_diff' => $diff,
        'expense_pct'  => $pct,
        'savings_rate' => $cur['income'] > 0 ? round(($cur['income'] - $cur['expense']) / $cur['income'] * 100, 1) : 0,
    ];
}

function formatAmount($amount, $currency = 'INR') {
    return getCurrencySymbol($currency) . number_format($amount, 2);
}

function addNotification($conn, $user_id, $message, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?,?,?)");
    $stmt->bind_param("iss", $user_id, $message, $type);
    $stmt->execute();
}

function getUnreadNotifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function markNotificationsRead($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}
