<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/render.php';

$admin = require_admin();
$pdo = db();
$canModerate = admin_has_role($admin, 'moderator');

$stats = [
    'total_videos'     => (int) $pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'published_videos' => (int) $pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'published'")->fetchColumn(),
    'total_views'      => (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM videos')->fetchColumn(),
    'pending_reports'  => $canModerate ? (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn() : 0,
    'pending_comments' => $canModerate ? (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn() : 0,
];

$pendingReports = [];
$resolvedReports = [];
if ($canModerate) {
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
}

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
$dashboardError = flash_get('dashboard_error');
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
  <?php $active = 'dashboard'; $pendingReportsCount = $stats['pending_reports']; $pendingCommentsCount = $stats['pending_comments']; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Dashboard</h2>
      </div>
      <div class="topbar-actions">
        <a href="/admin/videos.php?new=1" class="btn btn-primary btn-sm">+ Add Video</a>
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($dashboardError): ?>
        <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($dashboardError) ?></p>
      <?php endif; ?>
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
        <?php if ($canModerate): ?>
          <div class="stat-card" style="<?= $stats['pending_reports'] > 0 ? 'border-color:rgba(255,45,117,0.5);' : '' ?>">
            <div class="stat-card-top">
              <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg></div>
              <?php if ($stats['pending_reports'] > 0): ?><span class="delta">needs review</span><?php endif; ?>
            </div>
            <div class="stat-value"><?= number_format($stats['pending_reports']) ?></div>
            <div class="stat-label">Pending Reports</div>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($canModerate): ?>
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
      <?php endif; ?>

      <!-- Recent Uploads -->
      <div class="panel" id="recent-uploads" style="scroll-margin-top:20px;">
        <div class="panel-head">
          <h3>Recent Uploads</h3>
          <a href="/admin/videos.php" class="btn btn-secondary btn-sm">Manage All Videos</a>
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
                    <button type="button" class="share-btn" aria-label="Share" data-share-url="<?= e(rtrim(SITE_URL, '/') . '/video.php?slug=' . urlencode($v['slug'])) ?>" data-share-title="<?= e($v['title']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg></button>
                    <a href="/admin/videos.php?edit=<?= (int) $v['id'] ?>" aria-label="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg></a>
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

<?php include __DIR__ . '/../includes/partials/share_popover.php'; ?>

<script src="/assets/js/admin.js"></script>
<script src="/assets/js/share.js"></script>
</body>
</html>
