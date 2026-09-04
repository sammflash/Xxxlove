# XPORN LOVERS

Dark-luxury + pink brand system, now backed by a real PHP 8 + MySQL
application (PDO, no framework). Public site is age-gated and reads
videos/categories from the database; the admin dashboard is a real
authenticated, role-based app with video management and a working
content-report/removal pipeline.

See **DEPLOYMENT.md** for local setup and Hostinger deployment instructions.

## What's implemented

- **Age verification gate** (`includes/age_gate.php`) — a full-page
  interstitial in front of every public page. Verification is remembered
  via session + a signed, HMAC'd cookie (30 days) so it can't be forged by
  hand-setting a cookie value, and isn't re-shown every visit.
- **Role-based staff accounts** — three tiers on one `admins` table:
  - **Creator**: add/edit/delete any video, full video library.
  - **Moderator**: everything a creator can, plus the Reports panel
    (remove or dismiss reported videos).
  - **Admin**: everything a moderator can, plus creating new creator/
    moderator/admin accounts and a read-only website-code viewer.
  - **Owner** (one account, `is_owner` flag — not a role) — the only
    account that can suspend or delete other accounts. Never settable
    through the app. Founding owner: `Tyche` / `Tyche`.

  Every permission check is enforced server-side in the action handlers
  (`require_role()` in `includes/auth.php`), not just hidden in the UI —
  a creator who crafts a direct request to a moderator/admin/owner-only
  endpoint is refused there too.
- **Video management** (`admin/videos.php`) — add/edit/delete, category
  assignment, live thumbnail preview and video-URL preview while typing,
  publish/unpublish/removed status, paginated library with status filter.
- **Account management** (`admin/accounts.php`, admin role+) — create
  accounts of any role; only the owner sees/uses suspend, reactivate, and
  delete. Everyone (any role) can change their own username/password from
  **Account & Security** on the dashboard — never required, always optional.
- **Website code viewer** (`admin/code.php`, admin role+) — **read-only**
  on purpose. An in-app file *editor* that writes to the live server was
  asked for but deliberately not built: it's a standing RCE risk — if any
  account (creator/moderator/admin, or the owner) is ever compromised via
  a weak/reused/leaked password, session theft, etc., a write-capable code
  editor hands the attacker direct remote code execution on the host. Real
  code changes should go through git → deploy instead. `config/config.php`
  (real DB credentials) is hard-excluded from the viewer regardless of
  extension or query-string tricks.
- **Reports → admin dashboard** — every video card and the watch page
  carry a "Report" control (reason + optional details). Reports post to
  `api/report.php` (rate-limited, CSRF-protected, anonymous) and surface
  as a live pending-count badge + a Reports panel (moderator role+), where
  staff can **Remove** the video (sets it `removed` and closes the report)
  or **Dismiss** the report.
- **Admin authentication** — `password_hash()` / `password_verify()`,
  session regeneration on login, a per-account lockout after 5 failed
  attempts, suspended accounts blocked at login (checked only after the
  password verifies, so a wrong guess never reveals suspension status),
  and CSRF tokens on every form/POST endpoint.
- **Public site reads from MySQL** — homepage featured grid, `/videos.php`
  listing with category filter + pagination, and `/video.php?slug=...`
  detail page with an HTML5 `<video>` player, anonymous de-duplicated view
  counting, and related videos from the same category. No public
  registration/login anywhere — visitors are identified only by a random,
  httponly cookie for view/report de-duplication.

## Not yet built

Scoped out on purpose — ask if you want any of these next: category/blog
CRUD in the admin, likes/dislikes, comments, blog module, search, SEO
sitemap/robots.txt, and the `/video/slug` and `/category/slug` pretty-URL
rewrites (the `.htaccess` rules exist but nothing currently links to those
paths — all real links use `/video.php?slug=...` and
`/videos.php?category=...`).

## Structure

```
index.php                 Homepage — age-gated, reads featured videos from DB
videos.php                 Video listing — category filter + pagination
video.php                  Video detail — player, related videos, report
age-gate-action.php        Handles the age-gate form POST

admin/login.php            Real session-based admin login (any role)
admin/logout.php
admin/index.php            Canonical /admin entry point (routes by auth state)
admin/dashboard.php        Live stats, Reports panel (moderator+), Recent Uploads, Account & Security
admin/videos.php           Video library + add/edit form with live previews (creator+)
admin/accounts.php         Create accounts (admin+); suspend/reactivate/delete (owner only)
admin/code.php             Read-only source viewer (admin+) — no write endpoint anywhere
admin/actions/report_action.php          Remove / Dismiss a report (moderator+, CSRF)
admin/actions/video_action.php           Create/update/delete a video (creator+, CSRF)
admin/actions/account_action.php         Change own username / password (any role, CSRF)
admin/actions/account_manage_action.php  Create account (admin+) / suspend, reactivate, delete (owner only)

api/report.php             Public report-submission endpoint (CSRF, rate-limited)

includes/db.php             PDO connection
includes/session.php        Secure session bootstrap, CSRF helpers, visitor identifier
includes/auth.php           Login/logout/guard, require_role() permission checks, lockout
includes/age_gate.php       Age-gate render + check (site-wide include)
includes/age_gate_core.php  Pure age-gate helpers (cookie signing)
includes/render.php         format_views(), render_video_card()
includes/helpers.php        e(), redirect(), flash messages, time_ago(), slugify()
includes/partials/          Shared navbar/footer/head/report-modal/admin-sidebar HTML

config/config.example.php   Config template (copy to config.php — gitignored)
schema.sql                  Full MySQL schema (fresh installs)
migrations/002_accounts_and_roles.sql   Adds roles/status/owner to an existing DB
seed.sql                    Optional demo categories/videos for dev

errors/404.php, errors/500.php   Branded error pages — never leak internals
.htaccess                   DirectoryIndex, error docs, pretty-URL rules, file protection

assets/css/tokens.css       Design tokens: color, type, radius, motion
assets/css/main.css         Public site styles + report button/modal
assets/css/admin.css        Admin styles + sidebar badges + role UI
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
  `.report-modal`, `.sidebar-badge` (pending-report count pill).

Seed videos point at royalty-free sample clips/placeholder images purely
so the player and thumbnails render something real in development — add
real content via **Videos → + Add Video** in the dashboard.

## Responsive

Breakpoints are tuned for 390 / 768 / 1440 / 1920px: navbar collapses to a
hamburger + off-canvas drawer under 768px, the video grid steps 4 → 3 → 2
columns, and the admin sidebar becomes an off-canvas panel under 900px.
Unchanged from the approved frontend pass.
