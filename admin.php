<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'delete_user') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) jsonResponse(['success' => false, 'message' => 'Cannot delete yourself'], 400);
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'User deleted']);
}

if ($action === 'change_role') {
    $id   = (int)($_POST['id'] ?? 0);
    $role = in_array($_POST['role'] ?? '', ['admin','user']) ? $_POST['role'] : 'user';
    if ($id === (int)$_SESSION['user_id']) jsonResponse(['success' => false, 'message' => 'Cannot change own role'], 400);
    $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $id]);
    jsonResponse(['success' => true, 'message' => 'Role updated']);
}

if ($action === 'get_stats') {
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalTx    = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $totalInc   = $db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'")->fetchColumn();
    $totalExp   = $db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
    jsonResponse(['success' => true, 'data' => [
        'users'       => $totalUsers,
        'transactions' => $totalTx,
        'income'      => (float)$totalInc,
        'expense'     => (float)$totalExp,
    ]]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
?>
