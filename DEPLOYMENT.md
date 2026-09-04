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

# 2b. Already have a database from before the role/accounts update?
# Run the migration instead of re-importing schema.sql from scratch:
mysql -u xpl_dev -p xpornlovers_dev < migrations/002_accounts_and_roles.sql

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
   to **Videos → + Add Video**, paste a video URL and thumbnail URL and
   confirm both live-preview, save, and confirm it plays correctly on the
   public site.

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

11. **Test search** — not implemented yet (see README's "Not yet built").

12. **Test the blog** — not implemented yet (see README's "Not yet built").

13. **Test responsive** — 390 / 430 / 768 / 1024 / 1440 / 1920px, both the
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

Then add your real categories (direct SQL/phpMyAdmin for now — category
management isn't in the admin UI yet) and videos (via **Videos → + Add
Video** in the dashboard).

## Known limitations

- Category and blog-post management has no admin UI yet — categories go
  in via direct DB access; the blog schema exists but nothing reads/
  writes it yet. Video management is fully built (`admin/videos.php`).
- No likes/dislikes, comments, or search — the schema for likes/comments
  exists (`schema.sql`) but nothing reads/writes it yet.
- No sitemap.xml/robots.txt or structured data yet.
- No in-app code *editor* — `admin/code.php` is read-only by design (see
  README). Deploy code changes through git.
- The age gate is a real server-side gate (session + signed cookie), but
  it is a self-attestation click-through, not identity/age verification
  against a third-party service — that's a meaningfully bigger feature if
  your jurisdiction requires stronger verification than a click-through.
