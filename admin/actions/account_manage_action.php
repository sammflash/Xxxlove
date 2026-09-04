<?php
/**
 * POST-only: create an account (admin role+), or suspend/activate/delete
 * one (owner only — enforced here server-side, never just hidden in UI).
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();
require_role($admin, 'admin'); // only admin-role (and owner) accounts reach this at all

if (!is_post()) {
    redirect('/admin/accounts.php');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('accounts_error', 'Your session expired — please try that again.');
    redirect('/admin/accounts.php');
}

$pdo = db();
$manageAction = $_POST['manage_action'] ?? '';

if ($manageAction === 'create') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'creator';

    if (!in_array($role, ['creator', 'moderator', 'admin'], true)) {
        $role = 'creator'; // never trust the client for the role either
    }

    $errors = [];
    if ($username === '' || mb_strlen($username) > 50) {
        $errors[] = 'Username is required (max 50 characters).';
    } elseif (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, dots, dashes and underscores.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        $dupe = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $dupe->execute([$username]);
        if ($dupe->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    if ($errors) {
        flash_set('accounts_error', implode(' ', $errors));
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, role, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $hash, $role, $admin['id']]);
        flash_set('accounts_success', 'Created ' . $role . ' account "' . $username . '". Share the temporary password with them securely — they can change it any time from their own Account & Security panel.');
    }

    redirect('/admin/accounts.php');
}

// Everything past this point (suspend / activate / delete) is owner-only.
if (!$admin['is_owner']) {
    flash_set('accounts_error', 'Only the site owner can do that.');
    redirect('/admin/accounts.php');
}

$targetId = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);
if (!$targetId) {
    redirect('/admin/accounts.php');
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$targetId]);
$target = $stmt->fetch();

if (!$target) {
    flash_set('accounts_error', 'That account no longer exists.');
    redirect('/admin/accounts.php');
}
if ($target['is_owner'] || (int) $target['id'] === (int) $admin['id']) {
    flash_set('accounts_error', 'That account is protected and cannot be suspended or deleted.');
    redirect('/admin/accounts.php');
}

if ($manageAction === 'suspend') {
    $pdo->prepare("UPDATE admins SET status = 'suspended' WHERE id = ?")->execute([$targetId]);
    flash_set('accounts_success', 'Suspended "' . $target['username'] . '".');
} elseif ($manageAction === 'activate') {
    $pdo->prepare("UPDATE admins SET status = 'active', failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$targetId]);
    flash_set('accounts_success', 'Reactivated "' . $target['username'] . '".');
} elseif ($manageAction === 'delete') {
    $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$targetId]);
    flash_set('accounts_success', 'Deleted "' . $target['username'] . '".');
}

redirect('/admin/accounts.php');
