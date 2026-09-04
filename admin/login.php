<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Already signed in — go straight to the dashboard.
if (current_admin()) {
    redirect('/admin/dashboard.php');
}

$error = null;

if (is_post()) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Enter your username and password.';
        } else {
            $result = attempt_login($username, $password);
            if ($result['ok']) {
                redirect('/admin/dashboard.php');
            }
            $error = $result['error'];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login — <?= e(SITE_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">

<div class="auth-screen">
  <div class="auth-card">
    <a href="/index.php" class="logo logo--lg">
      <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
    </a>
    <div class="auth-head">
      <h1>Admin Sign In</h1>
      <p>Enter your credentials to access the dashboard.</p>
    </div>

    <?php if ($error): ?>
      <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:10px 14px; font-size:0.82rem; margin-bottom:18px;">
        <?= e($error) ?>
      </p>
    <?php endif; ?>

    <form action="/admin/login.php" method="post">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="Username" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <div class="field-row">
        <label class="check"><input type="checkbox" name="remember"> Remember me</label>
        <a href="#">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary">Sign In</button>
    </form>

    <p class="auth-foot">Restricted area — authorized administrators only.</p>
  </div>
</div>

</body>
</html>
