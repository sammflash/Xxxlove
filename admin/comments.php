<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
require_role($admin, 'moderator'); // creators don't get moderation access
$pdo = db();

$statusFilter = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = '1=1';
$params = [];
if ($statusFilter !== '') {
    $where = 'c.status = ?';
    $params[] = $statusFilter;
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM comments c WHERE {$where}");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.*, v.title AS video_title, v.slug AS video_slug
        FROM comments c
        JOIN videos v ON v.id = c.video_id
        WHERE {$where}
        ORDER BY c.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$statusBadges = [
    'pending'  => ['class' => 'badge-pending',  'label' => 'Pending'],
    'approved' => ['class' => 'badge-published', 'label' => 'Approved'],
    'rejected' => ['class' => 'badge-rejected',  'label' => 'Rejected'],
];

function comments_page_url(int $p, string $status): string
{
    $params = ['page' => $p];
    if ($status) $params['status'] = $status;
    return '/admin/comments.php?' . http_build_query($params);
}

$commentActionMsg = flash_get('comment_action');
$pendingReportsCount = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$pendingCommentsCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Comments — <?= e(SITE_NAME) ?> Admin</title>
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
  <?php $active = 'comments'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Comments</h2>
      </div>
      <div class="topbar-actions">
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($commentActionMsg): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($commentActionMsg) ?></p>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-head">
          <h3>All Comments <span style="color:var(--text-muted); font-weight:400;">(<?= (int) $total ?>)</span></h3>
          <div class="chip-row">
            <a href="/admin/comments.php?status=pending" class="chip <?= $statusFilter === 'pending' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Pending</a>
            <a href="/admin/comments.php?status=approved" class="chip <?= $statusFilter === 'approved' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Approved</a>
            <a href="/admin/comments.php?status=rejected" class="chip <?= $statusFilter === 'rejected' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Rejected</a>
            <a href="/admin/comments.php?status=all" class="chip <?= $statusFilter === '' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">All</a>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Video</th>
                <th>Name</th>
                <th>Comment</th>
                <th>Posted</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($comments): foreach ($comments as $c):
                $badge = $statusBadges[$c['status']] ?? ['class' => 'badge-draft', 'label' => ucfirst($c['status'])];
              ?>
                <tr>
                  <td class="row-title"><a href="/video.php?slug=<?= urlencode($c['video_slug']) ?>" target="_blank" rel="noopener" style="color:inherit;"><?= e($c['video_title']) ?></a></td>
                  <td><?= e($c['user_name']) ?></td>
                  <td style="max-width:320px;"><?= e(mb_strimwidth($c['comment'], 0, 140, '…')) ?></td>
                  <td><?= e(time_ago($c['created_at'])) ?></td>
                  <td><span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span></td>
                  <td>
                    <form method="post" action="/admin/actions/comment_action.php" style="display:flex; gap:8px; justify-content:flex-end;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>">
                      <?php if ($c['status'] !== 'approved'): ?>
                        <button type="submit" name="action" value="approve" class="btn btn-secondary btn-sm">Approve</button>
                      <?php endif; ?>
                      <?php if ($c['status'] !== 'rejected'): ?>
                        <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm">Reject</button>
                      <?php endif; ?>
                      <button type="submit" name="action" value="delete" class="btn btn-primary btn-sm" onclick="return confirm('Permanently delete this comment?');">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="6" style="color:var(--text-secondary);">No comments<?= $statusFilter ? ' with this status' : '' ?> yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <div class="pagination" style="padding:20px;">
            <a href="<?= e(comments_page_url(max(1, $page - 1), $statusFilter)) ?>" aria-label="Previous page">‹</a>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a href="<?= e(comments_page_url($p, $statusFilter)) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= e(comments_page_url(min($totalPages, $page + 1), $statusFilter)) ?>" aria-label="Next page">›</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
