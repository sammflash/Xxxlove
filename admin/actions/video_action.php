<?php
/** POST-only: create, update, or delete a video. Any signed-in role (creator+). */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/uploads.php';

$admin = require_admin();

if (!is_post()) {
    redirect('/admin/videos.php');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('video_error', 'Your session expired — please try that again.');
    redirect('/admin/videos.php');
}

$pdo = db();
$action = $_POST['action'] ?? '';

/** Generate a unique slug from a title, appending -2, -3, ... on collision. */
function unique_video_slug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM videos WHERE slug = ?' . ($excludeId ? ' AND id != ?' : '');
        $params = $excludeId ? [$slug, $excludeId] : [$slug];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

if ($action === 'delete') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT title, video_url, thumbnail_url FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
            delete_uploaded_file($row['video_url']);
            delete_uploaded_file($row['thumbnail_url']);
            flash_set('video_success', 'Deleted "' . $row['title'] . '".');
        }
    }
    redirect('/admin/videos.php');
}

if (!in_array($action, ['create', 'update'], true)) {
    redirect('/admin/videos.php');
}

// For an update, load the existing row up front — needed to fall back to
// the current video/thumbnail file when no replacement was submitted,
// and to know what (if anything) to delete from disk afterward.
$existingVideo = null;
if ($action === 'update') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        redirect('/admin/videos.php');
    }
    $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->execute([$id]);
    $existingVideo = $stmt->fetch();
    if (!$existingVideo) {
        flash_set('video_error', 'That video no longer exists.');
        redirect('/admin/videos.php');
    }
}

// ---- Shared field validation ----
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
$duration = trim((string) ($_POST['duration'] ?? ''));
$status = $_POST['status'] ?? 'published';
$sourceType = $_POST['source_type'] ?? 'upload';
$featured = isset($_POST['featured']) ? 1 : 0;

$errors = [];
$newVideoFilePath = null;   // newly-saved file this request, for cleanup on validation failure
$newThumbFilePath = null;

if ($title === '' || mb_strlen($title) > 255) {
    $errors[] = 'Title is required (max 255 characters).';
}
if ($duration !== '' && !preg_match('/^\d{1,2}:\d{2}$/', $duration)) {
    $errors[] = 'Duration must look like 12:34.';
}
if (!in_array($status, ['published', 'unpublished', 'removed'], true)) {
    $status = 'published';
}
if ($categoryId) {
    $catCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $catCheck->execute([$categoryId]);
    if ((int) $catCheck->fetchColumn() === 0) {
        $categoryId = null;
    }
}
if (!in_array($sourceType, ['upload', 'url', 'embed'], true)) {
    $sourceType = 'upload';
}

// ---- Video source: upload file / direct URL / embed code ----
$finalVideoUrl = null;
$finalEmbedUrl = null;

if ($sourceType === 'upload') {
    $upload = handle_video_upload($_FILES['video_file'] ?? []);
    if ($upload['error']) {
        $errors[] = $upload['error'];
    } elseif ($upload['ok']) {
        $finalVideoUrl = $upload['path'];
        $newVideoFilePath = $upload['path'];
    } elseif ($existingVideo && $existingVideo['source_type'] === 'upload' && $existingVideo['video_url']) {
        $finalVideoUrl = $existingVideo['video_url']; // keep current file, none re-uploaded
    } else {
        $errors[] = 'Choose a video file to upload.';
    }
} elseif ($sourceType === 'url') {
    $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
    if ($videoUrl === '' || !filter_var($videoUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $videoUrl)) {
        $errors[] = 'Enter a valid video URL (starting with http:// or https://).';
    } else {
        $finalVideoUrl = $videoUrl;
    }
} elseif ($sourceType === 'embed') {
    $embedInput = trim((string) ($_POST['embed_code'] ?? ''));
    $extracted = extract_embed_src($embedInput);
    if (!$extracted) {
        $errors[] = 'Enter a valid embed code, or an https:// embed URL.';
    } else {
        $finalEmbedUrl = $extracted;
    }
}

// ---- Thumbnail: upload only, required on create, optional (keep current) on edit ----
$finalThumbnailUrl = null;
$thumbUpload = handle_thumbnail_upload($_FILES['thumbnail_file'] ?? []);
if ($thumbUpload['error']) {
    $errors[] = $thumbUpload['error'];
} elseif ($thumbUpload['ok']) {
    $finalThumbnailUrl = $thumbUpload['path'];
    $newThumbFilePath = $thumbUpload['path'];
} elseif ($existingVideo && $existingVideo['thumbnail_url']) {
    $finalThumbnailUrl = $existingVideo['thumbnail_url']; // keep current thumbnail
} elseif ($action === 'create') {
    $errors[] = 'Choose a thumbnail image to upload.';
}

// Creator-role accounts land on admin/dashboard.php (their "dashboard" is
// the Add Video form itself) — send them back there, not the full library,
// so adding several videos in a row never needs the "+ Add Video" button.
$canModerate = admin_has_role($admin, 'moderator');
$createFormUrl = $canModerate ? '/admin/videos.php?new=1' : '/admin/dashboard.php';

if ($errors) {
    // Don't leave orphaned files behind from this failed attempt.
    delete_uploaded_file($newVideoFilePath);
    delete_uploaded_file($newThumbFilePath);
    flash_set('video_error', implode(' ', $errors));
    $back = $action === 'update' ? '/admin/videos.php?edit=' . (int) ($_POST['id'] ?? 0) : $createFormUrl;
    redirect($back);
}

if ($action === 'create') {
    $slug = unique_video_slug($pdo, $title);
    $stmt = $pdo->prepare(
        'INSERT INTO videos (title, slug, description, category_id, video_url, source_type, embed_url, thumbnail_url, duration, status, featured, created_by, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $slug, $description ?: null, $categoryId, $finalVideoUrl, $sourceType, $finalEmbedUrl,
        $finalThumbnailUrl, $duration ?: null, 'published', $featured, $admin['id'], $admin['id'],
    ]);
    flash_set('video_success', 'Added "' . $title . '".');
    redirect($canModerate ? '/admin/videos.php' : '/admin/dashboard.php');
}

// ---- update ----
$id = (int) $existingVideo['id'];
$slug = unique_video_slug($pdo, $title, $id);
$stmt = $pdo->prepare(
    'UPDATE videos SET title = ?, slug = ?, description = ?, category_id = ?, video_url = ?, source_type = ?,
     embed_url = ?, thumbnail_url = ?, duration = ?, status = ?, featured = ?, updated_by = ? WHERE id = ?'
);
$stmt->execute([
    $title, $slug, $description ?: null, $categoryId, $finalVideoUrl, $sourceType, $finalEmbedUrl,
    $finalThumbnailUrl, $duration ?: null, $status, $featured, $admin['id'], $id,
]);

// Clean up replaced files now that the new state is safely saved.
if ($newVideoFilePath && $existingVideo['video_url'] !== $finalVideoUrl) {
    delete_uploaded_file($existingVideo['video_url']);
}
if ($newThumbFilePath && $existingVideo['thumbnail_url'] !== $finalThumbnailUrl) {
    delete_uploaded_file($existingVideo['thumbnail_url']);
}

flash_set('video_success', 'Saved changes to "' . $title . '".');
redirect('/admin/videos.php');
