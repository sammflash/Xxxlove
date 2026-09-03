<?php
/**
 * POST /api/report.php — file a content report. Public, no account
 * required. Rate-limited per anonymous visitor identifier.
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
$reason  = trim((string) ($_POST['reason'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));

$allowedReasons = ['non_consensual', 'underage_concern', 'stolen_content', 'wrong_category', 'other'];

if (!$videoId) {
    json_fail('Missing or invalid video.');
}
if (!in_array($reason, $allowedReasons, true)) {
    json_fail('Please choose a valid reason.');
}
if (mb_strlen($details) > 1000) {
    $details = mb_substr($details, 0, 1000);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM videos WHERE id = ? LIMIT 1');
$stmt->execute([$videoId]);
if (!$stmt->fetch()) {
    json_fail('That video no longer exists.', 404);
}

$reporterHash = visitor_hash('report');

// Basic anti-spam: cap how many reports one visitor can file in a
// short window, and don't let the same visitor stack duplicate pending
// reports on the same video.
$recent = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE reporter_identifier = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)');
$recent->execute([$reporterHash]);
if ((int) $recent->fetchColumn() >= 5) {
    json_fail('You have submitted several reports recently. Please try again later.', 429);
}

$dup = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE video_id = ? AND reporter_identifier = ? AND status = 'pending'");
$dup->execute([$videoId, $reporterHash]);
if ((int) $dup->fetchColumn() > 0) {
    json_fail('You already reported this video — our team is reviewing it.');
}

$insert = $pdo->prepare('INSERT INTO reports (video_id, reason, details, reporter_identifier) VALUES (?, ?, ?, ?)');
$insert->execute([$videoId, $reason, $details !== '' ? $details : null, $reporterHash]);

echo json_encode(['ok' => true]);
