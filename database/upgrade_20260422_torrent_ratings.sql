CREATE TABLE IF NOT EXISTS `torrent_ratings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `torrent_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `torrent_user` (`torrent_id`,`user_id`),
  KEY `torrent_rating` (`torrent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
