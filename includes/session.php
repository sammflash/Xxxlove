<?php
/**
 * Secure session bootstrap + CSRF token helpers.
 * Include this before any output on every page (public or admin).
 */

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => defined('FORCE_HTTPS_COOKIES') && FORCE_HTTPS_COOKIES,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('xpl_sess');
    session_start();
}

/**
 * A stable, unguessable per-visitor identifier that does NOT require an
 * account. Used to de-duplicate views/likes/reports without tracking
 * real identity. Stored as an httponly cookie, not readable by JS.
 */
function visitor_id(): string
{
    if (empty($_COOKIE['xpl_vid']) || !preg_match('/^[a-f0-9]{64}$/', $_COOKIE['xpl_vid'])) {
        $id = bin2hex(random_bytes(32));
        setcookie('xpl_vid', $id, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => defined('FORCE_HTTPS_COOKIES') && FORCE_HTTPS_COOKIES,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['xpl_vid'] = $id;
        return $id;
    }

    return $_COOKIE['xpl_vid'];
}

/** Hash of visitor id + a per-purpose salt, safe to store in the DB. */
function visitor_hash(string $purpose): string
{
    return hash('sha256', $purpose . '|' . visitor_id());
}

// ---- CSRF ---------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!csrf_verify($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Invalid or expired security token. Please refresh and try again.']);
        exit;
    }
}
