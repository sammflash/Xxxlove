<?php
/**
 * Site-wide maintenance mode, toggled from Website Settings. Blocks the
 * public site with a branded holding page while leaving /admin fully
 * reachable (so staff can turn it back off, or keep working) and never
 * blocking the age gate itself (no point showing an interstitial in
 * front of another interstitial).
 *
 * Call check_maintenance_mode() from every public entry point, after
 * the age gate include and before any real work.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

function check_maintenance_mode(): void
{
    if (setting('maintenance_mode', '0') !== '1') {
        return;
    }
    if (current_admin() !== null) {
        return; // signed-in staff can still browse/preview the live site
    }

    http_response_code(503);
    header('Retry-After: 3600');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Under Maintenance — <?= e(SITE_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
      .maint-screen {
        min-height: 100vh; display: flex; align-items: center; justify-content: center;
        text-align: center; padding: 24px;
        background: radial-gradient(50% 50% at 50% 0%, rgba(255, 45, 117, 0.14) 0%, transparent 60%), var(--bg-primary);
      }
      .maint-screen h1 { margin-top: 20px; font-family: var(--font-display); font-size: 1.4rem; }
      .maint-screen p { color: var(--text-secondary); margin-top: 10px; max-width: 420px; }
    </style>
    </head>
    <body>
    <div class="maint-screen">
      <div>
        <div class="logo logo--lg" style="justify-content:center;">
          <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
        </div>
        <h1>We'll be right back</h1>
        <p><?= e(SITE_NAME) ?> is down for scheduled maintenance. Thanks for your patience.</p>
      </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}
