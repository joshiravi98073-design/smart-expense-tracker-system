<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
$year=(int)($_GET['year']??date('Y'));
// Yearly monthly breakdown
$months=[];
for($m=1;$m<=12;$m++){
  $s=$db->prepare("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) as inc, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) as exp FROM transactions WHERE user_id=? AND MONTH(date)=? AND YEAR(date)=?");
  $s->execute([$uid,$m,$year]);$r=$s->fetch();
  $months[]=array_merge(['month'=>date('M',mktime(0,0,0,$m,1)),'m'=>$m],$r);
}
// Top categories
$topCats=$db->prepare("SELECT c.name,c.icon,COALESCE(SUM(t.amount),0) as total FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE t.user_id=? AND t.type='expense' AND YEAR(t.date)=? GROUP BY c.id,c.name ORDER BY total DESC LIMIT 6");
$topCats->execute([$uid,$year]);$topCategories=$topCats->fetchAll();
$totalExp=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='expense' AND YEAR(date)=?");
$totalExp->execute([$uid,$year]);$yearExp=(float)$totalExp->fetchColumn();
pageHead('Reports');
renderHeader('Reports','reports');
?>
<div class="page-content">
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
      <h2>📈 Financial Reports</h2>
      <p>Advanced spending analysis and insights</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <select onchange="window.location='reports.php?year='+this.value" class="form-control form-select" style="width:110px">
        <?php for($y=date('Y');$y>=2020;$y--): ?><option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option><?php endfor; ?>
      </select>
      <button onclick="exportCSV()" class="btn btn-ghost btn-sm">📊 CSV</button>
      <button onclick="exportPDF()" class="btn btn-ghost btn-sm">📄 PDF</button>
    </div>
  </div>

  <!-- MONTHLY TABLE -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-header"><span class="card-title">📅 Monthly Breakdown — <?=$year?></span></div>
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Month</th><th>Income</th><th>Expenses</th><th>Net Balance</th><th>Savings %</th></tr></thead>
          <tbody>
          <?php foreach($months as $m): $net=(float)$m['inc']-(float)$m['exp'];$sp=$m['inc']>0?round($net/$m['inc']*100,1):0; ?>
          <tr>
            <td><strong><?=$m['month']?></strong></td>
            <td class="amount-income">+₹<?=number_format($m['inc'],2)?></td>
            <td class="amount-expense">-₹<?=number_format($m['exp'],2)?></td>
            <td class="<?=$net>=0?'amount-income':'amount-expense'?>"><?=$net>=0?'+':''?>₹<?=number_format($net,2)?></td>
            <td><span style="color:<?=$sp>=30?'var(--income)':($sp>=0?'var(--warning)':'var(--expense)')?>;font-weight:700"><?=$sp?>%</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TOP CATEGORIES -->
  <div class="card">
    <div class="card-header"><span class="card-title">🏆 Top Spending Categories — <?=$year?></span></div>
    <div class="card-body">
      <?php if(empty($topCategories)): ?>
      <div class="empty-state"><span class="emoji">📊</span><h3>No data for <?=$year?></h3></div>
      <?php else: ?>
      <?php foreach($topCategories as $cat): $pct=$yearExp>0?round($cat['total']/$yearExp*100,1):0; ?>
      <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span><?=htmlspecialchars($cat['icon'].' '.$cat['name'])?></span>
          <span style="font-weight:700">₹<?=number_format($cat['total'],2)?> (<?=$pct?>%)</span>
        </div>
        <div class="budget-bar-bg">
          <div class="budget-bar-fill <?=$pct>=40?'danger':($pct>=25?'warning':'')?>" style="width:<?=$pct?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
</body></html>
