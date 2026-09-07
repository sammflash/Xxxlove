-- ============================================================================
-- Migration: role-based accounts (creator/moderator/admin), account
-- status, and an owner flag. Safe to run once against a database that
-- was already provisioned from an earlier schema.sql.
--
--   mysql -u <user> -p <database> < migrations/002_accounts_and_roles.sql
--
-- If you are provisioning a brand-new database, just import the current
-- schema.sql instead — it already includes all of this.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS role ENUM('creator','moderator','admin') NOT NULL DEFAULT 'creator' AFTER password_hash,
    ADD COLUMN IF NOT EXISTS is_owner TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
    ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER is_owner,
    ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER status;

ALTER TABLE videos
    ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER removed_reason,
    ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL AFTER created_by;

-- Add the foreign keys only if they don't already exist (MySQL/MariaDB
-- have no ADD CONSTRAINT IF NOT EXISTS, so this checks information_schema).
SET @fk_admins_created_by := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND CONSTRAINT_NAME = 'fk_admins_created_by'
);
SET @sql := IF(@fk_admins_created_by = 0,
    'ALTER TABLE admins ADD CONSTRAINT fk_admins_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_videos_created_by := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'videos' AND CONSTRAINT_NAME = 'fk_videos_created_by'
);
SET @sql := IF(@fk_videos_created_by = 0,
    'ALTER TABLE videos ADD CONSTRAINT fk_videos_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_videos_updated_by := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'videos' AND CONSTRAINT_NAME = 'fk_videos_updated_by'
);
SET @sql := IF(@fk_videos_updated_by = 0,
    'ALTER TABLE videos ADD CONSTRAINT fk_videos_updated_by FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Promote whatever the previous single admin account was to the new
-- owner account. If your old admin username wasn't "admin", change the
-- WHERE clause before running — this only touches the password if the
-- username already matches "admin", so it's safe to leave as-is otherwise.
UPDATE admins
SET password_hash = '$2y$12$.e9BtHKbU7g.Vz7ii2dSS./2nAte6JOI2rb6OXqjoH6Kd9MXBqN2i', -- password_hash('admin', PASSWORD_DEFAULT)
    role = 'admin',
    is_owner = 1,
    status = 'active'
WHERE username = 'admin';

-- If no row matched above (e.g. your old admin used a different
-- username), make sure at least one owner exists so the app isn't left
-- with zero admin access.
INSERT INTO admins (username, password_hash, role, is_owner)
SELECT 'admin', '$2y$12$.e9BtHKbU7g.Vz7ii2dSS./2nAte6JOI2rb6OXqjoH6Kd9MXBqN2i', 'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE is_owner = 1);
