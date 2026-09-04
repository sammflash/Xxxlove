<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
require_role($admin, 'admin');
$pdo = db();

$accounts = $pdo->query(
    "SELECT a.id, a.username, a.role, a.is_owner, a.status, a.created_at, a.last_login,
            c.username AS created_by_username
     FROM admins a
     LEFT JOIN admins c ON c.id = a.created_by
     ORDER BY a.is_owner DESC, FIELD(a.role, 'admin', 'moderator', 'creator'), a.username"
)->fetchAll();

$roleLabels = ['creator' => 'Creator', 'moderator' => 'Moderator', 'admin' => 'Admin'];

$accountsError = flash_get('accounts_error');
$accountsSuccess = flash_get('accounts_success');
$pendingReportsCount = admin_has_role($admin, 'moderator')
    ? (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn()
    : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Manage Accounts — <?= e(SITE_NAME) ?> Admin</title>
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
  <?php $active = 'accounts'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Manage Accounts</h2>
      </div>
      <div class="topbar-actions">
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($accountsError): ?>
        <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($accountsError) ?></p>
      <?php endif; ?>
      <?php if ($accountsSuccess): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($accountsSuccess) ?></p>
      <?php endif; ?>

      <!-- Create account -->
      <div class="panel" style="margin-bottom:24px;">
        <div class="panel-head">
          <h3>Create Account</h3>
        </div>
        <form method="post" action="/admin/actions/account_manage_action.php" style="padding:22px 20px; display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:16px; align-items:end;">
          <?= csrf_field() ?>
          <input type="hidden" name="manage_action" value="create">
          <div class="field" style="margin-bottom:0;">
            <label for="new_account_username">Username</label>
            <input id="new_account_username" name="username" type="text" required>
          </div>
          <div class="field" style="margin-bottom:0;">
            <label for="new_account_password">Temporary password</label>
            <input id="new_account_password" name="password" type="text" minlength="8" required placeholder="At least 8 characters">
          </div>
          <div class="field" style="margin-bottom:0;">
            <label for="new_account_role">Role</label>
            <select id="new_account_role" name="role">
              <option value="creator">Creator</option>
              <option value="moderator">Moderator</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Create</button>
        </form>
      </div>

      <!-- Roster -->
      <div class="panel">
        <div class="panel-head">
          <h3>All Accounts <span style="color:var(--text-muted); font-weight:400;">(<?= count($accounts) ?>)</span></h3>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th>Last login</th>
                <?php if ($admin['is_owner']): ?><th style="text-align:right;">Actions</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($accounts as $a): ?>
                <tr>
                  <td class="row-title">
                    <?= e($a['username']) ?>
                    <?php if ($a['is_owner']): ?><span class="badge badge-published" style="margin-left:8px;">Owner</span><?php endif; ?>
                    <?php if ((int) $a['id'] === (int) $admin['id']): ?><span style="color:var(--text-muted); font-size:0.76rem;"> (you)</span><?php endif; ?>
                  </td>
                  <td><?= e($roleLabels[$a['role']] ?? ucfirst($a['role'])) ?></td>
                  <td>
                    <?php if ($a['status'] === 'active'): ?>
                      <span class="badge badge-published">Active</span>
                    <?php else: ?>
                      <span class="badge badge-pending">Suspended</span>
                    <?php endif; ?>
                  </td>
                  <td><?= e(time_ago($a['created_at'])) ?><?= $a['created_by_username'] ? '<div style="color:var(--text-muted); font-size:0.76rem;">by ' . e($a['created_by_username']) . '</div>' : '' ?></td>
                  <td><?= $a['last_login'] ? e(time_ago($a['last_login'])) : '<span style="color:var(--text-muted);">Never</span>' ?></td>
                  <?php if ($admin['is_owner']): ?>
                    <td>
                      <?php if (!$a['is_owner'] && (int) $a['id'] !== (int) $admin['id']): ?>
                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                          <form method="post" action="/admin/actions/account_manage_action.php" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
                            <?php if ($a['status'] === 'active'): ?>
                              <input type="hidden" name="manage_action" value="suspend">
                              <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Suspend <?= e($a['username']) ?>? They will not be able to sign in until reactivated.');">Suspend</button>
                            <?php else: ?>
                              <input type="hidden" name="manage_action" value="activate">
                              <button type="submit" class="btn btn-secondary btn-sm">Reactivate</button>
                            <?php endif; ?>
                          </form>
                          <form method="post" action="/admin/actions/account_manage_action.php" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="manage_action" value="delete">
                            <input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:transparent; border:1px solid var(--pink-dark); color:var(--pink-soft);" onclick="return confirm('Permanently delete <?= e($a['username']) ?>? This cannot be undone.');">Delete</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if (!$admin['is_owner']): ?>
        <p style="color:var(--text-muted); font-size:0.8rem; margin-top:16px;">Only the site owner can suspend or delete accounts.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
