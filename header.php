<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$user = getCurrentUser();
$pageTitle = $pageTitle ?? 'Expense Tracker';
$activePage = $activePage ?? '';

// Notifications
$notifications = [];
if ($user) {
    $notifications = getUnreadNotifications($conn, $user['id']);
    // Check if no entries today
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM transactions WHERE user_id=? AND DATE(created_at)=CURDATE()");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $todayCount = $stmt->get_result()->fetch_assoc()['cnt'];
    if ($todayCount == 0 && date('H') >= 18) {
        // Reminder after 6PM
        addNotification($conn, $user['id'], '📝 You haven\'t added any transactions today! Don\'t forget to log your expenses.', 'reminder');
        $notifications = getUnreadNotifications($conn, $user['id']);
    }
    $currencySymbol = getCurrencySymbol($user['currency']);
}
$theme = $user['theme'] ?? (isset($_COOKIE['et_theme']) ? $_COOKIE['et_theme'] : 'light');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title><?= htmlspecialchars($pageTitle) ?> - ExpenseTracker</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💰</text></svg>">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="bg-mesh"></div>
<div id="toast-container"></div>

<?php if ($user): ?>
<div class="app-layout">
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">💰</div>
    <div>
      <div class="logo-text">ExpenseTracker</div>
      <div class="logo-sub"><?= $user['role'] === 'admin' ? 'Admin Panel' : 'My Finance' ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Overview</div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='dashboard'?'active':'' ?>" href="/dashboard.php">
        <span class="nav-icon">📊</span> Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='transactions'?'active':'' ?>" href="/transactions.php">
        <span class="nav-icon">💳</span> Transactions
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='budget'?'active':'' ?>" href="/budget.php">
        <span class="nav-icon">🎯</span> Budget
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='reports'?'active':'' ?>" href="/reports.php">
        <span class="nav-icon">📂</span> Reports & Export
      </a>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <div class="nav-section-title">Admin</div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='admin-dashboard'?'active':'' ?>" href="/admin/dashboard.php">
        <span class="nav-icon">🛡️</span> Admin Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='admin-users'?'active':'' ?>" href="/admin/users.php">
        <span class="nav-icon">👥</span> Manage Users
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='admin-transactions'?'active':'' ?>" href="/admin/transactions.php">
        <span class="nav-icon">📋</span> All Transactions
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link <?= $activePage==='admin-categories'?'active':'' ?>" href="/admin/categories.php">
        <span class="nav-icon">🏷️</span> Categories
      </a>
    </div>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role"><?= ucfirst($user['role']) ?></div>
      </div>
    </div>
    <button class="theme-toggle-btn" id="theme-toggle">
      <span>🌙 Dark Mode</span>
      <div class="theme-toggle-switch"></div>
    </button>
    <a href="/logout.php" class="btn btn-secondary btn-sm btn-block mt-1" style="text-align:center;display:flex;justify-content:center;">
      🚪 Logout
    </a>
  </div>
</aside>

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Main Content -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" id="hamburger-btn">
        <span></span><span></span><span></span>
      </button>
      <div>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="topbar-subtitle"><?= date('l, F j, Y') ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <div style="position:relative;">
        <div class="notif-btn" id="notif-btn" title="Notifications">
          🔔
          <?php if (count($notifications) > 0): ?>
          <div class="notif-dot"></div>
          <?php endif; ?>
        </div>
        <div class="notif-dropdown" id="notif-dropdown">
          <div class="notif-header">
            <span>🔔 Notifications</span>
            <span style="font-size:12px;color:var(--text-muted)"><?= count($notifications) ?> unread</span>
          </div>
          <?php if (empty($notifications)): ?>
            <div class="notif-empty">🎉 All caught up! No notifications.</div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
            <div class="notif-item unread">
              <div class="notif-icon">
                <?= $n['type']==='reminder'?'📝':($n['type']==='warning'?'⚠️':'ℹ️') ?>
              </div>
              <div class="notif-text">
                <div><?= htmlspecialchars($n['message']) ?></div>
                <div class="notif-time"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <a href="/transactions.php?action=add" class="btn btn-primary btn-sm">
        + Add Transaction
      </a>
    </div>
  </div>
  <div class="page-content">
<?php endif; // end if user ?>
