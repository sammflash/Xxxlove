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
      <label class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" placeholder="Search videos…" aria-label="Search">
      </label>
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
  <label class="search-box">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" placeholder="Search videos…" aria-label="Search">
  </label>
</div>
