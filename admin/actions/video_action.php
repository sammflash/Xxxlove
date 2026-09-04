<?php
/** POST-only: create, update, or delete a video. Any signed-in role (creator+). */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

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
        $stmt = $pdo->prepare('SELECT title FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $title = $stmt->fetchColumn();
        if ($title !== false) {
            $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
            flash_set('video_success', 'Deleted "' . $title . '".');
        }
    }
    redirect('/admin/videos.php');
}

if (!in_array($action, ['create', 'update'], true)) {
    redirect('/admin/videos.php');
}

// ---- Shared validation for create + update ----
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
$videoUrl = trim((string) ($_POST['video_url'] ?? ''));
$thumbnailUrl = trim((string) ($_POST['thumbnail_url'] ?? ''));
$duration = trim((string) ($_POST['duration'] ?? ''));
$status = $_POST['status'] ?? 'published';

$errors = [];
if ($title === '' || mb_strlen($title) > 255) {
    $errors[] = 'Title is required (max 255 characters).';
}
if ($videoUrl === '' || !filter_var($videoUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $videoUrl)) {
    $errors[] = 'Enter a valid video URL (starting with http:// or https://).';
}
if ($thumbnailUrl !== '' && (!filter_var($thumbnailUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $thumbnailUrl))) {
    $errors[] = 'Thumbnail URL must be a valid http(s) link, or left blank.';
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

if ($errors) {
    flash_set('video_error', implode(' ', $errors));
    $back = $action === 'update' ? '/admin/videos.php?edit=' . (int) ($_POST['id'] ?? 0) : '/admin/videos.php?new=1';
    redirect($back);
}

if ($action === 'create') {
    $slug = unique_video_slug($pdo, $title);
    $stmt = $pdo->prepare(
        'INSERT INTO videos (title, slug, description, category_id, video_url, thumbnail_url, duration, status, created_by, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $slug, $description ?: null, $categoryId, $videoUrl, $thumbnailUrl ?: null,
        $duration ?: null, 'published', $admin['id'], $admin['id'],
    ]);
    flash_set('video_success', 'Added "' . $title . '".');
    redirect('/admin/videos.php');
}

// update
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('/admin/videos.php');
}
$existing = $pdo->prepare('SELECT id, title FROM videos WHERE id = ?');
$existing->execute([$id]);
if (!$existing->fetch()) {
    flash_set('video_error', 'That video no longer exists.');
    redirect('/admin/videos.php');
}

$slug = unique_video_slug($pdo, $title, $id);
$stmt = $pdo->prepare(
    'UPDATE videos SET title = ?, slug = ?, description = ?, category_id = ?, video_url = ?,
     thumbnail_url = ?, duration = ?, status = ?, updated_by = ? WHERE id = ?'
);
$stmt->execute([
    $title, $slug, $description ?: null, $categoryId, $videoUrl, $thumbnailUrl ?: null,
    $duration ?: null, $status, $admin['id'], $id,
]);
flash_set('video_success', 'Saved changes to "' . $title . '".');
redirect('/admin/videos.php');
