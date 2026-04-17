CREATE TABLE IF NOT EXISTS `comments_users_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `object_id` int NOT NULL,
  `comment_user_id` int NOT NULL DEFAULT '0',
  `reporter_user_id` int NOT NULL DEFAULT '0',
  `comment_text_snapshot` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `status` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by_user_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status_created` (`status`, `created_at`),
  KEY `comment_reporter` (`comment_id`, `reporter_user_id`),
  KEY `object_comment` (`object_id`, `comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
