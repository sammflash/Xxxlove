<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/maintenance.php';
check_maintenance_mode(); // before the age gate — a visitor shouldn't verify their age just to hit a holding page
require_once __DIR__ . '/includes/age_gate.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/render.php';

$pdo = db();

$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100); // a search box isn't a text field — cap it

$videos = [];
$total = 0;
$totalPages = 1;

if ($q !== '') {
    $like = '%' . like_escape($q) . '%';

    // MySQL's default LIKE escape character is already backslash, matching
    // what like_escape() produces — no explicit ESCAPE clause needed.
    $totalStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM videos v
         WHERE v.status = 'published' AND (v.title LIKE ? OR v.description LIKE ?)"
    );
    $totalStmt->execute([$like, $like]);
    $total = (int) $totalStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT v.*, c.name AS category_name
            FROM videos v
            LEFT JOIN categories c ON c.id = v.category_id
            WHERE v.status = 'published' AND (v.title LIKE ? OR v.description LIKE ?)
            ORDER BY v.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like]);
    $videos = $stmt->fetchAll();
}

function search_page_url(int $p, string $q): string
{
    return '/search.php?' . http_build_query(['q' => $q, 'page' => $p]);
}

$page_title = ($q !== '' ? 'Search: ' . $q . ' — ' : 'Search — ') . SITE_NAME;
$page_description = 'Search videos on ' . SITE_NAME . '.';
$canonical_path = '/search.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>

<?php $active = null; include __DIR__ . '/includes/partials/navbar.php'; ?>

<main>
  <div class="page-head">
    <div class="container">
      <span class="eyebrow">Search</span>
      <h1 style="margin-top:10px;"><?= $q !== '' ? 'Results for “' . e($q) . '”' : 'Search the library' ?></h1>
      <p><?php if ($q !== ''): ?><?= (int) $total ?> video<?= $total === 1 ? '' : 's' ?> found.<?php else: ?>Type something into the search box above to get started.<?php endif; ?></p>
    </div>
  </div>

  <section class="section" style="padding-top:36px;">
    <div class="container">
      <?php if ($q === ''): ?>
        <p style="color:var(--text-secondary);">Try a video title or a word from its description.</p>
      <?php elseif ($videos): ?>
        <div class="video-grid">
          <?php foreach ($videos as $video): ?>
            <?= render_video_card($video) ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--text-secondary);">No videos matched “<?= e($q) ?>”. Try a different search.</p>
      <?php endif; ?>

      <?php if ($totalPages > 1): ?>
        <div class="pagination" aria-label="Pagination">
          <a href="<?= e(search_page_url(max(1, $page - 1), $q)) ?>" aria-label="Previous page">‹</a>
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= e(search_page_url($p, $q)) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <a href="<?= e(search_page_url(min($totalPages, $page + 1), $q)) ?>" aria-label="Next page">›</a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
<?php include __DIR__ . '/includes/partials/report_modal.php'; ?>

<script src="/assets/js/main.js"></script>
</body>
</html>
