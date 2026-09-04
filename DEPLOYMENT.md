# Deployment & Setup

## 1. Local development

Requirements: PHP 8.1+ with `pdo_mysql`, and a MySQL/MariaDB server.

```bash
# 1. Create a database + user
mysql -u root -e "
  CREATE DATABASE xpornlovers_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'xpl_dev'@'localhost' IDENTIFIED BY 'pick-a-password';
  GRANT ALL PRIVILEGES ON xpornlovers_dev.* TO 'xpl_dev'@'localhost';
  FLUSH PRIVILEGES;
"

# 2. Import the schema, then (optionally) demo data
mysql -u xpl_dev -p xpornlovers_dev < schema.sql
mysql -u xpl_dev -p xpornlovers_dev < seed.sql   # optional, see "Removing demo data" below

# 2b. Already have a database from before the role/accounts update, the
# video-upload/embed update, or the featured/search/settings update?
# Run whichever migrations you're missing instead of re-importing
# schema.sql from scratch (all three are safe to run even if partially
# applied already):
mysql -u xpl_dev -p xpornlovers_dev < migrations/002_accounts_and_roles.sql
mysql -u xpl_dev -p xpornlovers_dev < migrations/003_upload_and_embed.sql
mysql -u xpl_dev -p xpornlovers_dev < migrations/004_featured_search_settings.sql

# 3. Configure
cp config/config.example.php config/config.php
# edit config/config.php: DB_HOST/DB_NAME/DB_USER/DB_PASS to match step 1,
# and set AGE_GATE_SECRET to a real random value:
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

# 4. Run
php -S 127.0.0.1:8000
```

Visit `http://127.0.0.1:8000/index.php` — you'll hit the age gate first,
then the homepage. Admin is at `/admin/login.php`.

> **Note on `php -S`:** PHP's built-in dev server falls back to
> `index.php` for *any* unmatched URL when no router script is given —
> so a deleted/nonexistent path can appear to "200" locally. That's a
> dev-server-only quirk; it does **not** happen under the real Apache
> setup below (there is no catch-all rewrite to `index.php` in
> `.htaccess`), so don't use it to judge routing/404 behavior locally.

## 2. Hostinger deployment (`public_html/`)

1. **Create the MySQL database** — hPanel → Databases → MySQL Databases.
   Create a database, a user, and note the host (usually `localhost`),
   database name, username, and password. Hostinger prefixes these with
   your account ID automatically.

2. **Import the schema** — phpMyAdmin → your database → Import →
   upload `schema.sql`. Optionally also import `seed.sql` if you want
   sample content to start from (see below to remove it later).

3. **Upload the files** — upload everything in this repo to
   `public_html/` (via File Manager's zip-upload-and-extract, or FTP/SFTP,
   or git if your plan supports it). Keep the folder structure as-is.

4. **Configure** — on the server, copy `config/config.example.php` to
   `config/config.php` and fill in:
   - `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` from step 1
   - `SITE_URL` = your real domain, e.g. `https://xpornlovers.com`
   - `AGE_GATE_SECRET` = a real random value (generate one the same way
     as in local setup, via SSH/Terminal in hPanel if available, or any
     offline PHP `bin2hex(random_bytes(32))`) — never reuse the example
     value or the one in this repo's local dev config.
   - `FORCE_HTTPS_COOKIES` = `true` once SSL (Hostinger's free SSL) is
     active on the domain
   - `APP_DEBUG` = `false`

   `config/config.php` is never committed to git (see `.gitignore`) and
   `config/.htaccess` blocks direct HTTP access to the whole folder as a
   second layer of protection — but double-check in your hosting panel
   that the file isn't web-readable (`https://yourdomain/config/config.php`
   should error out, not display PHP source).

5. **`.htaccess`** — already included at the repo root; no changes
   needed unless your Hostinger plan requires a different PHP handler
   directive (rare — ask Hostinger support if PHP files start
   downloading instead of executing).

6. **Set the website URL** — this is the `SITE_URL` constant from step 4;
   it's used for canonical/Open Graph tags.

