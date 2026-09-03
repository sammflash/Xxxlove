<?php
/** POST-only, admin-only: change username or password. Both optional/independent. */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();

if (!is_post()) {
    redirect('/admin/dashboard.php#account');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('account_error', 'Your session expired — please try again.');
    redirect('/admin/dashboard.php#account');
}

$pdo = db();
$form = $_POST['form'] ?? '';

// Re-fetch the current hash fresh (don't trust anything cached in session).
$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$admin['id']]);
$row = $stmt->fetch();

if ($form === 'username') {
    $newUsername = trim((string) ($_POST['new_username'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');

    if ($newUsername === '' || mb_strlen($newUsername) > 50) {
        flash_set('account_error', 'Enter a username up to 50 characters.');
    } elseif (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $newUsername)) {
        flash_set('account_error', 'Username can only contain letters, numbers, dots, dashes and underscores.');
    } elseif (!password_verify($currentPassword, $row['password_hash'])) {
        flash_set('account_error', 'Current password is incorrect.');
    } else {
        $dupe = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id != ? LIMIT 1');
        $dupe->execute([$newUsername, $admin['id']]);
        if ($dupe->fetch()) {
            flash_set('account_error', 'That username is already in use.');
        } else {
            $pdo->prepare('UPDATE admins SET username = ? WHERE id = ?')->execute([$newUsername, $admin['id']]);
            flash_set('account_success', 'Username updated. Use it next time you sign in.');
        }
    }
} elseif ($form === 'password') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($currentPassword, $row['password_hash'])) {
        flash_set('account_error', 'Current password is incorrect.');
    } elseif (mb_strlen($newPassword) < 8) {
        flash_set('account_error', 'New password must be at least 8 characters.');
    } elseif ($newPassword !== $confirmPassword) {
        flash_set('account_error', 'New password and confirmation do not match.');
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$newHash, $admin['id']]);
        flash_set('account_success', 'Password updated. Use it next time you sign in.');
    }
}

redirect('/admin/dashboard.php#account');
