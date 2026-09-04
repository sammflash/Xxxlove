<?php
/** Small shared utilities. */

/** Escape for safe HTML output. Use on every piece of DB/user data rendered into markup. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (empty($_SESSION['flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** Format a MySQL DATETIME as a short relative "x ago" string for display. */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 86400 * 30) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

/** URL-safe slug from a title: lowercase, ascii, hyphenated. */
function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

/**
 * Simple key/value site settings, read from the `settings` table.
 * Fetched once per request and cached in a static var — every setting
 * lookup after the first is free.
 */
function all_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        require_once __DIR__ . '/db.php';
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = all_settings();
    return $all[$key] ?? $default;
}

/** Escape a user-supplied string for safe use inside a LIKE pattern's %...% wildcards. */
function like_escape(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

/**
 * Turn a stored path/URL into an absolute URL: already-absolute
 * external links (old thumbnail_url values, seed data) pass through
 * unchanged; local /uploads/... paths get SITE_URL prefixed. Link
 * previews (WhatsApp, Telegram, etc.) fetch og:image directly, so it
 * must always be a fully-qualified URL, never a relative path.
 */
function absolute_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}
