<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
$cats=$db->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id=? ORDER BY name");
$cats->execute([$uid]);$categories=$cats->fetchAll();
pageHead('Transactions');
renderHeader('Transactions','transactions');
?>
<div class="page-content">
  <div class="page-header">
    <h2>💸 My Transactions</h2>
    <p>Manage all your income and expense entries</p>
  </div>
  <div class="filters-bar">
    <div class="form-group">
      <label>From Date</label>
      <input type="date" id="filterDateFrom" class="form-control">
    </div>
    <div class="form-group">
      <label>To Date</label>
      <input type="date" id="filterDateTo" class="form-control">
    </div>
    <div class="form-group">
      <label>Category</label>
      <select id="filterCategory" class="form-control form-select">
        <option value="">All Categories</option>
        <?php foreach($categories as $c): ?>
        <option value="<?=$c['id']?>"><?=htmlspecialchars($c['icon'].' '.$c['name'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Type</label>
      <select id="filterType" class="form-control form-select">
        <option value="">All Types</option>
        <option value="income">Income</option>
        <option value="expense">Expense</option>
      </select>
    </div>
    <button onclick="refreshTransactions()" class="btn btn-primary">🔍 Filter</button>
    <button onclick="resetFilters()" class="btn btn-ghost">↺ Reset</button>
    <div style="margin-left:auto;display:flex;gap:8px">
      <button onclick="exportCSV()" class="btn btn-ghost btn-sm">📊 CSV</button>
      <button onclick="exportPDF()" class="btn btn-ghost btn-sm">📄 PDF</button>
      <button onclick="shareWhatsApp()" class="btn btn-wa btn-sm">📱 Share</button>
      <button onclick="openAddTransaction('expense')" class="btn btn-danger">➕ Add Expense</button>
      <button onclick="openAddTransaction('income')" class="btn btn-success">💵 Add Income</button>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Category</th><th>Description</th><th>Actions</th></tr></thead>
          <tbody id="txTableBody"></tbody>
        </table>
      </div>
    </div>
    <div id="txPagination"></div>
  </div>
</div>
<?php include '../includes/tx_modal.php'; ?>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
<script>
function resetFilters(){
  ['filterDateFrom','filterDateTo','filterCategory','filterType'].forEach(id=>{const e=document.getElementById(id);if(e)e.value='';});
  document.getElementById('filterSearch').value='';
  refreshTransactions();
}
</script>
</body></html>
