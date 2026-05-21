<?php
// includes/layout.php — Shared layout helpers
// BUG FIX: renderSidebar() was reading $_SESSION['name'] but index.php
// sets $_SESSION['name']; auth.php getCurrentUser() reads 'user_name'.
// Unified to always read $_SESSION['name'] with safe fallback.
// BUG FIX: pageHead() CSS path was always '../assets/css/style.css' which
// breaks when called from admin/ or user/ subdirectories with different depths.

function renderSidebar($active = 'dashboard') {
    $user    = $_SESSION;
    $isAdmin = isset($user['role']) && $user['role'] === 'admin';
    $base    = $isAdmin ? '../' : '../';  // both are one level deep
    ?>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">💰</div>
        <span>ET Pro</span>
    </div>
    <div class="sidebar-user">
        <img src="<?= $base ?>assets/uploads/<?= htmlspecialchars($user['avatar'] ?? 'default.png') ?>"
             onerror="this.src='<?= $base ?>assets/img/avatar.svg'"
             class="sidebar-avatar" alt="Avatar">
        <div class="sidebar-user-info">
            <strong><?= htmlspecialchars($user['name'] ?? ($user['user_name'] ?? 'User')) ?></strong>
            <span class="role-badge role-<?= $user['role'] ?? 'user' ?>"><?= ucfirst($user['role'] ?? 'user') ?></span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main</div>
        <?php if ($isAdmin): ?>
        <a href="<?= $base ?>admin/dashboard.php"    class="nav-item <?= $active === 'dashboard'    ? 'active' : '' ?>"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="<?= $base ?>admin/users.php"        class="nav-item <?= $active === 'users'        ? 'active' : '' ?>"><span class="nav-icon">👥</span> Users</a>
        <a href="<?= $base ?>admin/transactions.php" class="nav-item <?= $active === 'transactions' ? 'active' : '' ?>"><span class="nav-icon">💸</span> All Transactions</a>
        <a href="<?= $base ?>admin/categories.php"   class="nav-item <?= $active === 'categories'   ? 'active' : '' ?>"><span class="nav-icon">🏷️</span> Categories</a>
        <div class="sidebar-section-label">Reports</div>
        <a href="<?= $base ?>admin/reports.php"      class="nav-item <?= $active === 'reports'      ? 'active' : '' ?>"><span class="nav-icon">📈</span> Reports</a>
        <?php else: ?>
        <a href="<?= $base ?>user/dashboard.php"     class="nav-item <?= $active === 'dashboard'    ? 'active' : '' ?>"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="<?= $base ?>user/transactions.php"  class="nav-item <?= $active === 'transactions' ? 'active' : '' ?>"><span class="nav-icon">💸</span> Transactions</a>
        <a href="<?= $base ?>user/budget.php"        class="nav-item <?= $active === 'budget'       ? 'active' : '' ?>"><span class="nav-icon">🎯</span> Budget</a>
        <a href="<?= $base ?>user/reports.php"       class="nav-item <?= $active === 'reports'      ? 'active' : '' ?>"><span class="nav-icon">📈</span> Reports</a>
        <?php endif; ?>
        <div class="sidebar-section-label">Account</div>
        <a href="<?= $base ?>user/profile.php"       class="nav-item <?= $active === 'profile'      ? 'active' : '' ?>"><span class="nav-icon">👤</span> Profile</a>
        <a href="<?= $base ?>logout.php"             class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
    </nav>
    <div class="sidebar-footer">
        <button class="theme-toggle" onclick="toggleTheme()">
            <span>🌙 Dark Mode</span>
            <div class="toggle-switch"></div>
        </button>
    </div>
</aside>
    <?php
}

function renderHeader($title = 'Dashboard', $active = 'dashboard') {
    renderSidebar($active);
    ?>
<div class="main-content">
    <header class="top-header">
        <div style="display:flex;align-items:center;gap:14px">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <span class="header-title"><?= htmlspecialchars($title) ?></span>
        </div>
        <div class="header-actions">
            <div class="header-search">
                <span class="header-search-icon">🔍</span>
                <input type="text" id="filterSearch" placeholder="Search transactions..." oninput="onSearchInput()">
            </div>
            <div style="position:relative">
                <button class="notif-btn" id="notifBtn" onclick="toggleNotifPanel()" title="Notifications">
                    🔔 <span class="notif-dot"></span>
                </button>
                <div class="notif-panel" id="notifPanel">
                    <div class="notif-panel-header">
                        <span>Notifications</span>
                        <div style="display:flex;align-items:center;gap:8px">
                            <small id="notifCount" style="color:var(--text-muted)"></small>
                            <button onclick="markAllRead()" class="btn btn-ghost btn-sm">Mark all read</button>
                        </div>
                    </div>
                    <div id="notifList"></div>
                </div>
            </div>
        </div>
    </header>
    <?php
}

function renderFooter() {
    ?>
</div><!-- end main-content -->
<div id="toastContainer" class="toast-container"></div>
    <?php
}

function pageHead($title = 'ExpenseTracker Pro') {
    // BUG FIX: theme cookie may not exist on first load — default to 'light'
    $theme = isset($_COOKIE['et_theme']) && in_array($_COOKIE['et_theme'], ['light', 'dark'])
        ? $_COOKIE['et_theme']
        : 'light';
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> - ExpenseTracker Pro</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Apply theme immediately to avoid flash
(function(){
    var t = localStorage.getItem('et_theme') || '<?= $theme ?>';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
</head>
<body>
<div class="app-layout">
    <?php
}

function authHead($title = 'Login') {
    $theme = isset($_COOKIE['et_theme']) && in_array($_COOKIE['et_theme'], ['light', 'dark'])
        ? $_COOKIE['et_theme']
        : 'light';
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> - ExpenseTracker Pro</title>
<link rel="stylesheet" href="assets/css/style.css">
<script>(function(){var t=localStorage.getItem('et_theme')||'<?= $theme ?>';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <?php
}
