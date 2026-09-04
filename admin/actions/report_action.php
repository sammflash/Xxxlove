<?php
/** POST-only, moderator+: resolve a pending report by removing or dismissing it. */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();
require_role($admin, 'moderator'); // creators don't get report/moderation access

if (!is_post()) {
    redirect('/admin/dashboard.php#reports');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('report_action', 'Your session expired — please try that again.');
    redirect('/admin/dashboard.php#reports');
}

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$reportId || !in_array($action, ['remove', 'dismiss'], true)) {
    redirect('/admin/dashboard.php#reports');
}

$pdo = db();

$stmt = $pdo->prepare("SELECT r.*, v.title AS video_title FROM reports r JOIN videos v ON v.id = r.video_id WHERE r.id = ? AND r.status = 'pending' LIMIT 1");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    flash_set('report_action', 'That report was already resolved.');
    redirect('/admin/dashboard.php#reports');
}

$pdo->beginTransaction();
try {
    if ($action === 'remove') {
        $pdo->prepare("UPDATE videos SET status = 'removed', removed_reason = ? WHERE id = ?")
            ->execute(['Reported: ' . $report['reason'], $report['video_id']]);
        $pdo->prepare("UPDATE reports SET status = 'removed', resolved_by = ?, resolved_at = NOW() WHERE id = ?")
            ->execute([$admin['id'], $reportId]);
        // Any other still-pending reports on the same (now removed) video resolve too.
        $pdo->prepare("UPDATE reports SET status = 'removed', resolved_by = ?, resolved_at = NOW() WHERE video_id = ? AND status = 'pending'")
            ->execute([$admin['id'], $report['video_id']]);
        $message = 'Removed "' . $report['video_title'] . '" and resolved the report.';
    } else {
        $pdo->prepare("UPDATE reports SET status = 'dismissed', resolved_by = ?, resolved_at = NOW() WHERE id = ?")
            ->execute([$admin['id'], $reportId]);
        $message = 'Dismissed the report on "' . $report['video_title'] . '".';
    }
    $pdo->commit();
    flash_set('report_action', $message);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[report_action] failed: ' . $e->getMessage());
    flash_set('report_action', 'Something went wrong — please try again.');
}

redirect('/admin/dashboard.php#reports');
