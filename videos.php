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
$categorySlug = trim((string) ($_GET['category'] ?? ''));

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();

$activeCategory = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === $categorySlug) {
        $activeCategory = $cat;
        break;
    }
}

$where = "v.status = 'published'";
$params = [];
if ($activeCategory) {
    $where .= ' AND v.category_id = ?';
    $params[] = $activeCategory['id'];
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM videos v WHERE {$where}");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
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

function videos_page_url(int $p, ?string $category): string
{
    $params = ['page' => $p];
    if ($category) {
        $params['category'] = $category;
    }
    return '/videos.php?' . http_build_query($params);
}

$page_title = $activeCategory ? e($activeCategory['name']) . ' Videos — ' . SITE_NAME : 'Videos — ' . SITE_NAME;
$page_description = 'Browse all videos on ' . SITE_NAME . '.';
$canonical_path = '/videos.php' . ($activeCategory ? '?category=' . urlencode($activeCategory['slug']) : '');
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>

<?php $active = 'videos'; include __DIR__ . '/includes/partials/navbar.php'; ?>

<main>
  <div class="page-head">
    <div class="container">
      <span class="eyebrow">All Videos</span>
      <h1 style="margin-top:10px;">Browse the full library</h1>
      <p><?= (int) $total ?> video<?= $total === 1 ? '' : 's' ?><?= $activeCategory ? ' in ' . e($activeCategory['name']) : '' ?>.</p>
    </div>
  </div>

  <section class="section" style="padding-top:36px;">
    <div class="container">
      <div class="chip-row" style="margin-bottom:28px;">
        <a href="/videos.php" class="chip<?= $activeCategory ? '' : ' active' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/videos.php?category=<?= urlencode($cat['slug']) ?>" class="chip<?= ($activeCategory && $activeCategory['id'] === $cat['id']) ? ' active' : '' ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
      </div>

      <?php if ($videos): ?>
        <div class="video-grid" id="video-grid">
          <?php foreach ($videos as $video): ?>
            <?= render_video_card($video) ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--text-secondary);">No videos found<?= $activeCategory ? ' in this category' : '' ?> yet.</p>
      <?php endif; ?>

      <?php if ($totalPages > 1): ?>
        <div class="pagination" aria-label="Pagination">
          <a href="<?= e(videos_page_url(max(1, $page - 1), $activeCategory['slug'] ?? null)) ?>" aria-label="Previous page">‹</a>
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= e(videos_page_url($p, $activeCategory['slug'] ?? null)) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <a href="<?= e(videos_page_url(min($totalPages, $page + 1), $activeCategory['slug'] ?? null)) ?>" aria-label="Next page">›</a>
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
