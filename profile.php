<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
$msg='';$msgType='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  if($action==='update_profile'){
    $name=sanitize($_POST['name']??'');$currency=in_array($_POST['currency']??'',['INR','USD','EUR','GBP'])?$_POST['currency']:'INR';
    $theme=in_array($_POST['theme']??'',['light','dark'])?$_POST['theme']:'light';
    if(!$name){$msg='Name is required.';$msgType='danger';}
    else{
      // Handle avatar upload
      $avatar=null;
      if(!empty($_FILES['avatar']['tmp_name'])){
        $ext=strtolower(pathinfo($_FILES['avatar']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','gif'])){$msg='Invalid image type.';$msgType='danger';}
        elseif($_FILES['avatar']['size']>2*1024*1024){$msg='Image too large (max 2MB).';$msgType='danger';}
        else{
          if(!is_dir(UPLOAD_DIR))mkdir(UPLOAD_DIR,0755,true);
          $avatar='avatar_'.$uid.'.'.$ext;
          move_uploaded_file($_FILES['avatar']['tmp_name'],UPLOAD_DIR.$avatar);
        }
      }
      if(!$msg){
        if($avatar){$db->prepare("UPDATE users SET name=?,currency=?,theme=?,avatar=? WHERE id=?")->execute([$name,$currency,$theme,$avatar,$uid]);$_SESSION['avatar']=$avatar;}
        else{$db->prepare("UPDATE users SET name=?,currency=?,theme=? WHERE id=?")->execute([$name,$currency,$theme,$uid]);}
        $_SESSION['name']=$name;$_SESSION['currency']=$currency;
        setcookie('et_theme',$theme,time()+60*60*24*365,'/');
        $msg='Profile updated!';$msgType='success';
      }
    }
  }
  if($action==='change_password'){
    $current=$_POST['current_password']??'';$new=$_POST['new_password']??'';$confirm=$_POST['confirm_password']??'';
    $userRow=$db->prepare("SELECT password FROM users WHERE id=?");$userRow->execute([$uid]);$urow=$userRow->fetch();
    if(!password_verify($current,$urow['password'])){$msg='Current password is incorrect.';$msgType='danger';}
    elseif(strlen($new)<6){$msg='New password must be at least 6 characters.';$msgType='danger';}
    elseif($new!==$confirm){$msg='Passwords do not match.';$msgType='danger';}
    else{$db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>12]),$uid]);$msg='Password changed!';$msgType='success';}
  }
}
$user=$db->prepare("SELECT * FROM users WHERE id=?");$user->execute([$uid]);$userData=$user->fetch();
pageHead('Profile');
renderHeader('Profile','profile');
?>
<div class="page-content">
  <div class="page-header"><h2>👤 My Profile</h2><p>Manage your account settings and preferences</p></div>
  <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=$msg?></div><?php endif; ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
      <div class="card-header"><span class="card-title">Profile Settings</span></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_profile">
          <div style="text-align:center;margin-bottom:20px">
            <img src="../assets/uploads/<?=htmlspecialchars($userData['avatar']??'default.png')?>"
                 onerror="this.src='../assets/img/avatar.svg'"
                 id="avatarPreview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-primary)">
            <div style="margin-top:10px">
              <label class="btn btn-ghost btn-sm" style="cursor:pointer">📷 Change Photo
                <input type="file" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
              </label>
            </div>
          </div>
          <div class="form-group"><label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($userData['name'])?>" required></div>
          <div class="form-group"><label class="form-label">Email (cannot change)</label>
            <input type="email" class="form-control" value="<?=htmlspecialchars($userData['email'])?>" disabled></div>
          <div class="form-group"><label class="form-label">Default Currency</label>
            <select name="currency" class="form-control form-select">
              <?php foreach(['INR'=>'₹ Indian Rupee','USD'=>'$ US Dollar','EUR'=>'€ Euro','GBP'=>'£ British Pound'] as $c=>$l): ?>
              <option value="<?=$c?>" <?=$userData['currency']===$c?'selected':''?>><?=$l?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Theme</label>
            <select name="theme" class="form-control form-select">
              <option value="light" <?=$userData['theme']==='light'?'selected':''?>>☀️ Light</option>
              <option value="dark"  <?=$userData['theme']==='dark'?'selected':''?>>🌙 Dark</option>
            </select></div>
          <button type="submit" class="btn btn-primary btn-block">💾 Save Changes</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">🔑 Change Password</span></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group"><label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required></div>
          <div class="form-group"><label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required></div>
          <div class="form-group"><label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required></div>
          <button type="submit" class="btn btn-danger btn-block">🔒 Update Password</button>
        </form>
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
          <h4 style="margin-bottom:12px;font-size:.95rem">Account Info</h4>
          <div style="font-size:.875rem;color:var(--text-muted)">
            <div style="margin-bottom:6px">📅 Joined: <?=date('d M Y',strtotime($userData['created_at']))?></div>
            <div style="margin-bottom:6px">👤 Role: <span class="role-badge role-<?=$userData['role']?>"><?=ucfirst($userData['role'])?></span></div>
            <div>💱 Currency: <?=$userData['currency']?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php renderFooter(); ?>
<script src="../assets/js/app.js"></script>
<script>
function previewAvatar(inp){if(inp.files&&inp.files[0]){const r=new FileReader();r.onload=e=>document.getElementById('avatarPreview').src=e.target.result;r.readAsDataURL(inp.files[0]);}}
</script>
</body></html>
