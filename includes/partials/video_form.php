<?php
/**
 * Shared add/edit video form — included from admin/videos.php (creator+
 * library page) and admin/dashboard.php (creator-role landing page, so
 * a creator never has to click "+ Add Video" to get here).
 *
 * Expects: $editingVideo (array|null), $categories (array),
 *   $formCancelUrl (string|null — omit the Cancel button when null,
 *   e.g. on the dashboard where this form has nowhere else to "cancel" to).
 */
$formCancelUrl = $formCancelUrl ?? null;
$currentSourceType = $editingVideo['source_type'] ?? 'upload';
?>
<div class="panel" style="margin-bottom:24px;">
  <div class="panel-head">
    <h3><?= $editingVideo ? 'Edit Video' : 'Add Video' ?></h3>
    <?php if ($formCancelUrl): ?><a href="<?= e($formCancelUrl) ?>" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
  </div>
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
        <label class="check" style="text-transform:none; font-weight:500; color:var(--text-primary); font-size:0.85rem;">
          <input type="checkbox" name="featured" value="1" <?= !empty($editingVideo['featured']) ? 'checked' : '' ?> style="width:auto;">
          Feature on homepage
        </label>
      </div>

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
