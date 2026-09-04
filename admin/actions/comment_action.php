<?php
/** POST-only, moderator+: approve, reject, or delete a comment. */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();
require_role($admin, 'moderator'); // creators don't get moderation access

if (!is_post()) {
    redirect('/admin/comments.php');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('comment_action', 'Your session expired — please try that again.');
    redirect('/admin/comments.php');
}

$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$commentId || !in_array($action, ['approve', 'reject', 'delete'], true)) {
    redirect('/admin/comments.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT c.*, v.title AS video_title FROM comments c JOIN videos v ON v.id = c.video_id WHERE c.id = ? LIMIT 1"
);
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    flash_set('comment_action', 'That comment no longer exists.');
    redirect('/admin/comments.php');
}

if ($action === 'approve') {
    $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$commentId]);
    flash_set('comment_action', 'Approved the comment on "' . $comment['video_title'] . '".');
} elseif ($action === 'reject') {
    $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?")->execute([$commentId]);
    flash_set('comment_action', 'Rejected the comment on "' . $comment['video_title'] . '".');
} else {
    $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$commentId]);
    flash_set('comment_action', 'Deleted the comment on "' . $comment['video_title'] . '".');
}

redirect('/admin/comments.php');
