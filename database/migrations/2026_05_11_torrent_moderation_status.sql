ALTER TABLE `torrents`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'approved' AFTER `banned`,
  ADD COLUMN IF NOT EXISTS `status_reason` TEXT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `reviewed_by` INT UNSIGNED NULL AFTER `status_reason`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME NULL AFTER `reviewed_by`,
  ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME NULL AFTER `reviewed_at`,
  ADD COLUMN IF NOT EXISTS `hidden_at` DATETIME NULL AFTER `submitted_at`,
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL AFTER `hidden_at`,
  ADD KEY IF NOT EXISTS `status_added` (`status`, `added`),
  ADD KEY IF NOT EXISTS `owner_status` (`id_user`, `status`);

UPDATE `torrents`
SET
  `status` = 'approved',
  `submitted_at` = IF(`submitted_at` IS NULL, `added`, `submitted_at`)
WHERE `status` IS NULL OR `status` = '' OR `status` = 'approved';

UPDATE `torrents`
SET `status` = 'approved'
WHERE `status` NOT IN ('pending', 'approved', 'need_fix', 'hidden', 'rejected', 'deleted');
