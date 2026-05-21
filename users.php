<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireAdmin();
$db=getDB();
$users=$db->query("SELECT u.*,(SELECT COUNT(*) FROM transactions WHERE user_id=u.id) as tx_count, (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=u.id AND type='expense') as total_exp FROM users u ORDER BY u.created_at DESC")->fetchAll();
pageHead('Manage Users');
renderHeader('Manage Users','users');
?>
<div class="page-content">
  <div class="page-header"><h2>👥 User Management</h2><p>View, edit and manage all registered users</p></div>
  <div class="card">
    <div class="card-header">
      <span class="card-title">All Users (<?=count($users)?>)</span>
    </div>
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Transactions</th><th>Total Spent</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="../assets/uploads/<?=htmlspecialchars($u['avatar']??'default.png')?>" onerror="this.src='../assets/img/avatar.svg'"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
                <strong><?=htmlspecialchars($u['name'])?></strong>
              </div>
            </td>
            <td><?=htmlspecialchars($u['email'])?></td>
            <td><span class="role-badge role-<?=$u['role']?>"><?=ucfirst($u['role'])?></span></td>
            <td><?=$u['tx_count']?></td>
            <td>₹<?=number_format($u['total_exp'],2)?></td>
            <td><?=date('d M Y',strtotime($u['created_at']))?></td>
            <td>
              <div class="action-btns">
                <button onclick="toggleUserRole(<?=$u['id']?>,'<?=$u['role']?>')" class="btn btn-ghost btn-sm" title="Toggle Role">
                  <?=$u['role']==='admin'?'👤 Make User':'🛡️ Make Admin'?>
                </button>
                <?php if($u['id']!==$_SESSION['user_id']): ?>
                <button onclick="deleteUser(<?=$u['id']?>)" class="btn btn-danger btn-sm" title="Delete">🗑️</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
</body></html>
