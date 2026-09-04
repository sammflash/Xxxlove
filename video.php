<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/maintenance.php';
check_maintenance_mode(); // before the age gate — a visitor shouldn't verify their age just to hit a holding page
require_once __DIR__ . '/includes/age_gate.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/render.php';

$pdo = db();

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    require __DIR__ . '/errors/404.php';
    exit;
}

$stmt = $pdo->prepare(
    "SELECT v.*, c.name AS category_name
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     WHERE v.slug = ? AND v.status = 'published'
     LIMIT 1"
);
$stmt->execute([$slug]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    require __DIR__ . '/errors/404.php';
    exit;
}

// Anonymous, de-duplicated view count: one view per visitor per video
// per rolling 12 hours, no account required.
$vHash = visitor_hash('view');
$dupView = $pdo->prepare(
    "SELECT COUNT(*) FROM video_views
     WHERE video_id = ? AND visitor_identifier = ? AND viewed_at > (NOW() - INTERVAL 12 HOUR)"
);
$dupView->execute([$video['id'], $vHash]);
if ((int) $dupView->fetchColumn() === 0) {
    $pdo->prepare('INSERT INTO video_views (video_id, visitor_identifier) VALUES (?, ?)')->execute([$video['id'], $vHash]);
    $pdo->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$video['id']]);
    $video['views']++;
}

$relatedStmt = $pdo->prepare(
    "SELECT v.*, c.name AS category_name
     FROM videos v
     LEFT JOIN categories c ON c.id = v.category_id
     WHERE v.status = 'published' AND v.id != ?
       AND (v.category_id = ? OR ? IS NULL)
     ORDER BY (v.category_id = ?) DESC, v.created_at DESC
     LIMIT 4"
);
$relatedStmt->execute([$video['id'], $video['category_id'], $video['category_id'], $video['category_id']]);
$related = $relatedStmt->fetchAll();

// Likes/dislikes: counts + this visitor's own vote (if any), anonymous.
$likeCounts = $pdo->prepare('SELECT type, COUNT(*) AS c FROM likes WHERE video_id = ? GROUP BY type');
$likeCounts->execute([$video['id']]);
$likeCount = 0;
$dislikeCount = 0;
foreach ($likeCounts->fetchAll() as $row) {
    if ($row['type'] === 'like') $likeCount = (int) $row['c'];
    if ($row['type'] === 'dislike') $dislikeCount = (int) $row['c'];
}
$myVoteStmt = $pdo->prepare('SELECT type FROM likes WHERE video_id = ? AND visitor_identifier = ? LIMIT 1');
$myVoteStmt->execute([$video['id'], visitor_hash('like')]);
$myVote = $myVoteStmt->fetchColumn() ?: null;

// Comments: only approved ones are public.
$commentsStmt = $pdo->prepare(
    "SELECT user_name, comment, created_at FROM comments
     WHERE video_id = ? AND status = 'approved'
     ORDER BY created_at DESC
     LIMIT 50"
);
$commentsStmt->execute([$video['id']]);
$comments = $commentsStmt->fetchAll();

$page_title = e($video['title']) . ' — ' . SITE_NAME;
$page_description = $video['description'] ? mb_substr(strip_tags($video['description']), 0, 160) : ('Watch ' . $video['title'] . ' on ' . SITE_NAME . '.');
$canonical_path = '/video.php?slug=' . urlencode($video['slug']);
// Absolute thumbnail URL so link previews (WhatsApp, Telegram, etc.)
// can actually fetch and show it — a relative path won't resolve for them.
$page_image = absolute_url($video['thumbnail_url']);
$og_type = 'video.other';
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>

<?php $active = 'videos'; include __DIR__ . '/includes/partials/navbar.php'; ?>

