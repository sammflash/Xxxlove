<?php
/**
 * POST /api/comment.php — submit a comment on a video. Public, no
 * account required (name + text only). Goes in as 'pending' — it does
 * not appear on the public site until a moderator approves it.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

function json_fail(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if (!is_post()) {
    json_fail('Method not allowed.', 405);
}

require_csrf(); // exits with its own JSON error on failure

$videoId = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
$userName = trim((string) ($_POST['user_name'] ?? ''));
$comment = trim((string) ($_POST['comment'] ?? ''));

if (!$videoId) {
    json_fail('Missing or invalid video.');
}
if ($userName === '' || mb_strlen($userName) > 80) {
    json_fail('Enter a name (max 80 characters).');
}
if ($comment === '' || mb_strlen($comment) > 1000) {
    json_fail('Enter a comment (max 1000 characters).');
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM videos WHERE id = ? AND status = 'published' LIMIT 1");
$stmt->execute([$videoId]);
if (!$stmt->fetch()) {
    json_fail('That video no longer exists.', 404);
}

// Basic anti-spam: cap how many comments one visitor can post in a short
// window. Comments aren't tied to a stored visitor identifier in the
// schema (kept anonymous by design, name-only) — rate-limit by session.
if (empty($_SESSION['comment_times'])) {
    $_SESSION['comment_times'] = [];
}
$_SESSION['comment_times'] = array_filter($_SESSION['comment_times'], fn($t) => $t > time() - 600);
if (count($_SESSION['comment_times']) >= 5) {
    json_fail('You have posted several comments recently. Please try again later.', 429);
}

$insert = $pdo->prepare('INSERT INTO comments (video_id, user_name, comment, status) VALUES (?, ?, ?, ?)');
$insert->execute([$videoId, $userName, $comment, 'pending']);

$_SESSION['comment_times'][] = time();

echo json_encode(['ok' => true, 'message' => 'Thanks — your comment is awaiting approval.']);
