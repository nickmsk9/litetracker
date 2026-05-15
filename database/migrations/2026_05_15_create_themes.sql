-- ============================================================
-- Create themes table for multi-theme support
-- Add theme_slug column to users table for per-user preference
-- ============================================================
-- Safe: CREATE TABLE IF NOT EXISTS, INSERT IGNORE
--       users.theme_slug added via idempotent PREPARE/EXECUTE
-- Tested on MySQL 8.4
-- ============================================================

-- Themes registry
CREATE TABLE IF NOT EXISTS `themes` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title`      VARCHAR(255) NOT NULL,
    `slug`       VARCHAR(64)  NOT NULL,
    `css_path`   VARCHAR(500) NOT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `is_default` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_themes_slug` (`slug`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed built-in themes (INSERT IGNORE skips if slug already exists)
INSERT IGNORE INTO `themes` (`title`, `slug`, `css_path`, `is_active`, `is_default`) VALUES
    ('LiteTracker Default', 'default', 'templates/default/css/my.css', 1, 1);

INSERT IGNORE INTO `themes` (`title`, `slug`, `css_path`, `is_active`, `is_default`) VALUES
    ('LiteTracker 2026 Minimal', 'litetracker_2026_minimal', 'templates/litetracker_2026_minimal/css/theme.css', 1, 0);

-- Add theme_slug column to users if it does not already exist (idempotent)
SET @_lt_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'theme_slug');
SET @_lt_col_sql := IF(@_lt_col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `theme_slug` VARCHAR(64) NOT NULL DEFAULT ""', 'SELECT 1 AS noop');
PREPARE __lt_theme_col_stmt FROM @_lt_col_sql;
EXECUTE __lt_theme_col_stmt;
DEALLOCATE PREPARE __lt_theme_col_stmt;
