<?php
/**
 * Read-only source viewer for the admin role. Deliberately has no save
 * endpoint anywhere in this file or the app — see README for why an
 * in-app file editor isn't implemented. config/config.php (real DB
 * credentials) is hard-excluded regardless of extension.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
require_role($admin, 'admin');
$pdo = db();

$projectRoot = realpath(__DIR__ . '/..');
$allowedExtensions = ['php', 'css', 'js', 'sql', 'md'];
$allowedBasenames = ['.htaccess', '.gitignore'];
$hardExcluded = ['config/config.php']; // real credentials — never viewable, no matter what

/** Recursively collect every project file this viewer is allowed to show, as repo-relative paths. */
function collect_viewable_files(string $root, array $allowedExt, array $allowedNames, array $excluded): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }
        $relative = ltrim(str_replace($root, '', $fileInfo->getPathname()), '/\\');
        $relative = str_replace('\\', '/', $relative);

        if (str_starts_with($relative, '.git/')) {
            continue;
        }
        if (in_array($relative, $excluded, true)) {
            continue;
        }
        $basename = $fileInfo->getBasename();
        $ext = strtolower($fileInfo->getExtension());
        if (!in_array($ext, $allowedExt, true) && !in_array($basename, $allowedNames, true)) {
            continue;
        }
        $files[] = $relative;
    }
    sort($files);
    return $files;
}

$viewableFiles = collect_viewable_files($projectRoot, $allowedExtensions, $allowedBasenames, $hardExcluded);

$requested = $_GET['file'] ?? '';
$selected = in_array($requested, $viewableFiles, true) ? $requested : null;
$fileContent = null;
if ($selected) {
    $fileContent = file_get_contents($projectRoot . '/' . $selected);
}

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
<title>Website Code — <?= e(SITE_NAME) ?> Admin</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
  .code-frame {
    display: flex;
    height: calc(100vh - var(--nav-height) - 56px);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--bg-card);
  }
  .code-file-list { width: 280px; flex-shrink: 0; border-right: 1px solid var(--border); overflow-y: auto; padding: 10px; }
  .code-file-list a {
    display: block; padding: 7px 10px; border-radius: var(--radius-sm);
    font-size: 0.8rem; color: var(--text-secondary); word-break: break-all;
    font-family: ui-monospace, "SF Mono", Consolas, monospace;
  }
  .code-file-list a:hover { background: var(--bg-elevated); color: var(--text-primary); }
  .code-file-list a.active { background: rgba(255,45,117,0.14); color: var(--pink-soft); }
  .code-view { flex: 1; overflow: auto; padding: 20px; }
  .code-view pre {
    margin: 0; font-family: ui-monospace, "SF Mono", Consolas, monospace;
    font-size: 0.82rem; line-height: 1.6; color: var(--text-primary);
    white-space: pre-wrap; word-break: break-word;
  }
  .code-empty { padding: 40px 20px; color: var(--text-muted); font-size: 0.88rem; text-align: center; }
</style>
</head>
<body class="admin">

<div class="admin-shell">
  <?php $active = 'code'; include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="btn-icon mobile-only" data-sidebar-toggle aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h2>Website Code</h2>
      </div>
      <div class="topbar-actions">
        <span style="font-size:0.76rem; color:var(--text-muted);">Read-only</span>
      </div>
    </header>

    <div class="admin-content">
      <p style="color:var(--text-secondary); font-size:0.84rem; margin-bottom:16px;">
        View-only. There's no save action here on purpose — an in-app file
        editor that writes to the live server is a standing security risk
        (if any account is ever compromised, it becomes remote code
        execution). Deploy code changes through git instead.
      </p>
      <div class="code-frame">
        <div class="code-file-list">
          <?php foreach ($viewableFiles as $f): ?>
            <a href="/admin/code.php?file=<?= urlencode($f) ?>" class="<?= $f === $selected ? 'active' : '' ?>"><?= e($f) ?></a>
          <?php endforeach; ?>
        </div>
        <div class="code-view">
          <?php if ($selected !== null): ?>
            <pre><?= e($fileContent) ?></pre>
          <?php else: ?>
            <div class="code-empty">Select a file on the left to view its source.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
