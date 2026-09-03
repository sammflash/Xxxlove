<?php
/**
 * Site-wide age verification gate.
 *
 * Include this at the very top of every public-facing page, after
 * session.php but before any real page output. If the visitor hasn't
 * verified, it renders a full-page interstitial and exits — the real
 * page never renders. Verification is remembered via the session AND a
 * signed cookie (HMAC'd with AGE_GATE_SECRET, see age_gate_core.php) so
 * returning visitors aren't re-gated every single browser session,
 * while the cookie can't simply be hand-set to bypass the check.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/age_gate_core.php';

if (age_gate_is_verified()) {
    return; // Verified — let the real page render.
}

// Not verified: render the gate and stop. Nothing below this file's
// output belongs to the requested page.
http_response_code(200);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Age Verification — <?= e(SITE_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
  .gate-screen {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background:
      radial-gradient(50% 50% at 50% 0%, rgba(255, 45, 117, 0.14) 0%, transparent 60%),
      var(--bg-primary);
  }
  .gate-card {
    width: 100%;
    max-width: 460px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 40px 34px;
    box-shadow: var(--shadow-card);
    text-align: center;
  }
  .gate-card .logo { justify-content: center; margin-bottom: 18px; }
  .gate-card h1 {
    font-family: var(--font-display);
    font-size: 1.35rem;
    font-weight: 700;
    margin-bottom: 12px;
  }
  .gate-card p {
    color: var(--text-secondary);
    font-size: 0.88rem;
    line-height: 1.6;
  }
  .gate-notice {
    margin-top: 18px;
    padding: 14px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-elevated);
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-align: left;
  }
  .gate-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 26px; }
  .gate-actions .btn { width: 100%; }
  .gate-legal { margin-top: 20px; font-size: 0.75rem; color: var(--text-muted); }
  .gate-legal a { color: var(--text-muted); text-decoration: underline; }
  .gate-legal a:hover { color: var(--pink-soft); }
</style>
</head>
<body class="admin">
<div class="gate-screen">
  <div class="gate-card">
    <a href="/index.php" class="logo logo--lg" aria-label="<?= e(SITE_NAME) ?> home" onclick="return false;">
      <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
    </a>
    <h1>You must be 18 or older to enter</h1>
    <p>This website contains adult content intended only for adults. By entering, you confirm you are at least 18 years old (or the age of majority where you live) and that viewing adult content is legal in your location.</p>

    <div class="gate-notice">
      All content on this site depicts consenting adult performers of verified legal age. See our
      <a href="#" style="color:var(--pink-soft);">Compliance</a> and
      <a href="#" style="color:var(--pink-soft);">Content Removal</a> pages for details.
    </div>

    <form action="/age-gate-action.php" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? '/index.php') ?>">
      <div class="gate-actions">
        <button type="submit" name="confirm" value="yes" class="btn btn-primary">I am 18 or older — Enter</button>
        <button type="submit" name="confirm" value="no" class="btn btn-secondary">I am under 18 — Exit</button>
      </div>
    </form>

    <p class="gate-legal">
      By entering you also agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
    </p>
  </div>
</div>
</body>
</html>
<?php
exit;
