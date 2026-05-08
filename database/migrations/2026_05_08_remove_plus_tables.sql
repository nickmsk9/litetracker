-- Remove legacy Plus ads/reactions storage.

DROP TABLE IF EXISTS `plus_ads`;
DROP TABLE IF EXISTS `plus_reactions`;

SET @old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '');

SET @drop_plus_until := (
  SELECT IF(COUNT(*) > 0, 'ALTER TABLE `users` DROP COLUMN `plus_until`', 'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plus_until'
);
PREPARE stmt FROM @drop_plus_until;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_plus_permanent := (
  SELECT IF(COUNT(*) > 0, 'ALTER TABLE `users` DROP COLUMN `plus_permanent`', 'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plus_permanent'
);
PREPARE stmt FROM @drop_plus_permanent;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_plus_source := (
  SELECT IF(COUNT(*) > 0, 'ALTER TABLE `users` DROP COLUMN `plus_source`', 'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plus_source'
);
PREPARE stmt FROM @drop_plus_source;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_plus_badge := (
  SELECT IF(COUNT(*) > 0, 'ALTER TABLE `users` DROP COLUMN `plus_badge`', 'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plus_badge'
);
PREPARE stmt FROM @drop_plus_badge;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET SESSION sql_mode = @old_sql_mode;
