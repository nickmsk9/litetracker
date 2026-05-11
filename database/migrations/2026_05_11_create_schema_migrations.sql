-- ============================================================
-- Create schema_migrations table to track DB migration state
-- ============================================================
-- Safe: new table, only created once
-- Applied on MySQL 8.4 / MyISAM
-- ============================================================

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `checksum` CHAR(40) NOT NULL COMMENT 'SHA1 of migration SQL content',
    `batch` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'batch number for grouped runs',
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when migration was applied',
    `execution_time_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'how long migration took to run',
    `status` ENUM('pending', 'applied', 'failed', 'changed') NOT NULL DEFAULT 'pending' COMMENT 'migration state',
    `error_message` LONGTEXT NULL COMMENT 'error details if failed',
    UNIQUE KEY `idx_migrations_name` (`migration`(100)),
    KEY `idx_migrations_status` (`status`),
    KEY `idx_migrations_batch` (`batch`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Track database migration history and state';

-- ============================================================
-- Notes:
-- - status values: pending (not run), applied (successful), failed (error), changed (checksum mismatch)
-- - checksum: SHA1 hash of migration SQL to detect manual changes
-- - batch: allows grouping multiple migrations run together
-- - execution_time_ms: helps identify slow migrations
-- - UNIQUE on migration(100) because MyISAM has 1000-byte limit for key length with utf8mb4
-- ============================================================
