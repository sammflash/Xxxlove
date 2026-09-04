<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Any signed-in role (creator and up) can manage categories — same tier as video management.
$admin = require_admin();
$pdo = db();

$editingCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editingCategory = $stmt->fetch() ?: null;
}
$showForm = $editingCategory !== null || isset($_GET['new']);

$categories = $pdo->query(
    "SELECT c.*, COUNT(v.id) AS video_count
     FROM categories c
     LEFT JOIN videos v ON v.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name"
)->fetchAll();

$categoryError = flash_get('category_error');
$categorySuccess = flash_get('category_success');
$pendingReportsCount = admin_has_role($admin, 'moderator')
    ? (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn()
    : 0;
$pendingCommentsCount = admin_has_role($admin, 'moderator')
    ? (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn()
    : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Categories — <?= e(SITE_NAME) ?> Admin</title>
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
  <?php $active = 'categories'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Categories</h2>
      </div>
      <div class="topbar-actions">
        <?php if (!$showForm): ?>
          <a href="/admin/categories.php?new=1" class="btn btn-primary btn-sm">+ Add Category</a>
        <?php endif; ?>
        <a href="/index.php" class="btn-icon" aria-label="View site" title="View site">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        </a>
      </div>
    </header>

    <div class="admin-content">
      <?php if ($categoryError): ?>
        <p style="background:rgba(255,45,117,0.12); border:1px solid var(--pink-dark); color:var(--pink-soft); border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($categoryError) ?></p>
      <?php endif; ?>
      <?php if ($categorySuccess): ?>
        <p style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.4); color:#4ADE80; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.85rem; margin-bottom:20px;"><?= e($categorySuccess) ?></p>
      <?php endif; ?>

      <?php if ($showForm): ?>
        <div class="panel" style="margin-bottom:24px;">
          <div class="panel-head">
            <h3><?= $editingCategory ? 'Edit Category' : 'Add Category' ?></h3>
            <a href="/admin/categories.php" class="btn btn-secondary btn-sm">Cancel</a>
          </div>
          <form method="post" action="/admin/actions/category_action.php" style="padding:22px 20px; max-width:480px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editingCategory ? 'update' : 'create' ?>">
            <?php if ($editingCategory): ?><input type="hidden" name="id" value="<?= (int) $editingCategory['id'] ?>"><?php endif; ?>

            <div class="field">
              <label for="name">Name</label>
              <input id="name" name="name" type="text" required maxlength="100" value="<?= e($editingCategory['name'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="slug">Slug <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional — auto-generated from the name if left blank)</span></label>
              <input id="slug" name="slug" type="text" maxlength="100" placeholder="auto-generated" value="<?= e($editingCategory['slug'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="description">Description <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional)</span></label>
              <textarea id="description" name="description" rows="3" maxlength="500"><?= e($editingCategory['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= $editingCategory ? 'Save Changes' : 'Add Category' ?></button>
          </form>
        </div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-head">
          <h3>All Categories <span style="color:var(--text-muted); font-weight:400;">(<?= count($categories) ?>)</span></h3>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Videos</th>
                <th>Description</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($categories): foreach ($categories as $cat): ?>
                <tr>
                  <td class="row-title"><?= e($cat['name']) ?></td>
                  <td><?= e($cat['slug']) ?></td>
                  <td><?= (int) $cat['video_count'] ?></td>
                  <td style="max-width:280px;"><?= $cat['description'] ? e(mb_strimwidth($cat['description'], 0, 80, '…')) : '<span style="color:var(--text-muted);">—</span>' ?></td>
                  <td>
                    <div class="row-actions">
                      <a href="/admin/categories.php?edit=<?= (int) $cat['id'] ?>" aria-label="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg></a>
                      <form method="post" action="/admin/actions/category_action.php" onsubmit="return confirm('Delete &quot;<?= e($cat['name']) ?>&quot;? <?= (int) $cat['video_count'] ?> video(s) in it will become uncategorized (General) — they will not be deleted.');" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                        <button type="submit" aria-label="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="5" style="color:var(--text-secondary);">No categories yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
