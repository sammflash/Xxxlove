<?php
/** Admin authentication: login, logout, session guard, basic rate limiting. */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, last_login FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    return $admin ?: null;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('/admin/login.php');
    }
    return $admin;
}

/**
 * Attempt an admin login. Returns ['ok' => bool, 'error' => ?string].
 * Applies a simple per-account lockout after repeated failures.
 */
function attempt_login(string $username, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // Constant-shape response whether or not the username exists, so we
    // don't leak which usernames are valid via timing/response differences.
    $dummyHash = '$2y$10$abcdefghijklmnopqrstuuVYNz0J8f8f8f8f8f8f8f8f8f8f8f8f8';

    if ($admin && !empty($admin['locked_until']) && strtotime($admin['locked_until']) > time()) {
        $waitMin = (int) ceil((strtotime($admin['locked_until']) - time()) / 60);
        return ['ok' => false, 'error' => "Too many attempts. Try again in {$waitMin} minute(s)."];
    }

    $valid = password_verify($password, $admin['password_hash'] ?? $dummyHash);

    if (!$admin || !$valid) {
        if ($admin) {
            $attempts = (int) $admin['failed_attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
                $attempts = 0;
            }
            $upd = db()->prepare('UPDATE admins SET failed_attempts = ?, locked_until = ? WHERE id = ?');
            $upd->execute([$attempts, $lockedUntil, $admin['id']]);
        }
        return ['ok' => false, 'error' => 'Incorrect username or password.'];
    }

    // Success: reset failure counter, regenerate session id (prevents
    // session fixation), record the login.
    $upd = db()->prepare('UPDATE admins SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?');
    $upd->execute([$admin['id']]);

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];

    return ['ok' => true, 'error' => null];
}

function logout_admin(): void
{
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
