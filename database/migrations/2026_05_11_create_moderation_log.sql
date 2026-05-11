CREATE TABLE IF NOT EXISTS `moderation_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `moderator_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `target_type` VARCHAR(64) NOT NULL,
  `target_id` INT UNSIGNED NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `reason` TEXT NULL,
  `ip` VARCHAR(64) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `moderator_created` (`moderator_id`, `created_at`),
  KEY `action_created` (`action`, `created_at`),
  KEY `target` (`target_type`, `target_id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
