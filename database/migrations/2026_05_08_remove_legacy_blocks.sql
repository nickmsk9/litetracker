-- Remove legacy dynamic block system.
-- The current UI uses static template sections for releases, news and project help.

DROP TABLE IF EXISTS `orbital_blocks`;

SET @old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '');

SET @drop_block_administrators := (
  SELECT IF(
    COUNT(*) > 0,
    'ALTER TABLE `priv` DROP COLUMN `block_administrators`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'priv'
    AND COLUMN_NAME = 'block_administrators'
);
PREPARE stmt FROM @drop_block_administrators;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_block_moderators := (
  SELECT IF(
    COUNT(*) > 0,
    'ALTER TABLE `priv` DROP COLUMN `block_moderators`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'priv'
    AND COLUMN_NAME = 'block_moderators'
);
PREPARE stmt FROM @drop_block_moderators;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET SESSION sql_mode = @old_sql_mode;
