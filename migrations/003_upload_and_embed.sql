-- ============================================================================
-- Migration: video source can now be an uploaded file, a direct URL, or
-- an embed code, instead of only a direct URL. Thumbnails become
-- upload-only (thumbnail_url now always holds a locally-uploaded file's
-- path, never an external link — no column change needed for that).
--
--   mysql -u <user> -p <database> < migrations/003_upload_and_embed.sql
--
-- If you are provisioning a brand-new database, just import the current
-- schema.sql instead — it already includes this.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE videos
    MODIFY COLUMN video_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS source_type ENUM('url','upload','embed') NOT NULL DEFAULT 'url' AFTER video_url,
    ADD COLUMN IF NOT EXISTS embed_url VARCHAR(500) NULL AFTER source_type;

-- Every existing row was a direct URL under the old schema.
UPDATE videos SET source_type = 'url' WHERE video_url IS NOT NULL AND source_type IS NULL;
