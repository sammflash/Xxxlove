<?php
/** Expects: $active ('home'|'videos'|null) */
$active = $active ?? null;
?>
<header class="navbar">
  <div class="container navbar-inner">
    <a href="/index.php" class="logo" aria-label="<?= e(SITE_NAME) ?> home">
      <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
    </a>
    <nav class="nav-links" aria-label="Primary">
      <a href="/index.php"<?= $active === 'home' ? ' class="active"' : '' ?>>Home</a>
      <a href="/videos.php"<?= $active === 'videos' ? ' class="active"' : '' ?>>Videos</a>
      <a href="/index.php#categories">Categories</a>
    </nav>
    <div class="nav-actions">
      <form class="search-box" action="/search.php" method="get" role="search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" name="q" placeholder="Search videos…" aria-label="Search" maxlength="100" value="<?= e($_GET['q'] ?? '') ?>">
      </form>
      <?php if (!empty($show_admin_lock)): ?>
        <a href="/admin/" class="btn-icon" aria-label="Admin" title="Admin">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </a>
      <?php endif; ?>
      <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span></button>
    </div>
  </div>
</header>
<div class="mobile-drawer">
  <nav class="nav-links" aria-label="Mobile primary">
    <a href="/index.php"<?= $active === 'home' ? ' class="active"' : '' ?>>Home</a>
    <a href="/videos.php"<?= $active === 'videos' ? ' class="active"' : '' ?>>Videos</a>
    <a href="/index.php#categories">Categories</a>
  </nav>
  <form class="search-box" action="/search.php" method="get" role="search">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" name="q" placeholder="Search videos…" aria-label="Search" maxlength="100" value="<?= e($_GET['q'] ?? '') ?>">
  </form>
</div>
