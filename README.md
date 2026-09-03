# XPORN LOVERS

Dark-luxury + pink brand system, now backed by a real PHP 8 + MySQL
application (PDO, no framework). Public site is age-gated and reads
videos/categories from the database; the admin dashboard is a real
authenticated app with a working content-report/removal pipeline.

See **DEPLOYMENT.md** for local setup and Hostinger deployment instructions.

## What's implemented

- **Age verification gate** (`includes/age_gate.php`) — a full-page
  interstitial in front of every public page. Verification is remembered
  via session + a signed, HMAC'd cookie (30 days) so it can't be forged by
  hand-setting a cookie value, and isn't re-shown every visit.
- **Reports → admin dashboard** — every video card and the watch page
  carry a "Report" control (reason + optional details). Reports post to
  `api/report.php` (rate-limited, CSRF-protected, anonymous) and surface
  as a live pending-count badge + a Reports panel in the admin dashboard,
  where the admin can **Remove** the video (sets it `removed` and closes
  the report) or **Dismiss** the report.
- **Admin authentication** — `admins` table, `password_hash()` /
  `password_verify()`, session regeneration on login, a per-account
  lockout after 5 failed attempts, and CSRF tokens on every form/POST
  endpoint. Default login is `admin` / `admin` and is never forced to
  change — the dashboard's **Account & Security** panel lets the admin
  change either whenever they want.
- **Public site reads from MySQL** — homepage featured grid, `/videos.php`
  listing with category filter + pagination, and `/video.php?slug=...`
  detail page with an HTML5 `<video>` player, anonymous de-duplicated view
  counting, and related videos from the same category. No public
  registration/login anywhere — visitors are identified only by a random,
  httponly cookie for view/report de-duplication.

## Not yet built

Scoped out of this pass on purpose — ask if you want any of these next:
full video/category/blog CRUD in the admin (Add/Edit forms — the Reports
"Remove" action is the only way to take a video down right now), likes/
dislikes, comments, blog module, search, SEO sitemap/robots.txt, and the
`/video/slug` and `/category/slug` pretty-URL rewrites (the `.htaccess`
rules exist but nothing currently links to those paths — all real links
use `/video.php?slug=...` and `/videos.php?category=...`).

## Structure

```
index.php                 Homepage — age-gated, reads featured videos from DB
videos.php                 Video listing — category filter + pagination
video.php                  Video detail — player, related videos, report
age-gate-action.php        Handles the age-gate form POST

admin/login.php            Real session-based admin login
admin/logout.php
admin/dashboard.php        Live stats, Reports panel, Recent Uploads, Account & Security
admin/actions/report_action.php    Remove / Dismiss a report (admin-only, CSRF)
admin/actions/account_action.php   Change username / password (admin-only, CSRF)

api/report.php             Public report-submission endpoint (CSRF, rate-limited)

includes/db.php             PDO connection
includes/session.php        Secure session bootstrap, CSRF helpers, visitor identifier
includes/auth.php           Admin login/logout/guard + lockout
includes/age_gate.php       Age-gate render + check (site-wide include)
includes/age_gate_core.php  Pure age-gate helpers (cookie signing)
includes/render.php         format_views(), render_video_card()
includes/helpers.php        e(), redirect(), flash messages, time_ago()
includes/partials/          Shared navbar/footer/head/report-modal HTML

config/config.example.php   Config template (copy to config.php — gitignored)
schema.sql                  Full MySQL schema
seed.sql                    Optional demo categories/videos for dev

errors/404.php, errors/500.php   Branded error pages — never leak internals
.htaccess                   DirectoryIndex, error docs, pretty-URL rules, file protection

assets/css/tokens.css       Design tokens: color, type, radius, motion
assets/css/main.css         Public site styles + report button/modal
assets/css/admin.css        Admin styles + sidebar report badge
assets/js/main.js           Mobile nav drawer, category chips, report modal wiring
assets/js/admin.js          Admin sidebar toggle (mobile off-canvas)
assets/img/favicon.svg      Favicon — the brand's large italic "X" mark
```

## Brand system

- **Name:** always rendered in full as `XPORN LOVERS` — never abbreviated.
- **Logo:** text-based lockup, `.logo` — a large, bold italic serif **X**
  (`.logo-mark`, Playfair Display 900, brand gradient fill) immediately
  followed by smaller bold uppercase **PORN LOVERS** (`.logo-text`, Inter
  800), together reading **XPORN LOVERS**. Variants: `.logo--sm`
  (mobile/admin sidebar), `.logo--lg` (admin auth screen).
- **Color:** dark backgrounds (`--bg-primary` `#050505` → `--bg-elevated`
  `#171717`) with pink as an accent only (`--pink` `#FF2D75`,
  `--pink-hover` `#FF5C9A`, `--pink-dark` `#D90052`, `--pink-soft` `#FF8DB5`).
  All tokens live in `assets/css/tokens.css`.
- **Gradient:** `--gradient-brand` (`#FF8DB5 → #FF2D75 → #D90052`), used
  sparingly — logo, active nav underline, primary gradient CTA, stat values.
- **Components:** `.btn-primary` / `.btn-secondary`, `.video-card` +
  `.play-btn` (circular pink play button), `.chip` category pills,
  `.stat-tile` / `.stat-card`, `.badge-*` status pills, `.report-btn` +
  `.report-modal` (new, styled to match).

Seed videos point at royalty-free sample clips/placeholder images purely
so the player and thumbnails render something real in development — swap
them for real content via direct DB inserts (a proper Add/Edit Video admin
form is one of the "not yet built" items above).

## Responsive

Breakpoints are tuned for 390 / 768 / 1440 / 1920px: navbar collapses to a
hamburger + off-canvas drawer under 768px, the video grid steps 4 → 3 → 2
columns, and the admin sidebar becomes an off-canvas panel under 900px.
Unchanged from the approved frontend pass — verified again after wiring
the backend (see DEPLOYMENT.md's testing notes).
