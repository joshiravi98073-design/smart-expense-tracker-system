<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
// Load all budgets for this user
$bStmt=$db->prepare("SELECT * FROM budgets WHERE user_id=? ORDER BY year DESC, month DESC");
$bStmt->execute([$uid]);$budgets=$bStmt->fetchAll();
pageHead('Budget');
renderHeader('Budget','budget');
?>
<div class="page-content">
  <div class="page-header">
    <h2>🎯 Budget Management</h2>
    <p>Set and track your monthly spending limits</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
      <div class="card-header"><span class="card-title">Set Monthly Budget</span></div>
      <div class="card-body">
        <form onsubmit="saveBudget(event)">
          <div class="alert alert-info">⚠️ Alerts fire at 75% (warning) and 100% (exceeded) of your budget.</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">Month</label>
              <select id="budgetMonth" class="form-control form-select">
                <?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=(int)date('m')==$m?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor; ?>
              </select></div>
            <div class="form-group"><label class="form-label">Year</label>
              <input type="number" id="budgetYear" class="form-control" value="<?=date('Y')?>" min="2020" max="2099"></div>
          </div>
          <div class="form-group"><label class="form-label">Budget Amount</label>
            <div class="input-group"><span class="input-icon">💰</span>
              <input type="number" id="budgetAmount" class="form-control" placeholder="e.g. 25000" step="0.01" min="1" required>
            </div></div>
          <button type="submit" class="btn btn-primary btn-block">💾 Save Budget</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">Budget History</span></div>
      <div class="card-body" style="padding:0">
        <?php if(empty($budgets)): ?>
        <div class="empty-state"><span class="emoji">🎯</span><h3>No budgets set</h3><p>Set your first budget using the form.</p></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Month</th><th>Year</th><th>Amount</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach($budgets as $b): ?>
              <tr>
                <td><?=date('F',mktime(0,0,0,$b['month'],1))?></td>
                <td><?=$b['year']?></td>
                <td style="font-family:var(--font-display);font-weight:700">₹<?=number_format($b['amount'],2)?></td>
                <td><button onclick="loadBudget(<?=$b['month']?>,<?=$b['year']?>,<?=$b['amount']?>)" class="btn btn-ghost btn-sm">✏️ Edit</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
<script>
function loadBudget(month,year,amount){
  document.getElementById('budgetMonth').value=month;
  document.getElementById('budgetYear').value=year;
  document.getElementById('budgetAmount').value=amount;
  window.scrollTo({top:0,behavior:'smooth'});
}
</script>
</body></html>
