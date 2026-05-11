
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,1,1,'2026-05-08 18:24:14');
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
DROP TABLE IF EXISTS `comment_edit_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_edit_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `context_type` varchar(32) COLLATE utf8mb3_bin NOT NULL,
  `comment_id` int unsigned NOT NULL,
  `editor_id` int unsigned NOT NULL,
  `old_text` text COLLATE utf8mb3_bin,
  `new_text` text COLLATE utf8mb3_bin,
  `edited_at` datetime NOT NULL,
  `edit_reason` text COLLATE utf8mb3_bin,
  PRIMARY KEY (`id`),
  KEY `comment_history` (`context_type`,`comment_id`,`edited_at`),
  KEY `editor_history` (`editor_id`,`edited_at`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comment_edit_history` WRITE;
/*!40000 ALTER TABLE `comment_edit_history` DISABLE KEYS */;
INSERT INTO `comment_edit_history` VALUES (1,'torrents',19,1,'autotest modern comments','autotest modern comments edited','2026-05-11 08:27:06','lint check'),(2,'torrents',19,1,'autotest modern comments edited','Комментарий удалён пользователем сайта','2026-05-11 08:27:20',NULL),(3,'torrents',19,1,'Комментарий удалён пользователем сайта','autotest modern comments edited','2026-05-11 08:31:47','restore'),(4,'torrents',19,1,'autotest modern comments edited','Комментарий удалён пользователем сайта','2026-05-11 08:31:58',NULL),(5,'torrents',20,1,'куку','Комментарий удалён пользователем сайта','2026-05-11 13:26:39',NULL),(6,'torrents',10,1,'ку','Комментарий удалён пользователем сайта','2026-05-11 13:44:24','так надо'),(7,'torrents',1,1,'тест комментария','тест комментария','2026-05-11 13:48:07',NULL),(8,'torrents',1,1,'тест комментария','тест комментария1234','2026-05-11 13:48:10',NULL);
/*!40000 ALTER TABLE `comment_edit_history` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comment_pins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_pins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `context_type` varchar(32) COLLATE utf8mb3_bin NOT NULL,
  `context_id` int unsigned NOT NULL,
  `comment_id` int unsigned NOT NULL,
  `pinned_by` int unsigned NOT NULL,
  `pinned_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `context_pin` (`context_type`,`context_id`),
  KEY `comment_pin` (`context_type`,`comment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comment_pins` WRITE;
/*!40000 ALTER TABLE `comment_pins` DISABLE KEYS */;
INSERT INTO `comment_pins` VALUES (4,'torrents',1,1,1,'2026-05-11 13:49:47'),(7,'news',3,3,2,'2026-05-11 15:14:49'),(6,'news',2,9,1,'2026-05-11 15:09:26');
/*!40000 ALTER TABLE `comment_pins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comment_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_reactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `context_type` varchar(32) COLLATE utf8mb3_bin NOT NULL,
  `comment_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `reaction` varchar(16) COLLATE utf8mb3_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_comment_reaction` (`context_type`,`comment_id`,`user_id`),
  KEY `comment_reaction` (`context_type`,`comment_id`,`reaction`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comment_reactions` WRITE;
/*!40000 ALTER TABLE `comment_reactions` DISABLE KEYS */;
INSERT INTO `comment_reactions` VALUES (2,'torrents',14,2,'like','2026-05-11 13:30:54','2026-05-11 13:45:29'),(3,'torrents',18,1,'dislike','2026-05-11 13:31:06','2026-05-11 13:46:39'),(4,'torrents',1,1,'like','2026-05-11 13:31:16','2026-05-11 14:32:10'),(11,'torrents',5,1,'like','2026-05-11 15:08:51',NULL),(6,'torrents',3,1,'like','2026-05-11 13:45:05',NULL),(8,'torrents',15,1,'like','2026-05-11 13:45:43',NULL),(10,'torrents',17,1,'like','2026-05-11 13:46:38',NULL);
/*!40000 ALTER TABLE `comment_reactions` ENABLE KEYS */;
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
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_by` int unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `delete_reason` text COLLATE cp1251_bin,
  PRIMARY KEY (`id`),
  KEY `idx_comments_news_deleted` (`is_deleted`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_news` WRITE;
