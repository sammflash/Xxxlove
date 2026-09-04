<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/age_gate.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/render.php';

$pdo = db();

$videoCount = (int) $pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'published'")->fetchColumn();
$categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalViews = (int) $pdo->query("SELECT COALESCE(SUM(views), 0) FROM videos WHERE status = 'published'")->fetchColumn();

$featured = $pdo->query(
    "SELECT v.*, c.name AS category_name
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     WHERE v.status = 'published'
     ORDER BY v.created_at DESC
     LIMIT 8"
)->fetchAll();

$categories = $pdo->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();

$page_title = SITE_NAME;
$page_description = 'XPORN LOVERS — a premium, dark, curated video platform.';
$canonical_path = '/';
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>

<?php $active = 'home'; $show_admin_lock = true; include __DIR__ . '/includes/partials/navbar.php'; ?>

<main>
  <!-- Hero -->
  <section class="hero">
    <div class="container hero-content">
      <span class="eyebrow">Dark · Premium · Curated</span>
      <div class="hero-actions" style="margin-top:28px;">
        <a href="/videos.php" class="btn btn-primary">Watch Video</a>
        <a href="#featured" class="btn btn-secondary">View More</a>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <div class="container" style="margin-top:-1px;">
    <div class="stats-strip">
      <div class="stat-tile"><div class="stat-value"><?= format_views($videoCount) ?></div><div class="stat-label">Videos</div></div>
      <div class="stat-tile"><div class="stat-value"><?= (int) $categoryCount ?></div><div class="stat-label">Categories</div></div>
      <div class="stat-tile"><div class="stat-value"><?= format_views($totalViews) ?></div><div class="stat-label">Total Views</div></div>
      <div class="stat-tile"><div class="stat-value">HD</div><div class="stat-label">Quality</div></div>
    </div>
  </div>

  <!-- Featured -->
  <section class="section" id="featured">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow">Handpicked</span>
          <h2 class="section-title" style="margin-top:6px;">Featured Videos</h2>
        </div>
        <a href="/videos.php" class="link-more">View all →</a>
      </div>

      <?php if ($featured): ?>
        <div class="video-grid">
          <?php foreach ($featured as $video): ?>
            <?= render_video_card($video) ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--text-secondary);">No videos published yet — check back soon.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Categories -->
  <section class="section" id="categories" style="background:var(--bg-secondary); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow">Browse</span>
          <h2 class="section-title" style="margin-top:6px;">Categories</h2>
        </div>
      </div>
      <div class="chip-row">
        <a href="/videos.php" class="chip active">All</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/videos.php?category=<?= urlencode($cat['slug']) ?>" class="chip"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA band -->
  <section class="section">
    <div class="container">
      <div class="cta-band">
        <div>
          <h3>Get the best of <?= e(SITE_NAME) ?> every week</h3>
          <p>New releases, trending picks, and exclusive drops — straight to your inbox.</p>
        </div>
        <a href="#" class="btn btn-gradient">Subscribe Now</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
<?php include __DIR__ . '/includes/partials/report_modal.php'; ?>

<script src="/assets/js/main.js"></script>
</body>
</html>
