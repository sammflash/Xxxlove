<?php
/** POST-only, admin role+: save website settings. */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();
require_role($admin, 'admin');

if (!is_post()) {
    redirect('/admin/settings.php');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('settings_error', 'Your session expired — please try that again.');
    redirect('/admin/settings.php');
}

$pdo = db();

$tagline = trim((string) ($_POST['site_tagline'] ?? ''));
$footerAbout = trim((string) ($_POST['footer_about'] ?? ''));
$contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
$socialX = trim((string) ($_POST['social_x'] ?? ''));
$socialInstagram = trim((string) ($_POST['social_instagram'] ?? ''));
$socialTelegram = trim((string) ($_POST['social_telegram'] ?? ''));
$maintenanceMode = isset($_POST['maintenance_mode']) ? '1' : '0';

$errors = [];
if (mb_strlen($tagline) > 200) {
    $errors[] = 'Tagline must be under 200 characters.';
}
if (mb_strlen($footerAbout) > 500) {
    $errors[] = 'Footer text must be under 500 characters.';
}
if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid contact email, or leave it blank.';
}
foreach (['social_x' => $socialX, 'social_instagram' => $socialInstagram, 'social_telegram' => $socialTelegram] as $label => $url) {
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a valid URL for ' . str_replace('social_', '', $label) . ', or leave it blank.';
    }
}

if ($errors) {
    flash_set('settings_error', implode(' ', $errors));
    redirect('/admin/settings.php');
}

$values = [
    'site_tagline'     => $tagline,
    'footer_about'     => $footerAbout,
    'contact_email'    => $contactEmail,
    'social_x'         => $socialX,
    'social_instagram' => $socialInstagram,
    'social_telegram'  => $socialTelegram,
    'maintenance_mode' => $maintenanceMode,
];

$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
foreach ($values as $key => $value) {
    $stmt->execute([$key, $value]);
}

flash_set('settings_success', 'Website settings saved.');
redirect('/admin/settings.php');
