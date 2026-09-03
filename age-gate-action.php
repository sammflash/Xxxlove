<?php
/** Handles the age-gate form POST. Public — must work with no admin session. */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/age_gate_core.php';

if (!is_post() || !csrf_verify($_POST['csrf_token'] ?? null)) {
    redirect('/index.php');
}

$redirectTo = $_POST['redirect'] ?? '/index.php';
// Only ever redirect to a local path — never to an attacker-supplied host.
if (!is_string($redirectTo) || $redirectTo === '' || $redirectTo[0] !== '/' || str_starts_with($redirectTo, '//')) {
    $redirectTo = '/index.php';
}

if (($_POST['confirm'] ?? '') === 'yes') {
    age_gate_grant();
    redirect($redirectTo);
}

// "I am under 18" — do not grant access, send them somewhere neutral.
redirect('https://www.google.com');
