<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/render.php';
require_once __DIR__ . '/../includes/uploads.php';

// Any signed-in role (creator and up) can manage videos.
$admin = require_admin();
$pdo = db();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// ---- Editing/adding: figure out which form (if any) to show ----
$editingVideo = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editingVideo = $stmt->fetch() ?: null;
}
$showForm = $editingVideo !== null || isset($_GET['new']);

// ---- Library listing (paginated) ----
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['published', 'unpublished', 'removed'];

$where = '1=1';
$params = [];
if (in_array($statusFilter, $validStatuses, true)) {
    $where = 'v.status = ?';
    $params[] = $statusFilter;
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM videos v WHERE {$where}");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT v.*, c.name AS category_name
        FROM videos v
        LEFT JOIN categories c ON c.id = v.category_id
        WHERE {$where}
        ORDER BY v.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();

$statusBadges = [
    'published'   => ['class' => 'badge-published', 'label' => 'Published'],
    'unpublished' => ['class' => 'badge-draft',      'label' => 'Unpublished'],
    'removed'     => ['class' => 'badge-pending',    'label' => 'Removed'],
];

function videos_lib_url(int $p, string $status): string
{
    $params = ['page' => $p];
    if ($status) $params['status'] = $status;
    return '/admin/videos.php?' . http_build_query($params);
}

$videoError = flash_get('video_error');
$videoSuccess = flash_get('video_success');
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
<title>Videos — <?= e(SITE_NAME) ?> Admin</title>
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
  <?php $active = 'videos'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Videos</h2>
      </div>
      <div class="topbar-actions">
        <?php if (!$showForm): ?>
          <a href="/admin/videos.php?new=1" class="btn btn-primary btn-sm">+ Add Video</a>
        <?php endif; ?>
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($videoError): ?>
        <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($videoError) ?></p>
      <?php endif; ?>
      <?php if ($videoSuccess): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($videoSuccess) ?></p>
      <?php endif; ?>

      <?php if ($showForm): ?>
        <!-- Add / Edit form -->
        <div class="panel" style="margin-bottom:24px;">
          <div class="panel-head">
            <h3><?= $editingVideo ? 'Edit Video' : 'Add Video' ?></h3>
            <a href="/admin/videos.php" class="btn btn-secondary btn-sm">Cancel</a>
          </div>
          <?php $currentSourceType = $editingVideo['source_type'] ?? 'upload'; ?>
          <form method="post" action="/admin/actions/video_action.php" enctype="multipart/form-data" style="padding:22px 20px; display:grid; grid-template-columns:1.3fr 1fr; gap:32px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editingVideo ? 'update' : 'create' ?>">
            <?php if ($editingVideo): ?><input type="hidden" name="id" value="<?= (int) $editingVideo['id'] ?>"><?php endif; ?>

            <div>
              <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" required value="<?= e($editingVideo['title'] ?? '') ?>">
              </div>
              <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= e($editingVideo['description'] ?? '') ?></textarea>
              </div>
              <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                  <option value="">— None —</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (isset($editingVideo['category_id']) && (int) $editingVideo['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label for="duration">Duration <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(e.g. 12:34)</span></label>
                <input id="duration" name="duration" type="text" pattern="^\d{1,2}:\d{2}$" placeholder="12:34" value="<?= e($editingVideo['duration'] ?? '') ?>">
              </div>
              <?php if ($editingVideo): ?>
                <div class="field">
                  <label for="status">Status</label>
                  <select id="status" name="status">
                    <option value="published" <?= $editingVideo['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="unpublished" <?= $editingVideo['status'] === 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
                    <option value="removed" <?= $editingVideo['status'] === 'removed' ? 'selected' : '' ?>>Removed</option>
                  </select>
                </div>
              <?php endif; ?>

              <div class="field">
                <label>Thumbnail <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(image upload<?= $editingVideo ? ' — optional, keeps the current one if left blank' : ', required' ?>)</span></label>
                <input id="thumbnail_file" name="thumbnail_file" type="file" accept="image/jpeg,image/png,image/webp" <?= $editingVideo ? '' : 'required' ?>>
              </div>
              <div id="thumb-preview-wrap" style="aspect-ratio:16/9; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--bg-elevated); overflow:hidden; display:<?= !empty($editingVideo['thumbnail_url']) ? 'block' : 'none' ?>;">
                <img id="thumb-preview" src="<?= e($editingVideo['thumbnail_url'] ?? '') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
              </div>
            </div>

            <div>
              <label style="display:block; font-size:0.75rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:var(--text-secondary); margin-bottom:8px;">Video Source</label>
              <div class="chip-row" id="source-type-tabs" style="margin-bottom:16px;">
                <label class="chip source-tab<?= $currentSourceType === 'upload' ? ' active' : '' ?>"><input type="radio" name="source_type" value="upload" style="display:none;" <?= $currentSourceType === 'upload' ? 'checked' : '' ?>> Upload File</label>
                <label class="chip source-tab<?= $currentSourceType === 'url' ? ' active' : '' ?>"><input type="radio" name="source_type" value="url" style="display:none;" <?= $currentSourceType === 'url' ? 'checked' : '' ?>> Direct URL</label>
                <label class="chip source-tab<?= $currentSourceType === 'embed' ? ' active' : '' ?>"><input type="radio" name="source_type" value="embed" style="display:none;" <?= $currentSourceType === 'embed' ? 'checked' : '' ?>> Embed Code</label>
              </div>

              <div class="source-pane" data-pane="upload">
                <div class="field">
                  <label for="video_file">Video file <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(.mp4, .webm, or .mov, up to 200MB)</span></label>
                  <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime">
                </div>
              </div>

              <div class="source-pane" data-pane="url" style="display:none;">
                <div class="field">
                  <label for="video_url">Video URL <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(direct .mp4/.webm link)</span></label>
                  <input id="video_url" name="video_url" type="url" placeholder="https://example.com/video.mp4" value="<?= $currentSourceType === 'url' ? e($editingVideo['video_url'] ?? '') : '' ?>">
                </div>
              </div>

              <div class="source-pane" data-pane="embed" style="display:none;">
                <div class="field">
                  <label for="embed_code">Embed code or URL <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(a full &lt;iframe&gt; snippet, or just the URL)</span></label>
                  <textarea id="embed_code" name="embed_code" rows="3" placeholder='&lt;iframe src="https://..."&gt;&lt;/iframe&gt;'><?= $currentSourceType === 'embed' ? e($editingVideo['embed_url'] ?? '') : '' ?></textarea>
                </div>
              </div>

              <?php if ($editingVideo && $currentSourceType !== 'embed' && !empty($editingVideo['video_url'])): ?>
                <video controls style="width:100%; aspect-ratio:16/9; background:#000; border-radius:var(--radius-sm); border:1px solid var(--border); margin-top:8px;">
                  <source src="<?= e($editingVideo['video_url']) ?>" type="video/mp4">
                </video>
              <?php elseif ($editingVideo && $currentSourceType === 'embed' && !empty($editingVideo['embed_url'])): ?>
                <iframe src="<?= e($editingVideo['embed_url']) ?>" sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer" style="width:100%; aspect-ratio:16/9; border:0; background:#000; border-radius:var(--radius-sm); border:1px solid var(--border); margin-top:8px;"></iframe>
              <?php endif; ?>
            </div>

            <div style="grid-column:1/-1;">
              <button type="submit" class="btn btn-primary"><?= $editingVideo ? 'Save Changes' : 'Add Video' ?></button>
            </div>
          </form>
        </div>
        <script>
          (function () {
            // Video source tabs
            const tabs = document.querySelectorAll('#source-type-tabs input[type="radio"]');
            const panes = document.querySelectorAll('.source-pane');
            function syncTabs() {
              tabs.forEach((t) => t.closest('.source-tab').classList.toggle('active', t.checked));
              panes.forEach((p) => { p.style.display = (p.dataset.pane === document.querySelector('#source-type-tabs input:checked')?.value) ? 'block' : 'none'; });
            }
            tabs.forEach((t) => t.addEventListener('change', syncTabs));
            syncTabs();

            // Thumbnail live preview from the chosen file (no network round-trip needed)
            const thumbFile = document.getElementById('thumbnail_file');
            const thumbWrap = document.getElementById('thumb-preview-wrap');
            const thumbImg = document.getElementById('thumb-preview');
            thumbFile.addEventListener('change', () => {
              const file = thumbFile.files[0];
              if (file) {
                thumbImg.src = URL.createObjectURL(file);
                thumbWrap.style.display = 'block';
              }
            });
          })();
        </script>
      <?php endif; ?>

      <!-- Library -->
      <div class="panel">
        <div class="panel-head">
          <h3>All Videos <span style="color:var(--text-muted); font-weight:400;">(<?= (int) $total ?>)</span></h3>
          <div class="chip-row">
            <a href="/admin/videos.php" class="chip <?= $statusFilter === '' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">All</a>
            <a href="/admin/videos.php?status=published" class="chip <?= $statusFilter === 'published' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Published</a>
            <a href="/admin/videos.php?status=unpublished" class="chip <?= $statusFilter === 'unpublished' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Unpublished</a>
            <a href="/admin/videos.php?status=removed" class="chip <?= $statusFilter === 'removed' ? 'active' : '' ?>" style="padding:6px 14px; font-size:0.7rem;">Removed</a>
          </div>
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
              <?php if ($videos): foreach ($videos as $v):
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
                  <td>
                    <div class="row-actions">
                      <button type="button" class="share-btn" aria-label="Share" data-share-url="<?= e(rtrim(SITE_URL, '/') . '/video.php?slug=' . urlencode($v['slug'])) ?>" data-share-title="<?= e($v['title']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg></button>
                      <a href="/admin/videos.php?edit=<?= (int) $v['id'] ?>" aria-label="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg></a>
                      <form method="post" action="/admin/actions/video_action.php" onsubmit="return confirm('Permanently delete this video? This cannot be undone.');" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                        <button type="submit" aria-label="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="6" style="color:var(--text-secondary);">No videos found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <div class="pagination" style="padding:20px;">
            <a href="<?= e(videos_lib_url(max(1, $page - 1), $statusFilter)) ?>" aria-label="Previous page">‹</a>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a href="<?= e(videos_lib_url($p, $statusFilter)) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= e(videos_lib_url(min($totalPages, $page + 1), $statusFilter)) ?>" aria-label="Next page">›</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/partials/share_popover.php'; ?>

<script src="/assets/js/admin.js"></script>
<script src="/assets/js/share.js"></script>
</body>
</html>
