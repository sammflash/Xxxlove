<?php
/**
 * Pure age-verification helpers — no auto-executing gate logic here.
 * Safe to include from anywhere (the gate page itself, the action
 * handler, or age_gate.php's own check).
 */

require_once __DIR__ . '/session.php';

const AGE_GATE_COOKIE = 'xpl_age_ok';
const AGE_GATE_TTL = 60 * 60 * 24 * 30; // 30 days

function age_gate_sign(int $expires): string
{
    return hash_hmac('sha256', 'age_ok|' . $expires, AGE_GATE_SECRET);
}

function age_gate_is_verified(): bool
{
    if (!empty($_SESSION['age_verified'])) {
        return true;
    }

    $cookie = $_COOKIE[AGE_GATE_COOKIE] ?? '';
    $parts = explode('.', $cookie, 2);
    if (count($parts) !== 2) {
        return false;
    }
    [$expires, $sig] = $parts;
    if (!ctype_digit($expires) || (int) $expires < time()) {
        return false;
    }
    if (!hash_equals(age_gate_sign((int) $expires), $sig)) {
        return false;
    }

    $_SESSION['age_verified'] = true;
    return true;
}

function age_gate_grant(): void
{
    $_SESSION['age_verified'] = true;
    $expires = time() + AGE_GATE_TTL;
    setcookie(AGE_GATE_COOKIE, $expires . '.' . age_gate_sign($expires), [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => defined('FORCE_HTTPS_COOKIES') && FORCE_HTTPS_COOKIES,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Known link-preview scraper user agents (WhatsApp, Telegram, Facebook,
 * X/Twitter, Slack, Discord, LinkedIn, Skype, Pinterest, Reddit). These
 * bots exist only to read a page's <head> meta tags and build a share
 * card — they never execute JS, submit the age-gate form, or stream a
 * video — so this lets a shared link show the real title/thumbnail
 * instead of a generic "Age Verification" card.
 *
 * This is a per-request User-Agent check, not a session/cookie grant:
 * it never marks the *visitor* as age-verified, so a real person
 * clicking that same link still hits the real gate exactly as before —
 * only the preview-fetching bot's own request skips the interstitial.
 * User-Agent is trivially spoofable, so this is a UX nicety for social
 * previews, not a security boundary; the actual protections (age gate
 * for humans, unpublished/removed videos never rendering) are unchanged.
 */
function is_link_preview_bot(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') {
        return false;
    }
    return (bool) preg_match(
        '/WhatsApp|TelegramBot|facebookexternalhit|Facebot|Twitterbot|Slackbot|LinkedInBot|Discordbot|SkypeUriPreview|Pinterest\/|redditbot|vkShare|Iframely|W3C_Validator/i',
        $ua
    );
}
