ALTER TABLE `comments_users`
  ADD COLUMN `parent_id` int NOT NULL DEFAULT '0' AFTER `text`,
  ADD KEY `id_users_parent` (`id_users`, `parent_id`);

CREATE TABLE IF NOT EXISTS `users_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `blocked_user_id` int NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_blocked_unique` (`user_id`, `blocked_user_id`),
  KEY `blocked_user_id` (`blocked_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
