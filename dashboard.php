<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
$month=(int)date('m');$year=(int)date('Y');
$bStmt=$db->prepare("SELECT amount FROM budgets WHERE user_id=? AND month=? AND year=?");
$bStmt->execute([$uid,$month,$year]);$bRow=$bStmt->fetch();$budget=$bRow?(float)$bRow['amount']:0;
pageHead('Dashboard');
renderHeader('Dashboard','dashboard');
?>
<div class="page-content">
  <div class="page-header">
    <h2>👋 Welcome back, <?=htmlspecialchars($_SESSION['name']??'User')?>!</h2>
    <p>Financial overview for <?=date('F Y')?></p>
  </div>
  <div class="stats-grid">
    <div class="stat-card balance">
      <div class="stat-icon purple">🏦</div>
      <div class="stat-label">Net Balance</div>
      <div class="stat-value" id="statBalance">Loading...</div>
      <div class="stat-trend">This month</div>
    </div>
    <div class="stat-card income-card">
      <div class="stat-icon green">📈</div>
      <div class="stat-label">Total Income</div>
      <div class="stat-value positive" id="statIncome">Loading...</div>
      <div class="stat-trend up">↑ This month</div>
    </div>
    <div class="stat-card expense-card">
      <div class="stat-icon red">📉</div>
      <div class="stat-label">Total Expenses</div>
      <div class="stat-value negative" id="statExpense">Loading...</div>
      <div class="stat-trend down">↓ This month</div>
    </div>
    <div class="stat-card budget-card">
      <div class="stat-icon orange">🎯</div>
      <div class="stat-label">Budget Remaining</div>
      <div class="stat-value" id="statBudget"><?=$budget>0?'Loading...':'Not Set'?></div>
      <?php if($budget>0): ?>
      <div class="budget-bar-wrap">
        <div class="budget-label-row"><span id="budgetLabel">Calculating...</span><span id="budgetPct">0%</span></div>
        <div class="budget-bar-bg"><div class="budget-bar-fill" id="budgetBar" style="width:0%"></div></div>
      </div>
      <?php else: ?>
      <div class="stat-trend"><a href="budget.php">Set budget →</a></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- MONTHLY/YEARLY TOTALS -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-header">
      <span class="card-title">📅 Monthly & Yearly Summary</span>
      <input type="number" id="totalsYear" value="<?=date('Y')?>" min="2020" max="2099" class="form-control" style="width:90px" onchange="loadTotals()">
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
        <?php
        $totCards=[
          ['totalMonthIncome','This Month Income','var(--income)','var(--income-light)'],
          ['totalMonthExpense','This Month Expense','var(--expense)','var(--expense-light)'],
          ['totalMonthBalance','This Month Net','var(--accent-primary)','var(--accent-light)'],
          ['totalYearIncome','Year Income','var(--income)','var(--income-light)'],
          ['totalYearExpense','Year Expense','var(--expense)','var(--expense-light)'],
          ['totalYearBalance','Year Net Balance','var(--accent-primary)','var(--accent-light)'],
        ];
        foreach($totCards as [$id,$label,$color,$bg]):?>
        <div style="background:<?=$bg?>;border-radius:var(--radius-md);padding:14px 16px;border:1px solid <?=$color?>22">
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:<?=$color?>;margin-bottom:5px"><?=$label?></div>
          <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:800;color:<?=$color?>" id="<?=$id?>">—</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="dashboard-grid">
    <div class="card">
      <div class="card-header"><span class="card-title">🥧 Spending by Category</span></div>
      <div class="card-body"><div class="chart-container"><canvas id="pieChart"></canvas></div></div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">📊 Monthly Trends</span></div>
      <div class="card-body"><div class="chart-container"><canvas id="barChart"></canvas></div></div>
    </div>
    <div class="insight-card col-full">
      <h4>💡 Smart Insight</h4>
      <div class="insight-text" id="insightText">Analyzing your finances...</div>
    </div>
    <div class="card col-full">
      <div class="card-header"><span class="card-title">⚡ Quick Actions</span></div>
      <div class="card-body" style="display:flex;flex-wrap:wrap;gap:10px">
        <button onclick="openAddTransaction('expense')" class="btn btn-danger">➕ Add Expense</button>
        <button onclick="openAddTransaction('income')" class="btn btn-success">💵 Add Income</button>
        <button onclick="openModal('budgetModal')" class="btn btn-outline">🎯 Set Budget</button>
        <button onclick="shareWhatsApp()" class="btn btn-wa">📱 WhatsApp Share</button>
        <button onclick="exportCSV()" class="btn btn-ghost">📊 Export CSV</button>
        <button onclick="exportPDF()" class="btn btn-ghost">📄 Export PDF</button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">🕐 Recent Transactions</span>
      <a href="transactions.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Category</th><th>Description</th><th>Actions</th></tr></thead>
          <tbody id="txTableBody"><tr><td colspan="6"><div class="empty-state"><span class="emoji">⏳</span><h3>Loading...</h3></div></td></tr></tbody>
        </table>
      </div>
    </div>
    <div id="txPagination"></div>
  </div>
</div>
<?php include '../includes/tx_modal.php'; ?>
<?php include '../includes/budget_modal.php'; ?>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
</body></html>