<main>
  <section class="section" style="padding-bottom:0;">
    <div class="container" style="max-width:1100px;">
      <div style="border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--border); background:#000;">
        <?php if ($video['source_type'] === 'embed' && !empty($video['embed_url'])): ?>
          <iframe src="<?= e($video['embed_url']) ?>" allowfullscreen
                  sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer"
                  style="width:100%; display:block; aspect-ratio:16/9; border:0; background:#000;"></iframe>
        <?php else: ?>
          <video controls preload="metadata" style="width:100%; display:block; aspect-ratio:16/9; background:#000;"
                 <?php if (!empty($video['thumbnail_url'])): ?>poster="<?= e($video['thumbnail_url']) ?>"<?php endif; ?>>
            <source src="<?= e($video['video_url']) ?>" type="video/mp4">
            Your browser does not support the video tag. <a href="<?= e($video['video_url']) ?>" style="color:var(--pink-soft);">Open the video directly</a>.
          </video>
        <?php endif; ?>
      </div>

      <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-top:24px; flex-wrap:wrap;">
        <div style="max-width:720px;">
          <span class="card-category"><?= e($video['category_name'] ?? 'General') ?></span>
          <h1 style="font-family:var(--font-display); font-size:1.5rem; font-weight:700; margin-top:6px;"><?= e($video['title']) ?></h1>
          <div class="card-meta" style="margin-top:10px;">
            <span><?= format_views((int) $video['views']) ?> views</span>
            <span class="dot"></span>
            <span><?= e(time_ago($video['created_at'])) ?></span>
          </div>
          <?php if (!empty($video['description'])): ?>
            <p style="color:var(--text-secondary); margin-top:16px; line-height:1.6; font-size:0.92rem;"><?= nl2br(e($video['description'])) ?></p>
          <?php endif; ?>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;" id="like-dislike-bar" data-video-id="<?= (int) $video['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">
          <button type="button" class="btn btn-secondary like-btn<?= $myVote === 'like' ? ' is-active' : '' ?>" data-vote-type="like" style="border-radius:var(--radius-full); padding:11px 20px; gap:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-2px; margin-right:6px;"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
            <span id="like-count"><?= format_views($likeCount) ?></span>
          </button>
          <button type="button" class="btn btn-secondary dislike-btn<?= $myVote === 'dislike' ? ' is-active' : '' ?>" data-vote-type="dislike" style="border-radius:var(--radius-full); padding:11px 20px; gap:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-2px; margin-right:6px;"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>
            <span id="dislike-count"><?= format_views($dislikeCount) ?></span>
          </button>
          <button type="button" class="btn btn-secondary share-btn" data-share-url="<?= e(rtrim(SITE_URL, '/') . $canonical_path) ?>" data-share-title="<?= e($video['title']) ?>" style="border-radius:var(--radius-full); padding:11px 20px; gap:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-2px; margin-right:6px;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
            Share
          </button>
          <button type="button" class="btn btn-secondary report-btn" data-video-id="<?= (int) $video['id'] ?>" data-video-title="<?= e($video['title']) ?>" style="position:static; opacity:1; width:auto; height:auto; border-radius:var(--radius-full); padding:11px 20px; gap:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:-2px; margin-right:6px;"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg>
            Report
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Comments -->
  <section class="section" style="padding-top:0;">
    <div class="container" style="max-width:1100px;">
      <div class="section-head">
        <div>
          <span class="eyebrow">Discussion</span>
          <h2 class="section-title" style="margin-top:6px;">Comments <span style="color:var(--text-muted); font-weight:400;">(<?= count($comments) ?>)</span></h2>
        </div>
      </div>

      <form id="comment-form" class="comment-form" data-video-id="<?= (int) $video['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="video_id" value="<?= (int) $video['id'] ?>">
        <div class="field">
          <label for="comment-name">Name</label>
          <input id="comment-name" name="user_name" type="text" maxlength="80" required placeholder="Your name">
        </div>
        <div class="field">
          <label for="comment-text">Comment</label>
          <textarea id="comment-text" name="comment" rows="3" maxlength="1000" required placeholder="Share your thoughts…"></textarea>
        </div>
        <p class="comment-form-status" id="comment-form-status" role="status" aria-live="polite"></p>
        <button type="submit" class="btn btn-primary" id="comment-submit-btn">Post Comment</button>
      </form>

      <div class="comment-list">
        <?php if ($comments): foreach ($comments as $c): ?>
          <div class="comment-item">
            <div class="comment-item-head">
              <strong><?= e($c['user_name']) ?></strong>
              <span><?= e(time_ago($c['created_at'])) ?></span>
            </div>
            <p><?= nl2br(e($c['comment'])) ?></p>
          </div>
        <?php endforeach; else: ?>
          <p style="color:var(--text-secondary); font-size:0.88rem; padding:20px 0;">No comments yet — be the first to share your thoughts.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($related): ?>
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <span class="eyebrow">Keep Watching</span>
            <h2 class="section-title" style="margin-top:6px;">Related Videos</h2>
          </div>
        </div>
        <div class="video-grid">
          <?php foreach ($related as $r): ?>
            <?= render_video_card($r) ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
<?php include __DIR__ . '/includes/partials/report_modal.php'; ?>
<?php include __DIR__ . '/includes/partials/share_popover.php'; ?>

<script src="/assets/js/main.js"></script>
<script src="/assets/js/share.js"></script>
</body>
</html>
