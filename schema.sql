-- ============================================================================
-- XPORN LOVERS — database schema (MySQL 5.7+ / MariaDB 10.3+)
--
-- Import this once against a fresh, empty database:
--   mysql -u <user> -p <database> < schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- admins — every authenticated account on the site (staff only; there is
-- still no public user-account system). role is a tier: creator <
-- moderator < admin. is_owner marks the single founding account that
-- alone may suspend/delete other accounts — it is never set through the
-- app, only here (or directly in the database).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('creator','moderator','admin') NOT NULL DEFAULT 'creator',
    is_owner        TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login      DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Founding owner account: username "Tyche", password "Tyche".
-- This hash was generated with PHP's password_hash('Tyche', PASSWORD_DEFAULT).
-- Change the password in the app (Account & Security) whenever you like —
-- never required. is_owner=1 is permanent: only this account (or whichever
-- account you manually flip is_owner on in the database) can suspend or
-- delete other admin/moderator/creator accounts.
INSERT INTO admins (username, password_hash, role, is_owner)
VALUES ('Tyche', '$2y$12$AZywfwTOFrSO9pv1zvOrF.Ypts2vtlptYzyvptQgnwk9Rcje8mKrm', 'admin', 1)
ON DUPLICATE KEY UPDATE username = username;

-- ---------------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- videos — content is always an external URL, never uploaded to this host.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS videos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    description    TEXT NULL,
    category_id    INT UNSIGNED NULL,
    video_url      VARCHAR(500) NOT NULL,
    thumbnail_url  VARCHAR(500) NULL,
    duration       VARCHAR(20) NULL,
    views          INT UNSIGNED NOT NULL DEFAULT 0,
    status         ENUM('published','unpublished','removed') NOT NULL DEFAULT 'published',
    removed_reason VARCHAR(255) NULL,
    created_by     INT UNSIGNED NULL,
    updated_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_videos_status_created (status, created_at),
    INDEX idx_videos_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- blog_posts
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_posts (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    excerpt        VARCHAR(500) NULL,
    content        MEDIUMTEXT NULL,
    featured_image VARCHAR(500) NULL,
    status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- comments — no account required; name + text only, admin-moderated.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id    INT UNSIGNED NOT NULL,
    user_name   VARCHAR(80) NOT NULL,
    comment     TEXT NOT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    INDEX idx_comments_video_status (video_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- likes — anonymous, de-duplicated by hashed visitor identifier.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS likes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id            INT UNSIGNED NOT NULL,
    visitor_identifier  CHAR(64) NOT NULL,
    type                ENUM('like','dislike') NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_visitor_video (video_id, visitor_identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- video_views — anonymous, time-deduplicated view counter.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS video_views (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id            INT UNSIGNED NOT NULL,
    visitor_identifier  CHAR(64) NOT NULL,
    viewed_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    INDEX idx_views_video_visitor_time (video_id, visitor_identifier, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- reports — visitor-submitted content reports, surfaced to the admin
-- dashboard as a notification; admin can remove the video or dismiss.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id            INT UNSIGNED NOT NULL,
    reason              VARCHAR(50) NOT NULL,
    details             VARCHAR(1000) NULL,
    reporter_identifier CHAR(64) NOT NULL,
    status              ENUM('pending','removed','dismissed') NOT NULL DEFAULT 'pending',
    resolved_by         INT UNSIGNED NULL,
    resolved_at         DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_reports_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- settings — simple key/value site settings.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key    VARCHAR(100) NOT NULL UNIQUE,
    setting_value  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'XPORN LOVERS'),
    ('site_tagline', 'Dark. Premium. Curated.')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

SET FOREIGN_KEY_CHECKS = 1;
