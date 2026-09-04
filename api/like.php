<?php
/**
 * POST /api/like.php — like or dislike a video. Public, no account
 * required, de-duplicated per anonymous visitor (one vote per video;
 * clicking the same choice again removes it, clicking the other choice
 * switches it). Returns the fresh counts so the page can update without
 * a reload.
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
$type = $_POST['type'] ?? '';

if (!$videoId) {
    json_fail('Missing or invalid video.');
}
if (!in_array($type, ['like', 'dislike'], true)) {
    json_fail('Invalid vote type.');
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM videos WHERE id = ? AND status = 'published' LIMIT 1");
$stmt->execute([$videoId]);
if (!$stmt->fetch()) {
    json_fail('That video no longer exists.', 404);
}

$visitor = visitor_hash('like');

$existing = $pdo->prepare('SELECT type FROM likes WHERE video_id = ? AND visitor_identifier = ? LIMIT 1');
$existing->execute([$videoId, $visitor]);
$existingType = $existing->fetchColumn();

$myVote = $type;
if ($existingType === $type) {
    // Clicking the same choice again removes the vote.
    $pdo->prepare('DELETE FROM likes WHERE video_id = ? AND visitor_identifier = ?')->execute([$videoId, $visitor]);
    $myVote = null;
} else {
    // New vote or switching choice — UNIQUE KEY (video_id, visitor_identifier)
    // makes this race-safe even if the same visitor double-clicks.
    $pdo->prepare(
        'INSERT INTO likes (video_id, visitor_identifier, type) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE type = VALUES(type)'
    )->execute([$videoId, $visitor, $type]);
}

$counts = $pdo->prepare("SELECT type, COUNT(*) AS c FROM likes WHERE video_id = ? GROUP BY type");
$counts->execute([$videoId]);
$likeCount = 0;
$dislikeCount = 0;
foreach ($counts->fetchAll() as $row) {
    if ($row['type'] === 'like') $likeCount = (int) $row['c'];
    if ($row['type'] === 'dislike') $dislikeCount = (int) $row['c'];
}

echo json_encode(['ok' => true, 'likes' => $likeCount, 'dislikes' => $dislikeCount, 'my_vote' => $myVote]);
