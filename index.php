<?php
require_once 'includes/config.php';

// BUG FIX: Redirect logged-in users correctly regardless of session key used
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$error   = '';
$success = '';
$tab     = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fa = $_POST['form_action'] ?? 'login';

    if ($fa === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if (!$email || !$pass) {
            $error = 'Please fill in all fields.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                // BUG FIX: Store both 'name' (used by layout.php) AND
                // 'user_name' / 'user_email' (used by auth.php getCurrentUser())
                // so both helpers work regardless of which key they read.
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['name']       = $user['name'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['avatar']     = $user['avatar'];
                $_SESSION['currency']   = $user['currency'];
                $_SESSION['theme']      = $user['theme'] ?? 'light';

                setcookie('et_theme', $user['theme'] ?? 'light', time() + 60 * 60 * 24 * 365, '/');
                header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }

    if ($fa === 'register') {
        $tab     = 'register';
        $name    = sanitize($_POST['name']         ?? '');
        $email   = trim($_POST['reg_email']        ?? '');
        $pass    = $_POST['reg_password']           ?? '';
        $confirm = $_POST['reg_confirm']            ?? '';

        if (!$name || !$email || !$pass) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db  = getDB();
            $chk = $db->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'Email already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'user')")->execute([$name, $email, $hash]);
                $success = 'Account created! Please log in.';
                $tab     = 'login';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - ExpenseTracker Pro</title>
<link rel="stylesheet" href="assets/css/style.css">
<script>(function(){const s=localStorage.getItem('et_theme')||'light';document.documentElement.setAttribute('data-theme',s);})();</script>
</head>
<body>
<div class="auth-page">
  <div class="auth-container fade-in">
    <div class="auth-logo">
      <div class="logo-icon">💰</div>
      <h1>ExpenseTracker Pro</h1>
      <p>Smart financial management for everyone</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <div class="auth-card">
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
      </div>
      <form id="loginForm" method="POST" style="<?= $tab !== 'login' ? 'display:none' : '' ?>">
        <input type="hidden" name="form_action" value="login">
        <div class="form-group"><label class="form-label">Email Address</label>
          <div class="input-group"><span class="input-icon">📧</span>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div></div>
        <div class="form-group"><label class="form-label">Password</label>
          <div class="input-group"><span class="input-icon">🔒</span>
            <input type="password" name="password" class="form-control" placeholder="Your password" required id="loginPass">
          </div></div>
        <div style="margin-bottom:16px"><label style="display:flex;align-items:center;gap:6px;font-size:.875rem;cursor:pointer">
          <input type="checkbox" onchange="document.getElementById('loginPass').type=this.checked?'text':'password'"> Show password
        </label></div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In →</button>
        <div style="margin-top:16px;background:var(--bg-input);border-radius:var(--radius-sm);padding:12px;font-size:.82rem;color:var(--text-muted);text-align:center;border:1px solid var(--border)">
          <strong>Demo:</strong> admin@demo.com / Admin@123 &nbsp;|&nbsp; user@demo.com / User@123
        </div>
      </form>
      <form id="registerForm" method="POST" style="<?= $tab !== 'register' ? 'display:none' : '' ?>">
        <input type="hidden" name="form_action" value="register">
        <div class="form-group"><label class="form-label">Full Name</label>
          <div class="input-group"><span class="input-icon">👤</span>
            <input type="text" name="name" class="form-control" placeholder="Your full name" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div></div>
        <div class="form-group"><label class="form-label">Email Address</label>
          <div class="input-group"><span class="input-icon">📧</span>
            <input type="email" name="reg_email" class="form-control" placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
          </div></div>
        <div class="form-group"><label class="form-label">Password</label>
          <div class="input-group"><span class="input-icon">🔒</span>
            <input type="password" name="reg_password" class="form-control" placeholder="Min. 6 characters" required>
          </div></div>
        <div class="form-group"><label class="form-label">Confirm Password</label>
          <div class="input-group"><span class="input-icon">🔒</span>
            <input type="password" name="reg_confirm" class="form-control" placeholder="Re-enter password" required>
          </div></div>
        <button type="submit" class="btn btn-success btn-block btn-lg">Create Account ✨</button>
      </form>
    </div>
    <div style="text-align:center;margin-top:20px">
      <button onclick="toggleTheme()" style="background:none;border:1px solid var(--border);border-radius:20px;padding:6px 16px;cursor:pointer;font-size:.8rem;color:var(--text-muted);font-family:inherit">🌙 Toggle Dark Mode</button>
    </div>
  </div>
</div>
<script>
function switchTab(t){
  document.getElementById('loginForm').style.display=t==='login'?'':'none';
  document.getElementById('registerForm').style.display=t==='register'?'':'none';
  document.querySelectorAll('.auth-tab').forEach((b,i)=>b.classList.toggle('active',(i===0&&t==='login')||(i===1&&t==='register')));
}
function toggleTheme(){
  const c=document.documentElement.getAttribute('data-theme');
  const n=c==='dark'?'light':'dark';
  document.documentElement.setAttribute('data-theme',n);
  localStorage.setItem('et_theme',n);
}
</script>
</body></html>
