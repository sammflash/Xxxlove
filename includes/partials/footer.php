<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-about">
        <a href="/index.php" class="logo">
          <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
        </a>
        <p><?= e(setting('footer_about', 'A premium curated video platform. Dark, minimal, and built for a fast, distraction-free viewing experience.')) ?></p>
        <div class="footer-social">
          <?php $socialX = setting('social_x'); $socialIg = setting('social_instagram'); $socialTg = setting('social_telegram'); ?>
          <?php if ($socialX): ?><a class="btn-icon" href="<?= e($socialX) ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow on X"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l16 16M20 4L4 20"/></svg></a><?php endif; ?>
          <?php if ($socialIg): ?><a class="btn-icon" href="<?= e($socialIg) ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow on Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/></svg></a><?php endif; ?>
          <?php if ($socialTg): ?><a class="btn-icon" href="<?= e($socialTg) ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow on Telegram"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-11 20-3-8-8-3z"/></svg></a><?php endif; ?>
        </div>
      </div>
      <div>
        <div class="footer-heading">Explore</div>
        <ul class="footer-links">
          <li><a href="/videos.php">Videos</a></li>
          <li><a href="/index.php#categories">Categories</a></li>
          <li><a href="/index.php#trending">Trending</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-heading">Company</div>
        <ul class="footer-links">
          <li><a href="#">About</a></li>
          <li><a href="<?= setting('contact_email') ? 'mailto:' . e(setting('contact_email')) : '#' ?>">Contact</a></li>
          <li><a href="#">Careers</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-heading">Legal</div>
        <ul class="footer-links">
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Content Removal</a></li>
          <li><a href="#">Compliance</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <div class="footer-legal">
        <span class="age-badge">18+ Verified Content Only</span>
      </div>
    </div>
  </div>
</footer>
