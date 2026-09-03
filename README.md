# XXPORN LOVERS

Brand website scaffold implementing the **XXPORN LOVERS** dark-luxury + pink
identity system: public site (home, videos, blog) and admin dashboard
(login + dashboard), built as static HTML/CSS/JS.

## Structure

```
index.html              Homepage — hero, featured videos, categories, CTA
videos.html              Full video grid with filters + pagination
blog.html                 Editorial / blog listing
admin/login.html          Admin sign-in screen
admin/dashboard.html      Admin dashboard shell (sidebar, stats, table)

assets/css/tokens.css     Design tokens: color, type, radius, motion
assets/css/main.css       Public site styles (navbar, logo, buttons, cards, footer)
assets/css/admin.css      Admin-only styles (sidebar, auth card, tables)
assets/js/main.js         Mobile nav drawer + category chip interactions
assets/js/admin.js        Admin sidebar toggle (mobile off-canvas)
assets/img/favicon.svg    Favicon — the brand's large italic "X" mark
```

## Brand system

- **Name:** always rendered in full as `XXPORN LOVERS` — never abbreviated.
- **Logo:** text-based lockup, `.logo` — a large italic serif **X**
  (`.logo-mark`, Playfair Display, brand gradient fill) immediately followed
  by smaller uppercase **XPORN LOVERS** (`.logo-text`) in Inter. Variants:
  `.logo--sm` (mobile/admin sidebar), `.logo--lg` (admin auth screen).
- **Color:** dark backgrounds (`--bg-primary` `#050505` → `--bg-elevated`
  `#171717`) with pink as an accent only (`--pink` `#FF2D75`,
  `--pink-hover` `#FF5C9A`, `--pink-dark` `#D90052`, `--pink-soft` `#FF8DB5`).
  All tokens live in `assets/css/tokens.css`.
- **Gradient:** `--gradient-brand` (`#FF8DB5 → #FF2D75 → #D90052`), used
  sparingly — logo, active nav underline, primary gradient CTA, stat values.
- **Components:** `.btn-primary` / `.btn-secondary`, `.video-card` +
  `.play-btn` (circular pink play button), `.chip` category pills,
  `.stat-tile` / `.stat-card`, `.badge-*` status pills.

All video/blog content in this scaffold is placeholder copy — no real
media is included; thumbnails are CSS gradient placeholders.

## Responsive

Breakpoints are tuned for 390 / 768 / 1440 / 1920px: navbar collapses to a
hamburger + off-canvas drawer under 768px, the video grid steps 4 → 3 → 2
columns, and the admin sidebar becomes an off-canvas panel under 900px.

## Running locally

No build step — open `index.html` directly, or serve the folder:

```
python3 -m http.server 8000
```
