CREATE TABLE IF NOT EXISTS `comment_pins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context_type` VARCHAR(32) NOT NULL,
  `context_id` INT UNSIGNED NOT NULL,
  `comment_id` INT UNSIGNED NOT NULL,
  `pinned_by` INT UNSIGNED NOT NULL,
  `pinned_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `context_pin` (`context_type`, `context_id`),
  KEY `comment_pin` (`context_type`, `comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

CREATE TABLE IF NOT EXISTS `comment_reactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context_type` VARCHAR(32) NOT NULL,
  `comment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `reaction` VARCHAR(16) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_comment_reaction` (`context_type`, `comment_id`, `user_id`),
  KEY `comment_reaction` (`context_type`, `comment_id`, `reaction`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

CREATE TABLE IF NOT EXISTS `comment_edit_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context_type` VARCHAR(32) NOT NULL,
  `comment_id` INT UNSIGNED NOT NULL,
  `editor_id` INT UNSIGNED NOT NULL,
  `old_text` TEXT NULL,
  `new_text` TEXT NULL,
  `edited_at` DATETIME NOT NULL,
  `edit_reason` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `comment_history` (`context_type`, `comment_id`, `edited_at`),
  KEY `editor_history` (`editor_id`, `edited_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

ALTER TABLE `comments_torrents`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `deleted_by` INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL,
  ADD KEY IF NOT EXISTS `idx_comments_torrents_deleted` (`is_deleted`, `date`);

ALTER TABLE `comments_users`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `deleted_by` INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL,
  ADD KEY IF NOT EXISTS `idx_comments_users_deleted` (`is_deleted`, `date`);

ALTER TABLE `comments_news`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `deleted_by` INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL,
  ADD KEY IF NOT EXISTS `idx_comments_news_deleted` (`is_deleted`, `date`);

ALTER TABLE `comments_faq`
  ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `deleted_by` INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL,
  ADD KEY IF NOT EXISTS `idx_comments_faq_deleted` (`is_deleted`, `date`);
