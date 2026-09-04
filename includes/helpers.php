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
