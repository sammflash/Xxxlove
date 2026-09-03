<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/render.php';

$admin = require_admin();
$pdo = db();

$stats = [
    'total_videos'     => (int) $pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'published_videos' => (int) $pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'published'")->fetchColumn(),
    'total_views'      => (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM videos')->fetchColumn(),
    'pending_reports'  => (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
];

$pendingReports = $pdo->query(
    "SELECT r.id, r.reason, r.details, r.created_at, v.id AS video_id, v.title AS video_title, v.status AS video_status
     FROM reports r
     JOIN videos v ON v.id = r.video_id
     WHERE r.status = 'pending'
     ORDER BY r.created_at ASC
     LIMIT 25"
)->fetchAll();

$resolvedReports = $pdo->query(
    "SELECT r.id, r.reason, r.status, r.resolved_at, v.title AS video_title
     FROM reports r
     JOIN videos v ON v.id = r.video_id
     WHERE r.status IN ('removed', 'dismissed')
     ORDER BY r.resolved_at DESC
     LIMIT 5"
)->fetchAll();

$recentVideos = $pdo->query(
    "SELECT v.*, c.name AS category_name
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     ORDER BY v.created_at DESC
     LIMIT 8"
)->fetchAll();

$reasonLabels = [
    'non_consensual'   => 'Non-consensual content',
    'underage_concern' => 'Underage concern',
    'stolen_content'   => 'Stolen / unauthorized upload',
    'wrong_category'   => 'Mislabeled or spam',
    'other'            => 'Other',
];

$statusBadges = [
    'published'   => ['class' => 'badge-published', 'label' => 'Published'],
    'unpublished' => ['class' => 'badge-draft',      'label' => 'Unpublished'],
    'removed'     => ['class' => 'badge-pending',    'label' => 'Removed'],
];

$accountSuccess = flash_get('account_success');
$accountError = flash_get('account_error');
$reportActionMsg = flash_get('report_action');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Dashboard — <?= e(SITE_NAME) ?> Admin</title>
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
  <div class="sidebar-scrim"></div>
  <aside class="sidebar">
    <div class="sidebar-head">
      <a href="/index.php" class="logo logo--sm">
        <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
      </a>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-group-label">Overview</div>
      <a href="/admin/dashboard.php" class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        Dashboard
      </a>
      <div class="sidebar-group-label">Content</div>
      <a href="#recent-uploads">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 8 6 4-6 4V8Z"/><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
        Videos
      </a>
      <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
        Categories
      </a>
      <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Blog Posts
      </a>
      <div class="sidebar-group-label">Moderation</div>
      <a href="#reports" style="justify-content:space-between;">
        <span style="display:flex; align-items:center; gap:12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg>
          Reports
        </span>
        <?php if ($stats['pending_reports'] > 0): ?>
          <span class="sidebar-badge"><?= (int) $stats['pending_reports'] ?></span>
        <?php endif; ?>
      </a>
      <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </a>
      <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Comments
      </a>
      <div class="sidebar-group-label">System</div>
      <a href="#account">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Account &amp; Security
      </a>
    </nav>
    <div class="sidebar-foot">
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($admin['username'], 0, 1))) ?></div>
      <div class="who">
        <strong><?= e($admin['username']) ?></strong>
        <span><a href="/admin/logout.php" style="color:var(--text-muted);">Sign out</a></span>
      </div>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Dashboard</h2>
      </div>
      <div class="topbar-actions">
        <a href="#" class="btn btn-primary btn-sm">+ Add Video</a>
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($reportActionMsg): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;">
          <?= e($reportActionMsg) ?>
        </p>
      <?php endif; ?>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-card-top">
            <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 8 6 4-6 4V8Z"/><rect x="3" y="3" width="18" height="18" rx="3"/></svg></div>
          </div>
          <div class="stat-value"><?= number_format($stats['total_videos']) ?></div>
          <div class="stat-label">Total Videos</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-top">
            <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></div>
          </div>
          <div class="stat-value"><?= number_format($stats['published_videos']) ?></div>
          <div class="stat-label">Published Videos</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-top">
            <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
          </div>
          <div class="stat-value"><?= format_views($stats['total_views']) ?></div>
          <div class="stat-label">Total Views</div>
        </div>
        <div class="stat-card" style="<?= $stats['pending_reports'] > 0 ? 'border-color:rgba(255,45,117,0.5);' : '' ?>">
          <div class="stat-card-top">
            <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg></div>
            <?php if ($stats['pending_reports'] > 0): ?><span class="delta">needs review</span><?php endif; ?>
          </div>
          <div class="stat-value"><?= number_format($stats['pending_reports']) ?></div>
          <div class="stat-label">Pending Reports</div>
        </div>
      </div>

      <!-- Reports -->
      <div class="panel" id="reports" style="margin-bottom:24px; scroll-margin-top:20px;">
        <div class="panel-head">
          <h3>Reports<?= $stats['pending_reports'] > 0 ? ' <span style="color:var(--pink-soft); font-weight:600;">(' . (int) $stats['pending_reports'] . ' pending)</span>' : '' ?></h3>
        </div>
        <?php if ($pendingReports): ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Video</th>
                  <th>Reason</th>
                  <th>Details</th>
                  <th>Reported</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingReports as $r): ?>
                  <tr>
                    <td class="row-title"><?= e($r['video_title']) ?></td>
                    <td><?= e($reasonLabels[$r['reason']] ?? $r['reason']) ?></td>
                    <td style="max-width:240px;"><?= $r['details'] ? e(mb_strimwidth($r['details'], 0, 80, '…')) : '<span style="color:var(--text-muted);">—</span>' ?></td>
                    <td><?= e(time_ago($r['created_at'])) ?></td>
                    <td>
                      <form method="post" action="/admin/actions/report_action.php" style="display:flex; gap:8px; justify-content:flex-end;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" name="action" value="dismiss" class="btn btn-secondary btn-sm">Dismiss</button>
                        <button type="submit" name="action" value="remove" class="btn btn-primary btn-sm" onclick="return confirm('Remove this video? It will be taken down immediately.');">Remove Video</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div style="padding:28px 20px; color:var(--text-secondary); font-size:0.88rem;">No pending reports. New reports from visitors will appear here.</div>
        <?php endif; ?>

        <?php if ($resolvedReports): ?>
          <div style="padding:16px 20px; border-top:1px solid var(--border);">
            <div class="sidebar-group-label" style="padding-left:0;">Recently resolved</div>
            <?php foreach ($resolvedReports as $r): ?>
              <div style="display:flex; justify-content:space-between; gap:12px; padding:8px 0; font-size:0.82rem; color:var(--text-secondary); border-bottom:1px solid var(--border);">
                <span><?= e($r['video_title']) ?> — <?= e($reasonLabels[$r['reason']] ?? $r['reason']) ?></span>
                <span class="badge <?= $r['status'] === 'removed' ? 'badge-pending' : 'badge-draft' ?>"><?= $r['status'] === 'removed' ? 'Removed' : 'Dismissed' ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Recent Uploads -->
      <div class="panel" id="recent-uploads" style="scroll-margin-top:20px;">
        <div class="panel-head">
          <h3>Recent Uploads</h3>
          <a href="#" class="btn btn-secondary btn-sm">Manage All Videos</a>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Video</th>
                <th>Category</th>
                <th>Uploaded</th>
                <th>Views</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentVideos): foreach ($recentVideos as $v):
                $badge = $statusBadges[$v['status']] ?? ['class' => 'badge-draft', 'label' => ucfirst($v['status'])];
              ?>
                <tr>
                  <td>
                    <div class="row-media">
                      <div class="row-thumb" <?= $v['thumbnail_url'] ? 'style="background-image:url(\'' . e($v['thumbnail_url']) . '\'); background-size:cover; background-position:center;"' : '' ?>></div>
                      <div class="row-title"><?= e($v['title']) ?><span>ID #<?= (int) $v['id'] ?></span></div>
                    </div>
                  </td>
                  <td><?= e($v['category_name'] ?? 'General') ?></td>
                  <td><?= e(time_ago($v['created_at'])) ?></td>
                  <td><?= number_format((int) $v['views']) ?></td>
                  <td><span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span></td>
                  <td><div class="row-actions">
                    <button aria-label="Edit" title="Editing coming soon" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg></button>
                    <button aria-label="Delete" title="Use Reports to remove a video" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg></button>
                  </div></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="6" style="color:var(--text-secondary);">No videos yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Account & Security -->
      <div class="panel" id="account" style="margin-top:24px; scroll-margin-top:20px;">
        <div class="panel-head">
          <h3>Account &amp; Security</h3>
        </div>
        <div style="padding:22px 20px; display:grid; grid-template-columns:1fr 1fr; gap:32px;">
          <div>
            <?php if ($accountError): ?>
              <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:10px 14px; font-size:0.82rem; margin-bottom:16px;"><?= e($accountError) ?></p>
            <?php endif; ?>
            <?php if ($accountSuccess): ?>
              <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:10px 14px; font-size:0.82rem; margin-bottom:16px;"><?= e($accountSuccess) ?></p>
            <?php endif; ?>

            <h4 style="font-size:0.85rem; margin-bottom:14px;">Change username</h4>
            <form method="post" action="/admin/actions/account_action.php">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="username">
              <div class="field">
                <label for="new_username">New username</label>
                <input id="new_username" name="new_username" type="text" value="<?= e($admin['username']) ?>" required>
              </div>
              <div class="field">
                <label for="username_current_password">Current password</label>
                <input id="username_current_password" name="current_password" type="password" placeholder="••••••••" required>
              </div>
              <button type="submit" class="btn btn-secondary btn-sm">Update Username</button>
            </form>
          </div>
          <div>
            <h4 style="font-size:0.85rem; margin-bottom:14px; visibility:hidden;">.</h4>
            <form method="post" action="/admin/actions/account_action.php">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="password">
              <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" placeholder="••••••••" required>
              </div>
              <div class="field">
                <label for="new_password">New password</label>
                <input id="new_password" name="new_password" type="password" placeholder="At least 8 characters" minlength="8" required>
              </div>
              <div class="field">
                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" name="confirm_password" type="password" placeholder="••••••••" minlength="8" required>
              </div>
              <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
