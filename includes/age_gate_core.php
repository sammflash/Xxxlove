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
