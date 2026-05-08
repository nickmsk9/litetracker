
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `lite` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `lite`;
DROP TABLE IF EXISTS `bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `id_user` int unsigned NOT NULL DEFAULT '0',
  `first` int DEFAULT NULL,
  `last` int DEFAULT NULL,
  `text` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `first_last` (`first`,`last`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bans` WRITE;
/*!40000 ALTER TABLE `bans` DISABLE KEYS */;
/*!40000 ALTER TABLE `bans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `birthday_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `birthday_rewards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reward_year` int NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_year` (`user_id`,`reward_year`),
  KEY `reward_year` (`reward_year`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `birthday_rewards` WRITE;
/*!40000 ALTER TABLE `birthday_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `birthday_rewards` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_torrent` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_books_user_torrent` (`id_user`,`id_torrent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `image` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `template` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Программы','1.gif',1,'2010-12-07 00:00:00'),(2,'Игры','2.png',3,'2010-12-07 00:00:00'),(3,'Фильмы','3.gif',2,'2010-12-07 00:00:00'),(4,'Музыка','4.gif',4,'2010-12-07 00:00:00'),(5,'Телешоу','5.gif',5,'2010-12-07 00:00:00'),(6,'Аниме','6.gif',6,'2010-12-07 00:00:00');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `userclass` int NOT NULL,
  `date` datetime NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_faq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_faq` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  `text` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `parent_id` int NOT NULL DEFAULT '0',
  `id_user_edit` int NOT NULL,
  `date_edit` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_faq` WRITE;
/*!40000 ALTER TABLE `comments_faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments_faq` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_news` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  `text` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `parent_id` int NOT NULL DEFAULT '0',
  `id_user_edit` int NOT NULL,
  `date_edit` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_news` WRITE;
/*!40000 ALTER TABLE `comments_news` DISABLE KEYS */;
INSERT INTO `comments_news` VALUES (1,1,1,'2026-05-07 20:16:17','что?',0,1,'2026-05-07 20:16:17');
/*!40000 ALTER TABLE `comments_news` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_torrents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_torrents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_torrents` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  `text` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `parent_id` int NOT NULL DEFAULT '0',
  `id_user_edit` int NOT NULL,
  `date_edit` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comments_torrents_object_date` (`id_torrents`,`date`),
  KEY `idx_comments_torrents_parent` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_torrents` WRITE;
/*!40000 ALTER TABLE `comments_torrents` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments_torrents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_users` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  `text` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `parent_id` int NOT NULL DEFAULT '0',
  `id_user_edit` int NOT NULL DEFAULT '0',
  `date_edit` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_users_parent` (`id_users`,`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users` WRITE;
/*!40000 ALTER TABLE `comments_users` DISABLE KEYS */;
INSERT INTO `comments_users` VALUES (1,1,1,'2026-05-07 18:50:59','давайте тут будут комментарии',0,0,NULL),(2,1,1,'2026-05-07 18:51:09','&#128513;',0,0,NULL),(3,1,1,'2026-05-07 18:51:14','давайте!',1,0,NULL),(4,2,2,'2026-05-08 14:44:32','&#128512;',0,0,NULL),(5,2,2,'2026-05-08 14:44:32','&#128512;',0,0,NULL),(6,1,2,'2026-05-08 14:44:50','что',3,0,NULL),(7,1,2,'2026-05-08 14:44:50','что',3,0,NULL);
/*!40000 ALTER TABLE `comments_users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_users_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_users_reports` (
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
  KEY `status_created` (`status`,`created_at`),
  KEY `comment_reporter` (`comment_id`,`reporter_user_id`),
  KEY `object_comment` (`object_id`,`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users_reports` WRITE;
/*!40000 ALTER TABLE `comments_users_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments_users_reports` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `confirm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `confirm` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `id_user` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `confirm` WRITE;
/*!40000 ALTER TABLE `confirm` DISABLE KEYS */;
/*!40000 ALTER TABLE `confirm` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron` (
  `cron_name` varchar(255) NOT NULL,
  `cron_value` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`cron_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cron` WRITE;
/*!40000 ALTER TABLE `cron` DISABLE KEYS */;
INSERT INTO `cron` VALUES ('autoclean_interval',1000),('autoclean_last',1777907575),('multi_remote',1),('remotecheck_interval',600),('remote_torrents',30),('remotepeers_cleantime',10800),('remote_lastchecked',0),('in_remotecheck',0),('num_checked',343),('last_remotecheck',1778241012),('multi_timeout',100);
/*!40000 ALTER TABLE `cron` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `added` datetime NOT NULL,
  `last_edit` datetime NOT NULL,
  `last_edit_user` int NOT NULL,
  `subject` varchar(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `faq` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_torrent` int NOT NULL,
  `filename` varchar(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `size` bigint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `forgot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forgot` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `forgot` WRITE;
/*!40000 ALTER TABLE `forgot` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgot` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `friends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friends` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `userid` int unsigned NOT NULL DEFAULT '0',
  `friendid` int unsigned NOT NULL DEFAULT '0',
  `status` enum('yes','no','pending') NOT NULL DEFAULT 'pending',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userfriend` (`userid`,`friendid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `friends` WRITE;
/*!40000 ALTER TABLE `friends` DISABLE KEYS */;
/*!40000 ALTER TABLE `friends` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `date` datetime NOT NULL,
  `id_user_in` int NOT NULL,
  `id_user_out` int NOT NULL,
  `delete_in` smallint NOT NULL,
  `delete_out` smallint NOT NULL,
  `reading` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mail` WRITE;
/*!40000 ALTER TABLE `mail` DISABLE KEYS */;
INSERT INTO `mail` VALUES (1,'Добро пожаловать!','Спасибо за регистрацию на LiteTracker! Заполните профиль, ознакомьтесь с правилами и начинайте пользоваться сайтом.','2026-05-08 14:44:27',2,0,0,0,0);
/*!40000 ALTER TABLE `mail` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `date` datetime NOT NULL,
  `id_user` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_news_date` (`date`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'что','[b]кукусики[/b]','2026-05-07 18:51:40',1);
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orbital_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orbital_blocks` (
  `bid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `position` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `weight` int NOT NULL DEFAULT '1',
  `active` int NOT NULL DEFAULT '1',
  `blockfile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `type` enum('all','guests','users','moderators','administrators') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'all',
  `which` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`bid`),
  KEY `title` (`title`),
  KEY `weight` (`weight`),
  KEY `active` (`active`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orbital_blocks` WRITE;
/*!40000 ALTER TABLE `orbital_blocks` DISABLE KEYS */;
INSERT INTO `orbital_blocks` VALUES (1,'Поиск','c',1,1,'block-search.php','all','index'),(2,'Чат','c',2,0,'block-chat.php','all','index'),(3,'Новинки месяца','c',4,1,'block-newreleases.php','all','index'),(4,'Нагрузка на сервер','c',5,1,'block-load_in_server.php','all','index'),(5,'Категории','l',1,1,'block-categories.php','all','all'),(6,'Теги','d',3,1,'block-tags.php','all','browse'),(15,'Кто он-лайн','d',1,0,'block-online.php','all','index'),(9,'Новости','l',2,1,'block-news.php','all','index'),(17,'Опрос','c',3,1,'block-poll.php','users','index'),(16,'Статистика','d',2,0,'block-stats.php','all','index');
/*!40000 ALTER TABLE `orbital_blocks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `peers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `torrent` int unsigned NOT NULL DEFAULT '0',
  `peer_id` varchar(20) CHARACTER SET cp1251 COLLATE cp1251_general_ci NOT NULL,
  `ip` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `uploaded` bigint unsigned NOT NULL DEFAULT '0',
  `downloaded` bigint unsigned NOT NULL DEFAULT '0',
  `uploadoffset` bigint unsigned NOT NULL DEFAULT '0',
  `downloadoffset` bigint unsigned NOT NULL DEFAULT '0',
  `to_go` bigint unsigned NOT NULL DEFAULT '0',
  `seeder` tinyint(1) NOT NULL DEFAULT '0',
  `started` datetime NOT NULL,
  `last_action` datetime NOT NULL,
  `prev_action` datetime NOT NULL,
  `connectable` tinyint(1) NOT NULL DEFAULT '1',
  `userid` int unsigned NOT NULL DEFAULT '0',
  `agent` varchar(60) NOT NULL,
  `finishedat` int unsigned NOT NULL DEFAULT '0',
  `passkey` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `torrent_peer_id` (`torrent`,`peer_id`),
  KEY `torrent` (`torrent`),
  KEY `torrent_seeder` (`torrent`,`seeder`),
  KEY `last_action` (`last_action`),
  KEY `connectable` (`connectable`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `peers` WRITE;
/*!40000 ALTER TABLE `peers` DISABLE KEYS */;
/*!40000 ALTER TABLE `peers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `plus_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plus_ads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `body` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `href` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `placement` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'sidebar',
  `enabled` tinyint NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `placement_enabled_sort` (`placement`,`enabled`,`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `plus_ads` WRITE;
/*!40000 ALTER TABLE `plus_ads` DISABLE KEYS */;
/*!40000 ALTER TABLE `plus_ads` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `plus_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plus_reactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `object_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `reaction` enum('like','dislike') CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_user` (`object_type`,`object_id`,`user_id`),
  KEY `object_reaction` (`object_type`,`object_id`,`reaction`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `plus_reactions` WRITE;
/*!40000 ALTER TABLE `plus_reactions` DISABLE KEYS */;
INSERT INTO `plus_reactions` VALUES (1,'comment_users',2,1,'like','2026-05-07 20:15:44'),(2,'comment_users',3,1,'dislike','2026-05-07 20:15:46'),(3,'comment_users',1,1,'like','2026-05-07 20:15:48');
/*!40000 ALTER TABLE `plus_reactions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sort` smallint NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `polls` WRITE;
/*!40000 ALTER TABLE `polls` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `polls_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls_questions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_poll` int NOT NULL,
  `subject` varchar(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `polls_questions` WRITE;
/*!40000 ALTER TABLE `polls_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls_questions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `polls_voting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls_voting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_poll` int NOT NULL,
  `id_question` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `polls_voting` WRITE;
/*!40000 ALTER TABLE `polls_voting` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls_voting` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `priv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `priv` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `EDIT_PRIV` smallint NOT NULL DEFAULT '0' COMMENT 'Кто может править классы . Делать это может только одна группа',
  `SIGNUP` smallint NOT NULL DEFAULT '0' COMMENT 'Класс по умолчанию',
  `DATE` datetime NOT NULL,
  `COLOR` varchar(6) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `NAME` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL COMMENT 'Название класса',
  `upload` smallint NOT NULL DEFAULT '0' COMMENT 'Загрузка торрента',
  `cats` smallint NOT NULL DEFAULT '0' COMMENT 'РЕдактирование категорий',
  `chat_delete` smallint NOT NULL DEFAULT '0' COMMENT 'Удалять чужые сообщения в чате',
  `chat_view` smallint NOT NULL DEFAULT '0' COMMENT 'Видеть чат',
  `chat_clear` smallint NOT NULL DEFAULT '0' COMMENT 'Очистка чата',
  `comments_edit` smallint NOT NULL DEFAULT '0' COMMENT 'Редактировать чужые комментарии',
  `comments_delete` smallint NOT NULL DEFAULT '0' COMMENT 'Удалять чужые комментарии',
  `details_banned_view` smallint NOT NULL DEFAULT '0' COMMENT 'Видеть забаненные торренты',
  `details_view` smallint NOT NULL DEFAULT '0' COMMENT 'Просмотр деталей',
  `download_torrent` smallint NOT NULL DEFAULT '0' COMMENT 'Скачивать торрент - файл',
  `download_magnet` smallint NOT NULL DEFAULT '0' COMMENT 'Скачивать через магнет',
  `edit_release` smallint NOT NULL DEFAULT '0' COMMENT 'Редактировать релизы',
  `edit_news` smallint NOT NULL DEFAULT '0' COMMENT 'Новинка месяца',
  `edit_banned` smallint NOT NULL DEFAULT '0' COMMENT 'Банить релизы',
  `messages` smallint NOT NULL DEFAULT '0' COMMENT 'Массовое отправление сообщений',
  `multitracker_accounts` smallint NOT NULL DEFAULT '0' COMMENT 'Просмотр мультитрекерных аккаунтов',
  `setting_user` smallint NOT NULL DEFAULT '0' COMMENT 'Редактировать профиль пользователя',
  `setting_class` smallint NOT NULL DEFAULT '0' COMMENT 'Изменение класса',
  `ip_util` smallint NOT NULL DEFAULT '0' COMMENT 'IP утилиты',
  `profile_view` smallint NOT NULL DEFAULT '0' COMMENT 'Просмотр профиля',
  `search_query` smallint NOT NULL DEFAULT '0' COMMENT 'Мониторинг поиска',
  `sessions_view` smallint NOT NULL DEFAULT '0' COMMENT 'Просмотр сессий',
  `sessions_clear` smallint NOT NULL DEFAULT '0' COMMENT 'Очистка сессий',
  `users_view` smallint NOT NULL DEFAULT '0' COMMENT 'Просмотр участников',
  `news_add` smallint NOT NULL DEFAULT '0' COMMENT 'Добавление новостей',
  `bad_rating` smallint NOT NULL DEFAULT '0' COMMENT 'Банить аккаунт , если плохой рейтинг',
  `user_add` smallint NOT NULL DEFAULT '0' COMMENT 'Добавление пользователя',
  `faq_moderate` smallint NOT NULL DEFAULT '0',
  `block_administrators` smallint NOT NULL DEFAULT '0',
  `block_moderators` smallint NOT NULL DEFAULT '0',
  `polls_moderate` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `priv` WRITE;
/*!40000 ALTER TABLE `priv` DISABLE KEYS */;
INSERT INTO `priv` VALUES (1,0,1,'0000-00-00 00:00:00','68838B','Пользователи',1,0,0,1,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,1,0,0,0,0,0),(2,0,0,'0000-00-00 00:00:00','00BFFF','VIP',1,1,0,1,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0),(3,0,0,'0000-00-00 00:00:00','FFA500','Релизеры',1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,0,0,0,0,1,1,0,0,1,0,0,0,0,0,0,0),(4,0,0,'0000-00-00 00:00:00','CD3333','Модераторы',1,0,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,0,1,1,1,0,0,1,0,0,0,1,0,1,1),(5,0,0,'0000-00-00 00:00:00','9ACD32','Администраторы',1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,0,0,1,1,1,1,1),(6,1,0,'0000-00-00 00:00:00','9B30FF','Создатели',1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,1,1,1,1,1),(7,0,0,'2011-01-02 16:32:31','','Гости',0,0,0,1,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `priv` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `retrackers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `retrackers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0',
  `announce_url` varchar(500) NOT NULL,
  `mask` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `retrackers` WRITE;
/*!40000 ALTER TABLE `retrackers` DISABLE KEYS */;
/*!40000 ALTER TABLE `retrackers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `search_query`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_query` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `num_views` int NOT NULL DEFAULT '1',
  `num_torrents` int NOT NULL,
  `id_user` int NOT NULL,
  `last_date` datetime NOT NULL,
  `sended` smallint NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  KEY `idx_search_query_user_last` (`id_user`,`last_date`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `search_query` WRITE;
/*!40000 ALTER TABLE `search_query` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_query` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(32) CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `user_id` int NOT NULL,
  `last_access` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` int NOT NULL,
  `user_agent` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `php_self` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `idx_sessions_user_access` (`user_id`,`last_access`),
  KEY `idx_sessions_last_access` (`last_access`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'cfeb7f7a71e2924e22bc498b5fa3ac55',-1,'2026-05-03 13:21:35',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(2,'477ad948299b8fc08c1edae2b8accea1',1,'2026-05-04 18:14:43',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(3,'4419cd46a2c17e5b261d3fa4b79c831e',-1,'2026-05-04 18:12:34',-1062715135,'curl/8.7.1','/index.php'),(4,'6d2a1e1d9e1277da15506290d80c99ef',-1,'2026-05-04 18:12:42',2130706433,'','/profile.php'),(5,'c1d890cd0410ff2a9bb87e3f0b710c51',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/shop.php'),(6,'af21a6b3223d81458ef7f3784f1cf86b',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/index.php'),(7,'870f06414b12fdfd5e1352db79df33a7',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/profile.php'),(8,'38d500d2c5ab1f31d23dd07215e01cb9',1,'2026-05-07 20:15:50',-1185611747,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(9,'d034ddc9c15e4307e1f626339c0a8d77',2,'2026-05-08 14:44:27',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `shop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `voice` float NOT NULL,
  `file` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shop` WRITE;
/*!40000 ALTER TABLE `shop` DISABLE KEYS */;
INSERT INTO `shop` VALUES (1,'1.png','5 GB к раздаче',20,'5gb.php','0000-00-00 00:00:00'),(2,'2.png','15 GB к раздаче',35,'15gb.php','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `shop` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `snatched`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `snatched` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userid` int DEFAULT '0',
  `torrent` int unsigned NOT NULL DEFAULT '0',
  `uploaded` bigint unsigned NOT NULL DEFAULT '0',
  `downloaded` bigint unsigned NOT NULL DEFAULT '0',
  `startedat` int NOT NULL,
  `completedat` int NOT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `snatch` (`torrent`,`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `snatched` WRITE;
/*!40000 ALTER TABLE `snatched` DISABLE KEYS */;
/*!40000 ALTER TABLE `snatched` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category` int NOT NULL DEFAULT '0',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `howmuch` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `torrent_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `torrent_ratings` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_ratings` WRITE;
/*!40000 ALTER TABLE `torrent_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `torrent_ratings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `torrent_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `torrent_views` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `torrent_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `visitor_hash` char(40) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `torrent_visitor` (`torrent_id`,`visitor_hash`),
  KEY `torrent_id` (`torrent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_views` WRITE;
/*!40000 ALTER TABLE `torrent_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `torrent_views` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `torrents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `torrents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banned` enum('1','0') CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '0',
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `size` bigint NOT NULL,
  `downloaded` int NOT NULL,
  `descr` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `infohash` varbinary(40) NOT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `id_category` int NOT NULL,
  `id_user` int NOT NULL,
  `added` datetime NOT NULL,
  `image` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `multi` enum('1','0') CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '0',
  `completed` int NOT NULL,
  `last_action` datetime NOT NULL,
  `screen_1` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `screen_2` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `screen_3` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `screen_4` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `video_vkontakte` text NOT NULL,
  `news` smallint NOT NULL DEFAULT '0',
  `content_type` varchar(64) NOT NULL DEFAULT '',
  `subtitles` text NOT NULL,
  `languages` text NOT NULL,
  `genres` text NOT NULL,
  `meta_info` text NOT NULL,
  `countries` text NOT NULL,
  `type` enum('multi','single') NOT NULL,
  `num_files` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_torrents_banned_added` (`banned`,`added`),
  KEY `idx_torrents_category_banned_added` (`id_category`,`banned`,`added`),
  KEY `idx_torrents_user_added` (`id_user`,`added`),
  KEY `idx_torrents_news_added` (`news`,`added`),
  KEY `idx_torrents_type` (`type`),
  KEY `idx_torrents_content_type` (`content_type`),
  KEY `idx_torrents_name` (`name`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrents` WRITE;
/*!40000 ALTER TABLE `torrents` DISABLE KEYS */;
/*!40000 ALTER TABLE `torrents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `trackers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trackers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `torrent` int unsigned NOT NULL,
  `tracker` varchar(255) NOT NULL DEFAULT 'localhost',
  `seeders` int unsigned NOT NULL DEFAULT '0',
  `leechers` int unsigned NOT NULL DEFAULT '0',
  `lastchecked` int unsigned NOT NULL DEFAULT '0',
  `state` varchar(300) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `torrent` (`torrent`,`tracker`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers` WRITE;
/*!40000 ALTER TABLE `trackers` DISABLE KEYS */;
/*!40000 ALTER TABLE `trackers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `avatar` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `password_code` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `ip` int NOT NULL,
  `class` int NOT NULL,
  `last_access` datetime NOT NULL,
  `added` datetime NOT NULL,
  `passkey` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `uploaded` bigint NOT NULL,
  `downloaded` bigint NOT NULL,
  `bad_rating` smallint NOT NULL DEFAULT '0',
  `money` int NOT NULL,
  `bonus` float NOT NULL DEFAULT '0',
  `sex` smallint NOT NULL DEFAULT '1',
  `birthday_date` date DEFAULT NULL,
  `profile_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `notify_comments` tinyint NOT NULL DEFAULT '0',
  `download_local_retracker` tinyint NOT NULL DEFAULT '1',
  `theme_dark` tinyint NOT NULL DEFAULT '0',
  `plus_until` datetime DEFAULT NULL,
  `plus_permanent` tinyint NOT NULL DEFAULT '0',
  `plus_source` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `plus_badge` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'star',
  `profile_slug` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `website` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `icq` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `banned` smallint NOT NULL DEFAULT '0',
  `last_chat` int NOT NULL,
  `num_messages` int NOT NULL,
  `num_friends` int NOT NULL,
  `voice` float NOT NULL DEFAULT '0',
  `confirm` smallint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `email` (`email`),
  KEY `idx_users_profile_slug` (`profile_slug`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','1_1778169081_0c7d3930.jpg','admin@admin.com','$2y$12$3ImCq59OZWwh3exrrcbtYOzvSD6sSoGU6WULEyr09QtgMPbJ8HiwW','',-1185611747,6,'2026-05-07 20:16:32','2026-05-04 18:14:43','ec932acf7c4e32b1f8f26ea1911bc30a',0,0,0,0,2327.94,1,'2009-04-04','',0,1,0,NULL,0,'','star','anminchd','','',0,0,0,0,0,1),(2,'nickmsk98','','sdfqwerfqwe@yandex.ru','$2y$12$i4wsRAw0O49PAiPM7vRI4u5QwJ7B8kjbtgTPKLA0/2n6fy0i1WHbm','',-1062715135,1,'2026-05-08 14:44:27','2026-05-08 14:44:27','52f0cf008ee335c101ebc51b32d2644c',0,0,0,0,633.36,1,'2008-05-04','',0,1,0,NULL,0,'','star','','','',0,0,0,0,0,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `blocked_user_id` int NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_blocked_unique` (`user_id`,`blocked_user_id`),
  KEY `blocked_user_id` (`blocked_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users_blacklist` WRITE;
/*!40000 ALTER TABLE `users_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_blacklist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

