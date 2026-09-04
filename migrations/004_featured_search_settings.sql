-- ============================================================================
-- Migration: adds the "featured" flag videos need for the homepage's
-- Featured/Latest/Trending sections, and seeds the extra settings keys
-- the new Website Settings admin page reads (site_tagline already
-- existed; the rest are new). Search, likes/dislikes, comments, and
-- category management use tables that already exist in schema.sql — no
-- structural change needed for those.
--
--   mysql -u <user> -p <database> < migrations/004_featured_search_settings.sql
--
-- If you are provisioning a brand-new database, just import the current
-- schema.sql instead — it already includes this.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE videos
    ADD COLUMN IF NOT EXISTS featured TINYINT(1) NOT NULL DEFAULT 0 AFTER removed_reason,
    ADD INDEX IF NOT EXISTS idx_videos_featured (featured, status, created_at);

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_tagline', 'Dark · Premium · Curated'),
    ('footer_about', 'A premium curated video platform. Dark, minimal, and built for a fast, distraction-free viewing experience.'),
    ('contact_email', ''),
    ('social_x', ''),
    ('social_instagram', ''),
    ('social_telegram', ''),
    ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
