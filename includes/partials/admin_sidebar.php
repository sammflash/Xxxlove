<?php
/**
 * Expects: $admin (current_admin() row), $active
 *   ('dashboard'|'videos'|'accounts'|'code'), $pendingReportsCount (int, optional)
 */
$active = $active ?? '';
$pendingReportsCount = $pendingReportsCount ?? 0;
$canModerate = admin_has_role($admin, 'moderator');
$canAdminister = admin_has_role($admin, 'admin');
$roleLabels = ['creator' => 'Creator', 'moderator' => 'Moderator', 'admin' => 'Admin'];
$roleLabel = ($admin['is_owner'] ?? false) ? 'Owner' : ($roleLabels[$admin['role']] ?? ucfirst($admin['role']));
?>
<div class="sidebar-scrim"></div>
<aside class="sidebar">
  <div class="sidebar-head">
    <a href="/index.php" class="logo logo--sm">
      <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
    </a>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-group-label">Overview</div>
    <a href="/admin/dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
      Dashboard
    </a>
    <div class="sidebar-group-label">Content</div>
    <a href="/admin/videos.php" class="<?= $active === 'videos' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 8 6 4-6 4V8Z"/><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
      Videos
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
      Categories
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Blog Posts
    </a>
    <?php if ($canModerate): ?>
      <div class="sidebar-group-label">Moderation</div>
      <a href="/admin/dashboard.php#reports" style="justify-content:space-between;">
        <span style="display:flex; align-items:center; gap:12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22V3"/></svg>
          Reports
        </span>
        <?php if ($pendingReportsCount > 0): ?>
          <span class="sidebar-badge"><?= (int) $pendingReportsCount ?></span>
        <?php endif; ?>
      </a>
      <a href="#">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Comments
      </a>
    <?php endif; ?>
    <?php if ($canAdminister): ?>
      <div class="sidebar-group-label">Administration</div>
      <a href="/admin/accounts.php" class="<?= $active === 'accounts' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Manage Accounts
      </a>
      <a href="/admin/code.php" class="<?= $active === 'code' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Website Code
      </a>
    <?php endif; ?>
    <div class="sidebar-group-label">System</div>
    <a href="/admin/dashboard.php#account">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Account &amp; Security
    </a>
  </nav>
  <div class="sidebar-foot">
    <div class="avatar"><?= e(mb_strtoupper(mb_substr($admin['username'], 0, 1))) ?></div>
    <div class="who">
      <strong><?= e($admin['username']) ?></strong>
      <span><?= e($roleLabel) ?> · <a href="/admin/logout.php" style="color:var(--text-muted);">Sign out</a></span>
    </div>
  </div>
</aside>
