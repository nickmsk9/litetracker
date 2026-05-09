SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_profile_slug'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `users` DROP INDEX `idx_users_profile_slug`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_columns := (
  SELECT GROUP_CONCAT(CONCAT('DROP COLUMN `', COLUMN_NAME, '`') ORDER BY FIELD(COLUMN_NAME, 'profile_slug', 'website', 'icq', 'support_enabled', 'support_until', 'warning_until', 'in_group') SEPARATOR ', ')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME IN ('profile_slug', 'website', 'icq', 'support_enabled', 'support_until', 'warning_until', 'in_group')
);
SET @sql := IF(@drop_columns IS NULL OR @drop_columns = '', 'SELECT 1', CONCAT('ALTER TABLE `users` ', @drop_columns));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