/*!40000 ALTER TABLE `comments_news` DISABLE KEYS */;
INSERT INTO `comments_news` VALUES (1,1,1,'2026-05-07 20:16:17','что?',0,1,'2026-05-07 20:16:17',0,NULL,NULL,NULL),(2,3,1,'2026-05-09 08:50:17','у меня логин просто Ataraveski при авторизации, не помню мб это он домен отрезает или я указывал отдельно\r\nв профили почту не увидеть\r\nТо есть флоу восстановления по почте фактически не работает без знания почты, как бы оно там не называлось\r\nМожет есть возможность сделать резервный адрес? Или альтернативные методы восстановления?',0,1,'2026-05-09 08:50:17',0,NULL,NULL,NULL),(3,3,1,'2026-05-09 08:50:25','подумаем над этим, если что, почта у вас такая как и ник',2,1,'2026-05-09 08:50:25',0,NULL,NULL,NULL),(5,2,1,'2026-05-11 08:08:58','нормик?',0,0,'2026-05-11 08:08:58',0,NULL,NULL,NULL),(6,3,1,'2026-05-11 13:12:27','че',3,0,'2026-05-11 13:12:27',0,NULL,NULL,NULL),(7,3,1,'2026-05-11 15:01:17','да!',3,0,'2026-05-11 15:01:17',0,NULL,NULL,NULL),(8,3,1,'2026-05-11 15:04:07','да!!!!!!',3,0,'2026-05-11 15:04:07',0,NULL,NULL,NULL),(9,2,1,'2026-05-11 15:09:19','взывав',0,0,'2026-05-11 15:09:19',0,NULL,NULL,NULL),(10,2,1,'2026-05-11 15:09:21','выапывапыв',0,0,'2026-05-11 15:09:21',0,NULL,NULL,NULL),(11,2,1,'2026-05-11 15:09:22','ывапыва',0,0,'2026-05-11 15:09:22',0,NULL,NULL,NULL),(12,2,1,'2026-05-11 15:09:23','провпаровап',0,0,'2026-05-11 15:09:23',0,NULL,NULL,NULL),(13,2,1,'2026-05-11 15:09:29','вапывап',9,0,'2026-05-11 15:09:29',0,NULL,NULL,NULL),(14,3,2,'2026-05-11 15:12:59','news pinned user2 1778501576392',3,0,'2026-05-11 15:12:59',0,NULL,NULL,NULL),(15,3,2,'2026-05-11 15:13:01','news nested user2 1778501576392',14,0,'2026-05-11 15:13:01',0,NULL,NULL,NULL),(16,3,2,'2026-05-11 15:13:37','news normal user2 1778501614893',2,0,'2026-05-11 15:13:37',0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `comments_news` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comments_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
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
  KEY `type_status_created` (`comment_type`,`status`,`created_at`),
  KEY `comment_reporter` (`comment_type`,`comment_id`,`reporter_user_id`),
  KEY `object_comment` (`comment_type`,`object_id`,`comment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_reports` WRITE;
/*!40000 ALTER TABLE `comments_reports` DISABLE KEYS */;
INSERT INTO `comments_reports` VALUES (1,'torrents',5,1,2,1,'ку','open','2026-05-11 08:08:23',NULL,0);
/*!40000 ALTER TABLE `comments_reports` ENABLE KEYS */;
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
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_by` int unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `delete_reason` text COLLATE cp1251_bin,
  PRIMARY KEY (`id`),
  KEY `idx_comments_torrents_object_date` (`id_torrents`,`date`),
  KEY `idx_comments_torrents_parent` (`parent_id`),
  KEY `idx_comments_torrents_deleted` (`is_deleted`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_torrents` WRITE;
/*!40000 ALTER TABLE `comments_torrents` DISABLE KEYS */;
INSERT INTO `comments_torrents` VALUES (1,1,2,'2026-05-08 15:05:12','тест комментария1234',0,1,'2026-05-11 13:48:10',0,NULL,NULL,NULL),(2,1,2,'2026-05-08 15:05:19','Комментарий удалён пользователем сайта',1,2,'2026-05-08 15:22:07',0,NULL,NULL,NULL),(3,1,2,'2026-05-08 15:21:58','че',2,2,'2026-05-08 15:21:58',0,NULL,NULL,NULL),(4,1,2,'2026-05-08 15:22:01','а',1,2,'2026-05-08 15:22:01',0,NULL,NULL,NULL),(5,1,2,'2026-05-08 15:22:02','ку',0,2,'2026-05-08 15:22:02',0,NULL,NULL,NULL),(6,1,2,'2026-05-08 15:29:19','Комментарий удалён администрацией сайта',0,1,'2026-05-08 18:49:34',0,NULL,NULL,NULL),(7,1,1,'2026-05-08 15:43:20','что такое?',6,1,'2026-05-08 15:43:20',0,NULL,NULL,NULL),(8,1,1,'2026-05-08 18:35:53','mariko?',5,1,'2026-05-08 18:35:53',0,NULL,NULL,NULL),(9,1,1,'2026-05-08 18:50:41','ничего',7,1,'2026-05-08 18:50:41',0,NULL,NULL,NULL),(10,1,1,'2026-05-09 08:30:59','Комментарий удалён пользователем сайта',9,1,'2026-05-11 13:44:24',1,1,'2026-05-11 13:44:24','так надо'),(11,1,1,'2026-05-09 08:31:02','Куку',0,1,'2026-05-09 08:31:02',0,NULL,NULL,NULL),(12,1,1,'2026-05-09 08:57:21','Комментарий удалён пользователем сайта',0,1,'2026-05-09 17:36:51',0,NULL,NULL,NULL),(14,1,3,'2026-05-10 17:55:56','notification integration test',1,0,'2026-05-10 17:55:56',0,NULL,NULL,NULL),(15,1,3,'2026-05-10 17:56:17','notification integration test 2',1,0,'2026-05-10 17:56:17',0,NULL,NULL,NULL),(16,1,2,'2026-05-10 17:56:38','self notification skip test',1,0,'2026-05-10 17:56:38',0,NULL,NULL,NULL),(17,1,3,'2026-05-10 17:57:38','torrent owner notification test',0,0,'2026-05-10 17:57:38',0,NULL,NULL,NULL),(18,1,3,'2026-05-10 18:00:50','уа',0,0,'2026-05-10 18:00:50',0,NULL,NULL,NULL),(13,1,1,'2026-05-09 08:58:29','да',12,0,'2026-05-09 08:58:29',0,NULL,NULL,NULL),(19,1,1,'2026-05-11 08:26:48','Комментарий удалён пользователем сайта',0,1,'2026-05-11 08:31:58',1,1,'2026-05-11 08:31:58',NULL),(20,1,1,'2026-05-11 13:10:55','Комментарий удалён пользователем сайта',0,1,'2026-05-11 13:26:39',1,1,'2026-05-11 13:26:39',NULL),(21,1,1,'2026-05-11 13:26:19','цукцу',0,0,'2026-05-11 13:26:19',0,NULL,NULL,NULL),(22,1,1,'2026-05-11 13:26:24','ываыв',18,0,'2026-05-11 13:26:24',0,NULL,NULL,NULL),(23,1,1,'2026-05-11 14:32:22','и я так думаю! закрепляю комментарий',1,0,'2026-05-11 14:32:22',0,NULL,NULL,NULL),(24,1,1,'2026-05-11 14:32:42','что',11,0,'2026-05-11 14:32:42',0,NULL,NULL,NULL),(25,1,1,'2026-05-11 15:03:07','pinned reply smoke 1778500984390',1,0,'2026-05-11 15:03:07',0,NULL,NULL,NULL),(26,1,1,'2026-05-11 15:05:21','даже так?',1,0,'2026-05-11 15:05:21',0,NULL,NULL,NULL),(27,1,1,'2026-05-11 15:10:00','details collapsed reply 1778501396661',1,0,'2026-05-11 15:10:00',0,NULL,NULL,NULL);
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
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_by` int unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `delete_reason` text COLLATE cp1251_bin,
  PRIMARY KEY (`id`),
  KEY `id_users_parent` (`id_users`,`parent_id`),
  KEY `idx_comments_users_deleted` (`is_deleted`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users` WRITE;
/*!40000 ALTER TABLE `comments_users` DISABLE KEYS */;
INSERT INTO `comments_users` VALUES (1,1,1,'2026-05-07 18:50:59','давайте тут будут комментарии',0,0,NULL,0,NULL,NULL,NULL),(2,1,1,'2026-05-07 18:51:09','&#128513;',0,0,NULL,0,NULL,NULL,NULL),(3,1,1,'2026-05-07 18:51:14','давайте!',1,0,NULL,0,NULL,NULL,NULL),(4,2,2,'2026-05-08 14:44:32','&#128512;',0,0,NULL,0,NULL,NULL,NULL),(5,2,2,'2026-05-08 14:44:32','23423',0,2,'2026-05-08 15:00:39',0,NULL,NULL,NULL),(6,1,2,'2026-05-08 14:44:50','что',3,0,NULL,0,NULL,NULL,NULL),(7,1,2,'2026-05-08 14:44:50','что',3,0,NULL,0,NULL,NULL,NULL),(8,2,2,'2026-05-08 15:03:15','ываыва',0,2,'2026-05-08 15:03:15',0,NULL,NULL,NULL),(9,2,2,'2026-05-08 15:03:16','ываыв',0,2,'2026-05-08 15:03:16',0,NULL,NULL,NULL),(10,2,2,'2026-05-08 15:03:17','ываы',0,2,'2026-05-08 15:03:17',0,NULL,NULL,NULL),(11,2,2,'2026-05-08 15:03:18','ываы',0,2,'2026-05-08 15:03:18',0,NULL,NULL,NULL),(12,2,2,'2026-05-08 15:03:20','ываыв',10,2,'2026-05-08 15:03:20',0,NULL,NULL,NULL),(13,2,2,'2026-05-08 15:03:25','ываы',9,2,'2026-05-08 15:03:25',0,NULL,NULL,NULL),(14,2,2,'2026-05-08 15:12:21','че',12,2,'2026-05-08 15:12:21',0,NULL,NULL,NULL),(15,1,1,'2026-05-09 08:48:09','ничего',7,1,'2026-05-09 08:48:09',0,NULL,NULL,NULL),(16,1,1,'2026-05-09 08:48:14','куку',0,1,'2026-05-09 08:48:14',0,NULL,NULL,NULL),(17,1,1,'2026-05-09 08:48:19',':)',0,1,'2026-05-09 08:48:19',0,NULL,NULL,NULL),(18,3,3,'2026-05-09 18:00:46','великая китайская стена',0,0,'2026-05-09 18:00:46',0,NULL,NULL,NULL),(19,3,1,'2026-05-10 07:33:48','ору',18,0,'2026-05-10 07:33:48',0,NULL,NULL,NULL),(20,3,2,'2026-05-10 07:55:35','привет, скоро увидимся в Беларуси!',0,0,'2026-05-10 07:55:35',0,NULL,NULL,NULL);
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users_reports` WRITE;
/*!40000 ALTER TABLE `comments_users_reports` DISABLE KEYS */;
INSERT INTO `comments_users_reports` VALUES (1,6,1,2,1,'что','resolved','2026-05-09 17:40:53','2026-05-09 17:57:38',1);
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
INSERT INTO `cron` VALUES ('autoclean_interval',1000),('autoclean_last',1777907575),('multi_remote',1),('remotecheck_interval',600),('remote_torrents',30),('remotepeers_cleantime',10800),('remote_lastchecked',0),('in_remotecheck',0),('num_checked',432),('last_remotecheck',1778506240),('multi_timeout',100);
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
  PRIMARY KEY (`id`),
  KEY `idx_files_torrent` (`id_torrent`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (1,1,'Казнить нельзя помиловать_2026_WEB-DLRip.avi',1565290496),(2,1,'Казнить нельзя помиловать_2026_WEB-DLRip.srt',3594);
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
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mail` WRITE;
/*!40000 ALTER TABLE `mail` DISABLE KEYS */;
INSERT INTO `mail` VALUES (1,'Добро пожаловать!','Спасибо за регистрацию на LiteTracker! Заполните профиль, ознакомьтесь с правилами и начинайте пользоваться сайтом.','2026-05-08 14:44:27',2,0,0,0,1),(2,'Обратная связь: Ошибки на сайте','Тема: [b]Ошибки на сайте[/b]\nОтправитель: [b]nickmsk98[/b] (ID 2)\nIP: 192.168.65.1\n\nног','2026-05-08 15:15:21',1,2,0,0,1),(3,'Сообщение','да','2026-05-08 15:29:40',1,2,0,0,1),(4,'укеук','уклею','2026-05-09 09:48:07',1,0,0,0,1),(5,'Новая жалоба на комментарий','Поступила новая жалоба на комментарий стены профиля.\nОтправитель: [b]admin[/b]\n[url=wall_reports.php?id=1]Открыть жалобу[/url]\n[url=profile.php?id=1#wall-comment-6]Открыть комментарий[/url]','2026-05-09 17:40:53',1,0,0,0,1),(6,'Ваш класс был изменен','Администрация изменила ваш класс на \"Администраторы\"','2026-05-09 17:54:05',2,0,0,0,1),(7,'Сообщение','привет','2026-05-09 17:58:10',2,1,0,0,1),(8,'Сообщение','как твои дела?','2026-05-09 17:58:15',2,1,0,0,1),(9,'Добро пожаловать!','Спасибо за регистрацию на LiteTracker! Заполните профиль, ознакомьтесь с правилами и начинайте пользоваться сайтом.','2026-05-09 17:59:16',3,0,0,0,1),(10,'Обратная связь: Реклама на сайте','Тема: [b]Реклама на сайте[/b]\nОтправитель: [b]webnet[/b] (ID 3)\nIP: 192.168.65.1\n\nя хчаыфлаоывалыфдваждфываф','2026-05-09 18:03:27',1,3,0,0,1),(11,'Обратная связь: Реклама на сайте','Тема: [b]Реклама на сайте[/b]\nОтправитель: [b]webnet[/b] (ID 3)\nIP: 192.168.65.1\n\nя хчаыфлаоывалыфдваждфываф','2026-05-09 18:03:27',2,3,0,0,1),(12,'Ваш класс был изменен','Администрация изменила ваш класс на \"VIP\"','2026-05-09 18:04:29',3,0,0,0,1),(13,'Сообщение','все хорошо','2026-05-09 18:14:36',2,1,0,0,1),(14,'Сообщение','и тебе приветик','2026-05-09 18:14:43',2,1,0,0,1),(15,'Сообщение','чего тебе?','2026-05-09 18:14:50',3,1,0,0,1),(16,'Сообщение','да просто','2026-05-09 18:16:00',1,3,0,0,1),(17,'Сообщение','ку','2026-05-09 18:16:07',2,3,0,0,1),(18,'Комментарий модератора','re','2026-05-09 18:20:22',2,1,0,0,1),(19,'Комментарий модератора','re','2026-05-09 18:20:22',2,1,0,0,1),(20,'Сообщение','че','2026-05-09 20:25:29',3,1,0,0,1),(21,'Сообщение','ясно','2026-05-10 07:55:42',3,2,0,0,1),(22,'Сообщение','теряйся','2026-05-10 07:55:48',1,2,0,0,1),(23,'Сообщение','яе','2026-05-10 08:02:43',1,3,0,0,1),(24,'Сообщение','оки','2026-05-10 08:02:48',2,3,0,0,1),(25,'Ваш класс был изменен','Администрация изменила ваш класс на \"Модераторы\"','2026-05-10 08:37:27',2,0,0,0,1),(26,'Ваш класс был изменен','Администрация изменила ваш класс на \"Модераторы\"','2026-05-10 08:37:27',2,0,0,0,1),(27,'Сообщение','notification pm test','2026-05-10 17:56:54',2,3,0,0,1),(28,'Сообщение','кукукуку','2026-05-10 17:56:55',3,1,0,0,0),(29,'Сообщение','notification pm test','2026-05-10 17:57:01',2,3,0,0,1),(30,'Сообщение','ку','2026-05-10 17:58:39',2,3,0,0,1);
/*!40000 ALTER TABLE `mail` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `moderation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `moderator_id` int unsigned NOT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(64) NOT NULL,
  `target_id` int unsigned DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `reason` text,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `moderator_created` (`moderator_id`,`created_at`),
  KEY `action_created` (`action`,`created_at`),
  KEY `target` (`target_type`,`target_id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `moderation_log` WRITE;
/*!40000 ALTER TABLE `moderation_log` DISABLE KEYS */;
INSERT INTO `moderation_log` VALUES (1,1,'torrent_need_fix','torrent',1,'approved','need_fix','Need fix password=[masked]','8.8.8.8','curl/8.7.1','2026-05-11 08:06:27'),(2,1,'torrent_restore','torrent',1,'need_fix','approved',NULL,'8.8.8.8','curl/8.7.1','2026-05-11 08:06:41'),(3,1,'torrent_hide','torrent',1,'approved','hidden','Проверка скрытия','8.8.8.8','curl/8.7.1','2026-05-11 08:06:41'),(4,1,'torrent_soft_delete','torrent',1,'hidden','deleted','Тест soft delete token=[masked]','8.8.8.8','curl/8.7.1','2026-05-11 08:06:41'),(5,1,'torrent_restore','torrent',1,'deleted','approved','Вернули после проверки','8.8.8.8','curl/8.7.1','2026-05-11 08:06:41'),(6,1,'torrent_need_fix','torrent',1,'approved','need_fix','Need fix token=[masked]','8.8.8.8','curl/8.7.1','2026-05-11 08:07:16'),(7,1,'torrent_approve','torrent',1,'need_fix','approved',NULL,'8.8.8.8','curl/8.7.1','2026-05-11 08:07:16');
/*!40000 ALTER TABLE `moderation_log` ENABLE KEYS */;
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Поддержка Magnet-ссылок','Добавлена поддержка приватных magnet-ссылок. Теперь вы можете загружать файлы не скачивая torrent-файл. Для того, чтобы воспользоваться magnet-ссылкой, нажмите на оранжевую секцию в кнопке \"Скачать торрент.\"','2026-05-07 18:51:40',1),(2,'Смена announce URL','Для всех новых скачиваемых торрент-файлов с нашего сайта теперь будет использоваться новый URL аннонсера: https://bt.animelayer.ru, с портом 443. Это обновление гарантирует повышенную безопасность ваших скачиваний, поскольку новый адрес работает через защищённый протокол HTTPS, который шифрует все данные и защищает вашу активность от возможных угроз.\r\n\r\nДля удобства и безопасности скачивания мы рекомендуем использовать торрент-клиент Transmission, который можно скачать по следующей ссылке: https://transmissionbt.com/download или qBittorrent, который можно скачать по следующей ссылке: https://www.qbittorrent.org/download\r\n\r\nОбратите внимание, что в ближайшее время старые торренты с доменом animelayer.ru и портом 80 (HTTP) больше работать не будут. Переход на защищённый HTTPS является важным шагом для обеспечения вашей безопасности и конфиденциальности. Пожалуйста, обновите свои ссылки и переходите на новый URL.','2026-05-09 08:49:19',1),(3,'Темная тема','Добавлена возможность отображения сайта в темном режиме. Тема внедрена экспериментально и включается в настройках аккаунта.\r\n\r\nПо умолчанию, сайт отображается в светлом режиме. Для переключения пройдите в настройки, кликнув на никнейм пользователя в правом верхнем углу, и выберите соответствующий раздел в списке. Далее обозначьте выбор темы и подтвердите действие.\r\n\r\nОбратите внимание, темная тема запущена в тестовом режиме. Если вы заметите критичные для себя артефакты, сообщите об этом в комментариях.','2026-05-09 08:50:00',1);
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `actor_id` int unsigned DEFAULT NULL,
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `related_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` int unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload_json` text COLLATE utf8mb4_unicode_ci,
  `dedupe_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_read_created` (`user_id`,`is_read`,`created_at`),
  KEY `user_created` (`user_id`,`created_at`),
  KEY `type_entity` (`type`,`entity_type`,`entity_id`),
  KEY `user_dedupe` (`user_id`,`dedupe_key`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,3,'comment_reply','comment',1,'comment',15,'Вам ответили на комментарий','webnet ответил на ваш комментарий.','details.php?id=1#wall-comment-15',NULL,'comment_reply:1:15',1,'2026-05-10 18:01:31',1,'2026-05-10 17:56:17','2026-05-10 18:01:31'),(2,2,3,'private_message','mail',27,NULL,NULL,'Новое личное сообщение','webnet написал: Сообщение','my.mail.php?act=conversation&id_user=3',NULL,'private_message:27',1,'2026-05-10 18:01:30',1,'2026-05-10 17:56:54','2026-05-10 18:01:30'),(3,3,1,'private_message','mail',28,NULL,NULL,'Новое личное сообщение','admin написал: Сообщение','my.mail.php?act=conversation&id_user=1',NULL,'private_message:28',0,NULL,0,'2026-05-10 17:56:55',NULL),(4,2,3,'private_message','mail',29,NULL,NULL,'Новое личное сообщение','webnet написал: Сообщение','my.mail.php?act=conversation&id_user=3',NULL,'private_message:29',1,'2026-05-10 18:01:30',1,'2026-05-10 17:57:01','2026-05-10 18:01:30'),(5,2,3,'torrent_comment','torrent',1,'comment',17,'Новый комментарий к релизу','webnet прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-17',NULL,'torrent_comment:1:17',1,'2026-05-10 17:58:13',1,'2026-05-10 17:57:38','2026-05-10 17:58:23'),(6,2,3,'private_message','mail',30,NULL,NULL,'Новое личное сообщение','webnet написал: Сообщение','my.mail.php?act=conversation&id_user=3',NULL,'private_message:30',1,'2026-05-10 18:01:29',1,'2026-05-10 17:58:39','2026-05-10 18:01:29'),(7,2,3,'torrent_comment','torrent',1,'comment',18,'Новый комментарий к релизу','webnet прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-18',NULL,'torrent_comment:1:18',1,'2026-05-10 18:01:28',1,'2026-05-10 18:00:50','2026-05-10 18:01:28'),(8,2,1,'torrent_approved','torrent',1,NULL,NULL,'Релиз одобрен','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" доступен в каталоге.','details.php?id=1',NULL,'torrent_status:approved:1:202605110758',0,NULL,0,'2026-05-11 07:58:05',NULL),(9,2,1,'torrent_need_fix','torrent',1,NULL,NULL,'Релиз отправлен на доработку','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" нужно доработать. Причина: Need fix password=secret','details.php?id=1',NULL,'torrent_status:need_fix:1:202605110806',0,NULL,0,'2026-05-11 08:06:27',NULL),(10,2,1,'torrent_approved','torrent',1,NULL,NULL,'Релиз одобрен','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" доступен в каталоге.','details.php?id=1',NULL,'torrent_status:approved:1:202605110806',0,NULL,0,'2026-05-11 08:06:41',NULL),(11,2,1,'torrent_hidden','torrent',1,NULL,NULL,'Релиз скрыт','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" скрыт модератором. Причина: Проверка скрытия','details.php?id=1',NULL,'torrent_status:hidden:1:202605110806',0,NULL,0,'2026-05-11 08:06:41',NULL),(12,2,1,'torrent_deleted','torrent',1,NULL,NULL,'Релиз удалён','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" удалён модератором. Причина: Тест soft delete token=qwerty','my.releases.php',NULL,'torrent_status:deleted:1:202605110806',0,NULL,0,'2026-05-11 08:06:41',NULL),(13,2,1,'torrent_need_fix','torrent',1,NULL,NULL,'Релиз отправлен на доработку','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" нужно доработать. Причина: Need fix token=secret','details.php?id=1',NULL,'torrent_status:need_fix:1:202605110807',0,NULL,0,'2026-05-11 08:07:16',NULL),(14,2,1,'torrent_approved','torrent',1,NULL,NULL,'Релиз одобрен','Ваш релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\" доступен в каталоге.','details.php?id=1',NULL,'torrent_status:approved:1:202605110807',0,NULL,0,'2026-05-11 08:07:16',NULL),(15,3,1,'comment_pinned','comment',18,'torrents',1,'Комментарий закреплён','Ваш комментарий закрепили.','details.php?id=1#wall-comment-18',NULL,'comment_pinned:torrents:18',0,NULL,0,'2026-05-11 08:26:34',NULL),(16,2,1,'torrent_comment','torrent',1,'comment',19,'Новый комментарий к релизу','admin прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-19',NULL,'torrent_comment:1:19',0,NULL,0,'2026-05-11 08:26:48',NULL),(17,2,1,'torrent_comment','torrent',1,'comment',20,'Новый комментарий к релизу','admin прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-20',NULL,'torrent_comment:1:20',0,NULL,0,'2026-05-11 13:10:55',NULL),(18,2,1,'torrent_comment','torrent',1,'comment',21,'Новый комментарий к релизу','admin прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-21',NULL,'torrent_comment:1:21',0,NULL,0,'2026-05-11 13:26:19',NULL),(19,3,1,'comment_reply','comment',18,'comment',22,'Вам ответили на комментарий','admin ответил на ваш комментарий.','details.php?id=1#wall-comment-22',NULL,'comment_reply:18:22',0,NULL,0,'2026-05-11 13:26:24',NULL),(20,2,1,'torrent_comment','torrent',1,'comment',22,'Новый комментарий к релизу','admin прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-22',NULL,'torrent_comment:1:22',0,NULL,0,'2026-05-11 13:26:24',NULL),(21,3,1,'comment_pinned','comment',15,'torrents',1,'Комментарий закреплён','Ваш комментарий закрепили.','details.php?id=1#wall-comment-15',NULL,'comment_pinned:torrents:15',0,NULL,0,'2026-05-11 13:45:39',NULL),(22,2,1,'comment_pinned','comment',1,'torrents',1,'Комментарий закреплён','Ваш комментарий закрепили.','details.php?id=1#wall-comment-1',NULL,'comment_pinned:torrents:1',0,NULL,0,'2026-05-11 13:49:47',NULL),(23,2,1,'comment_reply','comment',1,'comment',23,'Вам ответили на комментарий','admin ответил на ваш комментарий.','details.php?id=1#wall-comment-23',NULL,'comment_reply:1:23',0,NULL,0,'2026-05-11 14:32:22',NULL),(24,2,1,'torrent_comment','torrent',1,'comment',24,'Новый комментарий к релизу','admin прокомментировал релиз \"Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)\".','details.php?id=1#wall-comment-24',NULL,'torrent_comment:1:24',0,NULL,0,'2026-05-11 14:32:42',NULL),(25,2,1,'comment_reply','comment',1,'comment',25,'Вам ответили на комментарий','admin ответил на ваш комментарий.','details.php?id=1#wall-comment-25',NULL,'comment_reply:1:25',0,NULL,0,'2026-05-11 15:03:07',NULL),(26,2,1,'comment_reply','comment',1,'comment',26,'Вам ответили на комментарий','admin ответил на ваш комментарий.','details.php?id=1#wall-comment-26',NULL,'comment_reply:1:26',0,NULL,0,'2026-05-11 15:05:21',NULL),(27,2,1,'comment_reply','comment',1,'comment',27,'Вам ответили на комментарий','admin ответил на ваш комментарий.','details.php?id=1#wall-comment-27',NULL,'comment_reply:1:27',0,NULL,0,'2026-05-11 15:10:00',NULL),(28,1,2,'comment_reply','comment',3,'comment',14,'Вам ответили на комментарий','nickmsk98 ответил на ваш комментарий.','notifications.php',NULL,'comment_reply:3:14',1,'2026-05-11 16:39:23',0,'2026-05-11 15:12:59','2026-05-11 16:39:23'),(29,1,2,'comment_reply','comment',2,'comment',16,'Вам ответили на комментарий','nickmsk98 ответил на ваш комментарий.','notifications.php',NULL,'comment_reply:2:16',1,'2026-05-11 16:39:23',0,'2026-05-11 15:13:37','2026-05-11 16:39:23'),(30,1,2,'comment_pinned','comment',3,'news',3,'Комментарий закреплён','Ваш комментарий закрепили.','notifications.php',NULL,'comment_pinned:news:3',1,'2026-05-11 16:39:23',0,'2026-05-11 15:14:49','2026-05-11 16:39:23');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
  KEY `userid` (`userid`),
  KEY `idx_torrent_userid` (`torrent`,`userid`),
  KEY `idx_torrent_passkey` (`torrent`,`passkey`),
  KEY `idx_torrent_last_action` (`torrent`,`last_action`)
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
  `polls_moderate` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `priv` WRITE;
/*!40000 ALTER TABLE `priv` DISABLE KEYS */;
INSERT INTO `priv` VALUES (1,0,1,'0000-00-00 00:00:00','68838B','Пользователи',1,0,0,1,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,1,0,0,0),(2,0,0,'0000-00-00 00:00:00','00BFFF','VIP',1,1,0,1,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0),(3,0,0,'0000-00-00 00:00:00','FFA500','Релизеры',1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,0,0,0,0,1,1,0,0,1,0,0,0,0,0),(4,0,0,'0000-00-00 00:00:00','CD3333','Модераторы',1,0,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,0,1,1,1,0,0,1,0,0,0,1,1),(5,0,0,'0000-00-00 00:00:00','9ACD32','Администраторы',1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,0,0,1,1,1),(6,1,0,'0000-00-00 00:00:00','9B30FF','Создатели',1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,1,1,1),(7,0,0,'2011-01-02 16:32:31','','Гости',0,0,0,1,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0);
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
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `checksum` char(40) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'SHA1 of migration SQL content',
  `batch` int unsigned NOT NULL DEFAULT '0' COMMENT 'batch number for grouped runs',
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when migration was applied',
  `execution_time_ms` int unsigned NOT NULL DEFAULT '0' COMMENT 'how long migration took to run',
  `status` enum('pending','applied','failed','changed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'migration state',
  `error_message` longtext COLLATE utf8mb4_general_ci COMMENT 'error details if failed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_migrations_name` (`migration`(100)),
  KEY `idx_migrations_status` (`status`),
  KEY `idx_migrations_batch` (`batch`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Track database migration history and state';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `search_query` WRITE;
/*!40000 ALTER TABLE `search_query` DISABLE KEYS */;
INSERT INTO `search_query` VALUES (1,'Isekai Nonbiri',3,1,1,'2026-05-10 07:33:22',0),(2,'Матио',5,1,1,'2026-05-11 15:03:54',0),(3,'ываыв',1,0,1,'2026-05-11 14:54:32',0),(4,'2024ма',1,1,1,'2026-05-11 14:54:42',0),(5,'маь',1,0,1,'2026-05-11 14:54:47',0),(6,'выаываыва',1,0,1,'2026-05-11 14:55:00',0),(7,'маг',1,0,1,'2026-05-11 15:03:51',0);
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
) ENGINE=MyISAM AUTO_INCREMENT=355 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'cfeb7f7a71e2924e22bc498b5fa3ac55',-1,'2026-05-03 13:21:35',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(2,'477ad948299b8fc08c1edae2b8accea1',1,'2026-05-04 18:14:43',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(3,'4419cd46a2c17e5b261d3fa4b79c831e',-1,'2026-05-04 18:12:34',-1062715135,'curl/8.7.1','/index.php'),(4,'6d2a1e1d9e1277da15506290d80c99ef',-1,'2026-05-04 18:12:42',2130706433,'','/profile.php'),(5,'c1d890cd0410ff2a9bb87e3f0b710c51',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/shop.php'),(6,'af21a6b3223d81458ef7f3784f1cf86b',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/index.php'),(7,'870f06414b12fdfd5e1352db79df33a7',-1,'2026-05-04 18:13:26',-1062715135,'curl/8.7.1','/profile.php'),(8,'38d500d2c5ab1f31d23dd07215e01cb9',1,'2026-05-07 20:15:50',-1185611747,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(9,'d034ddc9c15e4307e1f626339c0a8d77',1,'2026-05-08 15:42:48',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(10,'8634e0ea774b829ff52cf60c00673aa6',-1,'2026-05-08 15:12:47',-1062715135,'curl/8.7.1','/index.php'),(11,'06811ddf3458e66442a1a420f01fa209',-1,'2026-05-08 15:14:15',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/index.php'),(12,'b8756136bd13de1d6765fe92c836668b',-1,'2026-05-08 15:17:37',-1062715135,'curl/8.7.1','/ajax/comments.php'),(13,'19b56a6751f476747a85bbd2d27e7e96',-1,'2026-05-08 15:17:45',-1062715135,'curl/8.7.1','/ajax/comments.php'),(14,'200aeb3af522b67dcbf619fd5df1fc41',-1,'2026-05-08 15:17:53',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/index.php'),(15,'c8f504baaeca657e0b87d404fbebe46d',-1,'2026-05-08 15:29:01',-1062715135,'curl/8.7.1','/details.php'),(16,'c3aa91056c445b2522068bc079d90b7d',-1,'2026-05-08 15:29:01',-1062715135,'curl/8.7.1','/details.php'),(17,'4234a099fed63088c76cfd7f5a4a2234',-1,'2026-05-08 15:29:26',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/details.php'),(18,'fb81c94b7a062ec25feb2ac7020a2362',-1,'2026-05-08 15:30:20',-1062715135,'curl/8.7.1','/details.php'),(19,'1f5b91b72d306ee94b4c07faff60789c',-1,'2026-05-08 15:40:20',-1062715135,'curl/8.7.1','/index.php'),(20,'f294d7d550cec6468b5cd0bc9c8e2aaa',-1,'2026-05-08 15:40:20',-1062715135,'curl/8.7.1','/details.php'),(21,'0730316a2d94a9dbb18b2cf5426ed4f7',-1,'2026-05-08 15:40:28',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/index.php'),(22,'091b07b0e218f25cd90d9f478a8730b8',1,'2026-05-08 18:50:17',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(23,'49928f95e0b9099e51886124b3413b5c',-1,'2026-05-08 18:24:32',-1062715135,'curl/8.7.1','/index.php'),(24,'1d941d6bd646900f28e4e21d9d38b9e6',-1,'2026-05-08 18:24:32',-1062715135,'curl/8.7.1','/index.php'),(25,'d6394b0bcea9935397e7abaeb968ddea',-1,'2026-05-08 18:29:43',-1062715135,'curl/8.7.1','/index.php'),(26,'18658ad22fc7da23451498b5e64a6ae2',-1,'2026-05-08 18:31:39',-1062715135,'curl/8.7.1','/index.php'),(27,'cd44a01e8982235d26de8b81f9341874',1,'2026-05-09 09:52:40',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/rules.php'),(28,'6d5b32721fe5d064004a7bfad3eee0e8',1,'2026-05-09 18:19:58',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(29,'0e3ac280bf73fc800108624600bbc957',-1,'2026-05-09 17:52:22',-1062715135,'curl/8.7.1','/rules.php'),(30,'7030d2e04a40a01f64091209a3111384',-1,'2026-05-09 17:52:26',-1062715135,'curl/8.7.1','/profile.php'),(31,'cdb59c5f2a8ec1076d994f020118919f',-1,'2026-05-09 18:04:02',-1062715135,'curl/8.7.1','/profile.php'),(32,'84cd8a301cdb2dcaea72d45cfffa3f44',-1,'2026-05-09 18:04:02',-1062715135,'curl/8.7.1','/my.setting.php'),(33,'c4d1b614f98682115d4c0cd8fad29a49',-1,'2026-05-09 18:04:41',-1062715135,'curl/8.7.1','/profile.php'),(34,'bdfbc5451c7006aa0c0ce3dfe4bd7c5d',-1,'2026-05-09 18:12:34',-1062715135,'curl/8.7.1','/my.mail.php'),(35,'820956700b079bde70d099a88d0f5d6a',-1,'2026-05-09 18:12:34',-1062715135,'curl/8.7.1','/profile.php'),(36,'228941e4982e10d36dbb993cb2db05a3',-1,'2026-05-09 18:12:56',-1062715135,'curl/8.7.1','/login.php'),(37,'27633e428a44b5e74cd82d365b77bc0c',-1,'2026-05-09 18:13:19',-1062715135,'curl/8.7.1','/profile.php'),(38,'aafff261fc9a9a9ae891f8a9a2983835',-1,'2026-05-09 18:16:14',-1062715135,'curl/8.7.1','/profile.php'),(39,'5a949338ab9b841d1beb9844b43c94aa',-1,'2026-05-09 18:18:30',-1062715135,'curl/8.7.1','/profile.php'),(40,'9754f964e823851ff5ec85ad897ecb6b',-1,'2026-05-09 18:18:35',-1062715135,'curl/8.7.1','/profile.php'),(41,'17c3041958b0200b65ef9c63b2a8ad27',1,'2026-05-09 18:19:00',-1062715135,'curl/8.7.1','/profile.php'),(42,'d523b8bc485c78f561ef5193d97ae9d8',1,'2026-05-09 18:19:41',-1062715135,'curl/8.7.1','/profile.php'),(43,'1d53e749aaa4347b2a974b9c46a85909',1,'2026-05-09 20:29:35',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/profile.php'),(44,'eb3b02384887a73183d45faa5e6da6cf',-1,'2026-05-09 20:09:12',-1407975423,'curl/8.7.1','/news.php'),(45,'f9b9742c1960c6f50cc239ad612d3026',-1,'2026-05-09 20:09:12',-1407975423,'curl/8.7.1','/news.php'),(46,'56fad0966f3ff68636f4cefd670a7326',-1,'2026-05-09 20:12:05',-1407975423,'curl/8.7.1','/signup.php'),(47,'3d72b99b3af8719f90413982e51f3cec',-1,'2026-05-09 20:13:09',-1407975423,'curl/8.7.1','/login.php'),(48,'3655fb831a89b79be6789464d2923ae5',-1,'2026-05-09 20:13:09',-1407975423,'curl/8.7.1','/signup.php'),(49,'a4ac2d151780674e79c61ba1162ff78c',-1,'2026-05-09 20:13:33',-1407975423,'curl/8.7.1','/login.php'),(50,'02b9422d71ce0979269d99ba83534799',-1,'2026-05-09 20:13:33',-1407975423,'curl/8.7.1','/signup.php'),(51,'e1cda2925f31a43ccf92fddebb2d7a73',-1,'2026-05-09 20:15:01',-1407975423,'curl/8.7.1','/login.php'),(52,'e3915a9afee2eed191be0d6d1c070126',-1,'2026-05-09 20:15:01',-1407975423,'curl/8.7.1','/signup.php'),(53,'fa918f6deb41bbdfa1a47a317742cc7e',-1,'2026-05-09 20:16:41',-1407975423,'curl/8.7.1','/index.php'),(54,'88b839f9774177966388bf8a9aa2656b',-1,'2026-05-09 20:16:59',-1407975423,'curl/8.7.1','/index.php'),(55,'63b42aea8ce13c7b40d9a35c9f4120a7',-1,'2026-05-09 20:18:18',-1407975423,'curl/8.7.1','/login.php'),(56,'8c0699d11ba1af566ec4397a5e0c99c6',-1,'2026-05-09 20:20:26',-1407975423,'curl/8.7.1','/index.php'),(57,'92916ed28353d9c60a88446504d45b78',-1,'2026-05-09 20:20:26',-1407975423,'curl/8.7.1','/rss.php'),(58,'c9f07cf4f69074e608d0cc102c7d3c39',1,'2026-05-10 08:36:47',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/details.php'),(59,'734c5fdc949148665153713cbdfe4f29',-1,'2026-05-10 07:33:51',-1062715135,'curl/8.7.1','/browse.php'),(60,'7ce791e8e24b12cc043c7cb0a43f0185',-1,'2026-05-10 07:33:51',-1062715135,'curl/8.7.1','/details.php'),(61,'dde34aab92a9639ad79a8463e81f1c1c',-1,'2026-05-10 07:33:51',-1062715135,'curl/8.7.1','/index.php'),(62,'23c32d553c249055a71b5f0f05f9deab',-1,'2026-05-10 07:36:38',-1062715135,'curl/8.7.1','/index.php'),(63,'07e5d9ce60671297a1da531bc1291ac6',1,'2026-05-10 07:36:51',-1062715135,'curl/8.7.1','/index.php'),(64,'605a016df0d2f64b54fb031bc4592f1b',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/index.php'),(65,'7b166870437fe982448c67def308c1cf',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/browse.php'),(66,'b7967c9922ebb4579a4d7bbc235bfefb',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/details.php'),(67,'3276df9046e5ee10d8545de07e9276c3',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/upload.php'),(68,'72ba1c1d819cd8fcd7e3e2f8cbb44048',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/edit.php'),(69,'f2e27418266668105af7cddeef0c12d3',1,'2026-05-10 07:37:18',-1062715135,'curl/8.7.1','/download.php'),(70,'2fc392672e3515bb51d8cf6e8b75de75',1,'2026-05-10 07:37:35',-1062715135,'curl/8.7.1','/index.php'),(71,'1f9cdd2188e0286eaafe35982ccbc9a5',1,'2026-05-10 07:37:54',-1062715135,'curl/8.7.1','/index.php'),(72,'d29a5a72d0c5441e1e4a2478a127bca9',1,'2026-05-10 07:37:54',-1062715135,'curl/8.7.1','/browse.php'),(73,'43a92964cb53687da2b199f2251a90d7',1,'2026-05-10 07:37:54',-1062715135,'curl/8.7.1','/details.php'),(74,'afef5bc0b5041a904cd2aef6fe733300',1,'2026-05-10 07:37:55',-1062715135,'curl/8.7.1','/upload.php'),(75,'783b24fcb164892ad522f5c27469621a',1,'2026-05-10 07:37:55',-1062715135,'curl/8.7.1','/edit.php'),(76,'e38a91ba8dc85d78e5cb3b624b9de698',1,'2026-05-10 07:37:55',-1062715135,'curl/8.7.1','/download.php'),(77,'8e552ea125562b3f268b6da4e52c3f91',1,'2026-05-10 07:38:15',-1062715135,'curl/8.7.1','/profile.php'),(78,'6fc6e83d843afe0ed689517ffab29d76',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/index.php'),(79,'52f65579e781c10dc849781ecd10f920',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/browse.php'),(80,'d7bb0106a99fccc8851668870487032b',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/details.php'),(81,'eee2bfb1aa169032639492cee5f90f13',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/upload.php'),(82,'1e6a98900d7d36a1ee85e00046870980',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/edit.php'),(83,'902fc8af3c1af7c6a134c50947329fad',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/profile.php'),(84,'1715fba4e5e5f8c48dd37f81bce356ff',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/index.php'),(85,'34682b13da0c021373f9b7393c744dc3',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/browse.php'),(86,'6794f4670a7b065c71942533a1391b78',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/details.php'),(87,'966b0d55fdd67d47f5f72012bd9ac8ab',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/upload.php'),(88,'9d6d678ec269299f4d8389665015293a',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/edit.php'),(89,'3ace49c616a1e41f7980c8675cc3258b',1,'2026-05-10 07:41:56',-1062715135,'curl/8.7.1','/profile.php'),(90,'219c1347418f3a9299e5d0b0c4d80956',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/index.php'),(91,'d3bbd0cb634f3e4eb445774093f8eee7',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/browse.php'),(92,'42f5f062959e663681319d47607ed893',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/details.php'),(93,'dc17da92338f4ebadebf897798b19cc8',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/upload.php'),(94,'14ce7e6260ed1e03db08319b5c0b6857',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/edit.php'),(95,'db86d12b81f51078514cf065d1f89c02',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/download.php'),(96,'dadefc0d34c64a9822b3e74c9f499ccd',1,'2026-05-10 07:42:12',-1062715135,'curl/8.7.1','/profile.php'),(97,'d3fef23bfedfc59aa9f18a19650d9367',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/index.php'),(98,'b77a5806dc61e176ba384f5e32600229',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/browse.php'),(99,'7da01d431cc0db71691eac3cab736a60',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/details.php'),(100,'1fdc03bbad59c53de699e4287cb03685',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/upload.php'),(101,'99982b813bcb80d71218613b8b3b0a5f',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/edit.php'),(102,'245065ae9d9cc9287b7fb4f61ba5fdaf',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/profile.php'),(103,'673c683843b9eff97c4a4012144da780',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/index.php'),(104,'07f107cf8b21aa0eaf88e75100afac7d',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/browse.php'),(105,'9711f4dde3c0ae4c02f7d5befb615c0a',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/details.php'),(106,'89d6a8e4fbc7d0f546baa451293d5151',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/upload.php'),(107,'5c472a6d8bb46c8b409bcd590e82f9c5',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/edit.php'),(108,'db7b96d812900bb0fb04b03da3ef6d1c',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/profile.php'),(109,'acf716baeeb50ddeed3ef272aeeca51f',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/index.php'),(110,'cac52bdeaa09fc01429f2438ba7da0ff',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/browse.php'),(111,'f018b347653c4fd54c5bc7843e85691f',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/details.php'),(112,'a1fd7fb9b10ec9a21bbb7acf56ff946a',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/upload.php'),(113,'ee34a9e685eeec672573decce2dcc6af',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/edit.php'),(114,'c859c72d46d8ce04656bcad452b0f88b',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/profile.php'),(115,'73b42f7022a13bfdacb213cd25b26aa0',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/index.php'),(116,'583c2b24ec324a5001145bb4f77c2c87',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/browse.php'),(117,'bf2d0282d16814197ccc8a4a77adf70e',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/details.php'),(118,'1139e2092730323cf59024c265f71449',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/upload.php'),(119,'2f3c385f92c0bfbe8997fbdd7a91e61f',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/edit.php'),(120,'627b68ce48e68d5e3f4b34a4d20c23b8',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/download.php'),(121,'8d47eb1ce561f0f90c4ea2015a4c845b',1,'2026-05-10 07:42:51',-1062715135,'curl/8.7.1','/profile.php'),(122,'f051a52e62dec08db69d89f2d7ef0e61',1,'2026-05-10 07:45:35',-1062715135,'curl/8.7.1','/details.php'),(123,'b8cdf5a3fabfb632fed4a4f8c6c7b24e',1,'2026-05-10 07:46:43',-1062715135,'curl/8.7.1','/details.php'),(124,'7d38bff6bda960902bca42d945222623',1,'2026-05-10 07:47:43',-1062715135,'curl/8.7.1','/details.php'),(125,'e970a1b1038b79de8161477f1e48dafe',1,'2026-05-10 07:47:46',-1062715135,'curl/8.7.1','/details.php'),(126,'d3faf5c2e81f99e2b0b59f4c55e4ecff',1,'2026-05-10 07:54:10',-1062715135,'curl/8.7.1','/index.php'),(127,'539582ac59be1fa65abff1d6cb398db7',1,'2026-05-10 07:54:10',-1062715135,'curl/8.7.1','/index.php'),(128,'af1956c0570b706af7267fdee0f71c4e',1,'2026-05-10 07:54:15',-1062715135,'curl/8.7.1','/browse.php'),(129,'e5155147ac86cf9007e3e04a278b7c4e',1,'2026-05-10 07:54:15',-1062715135,'curl/8.7.1','/browse.php'),(130,'4cd0de7198284d4a7bafa23cbf0af14a',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/profile.php'),(131,'b7868f563d4fc2c2fa49b0215cec041c',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/browse.php'),(132,'027ad16e26e118c6a84c407cea9fe70a',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/profile.php'),(133,'c29119ede8c307143ad357eb79d1d0e7',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/browse.php'),(134,'a1feda6b34b1d58195a1ba2a56c1f7a9',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/browse.php'),(135,'a43bd4de0ff0757fa89246d1f972bfd4',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/my.mail.php'),(136,'69aa6bebd6708fb51e9756da550d7bef',1,'2026-05-10 07:54:32',-1062715135,'curl/8.7.1','/details.php'),(137,'08f84b5ce78e933396de9acc711c4f24',1,'2026-05-10 07:55:06',-1062715135,'curl/8.7.1','/my.mail.php'),(138,'c7de6710c2e17c7e1a59e7a11a719b66',1,'2026-05-10 07:55:13',-1062715135,'curl/8.7.1','/details.php'),(139,'7f20c82360023a9b79f676b1095f24a6',1,'2026-05-10 07:55:13',-1062715135,'curl/8.7.1','/profile.php'),(140,'d6397af3d7f5bbeeb9bb350b303edba6',-1,'2026-05-10 07:55:28',-1062715135,'curl/8.7.1','/details.php'),(141,'87d349c4206eea8d7e1e0b05b61286a6',-1,'2026-05-10 08:01:08',-1062715135,'curl/8.7.1','/details.php'),(142,'5afe276487d1ea4083264a83112654bb',1,'2026-05-10 08:01:08',-1062715135,'curl/8.7.1','/details.php'),(143,'5dd3ee3c6bb28ecc6e949782278655f0',-1,'2026-05-10 08:01:41',-1062715135,'curl/8.7.1','/details.php'),(144,'86f84eb1558bfeedcd1bbc12abca897e',-1,'2026-05-10 08:01:41',-1062715135,'curl/8.7.1','/details.php'),(145,'089c6e21c6338d7215324c1c7ad63a07',-1,'2026-05-10 08:01:41',-1062715135,'curl/8.7.1','/details.php'),(146,'dfd6989be11524ddf164b147ca0300fb',2,'2026-05-10 08:01:41',-1062715135,'curl/8.7.1','/details.php'),(147,'aeddfefe7d1dfd40d12dff6c9e14921a',-1,'2026-05-10 08:01:41',-1062715135,'curl/8.7.1','/details.php'),(148,'3f165a352d24e0cebd04178550724a21',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/index.php'),(149,'69c49691597d7ea2c4b6cd153b6c92b3',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/index.php'),(150,'344a9b55c4cb4978dfc788af76568937',2,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/details.php'),(151,'c013035bbce0cc932bec9f0f3256b9a9',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/browse.php'),(152,'ae68a53a27a8cb3fadbaee3fb7f11fff',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/details.php'),(153,'b4db5845c6c43110e19740de8b2d8ee4',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/upload.php'),(154,'f131866c2805d77c29f325850e8bd40a',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/edit.php'),(155,'3609d775bd10d4b1d4b734fbb4fb4841',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/profile.php'),(156,'71db09002c3cf98250c9498703b3a92e',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/my.mail.php'),(157,'31e8cfb4f5e4f79139a73b93c5c14191',1,'2026-05-10 08:03:43',-1062715135,'curl/8.7.1','/download.php'),(158,'c7ff7c6ac430bb5765c59509bb8e423c',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/index.php'),(159,'c6c7cf0d51d8c617357b99ee6c5a8834',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/browse.php'),(160,'6c82fc78b96c801a4a45deab61f1a4b9',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/details.php'),(161,'44c0606164f360fc3f6b5260530dd170',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/upload.php'),(162,'20ca019f1adf9df4a1c65705bc32cb9d',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/edit.php'),(163,'5379dcc240205032c606006ab2ab8b67',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/profile.php'),(164,'78861f90e0a9c078859b12b08dcf1cd4',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/my.mail.php'),(165,'0c6d60503030ad4bce205c52fd3c38be',1,'2026-05-10 08:06:25',-1062715135,'curl/8.7.1','/download.php'),(166,'32fa4a15dbada264e8389e539d4b81e8',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/index.php'),(167,'91d67a26f5a0dc54aa4b920b96b8c556',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/browse.php'),(168,'8c77ab9c18c08b50ecadbcc837ac09d2',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/details.php'),(169,'66f516ecdda8ed5c342ed0fda3022303',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/upload.php'),(170,'fd0a3b02653f093439b0175247d84b9a',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/edit.php'),(171,'df6a7fb6abaddced1bbdf7fa8f94ab12',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/profile.php'),(172,'ed091f70caeaf46505ecbe860afffa4f',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/my.mail.php'),(173,'746477f82576fd2fb244689185a22f1f',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/shop.php'),(174,'617667b86fefd1df5de6f5e97a64ad99',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/comments.last.php'),(175,'17c26d646188f715d3e8cc43e14ab3e2',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/rating.php'),(176,'cd4997167a927279dd1d710e14cc45f2',1,'2026-05-10 08:09:48',-1062715135,'curl/8.7.1','/download.php'),(177,'4d683391d0a57b5b56b8d382fe7e5ccf',-1,'2026-05-10 08:33:21',-1062715135,'curl/8.7.1','/details.php'),(178,'129f7fa089bb494c35f11d5c39eacb8c',-1,'2026-05-10 08:33:21',-1062715135,'curl/8.7.1','/api/ratings.php'),(179,'53e84dacbf91584f4e24979f1f5246f9',-1,'2026-05-10 08:33:21',-1062715135,'curl/8.7.1','/api/ratings.php'),(180,'dc7f7387984379210b3b09a7c61c2a6b',1,'2026-05-10 08:33:29',-1062715135,'curl/8.7.1','/details.php'),(181,'2edc6364c6ced7b8a5cf7dcf5aa5e778',1,'2026-05-10 08:33:52',-1062715135,'curl/8.7.1','/details.php'),(182,'0bea6aaaea07859ac1b5939cc7c29120',1,'2026-05-10 08:34:19',-1062715135,'curl/8.7.1','/details.php'),(183,'13b8c2c03ffd8d8cc66e7ad249a5592b',-1,'2026-05-10 08:34:19',-1062715135,'curl/8.7.1','/details.php'),(184,'a3ed2319ce696eecc57b9772dcc840e2',-1,'2026-05-10 08:34:27',-1062715135,'curl/8.7.1','/api/ratings.php'),(185,'5e317d6ae0a1e7fae63eb3fe0dac970f',-1,'2026-05-10 08:34:27',-1062715135,'curl/8.7.1','/api/ratings.php'),(186,'3675c0720cf9146d12d90d6c042e9598',-1,'2026-05-10 18:03:10',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(187,'756c1361a2483a9bfc65b02a3178a30b',-1,'2026-05-10 14:02:50',-1062715135,'curl/8.7.1','/rss.php'),(188,'852289d85bb5117e527c024c19309b0b',-1,'2026-05-10 14:07:07',-1062715135,'curl/8.7.1','/index.php'),(189,'90c28a679bc5e29ea136d6da6b19afdf',-1,'2026-05-10 14:07:07',-1062715135,'curl/8.7.1','/browse.php'),(190,'2a50a19cb48ad122a796d8e49bb6927c',-1,'2026-05-10 14:07:07',-1062715135,'curl/8.7.1','/browse.php'),(191,'282c09de4d660352f79850ad3efd6a70',-1,'2026-05-10 14:07:07',-1062715135,'curl/8.7.1','/browse.php'),(192,'cd5166e5e7407f45aa0fb4865a826d3e',-1,'2026-05-10 14:07:15',-1062715135,'curl/8.7.1','/browse.php'),(193,'6001143755b70c6be628209ff8306b6b',-1,'2026-05-10 14:07:15',-1062715135,'curl/8.7.1','/browse.php'),(194,'db0e1bcc9767c7a11fab1f1b3f909633',-1,'2026-05-10 14:07:23',-1062715135,'curl/8.7.1','/browse.php'),(195,'06ccef2fc85921295ce576aac75868bd',-1,'2026-05-10 14:11:45',-1062715135,'curl/8.7.1','/api/tags_suggest.php'),(196,'dab455932a8d6e55600dccf637aec1c7',-1,'2026-05-10 14:11:45',-1062715135,'curl/8.7.1','/api/tags_suggest.php'),(197,'3ac4f5f4d66d4c971fe35c80c31a2c63',-1,'2026-05-10 14:11:45',-1062715135,'curl/8.7.1','/upload.php'),(198,'cf59b0fc75e076d3a7dfc7cfb4b6a6b4',-1,'2026-05-10 14:11:45',-1062715135,'curl/8.7.1','/login.php'),(199,'0eb5384d604062785937fbb8d95de580',-1,'2026-05-10 14:11:59',-1062715135,'curl/8.7.1','/edit.php'),(200,'25def05126613af9f516aa8aaf859083',-1,'2026-05-10 14:11:59',-1062715135,'curl/8.7.1','/api/tags_suggest.php'),(201,'5f54b034b85d784dc40a6c66bea6b775',-1,'2026-05-10 14:11:59',-1062715135,'curl/8.7.1','/login.php'),(202,'40c987d57a7c35cfd30bb644662c81d5',-1,'2026-05-10 14:12:09',-1062715135,'curl/8.7.1','/api/tags_suggest.php'),(203,'caa4316b48cfde74020419959ebccad4',-1,'2026-05-10 14:12:09',-1062715135,'curl/8.7.1','/upload.php'),(204,'3de6af2bf7c174d7d61ac39e53bff19a',-1,'2026-05-10 14:12:09',-1062715135,'curl/8.7.1','/login.php'),(205,'734075a3af26d1e38259bdbd42f224aa',-1,'2026-05-10 14:12:09',-1062715135,'curl/8.7.1','/edit.php'),(206,'82ea6790286e8e6ca8c4be86059b6b4d',-1,'2026-05-10 14:12:09',-1062715135,'curl/8.7.1','/login.php'),(207,'b479795d3075fc6a369d70dfffd1a54f',-1,'2026-05-10 14:18:05',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(208,'aff954051718fdede4ebfbb4a3ef5dc7',-1,'2026-05-10 14:18:05',-1062715135,'curl/8.7.1','/edit.php'),(209,'dedeaffeec49f524b947a61e6b4f0106',-1,'2026-05-10 14:18:05',-1062715135,'curl/8.7.1','/upload.php'),(210,'b62837e204364c1fa912b32caa5fd290',-1,'2026-05-10 14:18:05',-1062715135,'curl/8.7.1','/login.php'),(211,'1cb895eb42958705d17b2904902ed66a',-1,'2026-05-10 14:18:05',-1062715135,'curl/8.7.1','/login.php'),(212,'4041833a798dd358e1abdf6389b6d9a6',-1,'2026-05-10 14:18:18',-1062715135,'curl/8.7.1','/login.php'),(213,'d3bcdcc137393ef424e7b391dde54ec1',-1,'2026-05-10 14:18:50',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(214,'75036ae6724dc555648a20fffd2b76a8',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(215,'544fd6d748b390b9b7ac0a8b942898a9',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(216,'7fb1ea91490a246af8c0c6c7cf92f248',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(217,'14d1e53464a5227195f29d05d8f8b817',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(218,'aa3b3f8b5514cdf5eb713925d7bb3ac2',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(219,'29e16708e3f13f7929420181be3b7f59',-1,'2026-05-10 14:19:20',-1062715135,'curl/8.7.1','/upload.php'),(220,'ee1d4dc4188ff80ad313c946471512d9',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(221,'07337e0e53f58bb08d3f1b4875b47f23',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/upload.php'),(222,'dac29f3eabd3de06dec88cf797e4f0ce',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/login.php'),(223,'936ef14531aa65d93146fd75a3107257',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(224,'b28387d4bfb1d152cd344cdfedfd88b7',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/edit.php'),(225,'a6f744cd9448b0e8d5c38a21b7833fd1',-1,'2026-05-10 14:19:52',-1062715135,'curl/8.7.1','/login.php'),(226,'7724f2a21357638d957c07ba0a624b71',-1,'2026-05-10 14:25:49',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(227,'3773dc6d1a7ad96d4eceea7d4951ea8e',-1,'2026-05-10 14:25:49',-1062715135,'curl/8.7.1','/api/metadata_search.php'),(228,'5cf9bd55677219aa71292d3b49d15680',-1,'2026-05-10 14:26:35',-1062715135,'curl/8.7.1','/upload.php'),(229,'ff63c88bfc323246353de6f3e923c4d5',-1,'2026-05-10 14:26:35',-1062715135,'curl/8.7.1','/login.php'),(230,'713ada93e69021e24b0cfac04fe5e13e',-1,'2026-05-10 14:26:35',-1062715135,'curl/8.7.1','/edit.php'),(231,'3d028c84dc64718bb5287551371a2300',-1,'2026-05-10 14:26:35',-1062715135,'curl/8.7.1','/login.php'),(232,'4b9246e6f90cc6445b3c1cdda85bd9f3',-1,'2026-05-10 14:31:15',-1062715135,'curl/8.7.1','/details.php'),(233,'fbb85ba6196a18a47c001db3649df280',-1,'2026-05-10 14:31:15',-1062715135,'curl/8.7.1','/login.php'),(234,'f9a08185ff2e73809b3a4b9540df715e',1,'2026-05-10 14:32:15',-1062715135,'curl/8.7.1','/details.php'),(235,'71f230a1cf75408d05e597b8a2879700',3,'2026-05-10 14:32:39',-1062715135,'curl/8.7.1','/details.php'),(236,'514b37c9cd8bf514a01fcb34847b8cfe',2,'2026-05-10 14:32:39',-1062715135,'curl/8.7.1','/details.php'),(237,'52a1780b181893580d93d74721bf3671',-1,'2026-05-10 14:32:59',-1062715135,'curl/8.7.1','/details.php'),(238,'7f48fb021c62d45118a1741098057454',2,'2026-05-10 14:32:59',-1062715135,'curl/8.7.1','/details.php'),(239,'f07e141105c341a703004e458161ca38',-1,'2026-05-10 17:43:57',-1062715135,'curl/8.7.1','/index.php'),(240,'fd824eeaf26f972e8adaa03f146b8166',-1,'2026-05-10 17:44:03',-1062715135,'curl/8.7.1','/index.php'),(241,'7d4049785f74a9142178993652f2f439',3,'2026-05-10 17:57:38',-1062715135,'curl/8.7.1','/ajax/comments.php'),(242,'cfd4fc37c51ce838da839341cab95e0b',3,'2026-05-10 17:56:05',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(243,'14d34697aa270c90119f58c4a34193e5',2,'2026-05-10 17:56:05',-1062715135,'curl/8.7.1','/api/notifications/count.php'),(244,'da81ec1380cdb043d2ceb8e2244e722e',2,'2026-05-10 17:56:26',-1062715135,'curl/8.7.1','/api/notifications/count.php'),(245,'d0802b297778a8ed46304d19132f9bb3',2,'2026-05-10 17:56:26',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(246,'f5a655997f91a69811978d273e8a8374',3,'2026-05-10 17:56:26',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(247,'e878c770844d22294b991352cdcf4114',2,'2026-05-10 17:56:32',-1062715135,'curl/8.7.1','/details.php'),(248,'ef027044e165809ea6f05caddd81de55',3,'2026-05-10 17:56:47',-1062715135,'curl/8.7.1','/my.mail.php'),(249,'20cb19316daac5622e7ce01772f3f88e',3,'2026-05-10 17:57:13',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(250,'f304d643941fc1bfd45cdd6aa6dea5be',3,'2026-05-10 17:57:17',-1062715135,'curl/8.7.1','/api/notifications/mark_read.php'),(251,'864ac513a3446f0f71fde4872a7f7cd8',3,'2026-05-10 17:57:24',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(252,'e4767848cb8e5db9b088b157c8e9a70a',2,'2026-05-10 17:57:50',-1062715135,'curl/8.7.1','/index.php'),(253,'c4807d12a92b9d072ea2385ada1043fa',2,'2026-05-10 17:57:50',-1062715135,'curl/8.7.1','/notifications.php'),(257,'58624a6c2b0be890410e055791290af9',2,'2026-05-10 17:58:33',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(254,'e45975a900eead1419ca5a789db4ec5f',2,'2026-05-10 17:57:57',-1062715135,'curl/8.7.1','/api/notifications/count.php'),(255,'c361d9dce0bc8c60b07238c9e23ea021',2,'2026-05-10 17:57:57',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(256,'e263e4f71df9ab153f8b4d51b8bdc8fa',2,'2026-05-10 17:58:06',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(258,'81b32a0f775c8f830e98534bf5abb81b',2,'2026-05-10 17:58:54',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(259,'e9b6355b93a42d8e95e37af74944ea66',2,'2026-05-10 17:58:58',-1062715135,'curl/8.7.1','/api/notifications/list.php'),(262,'a707f3b2834b5dc943d82378895686a9',1,'2026-05-11 08:44:38',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/api/notifications/count.php'),(260,'8223fde5a0071c102b3712533835dc6e',2,'2026-05-10 17:59:49',-1062715135,'curl/8.7.1','/api/notifications/count.php'),(261,'656b4cf6f8fb2711346f2c2866072067',2,'2026-05-10 17:59:49',-1062715135,'curl/8.7.1','/notifications.php'),(263,'892e942b8879909c51cb5e984f40c778',-1,'2026-05-11 07:55:29',0,'','Standard input code'),(264,'df395fd40330063a5e6a16752117b62e',-1,'2026-05-11 07:55:42',-1407975423,'curl/8.7.1','/browse.php'),(265,'fdf4b932ecd1c60225f6a88cad32e071',-1,'2026-05-11 07:55:42',-1407975423,'curl/8.7.1','/download.php'),(266,'9e7526e0334ebc52554470a9b1f24c67',-1,'2026-05-11 07:55:42',-1407975423,'curl/8.7.1','/details.php'),(267,'829eea0c6c260d52dcbdb693f63b4054',-1,'2026-05-11 07:55:42',-1407975423,'curl/8.7.1','/index.php'),(268,'2bed8007a858318494312f9756f8e549',-1,'2026-05-11 07:55:49',-1407975423,'curl/8.7.1','/edit.php'),(269,'95e9d3e6f2b43ce8978508e35281f962',-1,'2026-05-11 07:55:49',-1407975423,'curl/8.7.1','/admin.php'),(270,'6232ad113b1eae95829024f0e9816c5a',-1,'2026-05-11 07:55:49',-1407975423,'curl/8.7.1','/upload.php'),(271,'73c706451f464dbbd663e4fe07ee945c',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/upload.php'),(272,'fae8efdc90f70e1e2abfca407365f4e3',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/edit.php'),(273,'c4ab64fb9e4c15e47733d14772c77f21',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/login.php'),(274,'dfd938e968ce596525cf66549955ebfe',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/admin.php'),(275,'0bc14f3baad8cf98225b629d2d452915',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/login.php'),(276,'a8ed506ea9aea9e50c582c4ca4f67bb9',-1,'2026-05-11 07:56:42',-1407975423,'curl/8.7.1','/login.php'),(277,'789b99ed0a8c02bb2a96beaf058a77ee',1,'2026-05-11 07:56:57',-1407975423,'curl/8.7.1','/upload.php'),(278,'fac713843b279adaf3d5df86f84c2b2f',1,'2026-05-11 07:56:57',-1407975423,'curl/8.7.1','/edit.php'),(279,'0f46b7fd3890671ac3c85d16d4927fa1',1,'2026-05-11 07:56:57',-1407975423,'curl/8.7.1','/admin.php'),(280,'58ccf80680f7bd4add426da1d4950c15',1,'2026-05-11 07:57:18',-1407975423,'curl/8.7.1','/admin.php'),(281,'9df49ec19e5ed7078d083a1ec863d392',-1,'2026-05-11 07:57:46',-1407975423,'curl/8.7.1','/details.php'),(282,'5bac35357ce40dcbe9400c486906d8bd',2,'2026-05-11 07:57:46',-1407975423,'curl/8.7.1','/details.php'),(283,'4a07a0ae3fedaaf06c97789e0d7f7dda',1,'2026-05-11 07:57:46',-1407975423,'curl/8.7.1','/details.php'),(284,'3d9c0e22bf934b9c9adf1dc144412b58',-1,'2026-05-11 07:57:46',-1407975423,'curl/8.7.1','/browse.php'),(285,'f716923e7dcf03816b6db83776bd8aae',1,'2026-05-11 07:57:57',-1407975423,'curl/8.7.1','/admin.php'),(286,'ca23eb9ac93be22b3070820dec29d857',-1,'2026-05-11 07:58:10',-1407975423,'curl/8.7.1','/download.php'),(287,'4cea35d8f91635f4a56005ecc9d2f75e',-1,'2026-05-11 07:58:10',-1407975423,'curl/8.7.1','/details.php'),(288,'b03f0172be7d0b95b4d5499801bc4937',-1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/download.php'),(289,'5fd372995121bbad3c9d4e14ea6b4aa4',-1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/index.php'),(290,'74b2de852f1b8be4f3ddf8caee7f92b9',1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/edit.php'),(291,'99888b35a8bf438baccfb14c1cd8f12e',1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/upload.php'),(292,'038b7a3a9aaa7f6342dae854741992e5',-1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/browse.php'),(293,'c4364caef8e53e6d0ccaf8c95ec3ff15',1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/admin.php'),(294,'578adff6e756340e267c78a4b61b8b8e',-1,'2026-05-11 07:59:12',-1407975423,'curl/8.7.1','/details.php'),(295,'ba54836f48cbad5772b1bdf55186b422',-1,'2026-05-11 08:05:14',0,'','Standard input code'),(296,'967b0750993ab4dabeedfde0a7355be5',1,'2026-05-11 08:05:20',-1407975423,'curl/8.7.1','/admin.php'),(297,'ef990a284aa0596e58bf703c0e5d7a50',1,'2026-05-11 08:05:48',-1407975423,'curl/8.7.1','/admin.php'),(298,'dc7b6791424eac7b138a916b78542787',1,'2026-05-11 08:06:17',-1407975423,'curl/8.7.1','/admin.php'),(299,'b709841e4ad2ec3c1959397256250c5b',1,'2026-05-11 08:06:41',-1407975423,'curl/8.7.1','/admin.php'),(300,'2062839b975aa478b5d57861fe45ee7a',1,'2026-05-11 08:06:41',-1407975423,'curl/8.7.1','/admin.php'),(301,'09f0aa2f57d0fecffd473a1550f79ed8',1,'2026-05-11 08:06:41',-1407975423,'curl/8.7.1','/admin.php'),(302,'abe88075cfdd4e0e1ce1b94349d2f62d',1,'2026-05-11 08:06:41',-1407975423,'curl/8.7.1','/admin.php'),(303,'dbbb796b6483a9dd4239212472409928',1,'2026-05-11 08:07:16',-1407975423,'curl/8.7.1','/admin.php'),(304,'960e352c8f7634eb4b29682b914ff9a6',1,'2026-05-11 08:07:16',-1407975423,'curl/8.7.1','/admin.php'),(309,'d42c676a8559e54d49776429d0056fc3',1,'2026-05-11 08:16:11',-1407975423,'curl/8.7.1','/admin.php'),(305,'879308039c9105f8fb5820a4679e328b',1,'2026-05-11 08:07:42',-1407975423,'curl/8.7.1','/details.php'),(306,'d235476a04dd70689c0d257d621e861e',3,'2026-05-11 08:07:42',-1407975423,'curl/8.7.1','/admin.php'),(307,'1f89cd3737e342473b1d5eaa95b62127',-1,'2026-05-11 08:07:42',-1407975423,'curl/8.7.1','/details.php'),(308,'836acab7cb18b79a2cba2544c8df1618',1,'2026-05-11 08:07:57',-1407975423,'curl/8.7.1','/admin.php'),(310,'ade1bc1eca2ebe21a31c737b5340dfad',1,'2026-05-11 08:16:11',-1407975423,'curl/8.7.1','/admin.php'),(311,'3de58f02d8210f9c84083821590f7083',1,'2026-05-11 08:16:11',-1407975423,'curl/8.7.1','/admin.php'),(312,'4c19f0403a75bbe3a6f798c48a2c684f',1,'2026-05-11 08:16:40',-1407975423,'curl/8.7.1','/admin.php'),(313,'f59120a5573fb9d50f91648188ae211b',1,'2026-05-11 08:16:40',-1407975423,'curl/8.7.1','/admin.php'),(314,'b71e7f98fec20f1eb20f5b10d6a3ae1e',1,'2026-05-11 08:25:20',-1407975423,'curl/8.7.1','/profile.php'),(315,'e5a720130985155d8d05af158b0f0bcc',1,'2026-05-11 08:25:20',-1407975423,'curl/8.7.1','/details.php'),(316,'b3918ecb8c70f48df65a39866b15ab33',1,'2026-05-11 08:25:20',-1407975423,'curl/8.7.1','/ajax/comments.php'),(317,'89861a141f4aad21611c83cc9841cf70',1,'2026-05-11 08:25:47',-1407975423,'curl/8.7.1','/ajax/comments.php'),(318,'bdb4194ae0ff6554a99f19571953a22d',1,'2026-05-11 08:25:47',-1407975423,'curl/8.7.1','/ajax/comments.php'),(319,'95fac778faf3dc875ddbea64b6dbf897',1,'2026-05-11 08:26:03',-1407975423,'curl/8.7.1','/details.php'),(320,'e48225911ee7cb50b1ab7be4c5c032eb',1,'2026-05-11 08:31:47',-1407975423,'curl/8.7.1','/ajax/comments.php'),(321,'2668aa4848b3891aae4997623c82b64f',1,'2026-05-11 08:28:09',-1407975423,'curl/8.7.1','/details.php'),(322,'29ebb7c1031675526516256b29d90a38',-1,'2026-05-11 08:30:26',-1407975423,'curl/8.7.1','/details.php'),(323,'7c3b049fa79d999196810436d299cd88',1,'2026-05-11 08:32:06',-1407975423,'curl/8.7.1','/details.php'),(324,'206717e5a562041e5c5cb2b8f984b81a',1,'2026-05-11 16:39:15',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/index.php'),(325,'dc52fe4189aee4fefdaaf908e8c183df',2,'2026-05-11 14:23:59',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.118.1 Chrome/142.0.7444.265 Electron/39.8.8 Safari/537.36','/browse.php'),(326,'4c8e19695334741e918cac5ea0c2cd3d',-1,'2026-05-11 14:24:28',-1407975423,'curl/8.7.1','/browse.php'),(327,'d46d18715afaab81d687d8eb1addcd2f',-1,'2026-05-11 14:47:48',-1407975423,'curl/8.7.1','/browse.php'),(328,'005ae787c4408b61eafd9a04aeb3925c',-1,'2026-05-11 14:47:52',-1407975423,'curl/8.7.1','/browse.php'),(329,'9e58d2f8dc19c623a1b5911b66c179e6',-1,'2026-05-11 14:51:04',-1407975423,'curl/8.7.1','/browse.php'),(330,'43314514f7c6f79b79d6abfb5020bef7',-1,'2026-05-11 14:51:04',-1407975423,'curl/8.7.1','/browse.php'),(331,'99912d35234ecc1a6d030a5335963fd1',-1,'2026-05-11 14:52:33',-1407975423,'curl/8.7.1','/browse.php'),(332,'96b5469ad4ad667a496955af82cd3d52',-1,'2026-05-11 14:52:33',-1407975423,'curl/8.7.1','/browse.php'),(333,'2292f6a67c41b37c89475d145487de59',-1,'2026-05-11 14:52:45',-1407975423,'curl/8.7.1','/browse.php'),(334,'b734bda71c73af51cfcb90f523c525e1',-1,'2026-05-11 14:52:58',-1407975423,'curl/8.7.1','/browse.php'),(335,'a99905a4308fb85f22f02fc6700c7aa0',-1,'2026-05-11 14:54:11',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/browse.php'),(336,'05ebb306d5c53e4a6a0778a867a589e7',-1,'2026-05-11 14:54:24',-1407975423,'curl/8.7.1','/browse.php'),(337,'62acd347a0a2c222298b23d62816166a',-1,'2026-05-11 14:54:24',-1407975423,'curl/8.7.1','/browse.php'),(338,'fc76c4a53aca64cab9f0cba61ebbd3d3',-1,'2026-05-11 15:01:30',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/browse.php'),(339,'afbe8316e32120e3164dcd19766a9d72',-1,'2026-05-11 15:02:02',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/details.php'),(340,'daaf46431d9e1f8af357e06b60000a89',-1,'2026-05-11 15:02:21',-1407975423,'curl/8.7.1','/details.php'),(341,'cb7400deb559c0f359ad60ad21b5ef9f',1,'2026-05-11 15:02:30',-1407975423,'curl/8.7.1','/details.php'),(342,'9520aedca9c653e09a6116bfd3726ead',1,'2026-05-11 15:03:05',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/details.php'),(343,'0773a59557d86820ab5f452e8d1672ee',-1,'2026-05-11 15:07:48',-1407975423,'curl/8.7.1','/browse.php'),(344,'dacc22314138556710a3854e95e1c422',1,'2026-05-11 15:09:58',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/details.php'),(345,'070d629c85ee46a6c86fefe9c4f5c467',1,'2026-05-11 15:10:22',-1407975423,'curl/8.7.1','/news.php'),(346,'e7c04f1d9c1b7228eb8ff3efe0738a7a',1,'2026-05-11 15:11:00',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/news.php'),(347,'67077bbe5615d9d2e248837c505e669b',-1,'2026-05-11 15:11:59',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/news.php'),(348,'cf1a42fcf8b5018f11a23e20be8805ea',-1,'2026-05-11 15:12:12',-1407975423,'curl/8.7.1','/news.php'),(349,'f04e1f3eabc8866ddbebfd5a3de5a112',2,'2026-05-11 15:12:25',-1407975423,'curl/8.7.1','/news.php'),(350,'83962fd0bb0ac08b4f248dbfa85dda83',2,'2026-05-11 15:12:57',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/news.php'),(351,'3e0391559fbb7901cc1afbbdf73ff062',2,'2026-05-11 15:13:35',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/news.php'),(352,'6224f583df9a7d19dff3e4fe549f2a55',2,'2026-05-11 15:14:46',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/146.0.0.0 Safari/537.36','/news.php'),(353,'9a8e13860329914622e3fd6b96d7d80a',1,'2026-05-11 15:15:21',-1407975423,'curl/8.7.1','/browse.php'),(354,'a95e695a4ef6a6969a9840c3603bc699',-1,'2026-05-11 15:15:21',-1407975423,'curl/8.7.1','/browse.php');
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
  UNIQUE KEY `snatch` (`torrent`,`userid`),
  KEY `idx_userid_finished` (`userid`,`finished`)
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,6,'исекай',1),(2,6,'повседневность',1),(3,6,'фэнтези',1);
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_ratings` WRITE;
/*!40000 ALTER TABLE `torrent_ratings` DISABLE KEYS */;
INSERT INTO `torrent_ratings` VALUES (1,1,2,5,'192.168.65.1','2026-05-08 15:06:30'),(2,1,1,5,'192.168.65.1','2026-05-10 08:34:19');
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
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_views` WRITE;
/*!40000 ALTER TABLE `torrent_views` DISABLE KEYS */;
INSERT INTO `torrent_views` VALUES (1,1,2,'33e75dafbd31084454d70392eda234536c511411','2026-05-08 15:04:36'),(2,1,0,'323215e3023f98f76abdced31e99a85a8573cfbd','2026-05-08 15:29:01'),(3,1,0,'322d0acc3b23f818d3918852dfff7097fd59e745','2026-05-08 15:29:26'),(4,1,1,'9bbb375c78e35e2cfb54f2e9ae2712d6207b3da9','2026-05-08 15:43:11'),(5,1,0,'841dc65f009b15758b77e1bc611754b705068172','2026-05-09 09:12:01'),(6,1,3,'5d9dc7d8b26969c20a0bd2b0820ae2a729a6114a','2026-05-09 18:03:33'),(7,1,0,'65f89a270b2054b7c3f8a8fc381cb20e2ab515b9','2026-05-09 20:17:50'),(8,1,0,'2274603d0478604e3ec9b5103b9355ce2d62ef76','2026-05-11 07:55:42'),(9,1,0,'9075b970062e35762c4c0c2d299da92fa88f994e','2026-05-11 08:30:26'),(10,1,0,'22fad7fac7b887da87a816eb98319ed3f9bea666','2026-05-11 13:24:21'),(11,1,0,'578607044ae9a55481b077f8c9b878575494fcf8','2026-05-11 15:02:02');
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
  `size` bigint unsigned NOT NULL DEFAULT '0',
  `downloaded` bigint unsigned NOT NULL DEFAULT '0',
  `descr` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `infohash` varbinary(40) NOT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `id_category` int NOT NULL,
  `id_user` int NOT NULL,
  `added` datetime NOT NULL,
  `image` tinytext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `multi` enum('1','0') CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '0',
  `completed` bigint unsigned NOT NULL DEFAULT '0',
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
  `status` varchar(32) NOT NULL DEFAULT 'approved',
  `status_reason` text,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_torrents_banned_added` (`banned`,`added`),
  KEY `idx_torrents_category_banned_added` (`id_category`,`banned`,`added`),
  KEY `idx_torrents_user_added` (`id_user`,`added`),
  KEY `idx_torrents_news_added` (`news`,`added`),
  KEY `idx_torrents_type` (`type`),
  KEY `idx_torrents_content_type` (`content_type`),
  KEY `idx_torrents_name` (`name`(191)),
  KEY `status_added` (`status`,`added`),
  KEY `owner_status` (`id_user`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrents` WRITE;
/*!40000 ALTER TABLE `torrents` DISABLE KEYS */;
INSERT INTO `torrents` VALUES (1,'0','Isekai Nonbiri Nouka 2 / Фермерская жизнь в ином мире [ТВ-2] (1—5)','[kinozal.tv]id2128508.torrent',1565294090,7,'[b]Тип:[/b] ТВ\n[b]Жанр:[/b] детектив, приключения, семейный\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 12 из 12\n[b]Продолжительность:[/b] 1 ч 52 мин\n[b]Режиссер:[/b] Дени Вильнев\n[b]Описание:[/b] После рождения наследника жизнь Матио Хираку в «Лесу Смерти» переходит на новый уровень: теперь ему предстоит совмещать отцовство с масштабным расширением Деревни Большого Древа. И пока слава о процветающем крае привлекает новых жителей и внимание могущественных соседей, герой основывает дополнительные поселения и продолжает внедрять земные новшества, доказывая, что доброта и упорный труд способны превратить опасную глушь в настоящий рай для десятков рас, где все смогут жить в мире и согласии.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] WEB-DL\n[b]Качество:[/b] 080p\n[b]Видео:[/b] H.264, 1920x1080, ~8000 Кбит/с\n[b]Аудио:[/b] китайский\n[b]Субтитры:[/b] русские\n[b]Страна:[/b] Россия, Франция, Индия',_binary '86105c82ca44f781936f5083b81d478a811bed28','исекай,повседневность,фэнтези',6,2,'2026-05-08 15:04:35','1.jpg','1',0,'2026-05-08 15:04:35','1_0.jpg','','','','',1,'tv','russian','chinese','detective,adventure,family','hevc','russia,france,india','multi',2,'approved',NULL,1,'2026-05-11 08:07:16','2026-05-11 04:57:25',NULL,NULL);
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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers` WRITE;
/*!40000 ALTER TABLE `trackers` DISABLE KEYS */;
INSERT INTO `trackers` VALUES (1,1,'localhost',0,0,1778506780,''),(2,1,'http://tr2.torrent4me.com/ann?uk=cAETnuUKbT',409,4,1778497046,'ok_announce'),(3,1,'http://tr2.tor4me.info/ann?uk=cAETnuUKbT',409,4,1778497046,'ok_announce'),(4,1,'http://tr2.tor2me.info/ann?uk=cAETnuUKbT',0,0,1778497045,'failed:no_benc_result_or_timeout_announce'),(5,1,'http://retracker.local/announce',0,0,1778497045,'failed:no_benc_result_or_timeout_announce');
/*!40000 ALTER TABLE `trackers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_admin_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_admin_notes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `note` text COLLATE utf8mb3_bin NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_created` (`user_id`,`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_admin_notes` WRITE;
/*!40000 ALTER TABLE `user_admin_notes` DISABLE KEYS */;
INSERT INTO `user_admin_notes` VALUES (1,3,1,'Класс изменен: Пользователи -> VIP','2026-05-09 18:04:29'),(2,2,1,'re','2026-05-09 18:20:22'),(3,2,1,'re','2026-05-09 18:20:22'),(4,2,1,'Класс изменен: Администраторы -> Модераторы','2026-05-10 08:37:27'),(5,2,1,'Класс изменен: Администраторы -> Модераторы','2026-05-10 08:37:27');
/*!40000 ALTER TABLE `user_admin_notes` ENABLE KEYS */;
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
  `banned` smallint NOT NULL DEFAULT '0',
  `last_chat` int NOT NULL,
  `num_messages` int NOT NULL,
  `num_friends` int NOT NULL,
  `voice` float NOT NULL DEFAULT '0',
  `confirm` smallint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `email` (`email`),
  KEY `idx_users_passkey` (`passkey`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','1_1778169081_0c7d3930.jpg','admin@admin.com','$2y$12$3ImCq59OZWwh3exrrcbtYOzvSD6sSoGU6WULEyr09QtgMPbJ8HiwW','',-1407975423,6,'2026-05-11 16:39:31','2026-05-04 18:14:43','ec932acf7c4e32b1f8f26ea1911bc30a',130350163636,0,0,0,0.0000678711,1,'2009-04-04','босс',0,1,0,0,0,0,0,0,1),(2,'nickmsk98','2_1778242020_4b6bbe01.jpg','sdfqwerfqwe@yandex.ru','$2y$12$i4wsRAw0O49PAiPM7vRI4u5QwJ7B8kjbtgTPKLA0/2n6fy0i1WHbm','',-1407975423,4,'2026-05-11 15:14:49','2026-05-08 14:44:27','52f0cf008ee335c101ebc51b32d2644c',21475910222,0,0,5,916.741,1,'2008-05-04','',0,1,0,0,0,0,0,0,1),(3,'webnet','3_1778338915_b2845127.jpg','fomalexus@yandex.ru','$2y$12$H1JKWqeQVL.WlrZ/KoxUqekUVEJ98wBwAkeDhGinU0oO6mhAzrDOy','',-1407975423,2,'2026-05-11 08:07:42','2026-05-09 17:59:16','01cb44447933a574453a8b5a83ce7578',0,10737418240,0,5,300,1,'2007-04-06','',0,1,0,0,0,1,0,0,1);
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

