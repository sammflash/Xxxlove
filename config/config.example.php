<?php
/**
 * XPORN LOVERS — site configuration template.
 *
 * Copy this file to config.php and fill in real values.
 * config.php is gitignored — never commit real credentials.
 *
 * On Hostinger: put the real config.php one level ABOVE public_html
 * if your plan allows it (e.g. /home/<user>/config.php) and adjust the
 * require_once path in includes/db.php accordingly. If your plan only
 * exposes public_html, leave config.php inside config/ — the bundled
 * .htaccess in that folder blocks direct HTTP access to it either way.
 */

// ---- Database -------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'xpornlovers_dev');
define('DB_USER', 'xpl_dev');
define('DB_PASS', 'dev_local_pw_change_me');
define('DB_CHARSET', 'utf8mb4');

// ---- Site -------------------------------------------------------------
define('SITE_URL', 'http://localhost:8000');
define('SITE_NAME', 'XPORN LOVERS');

// ---- Security ----------------------------------------------------------
// Random 32+ byte secret used to sign the persistent age-verification
// cookie so it can't be forged by just setting a cookie value by hand.
// Generate a real one with: bin2hex(random_bytes(32))
define('AGE_GATE_SECRET', 'CHANGE_ME_' . 'generate_a_real_random_secret_before_deploying');

// Set true once the site is served over HTTPS (Hostinger free SSL, etc.)
// Cookies (session + age-gate) are only marked Secure when this is true.
define('FORCE_HTTPS_COOKIES', false);

// ---- Environment --------------------------------------------------------
// Set to false in production — suppresses detailed PHP error output.
define('APP_DEBUG', true);
