<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
require_role($admin, 'admin');
$pdo = db();

// Force a fresh read (not the request-cached copy) in case another tab just saved.
$settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$current = [];
foreach ($settingsRows as $row) {
    $current[$row['setting_key']] = $row['setting_value'];
}
$get = fn(string $key, string $default = '') => $current[$key] ?? $default;

$settingsError = flash_get('settings_error');
$settingsSuccess = flash_get('settings_success');
$pendingReportsCount = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$pendingCommentsCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Website Settings — <?= e(SITE_NAME) ?> Admin</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">

<div class="admin-shell">
  <?php $active = 'settings'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Website Settings</h2>
      </div>
      <div class="topbar-actions">
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($settingsError): ?>
        <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($settingsError) ?></p>
      <?php endif; ?>
      <?php if ($settingsSuccess): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($settingsSuccess) ?></p>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-head">
          <h3>Site &amp; Footer</h3>
        </div>
        <form method="post" action="/admin/actions/settings_action.php" style="padding:22px 20px; max-width:560px;">
          <?= csrf_field() ?>

          <div class="field">
            <label for="site_tagline">Homepage tagline</label>
            <input id="site_tagline" name="site_tagline" type="text" maxlength="200" value="<?= e($get('site_tagline')) ?>">
          </div>
          <div class="field">
            <label for="footer_about">Footer about text</label>
            <textarea id="footer_about" name="footer_about" rows="3" maxlength="500"><?= e($get('footer_about')) ?></textarea>
          </div>
          <div class="field">
            <label for="contact_email">Contact email <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional — powers the footer's Contact link)</span></label>
            <input id="contact_email" name="contact_email" type="email" value="<?= e($get('contact_email')) ?>">
          </div>
          <div class="field">
            <label for="social_x">X (Twitter) URL <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional)</span></label>
            <input id="social_x" name="social_x" type="url" placeholder="https://x.com/yourhandle" value="<?= e($get('social_x')) ?>">
          </div>
          <div class="field">
            <label for="social_instagram">Instagram URL <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional)</span></label>
            <input id="social_instagram" name="social_instagram" type="url" placeholder="https://instagram.com/yourhandle" value="<?= e($get('social_instagram')) ?>">
          </div>
          <div class="field">
            <label for="social_telegram">Telegram URL <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional)</span></label>
            <input id="social_telegram" name="social_telegram" type="url" placeholder="https://t.me/yourchannel" value="<?= e($get('social_telegram')) ?>">
          </div>

          <div class="field" style="margin-top:26px; padding-top:20px; border-top:1px solid var(--border);">
            <label class="check" style="text-transform:none; font-weight:500; color:var(--text-primary); font-size:0.88rem;">
              <input type="checkbox" name="maintenance_mode" value="1" <?= $get('maintenance_mode', '0') === '1' ? 'checked' : '' ?> style="width:auto;">
              Maintenance mode — takes the public site offline for visitors with a holding page (staff can still browse while signed in)
            </label>
          </div>

          <button type="submit" class="btn btn-primary" style="margin-top:8px;">Save Settings</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
