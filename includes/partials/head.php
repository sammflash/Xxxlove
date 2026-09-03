<?php
/**
 * Expects (all optional except none — sane defaults provided):
 *   $page_title, $page_description, $canonical_path, $extra_head (raw HTML string)
 */
$page_title       = $page_title       ?? SITE_NAME;
$page_description = $page_description ?? 'XPORN LOVERS — a premium, dark, curated video platform.';
$canonical_path   = $canonical_path   ?? ($_SERVER['REQUEST_URI'] ?? '/');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_description) ?>">
<link rel="canonical" href="<?= e(rtrim(SITE_URL, '/') . $canonical_path) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_description) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(rtrim(SITE_URL, '/') . $canonical_path) ?>">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<?= $extra_head ?? '' ?>
