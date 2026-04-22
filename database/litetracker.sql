
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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `lite` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin */ /*!80016 DEFAULT ENCRYPTION='N' */;

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
DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_torrent` int NOT NULL,
  `id_user` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (3,4,2,'2026-04-22 16:22:14');
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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
INSERT INTO `chat` VALUES (7,2,'nickmsk9',6,'2026-04-19 15:39:43','[b]nickmsk9[/b]: xtg'),(6,2,'nickmsk9',6,'2026-04-19 15:30:46','я умею чистить чат');
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_news` WRITE;
/*!40000 ALTER TABLE `comments_news` DISABLE KEYS */;
INSERT INTO `comments_news` VALUES (1,1,2,'2026-04-19 16:11:07','чче?',0,2,'2026-04-19 16:11:07'),(2,1,2,'2026-04-19 17:07:47','Комментарий удалён пользователем сайта',0,2,'2026-04-22 15:26:22'),(3,1,2,'2026-04-22 15:26:26','sdfsdfs',1,2,'2026-04-22 15:26:26');
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_torrents` WRITE;
/*!40000 ALTER TABLE `comments_torrents` DISABLE KEYS */;
INSERT INTO `comments_torrents` VALUES (1,4,2,'2026-04-19 04:33:06','вапвыы',0,2,'2026-04-19 04:33:06'),(2,4,2,'2026-04-19 04:33:18','работаем',0,2,'2026-04-19 04:33:18'),(3,4,2,'2026-04-19 04:33:22','[b]nickmsk9[/b], че',0,2,'2026-04-19 04:33:22'),(4,4,2,'2026-04-19 17:06:49','[b]nickmsk9[/b], красота?',0,2,'2026-04-19 17:06:49'),(5,4,2,'2026-04-22 15:26:37','dfgdfgd',1,2,'2026-04-22 15:26:37');
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
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users` WRITE;
/*!40000 ALTER TABLE `comments_users` DISABLE KEYS */;
INSERT INTO `comments_users` VALUES (20,2,2,'2026-04-22 16:31:00','ваыпвапыв',0,0,NULL),(4,1,2,'2026-04-17 13:39:39','вапвапва',0,0,NULL),(5,1,2,'2026-04-17 13:39:42','вапвапвапвапвапв',0,0,NULL),(6,1,2,'2026-04-17 13:39:44','вапвапвапвапвап',0,0,NULL),(7,1,2,'2026-04-17 13:39:46','вапвапвапва',5,0,NULL),(8,1,2,'2026-04-17 13:39:49','вапвапвапвапвапв',6,0,NULL),(9,1,2,'2026-04-17 13:39:51','впарвапрвапрвапрва',6,0,NULL),(21,2,2,'2026-04-22 16:31:01','вапыап',0,0,NULL),(11,1,2,'2026-04-17 16:35:33','xt',9,0,NULL),(17,2,2,'2026-04-19 17:01:59','прикол',0,0,NULL),(18,2,2,'2026-04-19 17:07:11','в чем фвфвф',17,2,'2026-04-19 17:07:22'),(19,1,2,'2026-04-22 16:24:52','вапва',0,0,NULL),(22,2,2,'2026-04-22 16:31:02','пива',0,0,NULL),(23,2,2,'2026-04-22 16:31:04','вапывап',21,0,NULL),(24,1,2,'2026-04-22 16:31:12','пвапвыап',0,0,NULL);
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
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
INSERT INTO `cron` VALUES ('autoclean_interval',1000),('autoclean_last',1776874585),('multi_remote',1),('remotecheck_interval',600),('remote_torrents',30),('remotepeers_cleantime',10800),('remote_lastchecked',0),('in_remotecheck',0),('num_checked',276),('last_remotecheck',1776875426),('multi_timeout',100);
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
INSERT INTO `faq` VALUES (1,1,'2011-08-01 19:06:53','2011-08-02 06:49:54',1,'Как скачивать отсюда ?','На нашем трекере есть два варианта скачивания  . \r\n[i]Но сначала немного к слову :[/i] для того , чтобы вы могли скачивать , вам нужно скачать клиент для сетей BitTorrent ( к примеру , [url=http://www.utorrent.com/]uTorrent[/url])\r\n[url=http://www.utorrent.com/][img]http://www.utorrent.com/images/interface/logo.png[/img][/url]\r\n\r\nИтак , [b]первый вариант[/b]  : [u]скачивание торрент - файла[/u] .\r\nЗаходим Поиск релизов [b]->[/b] Какой - нибудь торрент [b]->[/b] Скачать торрент (ссылка слева , под обложкой) .\r\n[URL=http://xrupic.ru/share/321873-.png][IMG]http://xrupic.ru/graphic/thumbs/321873--th.png[/IMG][/URL]\r\n\r\n\r\nПосле нажатия , вам вылезет окошко сохранения , нажмите на \"Открыть через: uTorrent\" . \r\nПосле вас перебросит в клиент ,  где вы укажете , куда сохранить тот или иной файл . \r\n\r\n[URL=http://xrupic.ru/share/321874-.png][IMG]http://xrupic.ru/image/321874-.png[/IMG][/URL]\r\n\r\nИ , [b]второй вариант[/b] : [u]скачивание magnet - ссылкой[/u] . \r\nЕсли , к примеру , у какого - либо релиза нету торрент-файла , то вы всегда сможете получить magnet - ссылку ! \r\nВсе очень просто : Поиск релизов [b]->[/b] Какой - нибудь торрент [b]->[/b] Скачать magnet(ссылка слева , под обложкой) . После клика , вам преложат выбрать программу , через которую будете производить скачивание , а дальше все тоже самое : выбираете в какую папку будет идти скачивание ... \r\n\r\nСкачивать можно бесплатно и без регистрации .  \r\n\r\nДополнение 1: Если у вас закрыт порт ,  включите DHT !');
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (4,4,'Project.Hail.Mary.2026.WEBRip.1080p.H264.DD51.mkv',19894460567);
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
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mail` WRITE;
/*!40000 ALTER TABLE `mail` DISABLE KEYS */;
INSERT INTO `mail` VALUES (2,'Сообщение','пишу лс','2026-04-17 18:33:00',1,2,0,0,1),(4,'Сообщение','смтисм','2026-04-18 05:20:56',1,2,0,0,1),(5,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(6,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(7,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(8,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(9,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(10,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(11,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(12,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(13,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(14,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(15,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(16,'Сообщение','ку','2026-04-22 16:14:33',1,2,0,0,0);
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Смена announce URL','Трекер переходит на безопасное соединение HTTPS! Все новые торренты должны использовать адрес аннонсера https://bt.animelayer.ru:443. Скоро поддержка старого HTTP (http://animelayer.ru:80) будет отключена. Рекомендуем обновить клиенты и раздачи, а для загрузки использовать TransmissionBT.','2026-04-19 14:19:55',2);
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
INSERT INTO `orbital_blocks` VALUES (1,'Поиск','c',1,0,'block-search.php','all','index'),(2,'Чат','c',2,0,'block-chat.php','all','index'),(3,'Новинки месяца','c',4,1,'block-newreleases.php','all','index'),(4,'Нагрузка на сервер','c',5,1,'block-load_in_server.php','all','index'),(5,'Категории','l',1,1,'block-categories.php','all','all'),(6,'Теги','d',3,1,'block-tags.php','all','browse'),(7,'ВКонтакте','l',2,1,'block-vkontakte.php','all','all'),(15,'Кто он-лайн','d',1,0,'block-online.php','all','index'),(9,'Новости','l',3,1,'block-news.php','all','index'),(17,'Опрос','c',3,1,'block-poll.php','users','index'),(16,'Статистика','d',2,0,'block-stats.php','all','index');
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
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `search_query` WRITE;
/*!40000 ALTER TABLE `search_query` DISABLE KEYS */;
INSERT INTO `search_query` VALUES (1,'szdczsfdz',1,0,2,'2026-04-18 05:36:23',0),(2,'проект',1,1,2,'2026-04-18 06:06:41',0);
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
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=MyISAM AUTO_INCREMENT=423 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'dced3233c4d7cd8128474ef124ed7771',1,'2011-08-13 19:32:50',2130706433,'Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0','/index.php'),(2,'fb7760d75892d8cb088e5578e2bf63a5',1,'2011-08-13 19:34:42',2130706433,'Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0','/ajax/chat.php'),(3,'c04cf54b71de647454cfc0dfaa4872d0',-1,'2026-04-14 16:08:45',-1185611747,'curl/8.7.1','/index.php'),(4,'18cb2f516a5715af63fc529965d054a8',-1,'2026-04-14 16:21:35',-1185611747,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(5,'a129db590f2e10fc711efc0ae2d9280e',-1,'2026-04-14 16:10:31',-1185611747,'curl/8.7.1','/index.php'),(6,'41ad198a262695be346c5257dae264f3',-1,'2026-04-14 16:11:58',-1185611747,'curl/8.7.1','/index.php'),(7,'bd2ebf7a90b4db9f1c70e3b7e7d3ce82',-1,'2026-04-14 16:11:58',-1185611747,'curl/8.7.1','/index.php'),(8,'0407b7cf557ae3c4dc878b0c37e97444',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/index.php'),(9,'8bf8710c89e1d96b5d8b2e8cb3d8321b',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/browse.php'),(10,'68f50bc2ebc0a3d9768e3b0f67532c52',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/login.php'),(11,'10bbae56f4eb2497fd6febc718f7f540',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/signup.php'),(12,'cca54e1ee37e3e0a8e52c563e25e2068',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/news.php'),(13,'88a1c8d9b177f2b55fd388fdd99b6dbe',-1,'2026-04-14 16:12:21',-1185611747,'curl/8.7.1','/faq.php'),(14,'9b13b7bc51565de33d2a305fe462e4af',-1,'2026-04-14 16:17:04',-1185611747,'curl/8.7.1','/index.php'),(15,'16dc7200d3b9b6e8c0fc4b4f2ae3bad9',-1,'2026-04-14 16:17:04',-1185611747,'curl/8.7.1','/index.php'),(16,'26cd9e5d10916ff5dc838e09f20767eb',-1,'2026-04-14 16:17:05',-1185611747,'curl/8.7.1','/browse.php'),(17,'ef3c4b14c9101a95e64c9dd87593c6f8',-1,'2026-04-14 16:17:05',-1185611747,'curl/8.7.1','/login.php'),(18,'0f4e19f4cc022f09ebf4859736bd9fe4',-1,'2026-04-14 16:17:05',-1185611747,'curl/8.7.1','/signup.php'),(19,'a7de2a2664248606d6748864da96bfb3',-1,'2026-04-14 16:17:05',-1185611747,'curl/8.7.1','/news.php'),(20,'6edd9d9bb34a8b39e3ab641c8cea9103',-1,'2026-04-14 16:17:05',-1185611747,'curl/8.7.1','/faq.php'),(21,'0bcdc96de7c6a3602583319db1f3189d',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/index.php'),(22,'3992d82c05c9e288ba893da597d2ff59',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/browse.php'),(23,'2277441e6bcd9bd1fd5a11ed6e8931e7',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/login.php'),(24,'7d4ba918fc27e2b76bc6962762386e16',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/signup.php'),(25,'17426ea11ebb1d78c25a144005e6548f',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/news.php'),(26,'524f9bf89e07f5e13480b4981c9ec22f',-1,'2026-04-14 16:17:23',-1185611747,'curl/8.7.1','/faq.php'),(27,'afefcc8ab89f5a6634127e181d47e0dd',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/index.php'),(28,'37b547195c1f0749ccd36dfcee6287d5',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/index.php'),(29,'5309136f4d91dd6f24d517afc3581409',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/browse.php'),(30,'2fb8c1c110aca5ca5d10ac0595007c31',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/login.php'),(31,'aa68eaf6f3a349bc91e4ce4f8a179492',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/signup.php'),(32,'b31b34ff149ea1c06b4aed07de9f0c4b',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/news.php'),(33,'1ffafee57f7df85ecdcbc87b855a0fe1',-1,'2026-04-14 16:18:06',-1185611747,'curl/8.7.1','/faq.php'),(34,'f888f8ba0c475f0c6bdc86281ef7a646',-1,'2026-04-14 16:18:55',-1185611747,'curl/8.7.1','/download.php'),(35,'d971a5fa32773827d2744a128cb3803d',-1,'2026-04-14 16:18:58',-1185611747,'curl/8.7.1','/download.php'),(36,'98b7e59d66a149d11eedeb9ddfd721af',-1,'2026-04-15 16:01:34',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(37,'c8f8c3d9fbefe04d5bcf0e85984bfc5c',-1,'2026-04-15 15:17:24',-1062715135,'curl/8.7.1','/signup.php'),(38,'27941529d9bb4fb1dc6bc61e42e1bd60',-1,'2026-04-15 15:17:42',-1062715135,'curl/8.7.1','/signup.php'),(39,'98ce31bc3af1266f7008963edd98f03b',-1,'2026-04-15 15:18:58',-1062715135,'curl/8.7.1','/index.php'),(40,'1c2fac7d711954308b795ee8e15d5ffd',-1,'2026-04-15 15:22:44',-1062715135,'curl/8.7.1','/index.php'),(41,'c07eebdfc1e1f356d631e38a9c4f8c75',-1,'2026-04-15 15:22:44',-1062715135,'curl/8.7.1','/signup.php'),(42,'6e0a5022707a3d2c239b3e34d741e390',-1,'2026-04-15 15:22:47',-1062715135,'curl/8.7.1','/signup.php'),(43,'ace5845d7026ff205ca2b88c513bd286',-1,'2026-04-15 15:22:47',-1062715135,'curl/8.7.1','/index.php'),(44,'1ab395a787bca270068cc833e321d415',-1,'2026-04-15 15:23:10',-1062715135,'curl/8.7.1','/browse.php'),(45,'949c489583f88657fa5cd3f2e43a716a',-1,'2026-04-15 15:28:44',-1062715135,'curl/8.7.1','/browse.php'),(46,'15209576dc8d85f52f987f5a7dc0e186',-1,'2026-04-15 15:28:44',-1062715135,'curl/8.7.1','/index.php'),(47,'26f63d39afff149a5c995afeb71fedce',-1,'2026-04-15 15:28:44',-1062715135,'curl/8.7.1','/signup.php'),(48,'545fb4d3b46ad0bf28a5d5ae1a1e35a8',-1,'2026-04-15 15:29:46',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/update.peers.php'),(49,'b86527274a68f7df71128532e236b46a',-1,'2026-04-15 15:29:46',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/update.peers.php'),(50,'1c86df9f4cd2cbc5680c85ebd7d97404',-1,'2026-04-15 15:30:25',-1062715135,'curl/8.7.1','/index.php'),(51,'7f7adf9f6ddd4ee1e623be80ded17015',-1,'2026-04-15 15:30:26',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/index.php'),(52,'fbe4f6944a5f1aa45dfd1ccf59518273',-1,'2026-04-15 15:30:42',-1062715135,'curl/8.7.1','/profile.php'),(53,'04ceccb0caf927eea3804a5613d7eaf6',-1,'2026-04-15 15:30:43',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/profile.php'),(54,'224f17051869aceb9ad19fef1ceb7cda',-1,'2026-04-15 15:36:35',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/index.php'),(55,'60d5de03f8a42311a67b3672712c6fc5',-1,'2026-04-15 15:36:35',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/signup.php'),(56,'11f8a41e69db5eff1b09958dff4ccc36',-1,'2026-04-15 15:42:11',-1062715135,'curl/8.7.1','/index.php'),(57,'6b04069c0ef3d28495b665ad59f2a20b',-1,'2026-04-15 15:42:19',-1062715135,'curl/8.7.1','/index.php'),(58,'3e1288ee7d1cdce4e0e37dff6d6877db',2,'2026-04-15 15:42:53',-1062715135,'curl/8.7.1','/index.php'),(59,'9fe4d936e06b0ad5b716af47738b5708',2,'2026-04-15 15:43:16',-1062715135,'curl/8.7.1','/index.php'),(60,'ffe0f32611939188eab44bb389675c4d',-1,'2026-04-15 15:43:17',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/index.php'),(61,'2cf88ae10808165d0daa2fc8c0ef21da',-1,'2026-04-15 15:43:32',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/index.php'),(62,'d6e802eac0078b10c9f62fac78290103',-1,'2026-04-15 15:54:18',-1062715135,'curl/8.7.1','/index.php'),(63,'1a8e4fa893f9c299cc636faa621da37c',-1,'2026-04-15 15:54:19',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/update.peers.php'),(64,'65aef48d031de40bda8c841f59eb4b27',-1,'2026-04-15 15:54:19',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/update.peers.php'),(65,'9da9a1feb8e6331e25634996a9feea3c',-1,'2026-04-15 15:56:59',-1062715135,'curl/8.7.1','/index.php'),(66,'745c46dd002dc4405ccfc7772e47ebb5',-1,'2026-04-15 15:57:07',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/signup.php'),(67,'14aee8c9b83a6b19cdabfd9e481a9628',-1,'2026-04-15 15:57:07',-1062715135,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.15 Safari/537.36','/index.php'),(68,'0961fb0aba2346532d8862c6f71d6818',-1,'2026-04-15 15:57:17',-1062715135,'curl/8.7.1','/index.php'),(69,'23d594e27d41809d9fed904d50567c57',-1,'2026-04-15 15:57:17',-1062715135,'curl/8.7.1','/index.php'),(70,'bdc8566ea04f47ebd925b5e57b8ea6b0',2,'2026-04-16 16:32:19',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(71,'f2248f9dd299a10194e581fe1a1b8a1e',-1,'2026-04-16 14:42:24',-1062715135,'curl/8.7.1','/index.php'),(72,'298ab8a3f1760d98024a9993dc2081bb',-1,'2026-04-16 14:42:24',-1062715135,'curl/8.7.1','/login.php'),(73,'e363b66529e59be7ccc96ecd5ec7b045',-1,'2026-04-16 14:42:24',-1062715135,'curl/8.7.1','/news.php'),(74,'9653a781c4fe2081ba543d68483be169',-1,'2026-04-16 14:42:32',-1062715135,'curl/8.7.1','/browse.php'),(75,'f7a0d669974febb1aa44fd1910b7cd5e',-1,'2026-04-16 15:01:40',-1062715135,'curl/8.7.1','/profile.php'),(76,'715ea9e4c0c36c21c1d91490401accde',-1,'2026-04-16 15:01:53',-1062715135,'curl/8.7.1','/my.setting.php'),(77,'7d5be1bb2ece3c78a5795aaf9fe3f1bb',-1,'2026-04-16 15:01:53',-1062715135,'curl/8.7.1','/profile.php'),(78,'f2125bc385760dc2ec3df535fac20661',-1,'2026-04-16 15:01:53',-1062715135,'curl/8.7.1','/my.setting.php'),(79,'c6285a54b086c196fa8f1bf0d6f95c44',-1,'2026-04-16 15:03:55',-1062715135,'curl/8.7.1','/index.php'),(80,'37d8397bf0c36da8cef4b5943c471c5b',-1,'2026-04-16 15:03:55',-1062715135,'curl/8.7.1','/profile.php'),(81,'91517f97eae0a7aaf2c628a5907e41f4',-1,'2026-04-16 15:03:55',-1062715135,'curl/8.7.1','/my.setting.php'),(82,'ce1f6ee234c2572bd894fc4ee992182a',2,'2026-04-16 15:04:42',-1407975423,'','Standard input code'),(83,'060525905e8b0c24db88af575c95493b',2,'2026-04-16 15:04:42',-1407975423,'','Standard input code'),(84,'2af7a2059678699b95fe615e7be4c4f5',2,'2026-04-16 15:04:42',-1407975423,'','Standard input code'),(85,'79da9a5ff4f929c7930f2dff78a29002',2,'2026-04-16 15:05:23',-1407975423,'','Standard input code'),(86,'9860abe4074229f3ab3642a0b5ac2fa7',2,'2026-04-16 15:05:23',-1407975423,'','Standard input code'),(87,'61f5c7d5d926efd8e0e6001e71d13f24',2,'2026-04-16 15:06:11',-1407975423,'','Standard input code'),(88,'14b4d5e1e7d68128d3dd94c81ec1959d',2,'2026-04-16 15:06:19',-1407975423,'','Standard input code'),(89,'2db7a79d97f2bc622198ab4297d1d28a',2,'2026-04-16 15:06:25',-1407975423,'','Standard input code'),(90,'47e69ae2c439c5b633952bf541c5cc9f',2,'2026-04-16 15:06:31',-1407975423,'','Standard input code'),(91,'46764068be1c9e710801c31f6c805bc6',2,'2026-04-16 15:06:45',-1407975423,'','Standard input code'),(92,'6aa849267f6cd1b27c6977f75436c9aa',2,'2026-04-16 15:07:44',-1062715135,'curl/8.7.1','/profile.php'),(93,'4de3f24c1134cd6d8a1ea479d41f402f',2,'2026-04-16 15:07:44',-1062715135,'curl/8.7.1','/my.setting.php'),(94,'53890583f97055439fc4e4237086193f',2,'2026-04-17 04:18:47',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/my.mail.php'),(95,'97e411b377bc4db43ba54135e836eaec',2,'2026-04-17 13:44:59',-1185611747,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/profile.php'),(96,'5d86b19dada388c28bce558f14daf536',-1,'2026-04-17 13:30:18',2130706433,'','/profile.php'),(97,'f2f7c61d09c639c919ef2792ad47b3ec',2,'2026-04-17 13:31:01',2130706433,'','/profile.php'),(98,'52f734b58f9ec7eeb2649a01a29ddfd2',2,'2026-04-17 13:31:01',2130706433,'','/profile.php'),(99,'a8118db8fa07d0dea045fcb2da9b49fc',2,'2026-04-17 13:31:20',2130706433,'','/profile.php'),(100,'269e656042f38da4f39246b33781415f',2,'2026-04-17 13:31:52',2130706433,'','/profile.php'),(101,'5f862a908e8f6fda853d9ccd73575fba',2,'2026-04-17 13:31:52',2130706433,'','/ajax/profile.php'),(102,'2ce44089d858e15dbb816c63202b9d4e',2,'2026-04-17 13:31:52',2130706433,'','/ajax/profile.php'),(103,'33ff2ea8cafcaaa985727216a69f236f',2,'2026-04-17 13:31:52',2130706433,'','/ajax/profile.php'),(104,'eaf518006116e6f8413a9326e9423208',2,'2026-04-17 13:38:49',2130706433,'','/profile.php'),(105,'cc609044573b0d9aba72d632f0f7a571',2,'2026-04-17 13:39:01',2130706433,'','/profile.php'),(106,'5dadfd879923085f6b2f40d8802e2bd7',2,'2026-04-17 13:39:01',2130706433,'','/profile.php'),(107,'c645a6b5dc33a7f48bbfae9f8166181d',-1,'2026-04-17 13:39:13',2130706433,'','/profile.php'),(108,'27f12bf88ad9b0496d6f750950dd1727',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(109,'052b3586403ee78e04fcc9d540716743',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(110,'13407427ef9eb0ac0b21930ed0a1717b',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(111,'819881acffd47f318d7891607d8e1c2a',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(112,'d33782b96a7e3ebfbc5a4f0db846328d',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(113,'fb344a0380f118d6a0e59942388cd40c',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(114,'d50f4da8e0e77c8cdd3b27b161818e01',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(115,'59f85bee3c5879fc4bd654ff199d1c3a',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(116,'964708f41c69ffa48bd47b58d6cd92a5',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(117,'49860ad55251fc439df514c867e092a8',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(118,'f85c719eacbbd48c1d907c8274b012fd',2,'2026-04-17 13:39:21',2130706433,'','/profile.php'),(119,'d90c95eaaf5584f36965e410484016c7',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(120,'c8a74cbf14abc9697640617258da4fc3',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(121,'34f77e3a25748b09bf43afac6b61d929',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(122,'2e6c2a463955c9272c5ede04229dd797',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(123,'b5d5f4f0babcfcbbb42741755d658ce6',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(124,'e99fbe69d9a697d9d0477c9f2b4269a6',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(125,'5c41a98a85c7925de1747257c96c7344',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(126,'c800b95ee4195d0eb3bc77abcaced8c2',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(127,'c356521dcc75a82c959f5835007859c1',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(128,'c0388805c61dd47e93c8426609f8176d',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(129,'35107eeb7f453ececb7c4d025db42b50',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(130,'e5868bbce904ae26312e86c9af59930c',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(131,'93dce4d1ba5381a6481e0bc3831e7545',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(132,'0395f539aad8cd1687594aedde3bb1f1',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(133,'23f0a8e9bd94ac8e0cc582eeb4e23372',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(134,'abd20b6dbbb0a7d03fd7ec053ad7d465',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(135,'ff46b185639aae9447b8b52a9f273c20',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(136,'3530fb53f072f93cc0e0efc7333a3ea5',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(137,'c485d53704ee6a91fe926c923537dad5',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(138,'cece242695699e2c024fdee75b6e9a61',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(139,'5f252f3331068ec835ea1e721217ec35',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(140,'cb6a96e3af5f43147fcee15b1fd1bddc',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(141,'81872b6b9e682b9f256f674a45fd0248',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(142,'9454cec72586e1fdcd3e084222cbeb18',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(143,'44041cb29abfb847a49fe1f34b11e390',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(144,'f14e5c6a7d2a632fd62ea2c8debcfdfd',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(145,'3ef7e1842903fdd9aeb9d3631223621b',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(146,'71598c27fd3f7e5a694ec90e32497c80',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(147,'2d60f0c39993ff7b0961fdfa7e485479',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(148,'5ba6d8f0e29b18c13bf89116074d5ec9',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(149,'80fd7326a279ccbb9070fe033816a46f',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(150,'21f549a99b30189cc373d26ec2a6549a',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(151,'bd9a550a31254e0fe709c2b29160c5e2',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(152,'858cbd9b37510b0356195f8418e53e4a',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(153,'8b1a624b419ae345aa917437f3588f32',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(154,'f62ee3e16b3cba473939e90313146404',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(155,'eee23e0ee7256871fd46854d2c862859',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(156,'825b76fa15ca0158b8b5cc023a7806e0',2,'2026-04-17 13:39:29',2130706433,'','/profile.php'),(157,'c86e0132e618712ba9c95af0180f5a69',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(158,'e98fd252691538ef84c9454f8dedec3c',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(159,'53e3945bd9f74c2ccb348c14219f621a',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(160,'864861265d57cd90cf8755e5781b7062',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(161,'056fb8fc3af2aa64f55574eb85781b86',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(162,'51aac120d762e7b30af39e80d2bbd65b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(163,'67b4fb7ddf97634911626aa2f21457da',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(164,'02cf146811f769b5f239e20fda5bf904',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(165,'dec79beb1c845fc096be4fd781af57af',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(166,'32bf31121d011752699336f6f7327562',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(167,'1490a64fba5d1b07a66cebded3ffcb1f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(168,'969a4b1c96edf7f435e2387ed1402804',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(169,'ad2d2cc5612cbdae25160ce615ab0d92',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(170,'6678587a76bbfe0c7e20fd7068fac23f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(171,'fe0ee65307fab012229c950ba11c1f3d',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(172,'8bfe12c462f54907e52002a2c6e0b922',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(173,'34f363fdfe9967d7371927597e991b35',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(174,'313985221c8fbd9f03352a6f2312e7fd',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(175,'7db278f294e0f844bca54de3dd22e256',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(176,'e5b78510fed7dda2845da686d2c8bf67',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(177,'4bbba8200b948cc35fc81db9b2358728',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(178,'a14e9f508468494aa6975671fb13528e',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(179,'29baf239fde46cf32fbf2f4874cff231',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(180,'7c5f7e93e3aa901d6be422b4a58401c1',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(181,'71ac062b1cce8130d21ca515949df4c5',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(182,'2747ca356c9e7f75fdc0b561e37130e6',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(183,'0fa041b14f9b00f53541701542ec7660',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(184,'850a6c566d90f05aeb00a5060a884148',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(185,'bb9f9a913d22bcad2d3a0e12b1b80746',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(186,'0d189387b582edac30eb69d1edd1453e',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(187,'ff56fab3b562336dba26a86eb16d0ee4',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(188,'77172857b0856fdb19af78b75fe273ae',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(189,'67805f20b4ec37374965fbaf9eccab84',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(190,'ff1321849614866a4b9f47935f38fa19',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(191,'d5259bb44ddacac16e9bfb755caa352f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(192,'b91f60a6a0a6cbce0e022bbad93b8ca0',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(193,'cd2713c76919d0fbf7f3325d0a765718',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(194,'0176d59c62677d8f47764a0fe0c66e8b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(195,'c80651c7e329e2d6894ce85f7681aed2',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(196,'81d3503ee7a939033e1b3e4af4a9f4ee',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(197,'76c1111beb40033391b817b753e9eb61',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(198,'89184eee85564cf7fdb1f15d89f3b395',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(199,'64ed9085aceada4877ec0ae4f494c196',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(200,'31c1d6207779035ef4f4b3ff3209aa53',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(201,'0b7e6d7dae3407821898e3de47a30652',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(202,'de69ba32eb8dc67c23f10e9368117290',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(203,'81166dd339e4e40606df6478c3b70135',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(204,'cf110c70c78dd45aa1886710bc8aca35',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(205,'a1516dc5278991d0bd0c1a86e35dbc0f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(206,'03371c61c2d9b83a4c71dfda8acb1333',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(207,'affb223519e5c5cd536c94d9946692ad',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(208,'eb6db1dd9c8cdb7632eb3b3ead0f9ffe',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(209,'3082ed1fa8e406adb28642a2e53e543b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(210,'7c26300467aa65832426741d5a057077',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(211,'12e53eef9f49792019f6b0958a4ca587',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(212,'f1a564c704604c7de9d40f1b00f4c33a',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(213,'2b9fcdb47eb1f85d2c686e05abcf28e5',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(214,'ba6b9ac23eb144692d8918b11175e88a',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(215,'1c28a5cc2f72f517e1ac20de451bdd0f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(216,'7301e8c3a7d4bb10b2441d1d6c25e167',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(217,'eb170c3dbfaf354fc67007896e6dd543',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(218,'af432047cb815f4cd12dcb5123834b4a',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(219,'2b958a1d8e8b37ed0747a8ed24b9e7b9',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(220,'14c0a2a77be8aa52bc64e9775edd8314',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(221,'e5bbec145b1915bc8f8022391a05ed1e',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(222,'54683c021ef3b940f5240169888126e4',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(223,'47be2f5cddba78269013cfcb52d6831f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(224,'30edc72fa9764f8c93e1a27fb8337b1b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(225,'e66d4ca1a0d20830f16d2631537cf714',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(226,'f93736cd68b50a6cc244dcb80e40022c',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(227,'add9cf79734bef5efc992c067c92cbc3',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(228,'2bbe356e662c386cc2bc0077683a4b67',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(229,'e25f54c9f781b2b2ad9f791ae6c14010',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(230,'a72033bbf3fccd4f2583029e8d41f1e3',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(231,'3c9a56f2770e115c1859cf2168f6171f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(232,'9707a6cb25be9dff4193a81a38280248',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(233,'c2d38d1484489f9fb061817908512060',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(234,'beccf1dad8344f5dfc63a27f1c379365',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(235,'a7cf7871d050e23957073b9469939346',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(236,'19042e8e429d350a9ce9c4ed3cd2c355',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(237,'f727f771835a64bf7a26851488540617',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(238,'7b607b9fee8b30963293bef828c6fafc',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(239,'54b788bfe1e392da6c9ecd10cd20f347',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(240,'d2cd774c0a0fb713e969a8929779cbf9',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(241,'6c73863b32d9eda68d5a6d491649b08b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(242,'e432e155db023a5dd570c41f0f7f565b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(243,'5e1e71b51a8734691df9d65f61e16f7b',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(244,'fe5f22def684a867ab3ab672d2900977',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(245,'ddc055c8250196ccd6d24be5b8528522',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(246,'c7646592cd02341177a2e64fe249a6ff',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(247,'4600fde71204690b7b43a4e559baaf8f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(248,'d57824696ff8e614b8ab64784eee9a95',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(249,'ed5c4ab8a9199549a870c5309093d391',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(250,'0a02bf6acd08f2e27ead8b489efab832',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(251,'863f7e31b726188f4a9d21fb1f88ca4f',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(252,'f88594546756273cf2aff9729ccfccde',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(253,'0d774cb7342cfe28734bab11fa6b67f5',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(254,'aafb6e8c42c5061498379cb68a13ae76',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(255,'a96e2676b053ba14868789e8318f2593',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(256,'3fe0bac57e146e29796d8304dc4d91ff',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(257,'f865957dc2c40e762290cf9febd2cd9e',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(258,'98c960557808bbf02596406cd930ceb7',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(259,'eccec9b0e3a3e6614dcaf9725602792a',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(260,'14dda6a9d7349e94e1821c431c9147bb',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(261,'21fdb30379e1fa16f1fc1be8792d535d',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(262,'fc163a41e7f175a90caeeb26da33dcfc',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(263,'50703b948ece17c0d423dcedec8f8135',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(264,'c0d82d86429b6407e6e719957aaf4418',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(265,'b08eeed18ffc6d6c9a6bcd500de58cb5',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(266,'b0782870cf8fba0461e209f26e5436b9',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(267,'0c149105955838acce2bf56fdfaff3e0',2,'2026-04-17 13:39:30',2130706433,'','/profile.php'),(268,'bed1f312affba05f974ca812ebd9d6ed',2,'2026-04-17 18:51:51',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/upload.php'),(269,'f8ccb2421eafb99281f6dcaa288edeb1',-1,'2026-04-17 18:29:19',-1062715135,'curl/8.7.1','/index.php'),(270,'99745edda968e9c492b835220f287a90',-1,'2026-04-17 18:29:28',-1062715135,'curl/8.7.1','/ajax/profile.php'),(271,'ec56295dfe4565403965d51649be3e89',-1,'2026-04-17 18:29:37',-1062715135,'curl/8.7.1','/ajax/profile.php'),(272,'691966b33f8583d9bd4bf62fa72ca24a',3,'2026-04-17 18:30:20',-1062715135,'curl/8.7.1','/ajax/profile.php'),(273,'ca32618d14fa0a804e9f577dba1bb8ad',3,'2026-04-17 18:30:48',-1062715135,'curl/8.7.1','/ajax/profile.php'),(274,'44ab5c91b2784b3569718a69d3e10560',-1,'2026-04-17 18:32:54',-1062715135,'curl/8.7.1','/ajax/profile.php'),(275,'a51e95c76270b0cb382c0e9517162576',-1,'2026-04-17 18:34:10',-1062715135,'curl/8.7.1','/ajax/profile.php'),(276,'adbeb886f43311d300ca57198aad332f',2,'2026-04-17 18:34:26',-1062715135,'curl/8.7.1','/ajax/profile.php'),(277,'976c955d441b64798958cf2b71c37a7f',2,'2026-04-17 18:34:41',-1062715135,'curl/8.7.1','/profile.php'),(278,'68a80f008a7e87f20c9d314ee2dd6ac1',1,'2026-04-17 18:34:46',-1062715135,'curl/8.7.1','/profile.php'),(279,'4df0db64eb363c5743124c6fe590fe61',1,'2026-04-17 18:34:54',-1062715135,'curl/8.7.1','/ajax/profile.php'),(280,'23da073af22f3504776d69e2bc90e2ef',1,'2026-04-17 18:34:54',-1062715135,'curl/8.7.1','/wall_reports.php'),(281,'cfeb3243f999706a0a5673b3a8854eb8',1,'2026-04-17 18:35:48',-1062715135,'curl/8.7.1','/ajax/profile.php'),(282,'a5c2dddfd20ebb9a932a32e3c96d6b26',1,'2026-04-17 18:35:48',-1062715135,'curl/8.7.1','/ajax/profile.php'),(283,'51f15f20d87ad65214fae4024745d7c3',2,'2026-04-18 06:05:36',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(284,'4de40924148d4df3231f1fc3948fc439',-1,'2026-04-18 05:25:15',-1062715135,'curl/8.7.1','/browse.php'),(285,'210866967de96295cd0be564321ff9ac',-1,'2026-04-18 05:25:22',-1062715135,'curl/8.7.1','/browse.php'),(286,'9e50109fb4e02d49d410f7bff44fe290',-1,'2026-04-18 05:25:22',-1062715135,'curl/8.7.1','/browse.php'),(287,'21b6ce34ad403db7e7e401403581dd5c',-1,'2026-04-18 05:39:58',-1062715135,'curl/8.7.1','/upload.php'),(288,'d1657bcaf3140346598a354d24b669b5',4,'2026-04-18 05:40:13',2130706433,'curl/8.14.1','/upload.php'),(289,'54fe9eccd2c1491b98b84205e78d59a1',4,'2026-04-18 05:40:44',2130706433,'curl/8.14.1','/upload.php'),(290,'45c0c49360d2d93d6e3b220a5a589f6d',4,'2026-04-18 05:41:31',2130706433,'curl/8.14.1','/upload.php'),(291,'e9fabcd7fdd94705415cb88294a046d9',-1,'2026-04-18 05:41:40',-1062715135,'curl/8.7.1','/browse.php'),(292,'84c168f7949fe8534ee2167202584d14',4,'2026-04-18 05:41:40',2130706433,'curl/8.14.1','/upload.php'),(293,'a53cea028d45b344807dd87cc69edcf7',4,'2026-04-18 05:42:58',2130706433,'curl/8.14.1','/upload.php'),(294,'acc811f112629ae2123e5268d7e9924c',-1,'2026-04-18 05:43:08',-1062715135,'curl/8.7.1','/browse.php'),(295,'c85044910691751a091b04ce60384af7',-1,'2026-04-18 05:43:08',-1062715135,'curl/8.7.1','/browse.php'),(296,'0fa46032b7123e61eb2f35f939699c23',-1,'2026-04-18 05:44:28',-1062715135,'curl/8.7.1','/upload.php'),(297,'27b0af0bba91075bec7b9b695b09ffcd',-1,'2026-04-18 05:44:28',-1062715135,'curl/8.7.1','/browse.php'),(298,'321b039eef5461042397781832031dd7',-1,'2026-04-18 05:57:16',-1062715135,'curl/8.7.1','/upload.php'),(299,'5d3377a57a89f1a5ae9846bb0b5349fd',-1,'2026-04-18 05:57:16',-1062715135,'curl/8.7.1','/my.mail.php'),(300,'845ff562fccb848f22d5e1c3c67ad7d8',-1,'2026-04-18 05:57:25',-1062715135,'curl/8.7.1','/upload.php'),(301,'b901b28da0ac2fce85ab90bd8fed777a',-1,'2026-04-18 05:57:25',-1062715135,'curl/8.7.1','/upload.php'),(302,'9f03207d8d95c665a086ed22266d525f',-1,'2026-04-18 05:57:25',-1062715135,'curl/8.7.1','/my.mail.php'),(303,'107ef8451c0e700883cecc1aadc6eb85',-1,'2026-04-18 05:57:25',-1062715135,'curl/8.7.1','/my.mail.php'),(304,'7fb3863bccd3a776a40e496cbe206bed',-1,'2026-04-18 05:58:07',-1062715135,'curl/8.7.1','/upload.php'),(305,'e7d1e0d59ec621b1d39835a21ecd6b32',-1,'2026-04-18 05:58:07',-1062715135,'curl/8.7.1','/upload.php'),(306,'d6e02b5bf7ad6a8f767c636ba98c9aa1',-1,'2026-04-18 05:58:07',-1062715135,'curl/8.7.1','/my.mail.php'),(307,'3cc81a2215c99afc385fd50b017e973f',-1,'2026-04-18 05:58:07',-1062715135,'curl/8.7.1','/my.mail.php'),(308,'5e9cdca4f8e6606b77e9c5a2b08a3005',-1,'2026-04-18 05:58:07',-1062715135,'curl/8.7.1','/my.mail.php'),(309,'dc4fb761c54fb502fd72a8c4c08af28b',4,'2026-04-18 05:58:46',2130706433,'','/my.mail.php'),(310,'b2a47174d806a65a8cec93652a3971a4',4,'2026-04-18 05:58:46',2130706433,'','/upload.php'),(311,'756afa848fcff88f569017b79050a776',4,'2026-04-18 05:58:46',2130706433,'','/my.mail.php'),(312,'339211196bf6a5d7e441c450abde1f24',4,'2026-04-18 05:58:57',2130706433,'','/upload.php'),(313,'ff4388de1720b3ee34a5be13a140e8ab',4,'2026-04-18 05:58:57',2130706433,'','/my.mail.php'),(314,'775732f279d5efccef603ffaef9b75c6',4,'2026-04-18 05:58:57',2130706433,'','/my.mail.php'),(315,'429d2da24d62993d66e32a2262bf55bf',1,'2026-04-18 05:59:21',2130706433,'','/my.mail.php'),(316,'316362cee6f207aa7c40507b12fa9b38',1,'2026-04-18 05:59:21',2130706433,'','/my.mail.php'),(317,'c9a3e183fa2f228feeac72f32a5d2cb6',4,'2026-04-18 06:00:18',2130706433,'','/upload.php'),(318,'c099bb99001e89e0042806af54936e6a',2,'2026-04-18 06:07:22',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/update.peers.php'),(319,'a3c636ed1f753e7f16d1bda9b27bd3b4',2,'2026-04-18 06:18:24',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(320,'7d1992ed9270eb2a7b53052cc7a46d59',2,'2026-04-18 08:59:27',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/details.php'),(321,'13fd3dd347e8d9170ee070e143472bf7',2,'2026-04-18 15:32:42',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/ajax/chat.php'),(322,'7ce7f9d099b47fc00189845b544a9aa9',-1,'2026-04-18 15:22:45',-1407975423,'curl/8.7.1','/index.php'),(323,'56e1846a302c095e8b7e62682c7be409',-1,'2026-04-18 15:22:45',-1407975423,'curl/8.7.1','/index.php'),(324,'634fa9842191da8ea8cb097003eecf8e',-1,'2026-04-18 15:23:35',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','/index.php'),(325,'5bb9257a0fe2d62069f51b3809b23380',-1,'2026-04-18 15:24:27',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','/index.php'),(326,'698f992c97998c7399b9ba484e69c483',-1,'2026-04-18 15:24:38',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','/index.php'),(327,'721bb3e1dc43b85395bfd6e178257acc',-1,'2026-04-18 18:19:49',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.4 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.4 facebookexternalhit/1.1 Facebot Twitterbot/1.0','/index.php'),(328,'0065ec9ad1f745d3845adbe93753a02b',2,'2026-04-18 18:51:14',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/details.php'),(329,'bdd03a56eb37211efee1891284b93c5b',-1,'2026-04-18 18:25:17',-1407975423,'curl/8.7.1','/index.php'),(330,'24bd47851a5b8773c5b5468270e6abca',-1,'2026-04-18 18:42:14',-1407975423,'curl/8.7.1','/details.php'),(331,'30197ac51318b78f643b4a9663db6242',-1,'2026-04-18 18:42:14',-1407975423,'curl/8.7.1','/details.php'),(332,'c31405001cd7694337148c04d1e3cb9b',-1,'2026-04-18 18:42:20',-1407975423,'curl/8.7.1','/details.php'),(333,'7abc6e808232999a6149d87e4cc7a266',-1,'2026-04-18 18:43:11',-1407975423,'curl/8.7.1','/details.php'),(334,'656c4c0c058aae1c8a5814c3aef20c1e',-1,'2026-04-18 18:43:11',-1407975423,'curl/8.7.1','/details.php'),(335,'a7ab55a463949f2bdd9b52a359fc1375',2,'2026-04-18 18:43:35',-1407975423,'curl/8.7.1','/details.php'),(336,'0e86bd0ee267d01afe6aa39285d558cd',2,'2026-04-18 18:43:35',-1407975423,'curl/8.7.1','/details.php'),(337,'4448edd2eb6906f961c0196535486176',2,'2026-04-18 18:44:42',-1407975423,'curl/8.7.1','/details.php'),(338,'27202c2444132c88ba6705a77e9bec7c',2,'2026-04-18 18:45:03',-1407975423,'curl/8.7.1','/details.php'),(339,'6b2c7e0a52012e01e8b5f7f994afdda8',2,'2026-04-18 18:45:10',-1407975423,'curl/8.7.1','/details.php'),(340,'31b430a41b5e593fc5caa6d1f812dfc1',2,'2026-04-18 18:45:16',-1407975423,'curl/8.7.1','/details.php'),(341,'836e621742ba4d180c4bc4a600ee9eed',2,'2026-04-18 18:45:48',-1407975423,'curl/8.7.1','/details.php'),(342,'8407096aa553b38d51a8554918734cba',2,'2026-04-18 18:45:54',-1407975423,'curl/8.7.1','/details.php'),(343,'e0a46cfa8cea585f1e59f3464c2483d6',2,'2026-04-19 04:34:36',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/voice.webmoney.php'),(344,'c84e9633172abae48ed0b26fcbe43e32',-1,'2026-04-19 05:59:57',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/details.php'),(345,'9e4e7c8e892b20f7078d346b29e50b01',2,'2026-04-19 06:28:28',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/my.setting.php'),(346,'1f8584b9aa3d04cb6a12221f3bc10809',2,'2026-04-19 07:22:36',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/ajax/chat.php'),(347,'681ba7081c3b2bcb2a6e58745b78e8ee',2,'2026-04-19 08:48:35',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/ajax/chat.php'),(348,'659001d8e52e2711ba151ccd77b3cdc8',2,'2026-04-19 15:50:17',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/ajax/chat.php'),(349,'916f4288ae71bf509a41bb412bda1a2e',2,'2026-04-19 17:07:48',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/news.php'),(350,'b56bbce41e032cc838f0bd23e75e7513',-1,'2026-04-19 16:40:11',-1407975418,'curl/8.7.1','/update.peers.php'),(351,'4751b92b93cba13610d9a6c400c24b49',-1,'2026-04-19 16:50:11',-1407975418,'curl/8.7.1','/update.peers.php'),(352,'9379ab19f9963271f1d7219c9885a83b',-1,'2026-04-19 17:00:11',-1407975418,'curl/8.7.1','/update.peers.php'),(353,'efc66857e7af4de7d400c62466d151b2',-1,'2026-04-20 16:10:34',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/signup.php'),(354,'43497bffe0b029c6003b6f3ed9bcda27',-1,'2026-04-20 14:20:46',-1407975418,'curl/8.7.1','/update.peers.php'),(355,'988a8c353a13d1f930c3428b96df1345',-1,'2026-04-20 14:30:47',-1407975418,'curl/8.7.1','/update.peers.php'),(356,'a9c0c8454c468041b7322f0c24fa7a18',-1,'2026-04-20 14:40:47',-1407975418,'curl/8.7.1','/update.peers.php'),(357,'a37a24d826357eaf231e98a717d13356',-1,'2026-04-20 14:50:47',-1407975418,'curl/8.7.1','/update.peers.php'),(358,'50e398aff8b3b8062a99e20e78bddb70',-1,'2026-04-20 15:00:47',-1407975418,'curl/8.7.1','/update.peers.php'),(359,'e616e62e5fd4a87c2eff7b4c8f6d6ae9',-1,'2026-04-20 15:10:48',-1407975418,'curl/8.7.1','/update.peers.php'),(360,'689d44d56501ba6b56e5ef599a216d28',-1,'2026-04-20 15:20:48',-1407975418,'curl/8.7.1','/update.peers.php'),(361,'a5b54db124843a6c1fc940beee5954d6',-1,'2026-04-20 15:30:48',-1407975418,'curl/8.7.1','/update.peers.php'),(362,'2ac792914f08db3e45b126425efaa8c3',-1,'2026-04-20 15:40:48',-1407975418,'curl/8.7.1','/update.peers.php'),(363,'7bc0e69e51c7a0252353ee94a8266ee7',-1,'2026-04-20 15:50:48',-1407975418,'curl/8.7.1','/update.peers.php'),(364,'224b45d3581678268e9d1abd71363664',-1,'2026-04-20 16:00:49',-1407975418,'curl/8.7.1','/update.peers.php'),(365,'869c60261ef295286e3437e4f5350ecc',-1,'2026-04-20 16:10:49',-1407975418,'curl/8.7.1','/update.peers.php'),(366,'9c10e7b202ffa10e9bac4627a6399858',-1,'2026-04-20 16:20:49',-1407975418,'curl/8.7.1','/update.peers.php'),(367,'8b99c3248b41672b2e61464c79ba725c',2,'2026-04-21 17:49:49',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/profile.php'),(368,'26ef426c39a4411802b78657342a8010',-1,'2026-04-21 14:58:18',-1407975423,'curl/8.7.1','/index.php'),(369,'7dc274b5c170b772c4f60aa375596d5d',-1,'2026-04-21 14:58:18',-1407975423,'curl/8.7.1','/login.php'),(370,'293106450729d1dffaf6e68b9694845c',-1,'2026-04-21 15:00:07',-1407975422,'curl/8.7.1','/update.peers.php'),(371,'84a8e8a7fbb85c66ed674ecfff3ec166',-1,'2026-04-21 15:04:35',-1062715135,'curl/8.7.1','/index.php'),(372,'09578cedf487cfe3b4cb4afc0f49c1ef',-1,'2026-04-21 15:05:02',-1062715135,'curl/8.7.1','/login.php'),(373,'de16d69210e10c9a19c3cb8e340be7ea',4,'2026-04-21 15:05:13',-1062715135,'curl/8.7.1','/index.php'),(374,'157262fc0ac837fde27ba3d69b9496d0',-1,'2026-04-21 15:06:11',-1062715135,'curl/8.7.1','/index.php'),(375,'775ef08023b71f6543a3ec09ea4b53cf',-1,'2026-04-21 15:10:39',-1062715135,'curl/8.7.1','/index.php'),(376,'a8600238b7b199f6df991cd6987fc0d6',-1,'2026-04-21 15:20:03',-1062715135,'curl/8.7.1','/index.php'),(377,'118f0c7c19feddcd7899845fbd1f96fa',-1,'2026-04-21 15:20:03',-1062715135,'curl/8.7.1','/signup.php'),(378,'4704263b4864ca365caf4977c08b69b3',-1,'2026-04-21 15:20:46',-1062715135,'curl/8.7.1','/index.php'),(379,'e52094f45fbf4641467898176eb2ec35',-1,'2026-04-21 15:26:10',-1062715135,'curl/8.7.1','/index.php'),(380,'509e5dcf2080e23fdd21edcca7d9cfc3',-1,'2026-04-21 15:30:24',-1062715135,'curl/8.7.1','/index.php'),(381,'977da138c54b8cf36eea6f7282466832',-1,'2026-04-21 15:30:24',-1062715135,'curl/8.7.1','/login.php'),(382,'f4b817201eafca2799014acaa465f096',-1,'2026-04-21 15:31:05',-1062715135,'curl/8.7.1','/index.php'),(383,'725b96604f0ff25bdbf3aad9709d863a',-1,'2026-04-21 15:31:05',-1062715135,'curl/8.7.1','/login.php'),(384,'4236bfc39e1db6b7a5da502b78ae273c',-1,'2026-04-21 15:31:22',-1062715135,'curl/8.7.1','/login.php'),(385,'df2b0c6d4f496baa1ee389827ef85933',2,'2026-04-22 16:30:58',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/profile.php'),(386,'86bcf34f95e1b9cd67a943eb42375b41',-1,'2026-04-22 15:45:08',-1062715135,'curl/8.7.1','/index.php'),(387,'84c844ca3c86bea62f97b57f9f207439',-1,'2026-04-22 15:45:13',-1062715135,'curl/8.7.1','/index.php'),(388,'333063a4fad3182c129c6e5ee4514980',-1,'2026-04-22 15:46:06',-1062715135,'curl/8.7.1','/download.php'),(389,'91c87c4341fb526e9a44d93d57a88fb1',-1,'2026-04-22 15:48:00',-1062715135,'curl/8.7.1','/download.php'),(390,'04cfb959c7d13e8afff1d1137bde7194',-1,'2026-04-22 15:48:04',-1062715135,'curl/8.7.1','/download.php'),(391,'cbf397a922e2ecd744f88986b0f5192b',2,'2026-04-22 15:48:48',2130706433,'curl/8.14.1','/download.php'),(392,'5e5725ea99b69744c878c1b844a9d2a0',2,'2026-04-22 15:48:53',2130706433,'curl/8.14.1','/download.php'),(393,'7b54b3ad9879c7640287e3190f27442a',2,'2026-04-22 15:48:55',2130706433,'curl/8.14.1','/download.php'),(394,'bf761d418c0e1d0c3db473e9ca8a0d4d',2,'2026-04-22 15:48:58',2130706433,'curl/8.14.1','/details.php'),(395,'82a16090eaf0bbe3c32b6e7ad5c411e0',2,'2026-04-22 15:49:01',2130706433,'curl/8.14.1','/details.php'),(396,'858654d52c2102fa23f2f694df707add',2,'2026-04-22 15:49:04',2130706433,'curl/8.14.1','/details.php'),(397,'94da9b064906ce48c1fe616110636df5',2,'2026-04-22 15:50:03',2130706433,'curl/8.14.1','/download.php'),(398,'b9ff5139ab3724f5bd2066e1086c08ba',2,'2026-04-22 15:51:19',2130706433,'curl/8.14.1','/download.php'),(399,'9b2dd82a45f41391139ae323c7976eb7',2,'2026-04-22 15:53:16',2130706433,'curl/8.14.1','/download.php'),(400,'85be2dd4011acb0bc4d754c8d22002d1',2,'2026-04-22 16:05:16',2130706433,'curl/8.14.1','/my.mail.php'),(401,'052d5fb2a1c799459add1215eda96329',2,'2026-04-22 16:05:19',2130706433,'curl/8.14.1','/my.mail.php'),(402,'02b13ddbb77f1ded0609fb0b82a6a0bd',2,'2026-04-22 16:06:29',2130706433,'curl/8.14.1','/my.mail.php'),(403,'2a3a86f87d2ab171ba2f6186c8ed8746',2,'2026-04-22 16:06:31',2130706433,'curl/8.14.1','/my.mail.php'),(404,'1e6d15926935e98a1945d5a7a0f5a2ff',2,'2026-04-22 16:06:33',2130706433,'curl/8.14.1','/my.mail.php'),(405,'b7f3658ed69b52af60cdd5415cafb85d',2,'2026-04-22 16:06:36',2130706433,'curl/8.14.1','/my.mail.php'),(406,'c2c62d591e95f9a69a8079a8ab526729',2,'2026-04-22 16:06:36',2130706433,'curl/8.14.1','/my.mail.php'),(407,'8f323f69729961c92f27eaffc591850c',2,'2026-04-22 16:06:38',2130706433,'curl/8.14.1','/my.mail.php'),(408,'0bbf0db17a54b13a6c79d2a90067519a',2,'2026-04-22 16:06:40',2130706433,'curl/8.14.1','/my.mail.php'),(409,'bd5ff35428603f49da0d1f51e9d2a09c',2,'2026-04-22 16:06:40',2130706433,'curl/8.14.1','/my.mail.php'),(410,'134240560cd1233c22a6f70795eca3bc',2,'2026-04-22 16:06:42',2130706433,'curl/8.14.1','/my.mail.php'),(411,'dc600751169b4886d11f70d50754ff3a',-1,'2026-04-22 16:07:34',2130706433,'curl/8.14.1','/my.mail.php'),(412,'e46aa05c2672bf438cf521e78c5084b7',-1,'2026-04-22 16:07:36',2130706433,'curl/8.14.1','/my.mail.php'),(413,'f21d0240b09ffcb97d8707950f8ffa65',-1,'2026-04-22 16:07:38',2130706433,'curl/8.14.1','/my.mail.php'),(414,'095234bdf0788e66b6b1f3828324d417',2,'2026-04-22 16:08:36',2130706433,'curl/8.14.1','/my.mail.php'),(415,'66497b5d3644863bee041dba7570ea9c',2,'2026-04-22 16:08:39',2130706433,'curl/8.14.1','/my.mail.php'),(416,'ff7319b5fb64fa951c548f7f27e261d8',2,'2026-04-22 16:10:11',2130706433,'curl/8.14.1','/my.mail.php'),(417,'f6274b9b6d399d6841c32f91069f7aed',2,'2026-04-22 16:10:12',2130706433,'curl/8.14.1','/my.mail.php'),(418,'06af77e3f1fe8954df2ca05077234466',2,'2026-04-22 16:14:29',2130706433,'curl/8.14.1','/my.mail.php'),(419,'082e7dbf3e9b183ceb07d59125b73cd1',2,'2026-04-22 16:21:04',2130706433,'curl/8.14.1','/my.book.php'),(420,'5d1dc2d1a2eb77190277703cd120c720',2,'2026-04-22 16:21:19',2130706433,'curl/8.14.1','/my.book.php'),(421,'7dab03436faf4ee8185d627b6d7ab5e8',-1,'2026-04-22 16:31:09',-1062715135,'curl/8.7.1','/profile.php'),(422,'a555431e3f485992bdaf894ba2b4b346',-1,'2026-04-22 16:31:12',-1062715135,'curl/8.7.1','/profile.php');
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
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (5,3,'Фантастика',1),(6,3,'драма',1),(7,3,'триллер',1),(8,3,'экранизация',1),(9,3,'фильмы о космосе',1);
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_ratings` WRITE;
/*!40000 ALTER TABLE `torrent_ratings` DISABLE KEYS */;
INSERT INTO `torrent_ratings` VALUES (1,4,2,5,'192.168.65.1','2026-04-22 15:17:31');
/*!40000 ALTER TABLE `torrent_ratings` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrents` WRITE;
/*!40000 ALTER TABLE `torrents` DISABLE KEYS */;
INSERT INTO `torrents` VALUES (4,'0','Проект «Конец света»','[kinozal.tv]id2135330.torrent',19894460567,10,'Информация об аниме\r\nСтрана: Япония\r\nТип: ТВ\r\nЖанр: исекай, комедия, фэнтези, экшен\r\nГод выхода: 2026\r\nКол серий: >12 эп\r\nРежиссер: Цуда Наокацу\r\nПродолжительность: 25 мин\r\nОписание: После триумфального фестиваля Федерация Джура становится главным экономическим центром мира, но процветание порождает опасную зависть. Пока Римуру укрепляет дипломатические связи, из тени нападают те, кто привык тайно править человечеством — могущественный род Россо.\r\nВ этом противостоянии мечи уступят место коварным финансовым интригам и политическим заговорам. Мариабель Россо намерена разрушить репутацию Темпеста и подчинить себе волю Князя Тьмы, используя амбиции западных королей. Римуру предстоит доказать, что его страна способна выстоять не только в честном бою, но и в большой игре, где на кону стоит само право монстров на мирное сосуществование с людьми.\r\n\r\n[b]Дополнительно[/b]\r\nФормат: mkv\r\nРазрешение: 1280x720\r\nСубтитры: английские (полухардсаб), русские (софтсаб)\r\nЯзык: японский\r\n\r\nТоррент был обновлен\r\nПричина: Добавлены 3 эпизод и русские субтитры к нему.',_binary '2fb93f16ce6fb284a7846d4e3b207b0ef660382f','Фантастика,драма,триллер,экранизация,фильмы о космосе',3,2,'2026-04-18 06:04:30','4.jpg','0',0,'2026-04-18 06:04:30','4_0.jpg','','','','',1,'movie','russian','russian,english','action,detective,drama,documentary','licensed','usa,germany','single',1);
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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers` WRITE;
/*!40000 ALTER TABLE `trackers` DISABLE KEYS */;
INSERT INTO `trackers` VALUES (5,4,'localhost',0,0,1776874585,''),(6,4,'http://tr2.torrent4me.com/ann?uk=cAETnuUKbT',50,0,1776870024,'ok_announce'),(7,4,'http://retracker.local/announce',0,0,1776868823,'failed:no_benc_result_or_timeout_announce');
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
  `password` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `password_code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
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
  `profile_text` text COLLATE utf8mb3_bin NOT NULL,
  `notify_comments` tinyint NOT NULL DEFAULT '0',
  `download_local_retracker` tinyint NOT NULL DEFAULT '1',
  `theme_dark` tinyint NOT NULL DEFAULT '0',
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
  KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'jenaDI','','bitsteep@gmail.com','6f08171653dfa90991f0c9590ebf456d','2Asl11Svgp9eHMY41RUVlouySbLZ6x4d',2130706433,6,'2026-04-18 05:59:21','2011-08-13 19:33:21','137583ad9f348f10a6fb93b9168e6955',0,0,0,0,0,1,NULL,'',0,1,0,'','',0,0,3,0,0,1),(2,'nickmsk9','2_1776875030_a502cc0d.jpg','nickmsk9@icloud.com','018a76979afcb80a8e4a858c770a3823','mFk6uavwjOsICodexsxkW8URUIbkqogG',-1062715135,6,'2026-04-22 16:31:12','2026-04-15 15:18:44','b3700e4caf42228f8e101e3acfb10bb9',154178051871,123,1,233,55.56,1,'1995-03-09','тут я пишу описание о себе',0,0,0,'','',0,0,0,0,0,1),(4,'debuguser','','debuguser@example.com','d94b09df234cb957607ebed14e2e1175','7bb9e755437cb34ee1ce9048d1376049',-1062715135,1,'2026-04-21 15:05:24','2026-04-17 18:32:55','02ced8546fa261d4b5f14dec2cf51a59',0,0,1,0,27.78,1,NULL,'',0,1,0,'','',0,0,0,0,0,1);
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
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

