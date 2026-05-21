<?php
require_once '../includes/config.php';
requireLogin();

$action   = $_GET['action'] ?? 'export_csv';
$db       = getDB();
$uid      = (int)$_SESSION['user_id'];
$isAdmin  = isAdmin();

// Build query
$where    = [];
$params   = [];
if (!$isAdmin) { $where[] = "t.user_id = ?"; $params[] = $uid; }
if (!empty($_GET['search']))      { $where[] = "(t.description LIKE ? OR c.name LIKE ?)"; $s='%'.$_GET['search'].'%'; $params[]=$s;$params[]=$s; }
if (!empty($_GET['date_from']))   { $where[] = "t.date >= ?"; $params[] = $_GET['date_from']; }
if (!empty($_GET['date_to']))     { $where[] = "t.date <= ?"; $params[] = $_GET['date_to']; }
if (!empty($_GET['category_id'])) { $where[] = "t.category_id = ?"; $params[] = (int)$_GET['category_id']; }
if (!empty($_GET['type']) && in_array($_GET['type'],['income','expense'])) { $where[] = "t.type = ?"; $params[] = $_GET['type']; }

$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';
$stmt = $db->prepare("SELECT t.*, c.name as cat_name, u.name as user_name FROM transactions t LEFT JOIN categories c ON t.category_id=c.id LEFT JOIN users u ON t.user_id=u.id $whereStr ORDER BY t.date DESC, t.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Get user currency
$userStmt = $db->prepare("SELECT currency FROM users WHERE id=?");
$userStmt->execute([$uid]);
$currency = $userStmt->fetchColumn() ?: 'INR';

if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="expense_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date','Type','Amount','Currency','Category','Description','User']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['date'], $r['type'], $r['amount'], $r['currency'], $r['cat_name']??'Other', $r['description'], $r['user_name']]);
    }
    fclose($out);
    exit;
}

if ($action === 'export_pdf') {
    $totalInc = array_sum(array_map(fn($r) => $r['type']==='income' ? $r['amount'] : 0, $rows));
    $totalExp = array_sum(array_map(fn($r) => $r['type']==='expense' ? $r['amount'] : 0, $rows));
    $symbols  = ['INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£'];
    $sym      = $symbols[$currency] ?? $currency.' ';
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Expense Report - <?= date('F Y') ?></title>
<style>
  body{font-family:Arial,sans-serif;color:#0f172a;margin:0;padding:20px}
  h1{color:#6366f1;margin-bottom:4px}
  .meta{color:#94a3b8;font-size:.875rem;margin-bottom:24px}
  .summary{display:flex;gap:24px;margin-bottom:28px}
  .sum-box{background:#f0f4ff;border-radius:8px;padding:16px 24px;min-width:160px}
  .sum-box h3{margin:0 0 4px;font-size:.75rem;text-transform:uppercase;color:#94a3b8}
  .sum-box .val{font-size:1.4rem;font-weight:800}
  .val.green{color:#10b981}.val.red{color:#ef4444}.val.blue{color:#6366f1}
  table{width:100%;border-collapse:collapse;font-size:.85rem}
  th{background:#6366f1;color:white;padding:10px 12px;text-align:left}
  td{padding:9px 12px;border-bottom:1px solid #e2e8f0}
  tr:hover td{background:#f8fafc}
  .inc{color:#10b981;font-weight:700}.exp{color:#ef4444;font-weight:700}
  @media print{.no-print{display:none}}
</style>
</head>
<body>
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
  <div>
    <h1>💰 Expense Report</h1>
    <div class="meta">Generated: <?= date('d M Y, h:i A') ?> &nbsp;|&nbsp; <?= htmlspecialchars($_SESSION['name']??'') ?></div>
  </div>
  <button onclick="window.print()" class="no-print" style="background:#6366f1;color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:.9rem">🖨️ Print PDF</button>
</div>
<div class="summary">
  <div class="sum-box"><h3>Total Income</h3><div class="val green"><?= $sym.number_format($totalInc,2) ?></div></div>
  <div class="sum-box"><h3>Total Expenses</h3><div class="val red"><?= $sym.number_format($totalExp,2) ?></div></div>
  <div class="sum-box"><h3>Net Balance</h3><div class="val blue"><?= $sym.number_format($totalInc-$totalExp,2) ?></div></div>
  <div class="sum-box"><h3>Transactions</h3><div class="val"><?= count($rows) ?></div></div>
</div>
<table>
  <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Category</th><th>Description</th><?= $isAdmin ? '<th>User</th>' : '' ?></tr></thead>
  <tbody>
  <?php foreach($rows as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['date']) ?></td>
    <td><span class="<?= $r['type']==='income'?'inc':'exp' ?>"><?= ucfirst($r['type']) ?></span></td>
    <td class="<?= $r['type']==='income'?'inc':'exp' ?>"><?= ($r['type']==='income'?'+':'-').$sym.number_format($r['amount'],2) ?></td>
    <td><?= htmlspecialchars($r['cat_name']??'Other') ?></td>
    <td><?= htmlspecialchars($r['description']??'—') ?></td>
    <?= $isAdmin ? '<td>'.htmlspecialchars($r['user_name']??'').'</td>' : '' ?>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div style="text-align:center;margin-top:32px;color:#94a3b8;font-size:.8rem">Generated by ExpenseTracker Pro</div>
</body></html>
    <?php
    exit;
}
?>