6b. **Uploads folder + upload size limit** — `uploads/videos/` and
    `uploads/thumbnails/` need to be writable by the web server (usually
    already true after a normal zip-upload/FTP transfer; if an upload
    fails with a storage error, check their permissions in File
    Manager — 755 is typically enough). The repo's `.user.ini` raises
    PHP's `upload_max_filesize`/`post_max_size` to 200MB, which works
    under Hostinger's PHP-FPM (unlike `.htaccess` `php_value`, which FPM
    ignores) — but PHP-FPM only re-reads `.user.ini` every few minutes,
    so give it a little time after first upload, and your specific
    Hostinger plan may still cap uploads lower regardless (check
    hPanel → Advanced → PHP Configuration if large uploads keep failing).

7. **Test the admin login** — go to `https://yourdomain/admin/login.php`
   (or click the lock icon on the homepage, or visit `/admin`), sign in
   with the founding owner account, `Tyche` / `Tyche`. Immediately set a
   real password from **Account & Security** on the dashboard if this is
   going live for real — the default is intentionally never forced, but
   you should still change it yourself before publishing real content.
   From **Manage Accounts**, create separate creator/moderator/admin
   accounts for anyone else on the team rather than sharing the owner
   login — see README's role table for what each tier can do.

8. **Test video management** — as a creator-role account (or higher), go
   to **Videos → + Add Video** and try all three video source options
   (upload a real file, paste a direct URL, paste an embed snippet) plus
   the required thumbnail upload; confirm the previews update as you go,
   save, and confirm each plays correctly on the public site (embed
   videos render as an iframe, the other two as the native player).

9. **Test the report flow** — as a logged-out visitor, click "Report" on
   a video card, submit it, then sign in as a moderator-role account (or
   higher) and confirm it appears under **Reports** with a working
   Remove/Dismiss.

10. **Test the account hierarchy** — confirm a creator-role account
    cannot see or reach `/admin/accounts.php` or `/admin/code.php`
    (redirected back to the dashboard with a permission message), that a
    non-owner admin account can create accounts but has no suspend/
    delete controls, and that only the owner (`Tyche`, or whichever
    account you've flipped `is_owner` on) can suspend/reactivate/delete.

11. **Test search** — use the navbar search box (desktop and mobile) and
    confirm it lands on `/search.php?q=...` with matching results.

12. **Test likes/dislikes and comments** — on a video page, like, then
    dislike (confirm it switches, and clicking the same one again
    removes it), and post a comment; confirm it does **not** appear
    publicly until approved from **Comments** in the admin sidebar
    (moderator role+ — approve, reject, or delete).

13. **Test category management** — from **Categories** in the admin
    sidebar (creator role+), add, edit, and delete a category; confirm
    deleting one does not delete its videos (they fall back to
    "General").

14. **Test Website Settings** — from **Website Settings** (admin role+),
    save a tagline/footer/social change and confirm it shows on the
    public site; then briefly enable **maintenance mode** and confirm
    logged-out visitors see the holding page while you (signed in) can
    still browse normally — turn it back off before publishing for real.

15. **Test the blog** — not implemented yet (see README's "Not yet built").

16. **Test responsive** — 390 / 430 / 768 / 1024 / 1440 / 1920px, both the
    public site and the admin dashboard.

## Removing demo data before going live

The four `seed.sql` videos exist only so the player/report/admin flow have
something real to click through in development. Before publishing:

```sql
DELETE FROM reports;
DELETE FROM video_views;
DELETE FROM likes;
DELETE FROM comments;
DELETE FROM videos;
DELETE FROM categories;
```

Then add your real categories (**Categories → + Add Category** in the
dashboard) and videos (**Videos → + Add Video**).

## Known limitations

- Blog-post management has no admin UI yet — the `blog_posts` schema
  exists but nothing reads/writes it. Video, category, comment, and
  settings management are all fully built.
- No sitemap.xml/robots.txt or structured data yet.
- No in-app code *editor* — `admin/code.php` is read-only by design (see
  README). Deploy code changes through git.
- Uploaded video files live on your Hostinger disk quota and are served
  directly from PHP/Apache — no transcoding, no adaptive bitrate, no
  CDN. Fine for a modest catalog; a large one will want a proper media
  host eventually. Direct-URL and embed sources don't have this
  limitation since the file itself lives elsewhere.
- The age gate is a real server-side gate (session + signed cookie), but
  it is a self-attestation click-through, not identity/age verification
  against a third-party service — that's a meaningfully bigger feature if
  your jurisdiction requires stronger verification than a click-through.
