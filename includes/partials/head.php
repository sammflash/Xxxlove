<?php
/**
 * Expects (all optional except none — sane defaults provided):
 *   $page_title, $page_description, $canonical_path, $page_image
 *   (absolute URL — set this for anything you want to preview nicely
 *   when shared on WhatsApp/Telegram/etc.), $og_type ('website'|'video.other'),
 *   $extra_head (raw HTML string)
 */
$page_title       = $page_title       ?? SITE_NAME;
$page_description = $page_description ?? 'XPORN LOVERS — a premium, dark, curated video platform.';
$canonical_path   = $canonical_path   ?? ($_SERVER['REQUEST_URI'] ?? '/');
$page_image       = $page_image       ?? null;
$og_type          = $og_type          ?? 'website';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_description) ?>">
<link rel="canonical" href="<?= e(rtrim(SITE_URL, '/') . $canonical_path) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_description) ?>">
<meta property="og:type" content="<?= e($og_type) ?>">
<meta property="og:url" content="<?= e(rtrim(SITE_URL, '/') . $canonical_path) ?>">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<?php if ($page_image): ?>
<meta property="og:image" content="<?= e($page_image) ?>">
<meta property="og:image:secure_url" content="<?= e($page_image) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= e($page_image) ?>">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= e($page_title) ?>">
<meta name="twitter:description" content="<?= e($page_description) ?>">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<?= $extra_head ?? '' ?>
