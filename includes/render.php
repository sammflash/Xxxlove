<?php
/** Shared rendering helpers for public pages. */

require_once __DIR__ . '/helpers.php';

function format_views(int $n): string
{
    if ($n >= 1_000_000) {
        return round($n / 1_000_000, 1) . 'M';
    }
    if ($n >= 1_000) {
        return round($n / 1_000, 1) . 'K';
    }
    return (string) $n;
}

/**
 * Render one video card. $video is a row from `videos` LEFT JOIN
 * `categories`, expected keys: id, title, slug, thumbnail_url, duration,
 * views, created_at, category_name.
 */
function render_video_card(array $video): string
{
    $isNew = strtotime($video['created_at']) >= strtotime('-7 days');
    $category = $video['category_name'] ?? 'General';
    $thumb = trim($video['thumbnail_url'] ?? '');

    ob_start();
    ?>
    <div class="video-card" data-video-id="<?= (int) $video['id'] ?>">
      <a href="/video.php?slug=<?= urlencode($video['slug']) ?>" class="video-card-hitbox" aria-label="Watch <?= e($video['title']) ?>">
        <div class="thumb">
          <?php if ($thumb !== ''): ?>
            <img src="<?= e($thumb) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <?php if ($isNew): ?><span class="thumb-badge">New</span><?php endif; ?>
          <?php if (!empty($video['duration'])): ?>
            <span class="thumb-duration"><?= e($video['duration']) ?></span>
          <?php endif; ?>
          <span class="play-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
        </div>
        <div class="card-body">
          <span class="card-category"><?= e($category) ?></span>
          <h3 class="card-title"><?= e($video['title']) ?></h3>
          <div class="card-meta">
            <span><?= format_views((int) $video['views']) ?> views</span>
            <span class="dot"></span>
            <span><?= e(time_ago($video['created_at'])) ?></span>
          </div>
        </div>
      </a>
      <button type="button" class="report-btn" data-video-id="<?= (int) $video['id'] ?>" data-video-title="<?= e($video['title']) ?>" title="Report this video" aria-label="Report this video">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg>
      </button>
    </div>
    <?php
    return ob_get_clean();
}
