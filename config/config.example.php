<?php
/**
 * XPORN LOVERS — production configuration template.
 *
 * Copy this file to config.php (same folder) and fill in the 5 marked
 * values below — that's the only editing this deployment needs.
 * config.php is gitignored — never commit real credentials.
 *
 *   cp config/config.example.php config/config.php
 *
 * On Hostinger: hPanel → Databases → MySQL Databases gives you the
 * hostname/database/username/password for DB_HOST/DB_NAME/DB_USER/DB_PASS
 * below (Hostinger prefixes the database and username automatically —
 * copy them exactly as shown in hPanel, including that prefix).
 *
 * If your plan allows a folder one level ABOVE public_html, you can put
 * the real config.php there instead (e.g. /home/<user>/config.php) and
 * adjust the require_once path in includes/db.php accordingly — extra
 * safety since it's then outside the web root entirely. If your plan
 * only exposes public_html, leave config.php inside config/ as-is — the
 * bundled config/.htaccess blocks direct HTTP access to it either way.
 */

// ---- Database — EDIT THESE 4 -------------------------------------------
define('DB_HOST', 'localhost');                 // <-- 1. MySQL hostname (Hostinger: usually "localhost")
define('DB_NAME', 'CHANGE_ME_database_name');   // <-- 2. Database name (from hPanel)
define('DB_USER', 'CHANGE_ME_database_user');   // <-- 3. Database username (from hPanel)
define('DB_PASS', 'CHANGE_ME_database_password'); // <-- 4. Database password (from hPanel)
define('DB_CHARSET', 'utf8mb4');

// ---- Site — EDIT THIS 1 -------------------------------------------------
define('SITE_URL', 'https://CHANGE_ME_yourdomain.com'); // <-- 5. Your real domain, no trailing slash
define('SITE_NAME', 'XPORN LOVERS');

// ---- Security ------------------------------------------------------------
// Random 32+ byte secret used to sign the persistent age-verification
// cookie so it can't be forged by just setting a cookie value by hand.
// Generate a real one with: php -r "echo bin2hex(random_bytes(32));"
// Never reuse the placeholder below or any value from local dev.
define('AGE_GATE_SECRET', 'CHANGE_ME_generate_a_real_random_secret_before_deploying');

// Set true once the site is served over HTTPS (Hostinger's free SSL —
// turn this on right after activating it). Cookies (session + age-gate)
// are only marked Secure when this is true.
define('FORCE_HTTPS_COOKIES', false);

// ---- Environment ---------------------------------------------------------
// Keep this false in production. It controls whether a PHP error is ever
// shown in the response (never, when false — errors still get logged to
// the server's PHP error log either way) vs. shown with a full stack
// trace (only useful for local development, never on a live site).
define('APP_DEBUG', false);
