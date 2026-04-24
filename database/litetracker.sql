
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
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (3,4,2,'2026-04-22 16:22:14'),(4,5,7,'2026-04-24 05:08:02'),(5,5,8,'2026-04-24 05:14:15'),(6,6,8,'2026-04-24 02:55:30'),(7,6,9,'2026-04-24 02:28:03'),(8,7,9,'2026-04-24 02:15:56'),(9,7,10,'2026-04-24 02:20:26'),(10,8,10,'2026-04-24 02:23:13'),(11,8,5,'2026-04-24 01:30:15'),(12,9,5,'2026-04-23 23:32:32'),(13,9,6,'2026-04-24 00:02:49'),(14,10,6,'2026-04-23 23:04:01'),(15,10,7,'2026-04-23 23:27:49'),(16,11,7,'2026-04-23 23:30:56'),(17,11,8,'2026-04-23 22:37:34'),(18,12,8,'2026-04-23 23:10:32'),(19,12,9,'2026-04-23 22:42:49'),(20,13,9,'2026-04-23 21:09:45'),(21,13,10,'2026-04-23 20:59:32'),(22,14,10,'2026-04-23 22:20:42'),(23,14,5,'2026-04-23 21:49:53'),(24,15,5,'2026-04-23 20:42:12'),(25,15,6,'2026-04-23 20:28:31'),(26,16,6,'2026-04-23 16:17:57'),(27,16,7,'2026-04-23 16:19:06'),(28,17,7,'2026-04-23 18:15:40'),(29,17,8,'2026-04-23 18:18:11'),(30,18,8,'2026-04-23 17:07:54'),(31,18,9,'2026-04-23 17:40:20'),(32,19,9,'2026-04-23 16:41:23'),(33,19,10,'2026-04-23 16:45:06'),(34,20,10,'2026-04-23 15:27:37'),(35,20,5,'2026-04-23 15:32:40'),(36,21,5,'2026-04-23 13:45:53'),(37,21,6,'2026-04-23 14:28:39'),(38,22,6,'2026-04-23 10:45:36'),(39,22,7,'2026-04-23 10:38:40'),(40,23,7,'2026-04-23 11:03:06'),(41,23,8,'2026-04-23 10:24:10'),(42,24,8,'2026-04-23 11:25:22'),(43,24,9,'2026-04-23 11:38:38');
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
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
INSERT INTO `chat` VALUES (8,5,'AkiSora',1,'2026-04-24 04:31:41','[DEMO] Кто уже посмотрел первые серии? Интерфейс комментариев проверяется отлично.'),(7,2,'nickmsk9',6,'2026-04-19 15:39:43','[b]nickmsk9[/b]: xtg'),(6,2,'nickmsk9',6,'2026-04-19 15:30:46','я умею чистить чат'),(9,6,'MioRain',1,'2026-04-24 04:33:41','[DEMO] Закинул пару релизов в закладки, карточки выглядят аккуратно.'),(10,7,'RenTori',1,'2026-04-24 04:35:41','[DEMO] Проверяю ответы в профилях, треды на стене собираются как надо.'),(11,8,'YukiNova',1,'2026-04-24 04:37:41','[DEMO] Неплохо бы ещё погонять сортировку по раздающим и размеру.'),(12,9,'KaiZen',1,'2026-04-24 04:39:41','[DEMO] Рейтинг релизов тоже ожил, можно спокойно тестировать детали.'),(13,10,'NamiFox',1,'2026-04-24 04:41:41','[DEMO] Всё синтетическое и локальное, зато для UI теперь есть на что смотреть.');
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
INSERT INTO `comments_news` VALUES (1,1,2,'2026-04-19 16:11:07','чче?a sdasdasda',0,2,'2026-04-23 16:18:00'),(2,1,2,'2026-04-19 17:07:47','Комментарий удалён пользователем сайта',0,2,'2026-04-22 15:26:22'),(3,1,2,'2026-04-22 15:26:26','Комментарий удалён пользователем сайта',1,2,'2026-04-23 16:17:56');
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
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_torrents` WRITE;
/*!40000 ALTER TABLE `comments_torrents` DISABLE KEYS */;
INSERT INTO `comments_torrents` VALUES (1,4,2,'2026-04-19 04:33:06','вапвыы',0,2,'2026-04-19 04:33:06'),(2,4,2,'2026-04-19 04:33:18','работаем',0,2,'2026-04-19 04:33:18'),(3,4,2,'2026-04-19 04:33:22','[b]nickmsk9[/b], че',0,2,'2026-04-19 04:33:22'),(4,4,2,'2026-04-19 17:06:49','[b]nickmsk9[/b], красота?',0,2,'2026-04-19 17:06:49'),(5,4,2,'2026-04-22 15:26:37','dfgdfgd',1,2,'2026-04-22 15:26:37'),(6,4,2,'2026-04-22 17:51:02','Комментарий удалён пользователем сайта',4,2,'2026-04-22 17:51:19'),(7,4,2,'2026-04-22 17:51:11','уверен?',6,2,'2026-04-22 17:51:11'),(8,4,2,'2026-04-22 17:51:16','уверен!',0,2,'2026-04-22 17:51:16'),(9,4,2,'2026-04-23 16:15:00','ладно',0,2,'2026-04-23 16:15:00'),(10,5,6,'2026-04-24 05:26:24','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-24 05:26:24'),(11,5,7,'2026-04-24 05:34:18','Да, тоже отметил себе, особенно удобно смотреть в компактном виде.',10,7,'2026-04-24 05:34:18'),(12,5,8,'2026-04-24 05:45:00','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-24 05:45:00'),(13,6,7,'2026-04-24 02:52:16','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-24 02:52:16'),(14,6,8,'2026-04-24 03:04:15','Согласен, на такой карточке сразу видно метаданные и активность.',13,8,'2026-04-24 03:04:15'),(15,6,9,'2026-04-24 03:07:30','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-24 03:07:30'),(16,7,8,'2026-04-24 02:53:24','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-24 02:53:24'),(17,7,9,'2026-04-24 03:08:53','Я ещё рейтинг проверил, всё обновляется как ожидалось.',16,9,'2026-04-24 03:08:53'),(18,7,10,'2026-04-24 02:59:51','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-24 02:59:51'),(19,8,9,'2026-04-24 02:13:15','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-24 02:13:15'),(20,8,10,'2026-04-24 02:19:10','Позже напишу впечатления, но пока выглядит очень убедительно.',19,10,'2026-04-24 02:19:10'),(21,8,5,'2026-04-24 02:15:31','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-24 02:15:31'),(22,9,10,'2026-04-24 00:05:01','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-24 00:05:01'),(23,9,5,'2026-04-24 00:05:12','Хороший пример для проверки страницы деталей и профиля автора.',22,5,'2026-04-24 00:05:12'),(24,9,6,'2026-04-24 00:13:26','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-24 00:13:26'),(25,10,5,'2026-04-23 23:37:35','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-23 23:37:35'),(26,10,6,'2026-04-23 23:50:56','Тоже заметил, что список комментариев стал куда полезнее для тестов.',25,6,'2026-04-23 23:50:56'),(27,10,7,'2026-04-23 23:50:25','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 23:50:25'),(28,11,6,'2026-04-23 23:09:30','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-23 23:09:30'),(29,11,7,'2026-04-23 23:30:39','Да, тоже отметил себе, особенно удобно смотреть в компактном виде.',28,7,'2026-04-23 23:30:39'),(30,11,8,'2026-04-23 23:26:49','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-23 23:26:49'),(31,12,7,'2026-04-23 22:48:55','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 22:48:55'),(32,12,8,'2026-04-23 23:13:08','Согласен, на такой карточке сразу видно метаданные и активность.',31,8,'2026-04-23 23:13:08'),(33,12,9,'2026-04-23 23:16:50','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-23 23:16:50'),(34,13,8,'2026-04-23 21:05:46','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-23 21:05:46'),(35,13,9,'2026-04-23 21:31:39','Я ещё рейтинг проверил, всё обновляется как ожидалось.',34,9,'2026-04-23 21:31:39'),(36,13,10,'2026-04-23 21:39:58','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-23 21:39:58'),(37,14,9,'2026-04-23 21:48:12','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-23 21:48:12'),(38,14,10,'2026-04-23 22:17:56','Позже напишу впечатления, но пока выглядит очень убедительно.',37,10,'2026-04-23 22:17:56'),(39,14,5,'2026-04-23 22:16:12','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-23 22:16:12'),(40,15,10,'2026-04-23 20:30:06','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-23 20:30:06'),(41,15,5,'2026-04-23 20:57:28','Хороший пример для проверки страницы деталей и профиля автора.',40,5,'2026-04-23 20:57:28'),(42,15,6,'2026-04-23 20:52:55','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-23 20:52:55'),(43,16,5,'2026-04-23 16:36:38','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-23 16:36:38'),(44,16,6,'2026-04-23 16:40:18','Тоже заметил, что список комментариев стал куда полезнее для тестов.',43,6,'2026-04-23 16:40:18'),(45,16,7,'2026-04-23 16:44:00','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 16:44:00'),(46,17,6,'2026-04-23 18:34:32','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-23 18:34:32'),(47,17,7,'2026-04-23 18:50:33','Да, тоже отметил себе, особенно удобно смотреть в компактном виде.',46,7,'2026-04-23 18:50:33'),(48,17,8,'2026-04-23 19:05:52','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-23 19:05:52'),(49,18,7,'2026-04-23 17:38:09','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 17:38:09'),(50,18,8,'2026-04-23 17:43:14','Согласен, на такой карточке сразу видно метаданные и активность.',49,8,'2026-04-23 17:43:14'),(51,18,9,'2026-04-23 17:53:11','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-23 17:53:11'),(52,19,8,'2026-04-23 17:06:30','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-23 17:06:30'),(53,19,9,'2026-04-23 17:20:08','Я ещё рейтинг проверил, всё обновляется как ожидалось.',52,9,'2026-04-23 17:20:08'),(54,19,10,'2026-04-23 17:15:33','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-23 17:15:33'),(55,20,9,'2026-04-23 15:41:46','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-23 15:41:46'),(56,20,10,'2026-04-23 15:43:33','Позже напишу впечатления, но пока выглядит очень убедительно.',55,10,'2026-04-23 15:43:33'),(57,20,5,'2026-04-23 15:50:12','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-23 15:50:12'),(58,21,10,'2026-04-23 14:07:39','Понравилось, что есть и комментарии, и немного активности на стенах.',0,10,'2026-04-23 14:07:39'),(59,21,5,'2026-04-23 14:23:36','Хороший пример для проверки страницы деталей и профиля автора.',58,5,'2026-04-23 14:23:36'),(60,21,6,'2026-04-23 14:27:55','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-23 14:27:55'),(61,22,5,'2026-04-23 10:38:37','Для тестов карточка отличная, есть на чём проверить интерфейс.',0,5,'2026-04-23 10:38:37'),(62,22,6,'2026-04-23 10:45:45','Тоже заметил, что список комментариев стал куда полезнее для тестов.',61,6,'2026-04-23 10:45:45'),(63,22,7,'2026-04-23 10:56:12','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 10:56:12'),(64,23,6,'2026-04-23 10:49:16','Очень аккуратный релиз, спасибо за оформление.',0,6,'2026-04-23 10:49:16'),(65,23,7,'2026-04-23 11:04:27','Да, тоже отметил себе, особенно удобно смотреть в компактном виде.',64,7,'2026-04-23 11:04:27'),(66,23,8,'2026-04-23 10:59:37','Описание получилось цепляющим, сразу захотелось открыть детали.',0,8,'2026-04-23 10:59:37'),(67,24,7,'2026-04-23 11:02:28','Забрал в закладки, позже отпишусь после просмотра.',0,7,'2026-04-23 11:02:28'),(68,24,8,'2026-04-23 11:26:58','Согласен, на такой карточке сразу видно метаданные и активность.',67,8,'2026-04-23 11:26:58'),(69,24,9,'2026-04-23 11:30:00','Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',0,9,'2026-04-23 11:30:00');
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
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comments_users` WRITE;
/*!40000 ALTER TABLE `comments_users` DISABLE KEYS */;
INSERT INTO `comments_users` VALUES (20,2,2,'2026-04-22 16:31:00','ваыпвапыв',0,0,NULL),(4,1,2,'2026-04-17 13:39:39','вапвапва',0,0,NULL),(5,1,2,'2026-04-17 13:39:42','вапвапвапвапвапв',0,0,NULL),(6,1,2,'2026-04-17 13:39:44','вапвапвапвапвап',0,0,NULL),(7,1,2,'2026-04-17 13:39:46','вапвапвапва',5,0,NULL),(8,1,2,'2026-04-17 13:39:49','вапвапвапвапвапв',6,0,NULL),(9,1,2,'2026-04-17 13:39:51','впарвапрвапрвапрва',6,0,NULL),(21,2,2,'2026-04-22 16:31:01','вапыап',0,0,NULL),(11,1,2,'2026-04-17 16:35:33','xt',9,0,NULL),(17,2,2,'2026-04-19 17:01:59','прикол',0,0,NULL),(18,2,2,'2026-04-19 17:07:11','в чем фвфвф',17,2,'2026-04-19 17:07:22'),(19,1,2,'2026-04-22 16:24:52','вапва',0,0,NULL),(22,2,2,'2026-04-22 16:31:02','пива',0,0,NULL),(23,2,2,'2026-04-22 16:31:04','вапывап',21,0,NULL),(24,1,2,'2026-04-22 16:31:12','пвапвыап',0,0,NULL),(25,4,2,'2026-04-22 16:39:47','привет',0,0,NULL),(26,5,6,'2026-04-24 03:27:06','Заглянул на стену, интерфейс ответов работает отлично.',0,0,NULL),(27,5,7,'2026-04-24 04:21:59','Неплохой профиль, особенно когда есть живая стена и свежие даты.',26,0,NULL),(28,5,8,'2026-04-24 04:45:33','Добавил пару релизов в закладки, позже приду с отзывом.',0,0,NULL),(29,6,7,'2026-04-24 04:06:46','Оставляю тестовый след, чтобы было что проверять в профиле.',0,0,NULL),(30,6,8,'2026-04-24 04:21:31','Смотрел твои последние раздачи, карточки выглядят стабильно.',29,0,NULL),(31,6,9,'2026-04-24 04:36:40','Проверяю быстрые ответы на стене, всё выглядит аккуратно.',0,0,NULL),(32,7,8,'2026-04-24 03:41:41','Неплохой профиль, особенно когда есть живая стена и свежие даты.',0,0,NULL),(33,7,9,'2026-04-24 04:20:02','Добавил пару релизов в закладки, позже приду с отзывом.',32,0,NULL),(34,7,10,'2026-04-24 04:33:48','Заглянул на стену, интерфейс ответов работает отлично.',0,0,NULL),(35,8,9,'2026-04-24 03:23:48','Смотрел твои последние раздачи, карточки выглядят стабильно.',0,0,NULL),(36,8,10,'2026-04-24 04:35:06','Проверяю быстрые ответы на стене, всё выглядит аккуратно.',35,0,NULL),(37,8,5,'2026-04-24 04:40:32','Оставляю тестовый след, чтобы было что проверять в профиле.',0,0,NULL),(38,9,10,'2026-04-24 04:17:11','Добавил пару релизов в закладки, позже приду с отзывом.',0,0,NULL),(39,9,5,'2026-04-24 04:34:08','Заглянул на стену, интерфейс ответов работает отлично.',38,0,NULL),(40,9,6,'2026-04-24 04:40:36','Неплохой профиль, особенно когда есть живая стена и свежие даты.',0,0,NULL),(41,10,5,'2026-04-24 03:44:25','Проверяю быстрые ответы на стене, всё выглядит аккуратно.',0,0,NULL),(42,10,6,'2026-04-24 04:17:32','Оставляю тестовый след, чтобы было что проверять в профиле.',41,0,NULL),(43,10,7,'2026-04-24 04:33:07','Смотрел твои последние раздачи, карточки выглядят стабильно.',0,0,NULL);
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
INSERT INTO `cron` VALUES ('autoclean_interval',1000),('autoclean_last',1777005491),('multi_remote',1),('remotecheck_interval',600),('remote_torrents',30),('remotepeers_cleantime',10800),('remote_lastchecked',4),('in_remotecheck',0),('num_checked',293),('last_remotecheck',1777005611),('multi_timeout',100);
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
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (4,4,'Project.Hail.Mary.2026.WEBRip.1080p.H264.DD51.mkv',19894460567),(5,5,'..part01.mkv',8168326342),(6,6,'..part01.mkv',17877751582),(7,7,'..part01.mkv',2717783159),(8,7,'..part02.mkv',2717783160),(9,7,'..part03.mkv',2717783160),(10,8,'..part01.mkv',4775587040),(11,9,'..part01.mkv',2916496981),(12,9,'..part02.mkv',2916496982),(13,10,'..part01.mkv',20171784759),(14,11,'..part01.mkv',5068603914),(15,12,'..part01.mkv',1425169562),(16,12,'..part02.mkv',1425169563),(17,13,'..part01.mkv',1674718190),(18,13,'..part02.mkv',1674718190),(19,13,'..part03.mkv',1674718191),(20,14,'..part01.mkv',8813668612),(21,15,'..part01.mkv',1974839144),(22,15,'..part02.mkv',1974839144),(23,16,'.7.part01.mkv',2048697912),(24,16,'.7.part02.mkv',2048697913),(25,16,'.7.part03.mkv',2048697913),(26,17,'..part01.mkv',4893986394),(27,18,'..part01.mkv',10088854921),(28,19,'..part01.mkv',2677462003),(29,19,'..part02.mkv',2677462004),(30,19,'..part03.mkv',2677462004),(31,20,'..part01.mkv',4766837278),(32,21,'..part01.mkv',3924156030),(33,21,'..part02.mkv',3924156030),(34,22,'..part01.mkv',9014979758),(35,23,'..part01.mkv',4486253368),(36,24,'..part01.mkv',1983529375),(37,24,'..part02.mkv',1983529375);
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
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mail` WRITE;
/*!40000 ALTER TABLE `mail` DISABLE KEYS */;
INSERT INTO `mail` VALUES (2,'Сообщение','пишу лс','2026-04-17 18:33:00',1,2,0,0,1),(4,'Сообщение','смтисм','2026-04-18 05:20:56',1,2,0,0,1),(5,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(6,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(7,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(8,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(9,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(10,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(11,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(12,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(13,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(14,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(15,'test','msg','2026-04-22 16:05:12',1,2,0,0,0),(16,'Сообщение','ку','2026-04-22 16:14:33',1,2,0,0,0),(17,'Сообщение','ку','2026-04-22 16:39:50',4,2,0,0,0);
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
INSERT INTO `news` VALUES (1,'Смена announce URL','Для всех новых скачиваемых торрент-файлов с нашего сайта теперь будет использоваться новый URL аннонсера: https://bt.animelayer.ru, с портом 443. Это обновление гарантирует повышенную безопасность ваших скачиваний, поскольку новый адрес работает через защищённый протокол HTTPS, который шифрует все данные и защищает вашу активность от возможных угроз.\r\n\r\nДля удобства и безопасности скачивания мы рекомендуем использовать торрент-клиент Transmission, который можно скачать по следующей ссылке: https://transmissionbt.com/download или qBittorrent, который можно скачать по следующей ссылке: https://www.qbittorrent.org/download\r\n\r\nОбратите внимание, что в ближайшее время старые торренты с доменом animelayer.ru и портом 80 (HTTP) больше работать не будут. Переход на защищённый HTTPS является важным шагом для обеспечения вашей безопасности и конфиденциальности. Пожалуйста, обновите свои ссылки и переходите на новый URL.','2026-04-23 15:43:23',2);
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
) ENGINE=MyISAM AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `peers` WRITE;
/*!40000 ALTER TABLE `peers` DISABLE KEYS */;
INSERT INTO `peers` VALUES (1,5,'-LTDEMO-01c5e63638f4','10.20.0.11',51001,9780928760,8168326342,0,0,0,1,'2026-04-24 04:10:05','2026-04-24 05:42:15','2026-04-24 05:31:27',1,5,'LiteTracker Demo Seeder',1777009335,'a217c5ee254e83bd81ee2990b2f39e46'),(2,5,'-LTDEMO-3f1d13ace0c4','10.20.0.12',51002,10756923193,8168326342,0,0,0,1,'2026-04-24 04:15:19','2026-04-24 05:45:55','2026-04-24 05:31:38',1,6,'LiteTracker Demo Seeder',1777009555,'45cb04516b59322561ce90bf0ea392dc'),(3,6,'-LTDEMO-cce27a4d93ba','10.20.0.12',51001,19155831037,17877751582,0,0,0,1,'2026-04-24 02:27:10','2026-04-24 03:16:22','2026-04-24 03:08:37',1,6,'LiteTracker Demo Seeder',1777000582,'45cb04516b59322561ce90bf0ea392dc'),(4,6,'-LTDEMO-9c4ca77eaff1','10.20.0.13',51002,21011293429,17877751582,0,0,0,1,'2026-04-24 02:00:49','2026-04-24 03:09:30','2026-04-24 03:00:42',1,7,'LiteTracker Demo Seeder',1777000170,'28a3317599057a5d08cfecfbf124bcf5'),(5,6,'-LTDEMO-96f9ad731bd6','10.20.0.14',51003,20231866709,17877751582,0,0,0,1,'2026-04-24 01:59:36','2026-04-24 03:15:54','2026-04-24 03:07:02',1,8,'LiteTracker Demo Seeder',1777000554,'857fea14026d59549178ae99da5d9a95'),(6,6,'-LTDEMO-d9c03f7a18bd','10.20.0.15',51004,792024048,12514426107,0,0,5363325475,0,'2026-04-24 01:14:10','2026-04-24 03:12:24','2026-04-24 02:59:48',1,9,'LiteTracker Demo Seeder',0,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(7,7,'-LTDEMO-7a4c06ac8a60','10.20.0.13',51001,10892354814,8153349479,0,0,0,1,'2026-04-24 01:10:15','2026-04-24 03:06:41','2026-04-24 02:56:28',1,7,'LiteTracker Demo Seeder',1777000001,'28a3317599057a5d08cfecfbf124bcf5'),(8,7,'-LTDEMO-12e534343527','10.20.0.14',51002,9265291533,8153349479,0,0,0,1,'2026-04-24 01:41:58','2026-04-24 03:09:05','2026-04-24 02:56:58',1,8,'LiteTracker Demo Seeder',1777000145,'857fea14026d59549178ae99da5d9a95'),(9,7,'-LTDEMO-faca3d09aba9','10.20.0.15',51003,10867258899,8153349479,0,0,0,1,'2026-04-24 01:20:22','2026-04-24 03:05:15','2026-04-24 02:48:57',1,9,'LiteTracker Demo Seeder',1776999915,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(10,7,'-LTDEMO-0841c0c08433','10.20.0.16',51004,9660621282,8153349479,0,0,0,1,'2026-04-24 02:10:08','2026-04-24 03:12:44','2026-04-24 03:08:18',1,10,'LiteTracker Demo Seeder',1777000364,'b9feffb7d138e319f492ca95cec48a6a'),(11,7,'-LTDEMO-75ffa47809ce','10.20.0.11',51005,1364891839,1223002421,0,0,6930347058,0,'2026-04-24 02:38:12','2026-04-24 03:09:51','2026-04-24 02:56:59',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46'),(12,7,'-LTDEMO-daed77d09d8a','10.20.0.12',51006,1664420459,2344087975,0,0,5809261504,0,'2026-04-24 02:13:32','2026-04-24 03:10:51','2026-04-24 02:51:13',1,6,'LiteTracker Demo Seeder',0,'45cb04516b59322561ce90bf0ea392dc'),(13,8,'-LTDEMO-b263aa45d189','10.20.0.14',51001,5838570345,4775587040,0,0,0,1,'2026-04-24 01:17:29','2026-04-24 02:21:00','2026-04-24 02:01:51',1,8,'LiteTracker Demo Seeder',1776997260,'857fea14026d59549178ae99da5d9a95'),(14,8,'-LTDEMO-31d25cdc96ed','10.20.0.15',51002,7049757822,4775587040,0,0,0,1,'2026-04-24 01:32:48','2026-04-24 02:20:39','2026-04-24 02:09:19',1,9,'LiteTracker Demo Seeder',1776997239,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(15,8,'-LTDEMO-9d02c304edca','10.20.0.16',51003,5490141975,4775587040,0,0,0,1,'2026-04-24 00:43:06','2026-04-24 02:22:27','2026-04-24 02:15:05',1,10,'LiteTracker Demo Seeder',1776997347,'b9feffb7d138e319f492ca95cec48a6a'),(16,8,'-LTDEMO-83ec25b811f2','10.20.0.11',51004,6034017439,4775587040,0,0,0,1,'2026-04-24 00:30:47','2026-04-24 02:20:21','2026-04-24 02:17:02',1,5,'LiteTracker Demo Seeder',1776997221,'a217c5ee254e83bd81ee2990b2f39e46'),(17,8,'-LTDEMO-651212d97b4f','10.20.0.12',51005,7331078184,4775587040,0,0,0,1,'2026-04-24 01:15:47','2026-04-24 02:19:22','2026-04-24 02:02:14',1,6,'LiteTracker Demo Seeder',1776997162,'45cb04516b59322561ce90bf0ea392dc'),(18,9,'-LTDEMO-e6019d072664','10.20.0.15',51001,8034612769,5832993963,0,0,0,1,'2026-04-23 22:27:46','2026-04-24 00:17:18','2026-04-23 23:58:50',1,9,'LiteTracker Demo Seeder',1776989838,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(19,9,'-LTDEMO-41e57a77c780','10.20.0.16',51002,7143745841,5832993963,0,0,0,1,'2026-04-23 23:35:26','2026-04-24 00:18:53','2026-04-24 00:04:08',1,10,'LiteTracker Demo Seeder',1776989933,'b9feffb7d138e319f492ca95cec48a6a'),(20,9,'-LTDEMO-4b3897b01872','10.20.0.11',51003,905099969,3281059104,0,0,2551934859,0,'2026-04-23 22:22:04','2026-04-24 00:15:57','2026-04-24 00:06:32',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46'),(21,10,'-LTDEMO-44bed05a1682','10.20.0.16',51001,22521195836,20171784759,0,0,0,1,'2026-04-23 22:51:22','2026-04-23 23:56:47','2026-04-23 23:47:33',1,10,'LiteTracker Demo Seeder',1776988607,'b9feffb7d138e319f492ca95cec48a6a'),(22,10,'-LTDEMO-e6478946aa5d','10.20.0.11',51002,21736221451,20171784759,0,0,0,1,'2026-04-23 21:59:28','2026-04-23 23:53:56','2026-04-23 23:49:03',1,5,'LiteTracker Demo Seeder',1776988436,'a217c5ee254e83bd81ee2990b2f39e46'),(23,10,'-LTDEMO-f34845546b58','10.20.0.12',51003,22915318726,20171784759,0,0,0,1,'2026-04-23 22:58:00','2026-04-23 23:51:16','2026-04-23 23:37:35',1,6,'LiteTracker Demo Seeder',1776988276,'45cb04516b59322561ce90bf0ea392dc'),(24,10,'-LTDEMO-fbf99011639d','10.20.0.13',51004,531656861,14120249331,0,0,6051535428,0,'2026-04-23 22:47:59','2026-04-23 23:55:49','2026-04-23 23:44:02',1,7,'LiteTracker Demo Seeder',0,'28a3317599057a5d08cfecfbf124bcf5'),(25,10,'-LTDEMO-d24fd2fa489b','10.20.0.14',51005,401929424,3025767713,0,0,17146017046,0,'2026-04-23 23:09:15','2026-04-23 23:59:07','2026-04-23 23:45:48',1,8,'LiteTracker Demo Seeder',0,'857fea14026d59549178ae99da5d9a95'),(26,11,'-LTDEMO-7670e1d1a1ec','10.20.0.11',51001,7977320250,5068603914,0,0,0,1,'2026-04-23 22:56:36','2026-04-23 23:35:00','2026-04-23 23:25:00',1,5,'LiteTracker Demo Seeder',1776987300,'a217c5ee254e83bd81ee2990b2f39e46'),(27,11,'-LTDEMO-44c97460aff4','10.20.0.12',51002,7227058156,5068603914,0,0,0,1,'2026-04-23 21:59:53','2026-04-23 23:28:14','2026-04-23 23:20:49',1,6,'LiteTracker Demo Seeder',1776986894,'45cb04516b59322561ce90bf0ea392dc'),(28,11,'-LTDEMO-8a28e9ec4e15','10.20.0.13',51003,6444896051,5068603914,0,0,0,1,'2026-04-23 21:39:18','2026-04-23 23:34:04','2026-04-23 23:27:18',1,7,'LiteTracker Demo Seeder',1776987244,'28a3317599057a5d08cfecfbf124bcf5'),(29,11,'-LTDEMO-592afc4c588d','10.20.0.14',51004,6667731415,5068603914,0,0,0,1,'2026-04-23 21:43:57','2026-04-23 23:34:09','2026-04-23 23:23:41',1,8,'LiteTracker Demo Seeder',1776987249,'857fea14026d59549178ae99da5d9a95'),(30,12,'-LTDEMO-1c6d47596709','10.20.0.12',51001,4296345517,2850339125,0,0,0,1,'2026-04-23 21:55:39','2026-04-23 23:19:46','2026-04-23 23:07:22',1,6,'LiteTracker Demo Seeder',1776986386,'45cb04516b59322561ce90bf0ea392dc'),(31,12,'-LTDEMO-fff1b3a5fd35','10.20.0.13',51002,3625409798,2850339125,0,0,0,1,'2026-04-23 22:29:56','2026-04-23 23:27:37','2026-04-23 23:23:41',1,7,'LiteTracker Demo Seeder',1776986857,'28a3317599057a5d08cfecfbf124bcf5'),(32,12,'-LTDEMO-4d33d8685a44','10.20.0.14',51003,3896673225,2850339125,0,0,0,1,'2026-04-23 21:27:28','2026-04-23 23:23:23','2026-04-23 23:11:18',1,8,'LiteTracker Demo Seeder',1776986603,'857fea14026d59549178ae99da5d9a95'),(33,12,'-LTDEMO-59a67d8fa4f4','10.20.0.15',51004,4587421634,2850339125,0,0,0,1,'2026-04-23 22:47:28','2026-04-23 23:19:30','2026-04-23 23:15:05',1,9,'LiteTracker Demo Seeder',1776986370,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(34,12,'-LTDEMO-5813b99225cd','10.20.0.16',51005,4934811225,2850339125,0,0,0,1,'2026-04-23 22:36:17','2026-04-23 23:18:53','2026-04-23 23:09:16',1,10,'LiteTracker Demo Seeder',1776986333,'b9feffb7d138e319f492ca95cec48a6a'),(35,12,'-LTDEMO-ed8e7e32a335','10.20.0.11',51006,310123856,819472498,0,0,2030866627,0,'2026-04-23 22:32:25','2026-04-23 23:24:06','2026-04-23 23:15:42',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46'),(36,13,'-LTDEMO-47bbdf87a8b4','10.20.0.13',51001,6229443627,5024154571,0,0,0,1,'2026-04-23 20:19:54','2026-04-23 21:39:33','2026-04-23 21:24:02',1,7,'LiteTracker Demo Seeder',1776980373,'28a3317599057a5d08cfecfbf124bcf5'),(37,13,'-LTDEMO-71857a747476','10.20.0.14',51002,6078838520,5024154571,0,0,0,1,'2026-04-23 20:39:28','2026-04-23 21:34:03','2026-04-23 21:22:35',1,8,'LiteTracker Demo Seeder',1776980043,'857fea14026d59549178ae99da5d9a95'),(38,13,'-LTDEMO-1c20d1c541e5','10.20.0.15',51003,1755057224,2826086946,0,0,2198067625,0,'2026-04-23 20:55:27','2026-04-23 21:40:34','2026-04-23 21:24:12',1,9,'LiteTracker Demo Seeder',0,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(39,13,'-LTDEMO-a89be66996ab','10.20.0.16',51004,1191710143,3516908199,0,0,1507246372,0,'2026-04-23 20:41:16','2026-04-23 21:34:05','2026-04-23 21:21:19',1,10,'LiteTracker Demo Seeder',0,'b9feffb7d138e319f492ca95cec48a6a'),(40,14,'-LTDEMO-7d2dc08bb7d9','10.20.0.14',51001,9344536221,8813668612,0,0,0,1,'2026-04-23 21:15:32','2026-04-23 22:14:37','2026-04-23 22:07:59',1,8,'LiteTracker Demo Seeder',1776982477,'857fea14026d59549178ae99da5d9a95'),(41,14,'-LTDEMO-37fc123cf9b0','10.20.0.15',51002,11467962156,8813668612,0,0,0,1,'2026-04-23 21:47:15','2026-04-23 22:21:14','2026-04-23 22:13:36',1,9,'LiteTracker Demo Seeder',1776982874,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(42,14,'-LTDEMO-3803e6c1c1fd','10.20.0.16',51003,9580519589,8813668612,0,0,0,1,'2026-04-23 20:44:15','2026-04-23 22:16:01','2026-04-23 22:03:36',1,10,'LiteTracker Demo Seeder',1776982561,'b9feffb7d138e319f492ca95cec48a6a'),(43,15,'-LTDEMO-d652847e7411','10.20.0.15',51001,4522467196,3949678288,0,0,0,1,'2026-04-23 19:12:57','2026-04-23 21:03:00','2026-04-23 20:48:25',1,9,'LiteTracker Demo Seeder',1776978180,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(44,15,'-LTDEMO-9aee9f4a575a','10.20.0.16',51002,5171442174,3949678288,0,0,0,1,'2026-04-23 19:28:55','2026-04-23 21:01:59','2026-04-23 20:43:41',1,10,'LiteTracker Demo Seeder',1776978119,'b9feffb7d138e319f492ca95cec48a6a'),(45,15,'-LTDEMO-a3ed8ee7170d','10.20.0.11',51003,5410235679,3949678288,0,0,0,1,'2026-04-23 19:26:25','2026-04-23 21:00:53','2026-04-23 20:55:30',1,5,'LiteTracker Demo Seeder',1776978053,'a217c5ee254e83bd81ee2990b2f39e46'),(46,15,'-LTDEMO-8de58bbf33a2','10.20.0.12',51004,4947275759,3949678288,0,0,0,1,'2026-04-23 20:24:56','2026-04-23 20:56:29','2026-04-23 20:53:50',1,6,'LiteTracker Demo Seeder',1776977789,'45cb04516b59322561ce90bf0ea392dc'),(47,15,'-LTDEMO-27328fdb343d','10.20.0.13',51005,532590199,592451743,0,0,3357226545,0,'2026-04-23 19:11:10','2026-04-23 20:56:54','2026-04-23 20:50:19',1,7,'LiteTracker Demo Seeder',0,'28a3317599057a5d08cfecfbf124bcf5'),(48,16,'-LTDEMO-4dccaa92b52d','10.20.0.16',51001,7240659932,6146093738,0,0,0,1,'2026-04-23 15:21:10','2026-04-23 16:46:39','2026-04-23 16:40:48',1,10,'LiteTracker Demo Seeder',1776962799,'b9feffb7d138e319f492ca95cec48a6a'),(49,16,'-LTDEMO-25228c9d3bb0','10.20.0.11',51002,8659744309,6146093738,0,0,0,1,'2026-04-23 15:44:17','2026-04-23 16:52:00','2026-04-23 16:39:07',1,5,'LiteTracker Demo Seeder',1776963120,'a217c5ee254e83bd81ee2990b2f39e46'),(50,16,'-LTDEMO-27fb893b535a','10.20.0.12',51003,7014645757,6146093738,0,0,0,1,'2026-04-23 15:21:53','2026-04-23 16:54:59','2026-04-23 16:39:50',1,6,'LiteTracker Demo Seeder',1776963299,'45cb04516b59322561ce90bf0ea392dc'),(51,16,'-LTDEMO-e849db570f81','10.20.0.13',51004,8509382618,6146093738,0,0,0,1,'2026-04-23 15:28:34','2026-04-23 16:52:09','2026-04-23 16:44:11',1,7,'LiteTracker Demo Seeder',1776963129,'28a3317599057a5d08cfecfbf124bcf5'),(52,16,'-LTDEMO-7c98cbf684fd','10.20.0.14',51005,8776294206,6146093738,0,0,0,1,'2026-04-23 16:14:24','2026-04-23 16:50:54','2026-04-23 16:32:46',1,8,'LiteTracker Demo Seeder',1776963054,'857fea14026d59549178ae99da5d9a95'),(53,16,'-LTDEMO-fc03da7d79bf','10.20.0.15',51006,276909639,1767001949,0,0,4379091789,0,'2026-04-23 16:03:24','2026-04-23 16:52:38','2026-04-23 16:38:20',1,9,'LiteTracker Demo Seeder',0,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(54,16,'-LTDEMO-1faf7a8615cf','10.20.0.16',51007,753836254,2612089838,0,0,3534003900,0,'2026-04-23 15:32:32','2026-04-23 16:52:53','2026-04-23 16:33:20',1,10,'LiteTracker Demo Seeder',0,'b9feffb7d138e319f492ca95cec48a6a'),(55,17,'-LTDEMO-22bf45aca61e','10.20.0.11',51001,7384858640,4893986394,0,0,0,1,'2026-04-23 17:34:31','2026-04-23 19:01:19','2026-04-23 18:45:12',1,5,'LiteTracker Demo Seeder',1776970879,'a217c5ee254e83bd81ee2990b2f39e46'),(56,17,'-LTDEMO-3657b03dfbd8','10.20.0.12',51002,7958774518,4893986394,0,0,0,1,'2026-04-23 17:37:12','2026-04-23 19:01:17','2026-04-23 18:51:29',1,6,'LiteTracker Demo Seeder',1776970877,'45cb04516b59322561ce90bf0ea392dc'),(57,18,'-LTDEMO-584aa31aa978','10.20.0.12',51001,12491552105,10088854921,0,0,0,1,'2026-04-23 17:19:18','2026-04-23 17:58:56','2026-04-23 17:49:44',1,6,'LiteTracker Demo Seeder',1776967136,'45cb04516b59322561ce90bf0ea392dc'),(58,18,'-LTDEMO-5011a08186e4','10.20.0.13',51002,12633378334,10088854921,0,0,0,1,'2026-04-23 17:24:19','2026-04-23 17:58:20','2026-04-23 17:50:53',1,7,'LiteTracker Demo Seeder',1776967100,'28a3317599057a5d08cfecfbf124bcf5'),(59,18,'-LTDEMO-fdcaf37fa4a2','10.20.0.14',51003,13234025978,10088854921,0,0,0,1,'2026-04-23 16:50:02','2026-04-23 18:02:01','2026-04-23 17:57:00',1,8,'LiteTracker Demo Seeder',1776967321,'857fea14026d59549178ae99da5d9a95'),(60,18,'-LTDEMO-5022a7eabe82','10.20.0.15',51004,1040540745,7062198444,0,0,3026656477,0,'2026-04-23 16:15:46','2026-04-23 17:59:57','2026-04-23 17:50:02',1,9,'LiteTracker Demo Seeder',0,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(61,19,'-LTDEMO-9686923026e7','10.20.0.13',51001,9241868846,8032386011,0,0,0,1,'2026-04-23 16:36:31','2026-04-23 17:22:40','2026-04-23 17:16:00',1,7,'LiteTracker Demo Seeder',1776964960,'28a3317599057a5d08cfecfbf124bcf5'),(62,19,'-LTDEMO-e2bd5d7d65c1','10.20.0.14',51002,9115677210,8032386011,0,0,0,1,'2026-04-23 15:54:50','2026-04-23 17:25:57','2026-04-23 17:06:59',1,8,'LiteTracker Demo Seeder',1776965157,'857fea14026d59549178ae99da5d9a95'),(63,19,'-LTDEMO-fabbb3a59a4b','10.20.0.15',51003,11185335797,8032386011,0,0,0,1,'2026-04-23 15:28:04','2026-04-23 17:19:25','2026-04-23 17:02:18',1,9,'LiteTracker Demo Seeder',1776964765,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(64,19,'-LTDEMO-e6d4a70bc533','10.20.0.16',51004,9521464975,8032386011,0,0,0,1,'2026-04-23 16:06:57','2026-04-23 17:23:32','2026-04-23 17:19:21',1,10,'LiteTracker Demo Seeder',1776965012,'b9feffb7d138e319f492ca95cec48a6a'),(65,19,'-LTDEMO-35513e220f6b','10.20.0.11',51005,1119860074,1204857901,0,0,6827528110,0,'2026-04-23 15:52:24','2026-04-23 17:21:05','2026-04-23 17:11:28',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46'),(66,19,'-LTDEMO-1976231f8985','10.20.0.12',51006,1030768561,2309310978,0,0,5723075033,0,'2026-04-23 15:31:56','2026-04-23 17:19:46','2026-04-23 17:01:57',1,6,'LiteTracker Demo Seeder',0,'45cb04516b59322561ce90bf0ea392dc'),(67,20,'-LTDEMO-6062f14d6af9','10.20.0.14',51001,7121484343,4766837278,0,0,0,1,'2026-04-23 14:04:38','2026-04-23 16:00:58','2026-04-23 15:49:01',1,8,'LiteTracker Demo Seeder',1776960058,'857fea14026d59549178ae99da5d9a95'),(68,20,'-LTDEMO-98ae4a810d23','10.20.0.15',51002,6352944394,4766837278,0,0,0,1,'2026-04-23 14:35:39','2026-04-23 15:52:59','2026-04-23 15:40:24',1,9,'LiteTracker Demo Seeder',1776959579,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(69,20,'-LTDEMO-bdc4b492568d','10.20.0.16',51003,7857648327,4766837278,0,0,0,1,'2026-04-23 14:01:11','2026-04-23 16:00:07','2026-04-23 15:44:47',1,10,'LiteTracker Demo Seeder',1776960007,'b9feffb7d138e319f492ca95cec48a6a'),(70,20,'-LTDEMO-f62a76f0ea54','10.20.0.11',51004,7315313367,4766837278,0,0,0,1,'2026-04-23 14:31:00','2026-04-23 15:56:29','2026-04-23 15:38:50',1,5,'LiteTracker Demo Seeder',1776959789,'a217c5ee254e83bd81ee2990b2f39e46'),(71,20,'-LTDEMO-3ea3f86051f8','10.20.0.12',51005,7240849292,4766837278,0,0,0,1,'2026-04-23 14:24:50','2026-04-23 15:56:39','2026-04-23 15:40:05',1,6,'LiteTracker Demo Seeder',1776959799,'45cb04516b59322561ce90bf0ea392dc'),(72,21,'-LTDEMO-79302b7c56fc','10.20.0.15',51001,10893269819,7848312060,0,0,0,1,'2026-04-23 12:54:59','2026-04-23 14:34:02','2026-04-23 14:14:13',1,9,'LiteTracker Demo Seeder',1776954842,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(73,21,'-LTDEMO-6e010e795bd7','10.20.0.16',51002,9854451237,7848312060,0,0,0,1,'2026-04-23 13:54:42','2026-04-23 14:34:39','2026-04-23 14:15:00',1,10,'LiteTracker Demo Seeder',1776954879,'b9feffb7d138e319f492ca95cec48a6a'),(74,21,'-LTDEMO-913e0c46e989','10.20.0.11',51003,1124677368,4414675533,0,0,3433636527,0,'2026-04-23 13:05:33','2026-04-23 14:34:43','2026-04-23 14:23:50',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46'),(75,22,'-LTDEMO-5870f6bbef77','10.20.0.16',51001,10206794648,9014979758,0,0,0,1,'2026-04-23 08:54:14','2026-04-23 10:53:57','2026-04-23 10:34:30',1,10,'LiteTracker Demo Seeder',1776941637,'b9feffb7d138e319f492ca95cec48a6a'),(76,22,'-LTDEMO-39dd6ced186c','10.20.0.11',51002,11440741514,9014979758,0,0,0,1,'2026-04-23 09:44:20','2026-04-23 10:58:48','2026-04-23 10:43:39',1,5,'LiteTracker Demo Seeder',1776941928,'a217c5ee254e83bd81ee2990b2f39e46'),(77,22,'-LTDEMO-4c951f29804e','10.20.0.12',51003,10867116145,9014979758,0,0,0,1,'2026-04-23 09:22:53','2026-04-23 10:51:19','2026-04-23 10:40:35',1,6,'LiteTracker Demo Seeder',1776941479,'45cb04516b59322561ce90bf0ea392dc'),(78,22,'-LTDEMO-aa46d75bb5bf','10.20.0.13',51004,2108217606,6310485830,0,0,2704493928,0,'2026-04-23 10:23:11','2026-04-23 10:57:02','2026-04-23 10:44:08',1,7,'LiteTracker Demo Seeder',0,'28a3317599057a5d08cfecfbf124bcf5'),(79,22,'-LTDEMO-6751746ad83c','10.20.0.14',51005,767848272,1352246963,0,0,7662732795,0,'2026-04-23 09:43:28','2026-04-23 10:51:21','2026-04-23 10:37:34',1,8,'LiteTracker Demo Seeder',0,'857fea14026d59549178ae99da5d9a95'),(80,23,'-LTDEMO-cb454b8e973a','10.20.0.11',51001,7425858791,4486253368,0,0,0,1,'2026-04-23 10:41:55','2026-04-23 11:13:09','2026-04-23 11:10:15',1,5,'LiteTracker Demo Seeder',1776942789,'a217c5ee254e83bd81ee2990b2f39e46'),(81,23,'-LTDEMO-200582cabf60','10.20.0.12',51002,7396239435,4486253368,0,0,0,1,'2026-04-23 10:21:45','2026-04-23 11:04:10','2026-04-23 11:00:06',1,6,'LiteTracker Demo Seeder',1776942250,'45cb04516b59322561ce90bf0ea392dc'),(82,23,'-LTDEMO-5f504dd6d93c','10.20.0.13',51003,7542276993,4486253368,0,0,0,1,'2026-04-23 10:18:16','2026-04-23 11:08:01','2026-04-23 11:04:58',1,7,'LiteTracker Demo Seeder',1776942481,'28a3317599057a5d08cfecfbf124bcf5'),(83,23,'-LTDEMO-ff141e0c22de','10.20.0.14',51004,7632832404,4486253368,0,0,0,1,'2026-04-23 10:24:25','2026-04-23 11:11:11','2026-04-23 11:04:22',1,8,'LiteTracker Demo Seeder',1776942671,'857fea14026d59549178ae99da5d9a95'),(84,24,'-LTDEMO-79e3dc8bc61e','10.20.0.12',51001,4535142288,3967058750,0,0,0,1,'2026-04-23 10:50:10','2026-04-23 11:39:00','2026-04-23 11:19:42',1,6,'LiteTracker Demo Seeder',1776944340,'45cb04516b59322561ce90bf0ea392dc'),(85,24,'-LTDEMO-df2ee65fb931','10.20.0.13',51002,5433995654,3967058750,0,0,0,1,'2026-04-23 10:38:38','2026-04-23 11:33:22','2026-04-23 11:20:19',1,7,'LiteTracker Demo Seeder',1776944002,'28a3317599057a5d08cfecfbf124bcf5'),(86,24,'-LTDEMO-ce2066c8fd6c','10.20.0.14',51003,5321622612,3967058750,0,0,0,1,'2026-04-23 10:02:21','2026-04-23 11:39:48','2026-04-23 11:33:18',1,8,'LiteTracker Demo Seeder',1776944388,'857fea14026d59549178ae99da5d9a95'),(87,24,'-LTDEMO-0dbb0a015476','10.20.0.15',51004,6229798234,3967058750,0,0,0,1,'2026-04-23 10:43:43','2026-04-23 11:36:14','2026-04-23 11:26:00',1,9,'LiteTracker Demo Seeder',1776944174,'9e722e858e3cd3e8d14d0c0b1fa63df6'),(88,24,'-LTDEMO-238eefdeac9a','10.20.0.16',51005,6098911732,3967058750,0,0,0,1,'2026-04-23 10:05:39','2026-04-23 11:32:46','2026-04-23 11:20:53',1,10,'LiteTracker Demo Seeder',1776943966,'b9feffb7d138e319f492ca95cec48a6a'),(89,24,'-LTDEMO-6d7c3ffb2b3c','10.20.0.11',51006,1784497624,1140529390,0,0,2826529360,0,'2026-04-23 09:54:37','2026-04-23 11:39:29','2026-04-23 11:30:26',1,5,'LiteTracker Demo Seeder',0,'a217c5ee254e83bd81ee2990b2f39e46');
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `search_query` WRITE;
/*!40000 ALTER TABLE `search_query` DISABLE KEYS */;
INSERT INTO `search_query` VALUES (4,'кино',1,0,2,'2026-04-23 16:14:50',0),(3,'тестим',1,0,2,'2026-04-23 16:14:47',0);
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
) ENGINE=MyISAM AUTO_INCREMENT=456 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (455,'dc109721e7bea2462b6ccde03d1d37de',-1,'2026-04-24 04:46:59',0,'','Standard input code'),(454,'7457cd27b1ab6a92be62781bc4e7a6a2',-1,'2026-04-24 04:46:59',0,'','Standard input code'),(453,'5a1ecee1d4b4e0e9de577e31209e09e9',-1,'2026-04-24 04:46:49',0,'','Standard input code'),(451,'04deaf1b5e754f9dd5a9f539d895cfe3',7,'2026-04-24 04:39:55',169082893,'LiteTracker Demo Seeder','/index.php'),(452,'b7e5be1ed5c358b8f8090ddeb659932a',8,'2026-04-24 04:43:41',169082894,'LiteTracker Demo Seeder','/index.php'),(450,'54f16a615896cc24b92c1609b38b791f',6,'2026-04-24 04:44:28',169082892,'LiteTracker Demo Seeder','/index.php'),(449,'385080cb3991019629809a335a174d0b',5,'2026-04-24 04:45:45',169082891,'LiteTracker Demo Seeder','/index.php'),(448,'aee8c7a623f0b040bce374bba1dca03c',-1,'2026-04-24 04:46:41',2130706433,'','/var/www/html/scripts/seed_demo_activity.php'),(447,'622bf2fb04168b6c04f4050660765b67',-1,'2026-04-24 04:43:53',0,'','Standard input code'),(446,'01acdbac02e1c0bd46685b82c180c1e9',-1,'2026-04-24 04:43:21',0,'','Standard input code'),(445,'3e6652ce56bb8bfa54715568c5c85f50',-1,'2026-04-24 04:43:21',0,'','Standard input code'),(444,'d9d50d56dc0194d053391dc107155d38',-1,'2026-04-24 04:47:32',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(443,'0f41647299e87f64787273bab23ca8d5',2,'2026-04-23 16:18:46',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/edit.php');
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
) ENGINE=MyISAM AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `snatched` WRITE;
/*!40000 ALTER TABLE `snatched` DISABLE KEYS */;
INSERT INTO `snatched` VALUES (1,6,5,9801991610,8168326342,1776816206,1776982214,1),(2,7,5,9801991610,8168326342,1776845189,1776999717,1),(3,8,5,1225248951,5309412122,1776907378,0,0),(4,7,6,21453301898,17877751582,1776853398,1776979166,1),(5,8,6,21453301898,17877751582,1776861564,1776960642,1),(6,9,6,2681662737,11620538528,1776900344,0,0),(7,10,6,2681662737,11620538528,1776931220,0,0),(8,8,7,9784019374,8153349479,1776901586,1776963092,1),(9,9,7,9784019374,8153349479,1776902584,1776967927,1),(10,10,7,1223002421,5299677161,1776985600,0,0),(11,5,7,1223002421,5299677161,1776813280,0,0),(12,6,7,1223002421,5299677161,1776860417,0,0),(13,9,8,5730704448,4775587040,1776898327,1776958874,1),(14,10,8,5730704448,4775587040,1776953165,1776994721,1),(15,5,8,716338056,3104131576,1776882992,0,0),(16,10,9,6999592755,5832993963,1776926748,1776988530,1),(17,5,9,6999592755,5832993963,1776805444,1776953434,1),(18,6,9,874949094,3791446075,1776850116,0,0),(19,7,9,874949094,3791446075,1776852125,0,0),(20,5,10,24206141710,20171784759,1776797395,1776954674,1),(21,6,10,24206141710,20171784759,1776941594,1776985740,1),(22,7,10,3025767713,13111660093,1776838569,0,0),(23,8,10,3025767713,13111660093,1776843989,0,0),(24,9,10,3025767713,13111660093,1776880089,0,0),(25,6,11,6082324696,5068603914,1776860528,1776960737,1),(26,7,11,6082324696,5068603914,1776864373,1776977708,1),(27,8,11,760290587,3294592544,1776851194,0,0),(28,7,12,3420406950,2850339125,1776912553,1776980165,1),(29,8,12,3420406950,2850339125,1776795007,1776949465,1),(30,9,12,427550868,1852720431,1776852247,0,0),(31,10,12,427550868,1852720431,1776855335,0,0),(32,8,13,6028985485,5024154571,1776836138,1776951753,1),(33,9,13,6028985485,5024154571,1776784582,1776951040,1),(34,10,13,753623185,3265700471,1776879189,0,0),(35,5,13,753623185,3265700471,1776865900,0,0),(36,6,13,753623185,3265700471,1776957958,0,0),(37,9,14,10576402334,8813668612,1776869544,1776952646,1),(38,10,14,10576402334,8813668612,1776834437,1776961164,1),(39,5,14,1322050291,5728884597,1776880250,0,0),(40,10,15,4739613945,3949678288,1776910375,1776954373,1),(41,5,15,4739613945,3949678288,1776856487,1776964502,1),(42,6,15,592451743,2567290887,1776879804,0,0),(43,7,15,592451743,2567290887,1776947464,0,0),(44,5,16,7375312485,6146093738,1776948780,1776957095,1),(45,6,16,7375312485,6146093738,1776885481,1776937705,1),(46,7,16,921914060,3994960929,1776806723,0,0),(47,8,16,921914060,3994960929,1776814215,0,0),(48,9,16,921914060,3994960929,1776790138,0,0),(49,6,17,5872783672,4893986394,1776877221,1776934622,1),(50,7,17,5872783672,4893986394,1776913158,1776957871,1),(51,8,17,734097959,3181091156,1776837223,0,0),(52,7,18,12106625905,10088854921,1776849009,1776952722,1),(53,8,18,12106625905,10088854921,1776905960,1776940033,1),(54,9,18,1513328238,6557755698,1776783746,0,0),(55,10,18,1513328238,6557755698,1776908256,0,0),(56,8,19,9638863213,8032386011,1776878945,1776959921,1),(57,9,19,9638863213,8032386011,1776794050,1776945680,1),(58,10,19,1204857901,5221050907,1776811244,0,0),(59,5,19,1204857901,5221050907,1776922246,0,0),(60,6,19,1204857901,5221050907,1776800504,0,0),(61,9,20,5720204733,4766837278,1776934014,1776955456,1),(62,10,20,5720204733,4766837278,1776830401,1776951521,1),(63,5,20,715025591,3098444230,1776838429,0,0),(64,10,21,9417974472,7848312060,1776899144,1776940946,1),(65,5,21,9417974472,7848312060,1776811361,1776939777,1),(66,6,21,1177246809,5101402839,1776846321,0,0),(67,7,21,1177246809,5101402839,1776864423,0,0),(68,5,22,10817975709,9014979758,1776891870,1776905963,1),(69,6,22,10817975709,9014979758,1776778657,1776923974,1),(70,7,22,1352246963,5859736842,1776848660,0,0),(71,8,22,1352246963,5859736842,1776899872,0,0),(72,9,22,1352246963,5859736842,1776878208,0,0),(73,6,23,5383504041,4486253368,1776800317,1776940077,1),(74,7,23,5383504041,4486253368,1776735858,1776899835,1),(75,8,23,672938005,2916064689,1776888033,0,0),(76,7,24,4760470500,3967058750,1776815169,1776908847,1),(77,8,24,4760470500,3967058750,1776846400,1776916080,1),(78,9,24,595058812,2578588187,1776896779,0,0),(79,10,24,595058812,2578588187,1776803938,0,0);
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
) ENGINE=MyISAM AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrent_ratings` WRITE;
/*!40000 ALTER TABLE `torrent_ratings` DISABLE KEYS */;
INSERT INTO `torrent_ratings` VALUES (1,4,2,5,'192.168.65.1','2026-04-22 15:17:31'),(2,5,6,4,'10.20.0.12','2026-04-24 05:36:26'),(3,5,7,5,'10.20.0.13','2026-04-24 05:43:20'),(4,5,8,4,'10.20.0.14','2026-04-24 05:32:01'),(5,6,7,5,'10.20.0.13','2026-04-24 03:04:37'),(6,6,8,4,'10.20.0.14','2026-04-24 03:00:22'),(7,6,9,5,'10.20.0.15','2026-04-24 02:47:23'),(8,7,8,4,'10.20.0.14','2026-04-24 03:13:08'),(9,7,9,5,'10.20.0.15','2026-04-24 03:07:20'),(10,7,10,4,'10.20.0.16','2026-04-24 02:49:10'),(11,8,9,5,'10.20.0.15','2026-04-24 02:07:36'),(12,8,10,4,'10.20.0.16','2026-04-24 02:11:53'),(13,8,5,5,'10.20.0.11','2026-04-24 02:11:42'),(14,9,10,4,'10.20.0.16','2026-04-24 00:15:48'),(15,9,5,5,'10.20.0.11','2026-04-23 23:54:11'),(16,9,6,4,'10.20.0.12','2026-04-24 00:04:48'),(17,10,5,5,'10.20.0.11','2026-04-23 23:30:59'),(18,10,6,4,'10.20.0.12','2026-04-23 23:36:06'),(19,10,7,5,'10.20.0.13','2026-04-23 23:56:38'),(20,11,6,4,'10.20.0.12','2026-04-23 23:29:42'),(21,11,7,5,'10.20.0.13','2026-04-23 23:13:46'),(22,11,8,4,'10.20.0.14','2026-04-23 23:07:25'),(23,12,7,5,'10.20.0.13','2026-04-23 23:07:43'),(24,12,8,4,'10.20.0.14','2026-04-23 23:01:43'),(25,12,9,5,'10.20.0.15','2026-04-23 23:19:44'),(26,13,8,4,'10.20.0.14','2026-04-23 21:28:45'),(27,13,9,5,'10.20.0.15','2026-04-23 21:29:39'),(28,13,10,4,'10.20.0.16','2026-04-23 21:16:21'),(29,14,9,5,'10.20.0.15','2026-04-23 21:58:50'),(30,14,10,4,'10.20.0.16','2026-04-23 22:10:49'),(31,14,5,5,'10.20.0.11','2026-04-23 21:55:30'),(32,15,10,4,'10.20.0.16','2026-04-23 20:59:59'),(33,15,5,5,'10.20.0.11','2026-04-23 20:43:57'),(34,15,6,4,'10.20.0.12','2026-04-23 20:59:33'),(35,16,5,5,'10.20.0.11','2026-04-23 16:43:37'),(36,16,6,4,'10.20.0.12','2026-04-23 16:54:45'),(37,16,7,5,'10.20.0.13','2026-04-23 16:47:26'),(38,17,6,4,'10.20.0.12','2026-04-23 18:54:39'),(39,17,7,5,'10.20.0.13','2026-04-23 18:55:03'),(40,17,8,4,'10.20.0.14','2026-04-23 18:54:56'),(41,18,7,5,'10.20.0.13','2026-04-23 17:57:27'),(42,18,8,4,'10.20.0.14','2026-04-23 17:58:42'),(43,18,9,5,'10.20.0.15','2026-04-23 18:01:39'),(44,19,8,4,'10.20.0.14','2026-04-23 17:09:01'),(45,19,9,5,'10.20.0.15','2026-04-23 17:10:58'),(46,19,10,4,'10.20.0.16','2026-04-23 17:23:45'),(47,20,9,5,'10.20.0.15','2026-04-23 15:57:41'),(48,20,10,4,'10.20.0.16','2026-04-23 15:51:13'),(49,20,5,5,'10.20.0.11','2026-04-23 15:51:49'),(50,21,10,4,'10.20.0.16','2026-04-23 14:26:43'),(51,21,5,5,'10.20.0.11','2026-04-23 14:12:25'),(52,21,6,4,'10.20.0.12','2026-04-23 14:13:36'),(53,22,5,5,'10.20.0.11','2026-04-23 10:38:16'),(54,22,6,4,'10.20.0.12','2026-04-23 10:50:29'),(55,22,7,5,'10.20.0.13','2026-04-23 10:58:52'),(56,23,6,4,'10.20.0.12','2026-04-23 10:59:33'),(57,23,7,5,'10.20.0.13','2026-04-23 10:46:11'),(58,23,8,4,'10.20.0.14','2026-04-23 11:11:56'),(59,24,7,5,'10.20.0.13','2026-04-23 11:29:57'),(60,24,8,4,'10.20.0.14','2026-04-23 11:34:18'),(61,24,9,5,'10.20.0.15','2026-04-23 11:16:33');
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
  PRIMARY KEY (`id`),
  KEY `idx_torrents_banned_added` (`banned`,`added`),
  KEY `idx_torrents_category_banned_added` (`id_category`,`banned`,`added`),
  KEY `idx_torrents_user_added` (`id_user`,`added`),
  KEY `idx_torrents_news_added` (`news`,`added`),
  KEY `idx_torrents_type` (`type`),
  KEY `idx_torrents_content_type` (`content_type`),
  KEY `idx_torrents_name` (`name`(191))
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `torrents` WRITE;
/*!40000 ALTER TABLE `torrents` DISABLE KEYS */;
INSERT INTO `torrents` VALUES (4,'0','Проект «Конец света»','[kinozal.tv]id2135330.torrent',19894460567,11,'Информация об аниме\r\nСтрана: Япония\r\nТип: ТВ\r\nЖанр: исекай, комедия, фэнтези, экшен\r\nГод выхода: 2026\r\nКол серий: >12 эп\r\nРежиссер: Цуда Наокацу\r\nПродолжительность: 25 мин\r\nОписание: После триумфального фестиваля Федерация Джура становится главным экономическим центром мира, но процветание порождает опасную зависть. Пока Римуру укрепляет дипломатические связи, из тени нападают те, кто привык тайно править человечеством — могущественный род Россо.\r\nВ этом противостоянии мечи уступят место коварным финансовым интригам и политическим заговорам. Мариабель Россо намерена разрушить репутацию Темпеста и подчинить себе волю Князя Тьмы, используя амбиции западных королей. Римуру предстоит доказать, что его страна способна выстоять не только в честном бою, но и в большой игре, где на кону стоит само право монстров на мирное сосуществование с людьми.\r\n\r\n[b]Дополнительно[/b]\r\nФормат: mkv\r\nРазрешение: 1280x720\r\nСубтитры: английские (полухардсаб), русские (софтсаб)\r\nЯзык: японский\r\n\r\nТоррент был обновлен\r\nПричина: Добавлены 3 эпизод и русские субтитры к нему.',_binary '2fb93f16ce6fb284a7846d4e3b207b0ef660382f','Фантастика,драма,триллер,экранизация,фильмы о космосе',3,2,'2026-04-18 06:04:30','4.jpg','0',0,'2026-04-18 06:04:30','4_0.jpg','','','','',1,'movie','russian','russian,english','action,detective,drama,documentary','licensed','usa,germany','single',1),(5,'0','[DEMO] Ночной Архив','demo-release-01.torrent',8168326342,531,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 10 из 10\n[b]Продолжительность:[/b] 24 мин\n[b]Режиссер:[/b] Морита Сюн\n[b]Описание:[/b] История о команде школьников, которые случайно открывают доступ к закрытому архиву воспоминаний и пытаются понять, почему город начал забывать собственное прошлое.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 1080p\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '1e1af37df09a2534a8b398be92779501be84083f','демо,Аниме,приключения,фантастика,сезон',6,5,'2026-04-24 03:45:41','5.gif','0',10,'2026-04-24 05:47:41','5_0.gif','','','','',1,'tv','russian,english','japanese,russian','adventure,fantasy,drama','licensed','japan','single',1),(6,'0','[DEMO] Сад Комет','demo-release-02.torrent',17877751582,489,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 1 из 1\n[b]Продолжительность:[/b] 25 мин\n[b]Режиссер:[/b] Окада Рина\n[b]Описание:[/b] Небольшая студия озвучки получает шанс спасти любимый сериал, но для этого героям приходится объединиться с людьми, которых они раньше обходили стороной.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 720p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'd948dd4b47ed5dfe824d565d05b1b5f892155616','демо,Аниме,приключения,фантастика,сезон',6,6,'2026-04-24 02:44:41','6.gif','0',31,'2026-04-24 03:17:09','6_0.gif','','','','',1,'movie','russian,english','japanese,russian','fantasy,drama,comedy','licensed,hevc','japan','single',1),(7,'0','[DEMO] Город Тихих Масок','demo-release-03.torrent',8153349479,486,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 13 из 13\n[b]Продолжительность:[/b] 47 мин\n[b]Режиссер:[/b] Фудзивара Кэй\n[b]Описание:[/b] После странного метеоритного дождя привычные маршруты города меняются, а каждая ночь приносит новые правила и новые обещания.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] WEB-DL\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'ea36ce93ff3c22ddf037e3ffca5da10753eb757f','демо,Аниме,приключения,фантастика,сезон',6,7,'2026-04-24 01:43:41','7.gif','0',100,'2026-04-24 03:14:23','7_0.gif','','','','',1,'ova','russian,english','japanese,russian','drama,comedy,science_fiction','licensed','japan','single',3),(8,'0','[DEMO] Почтальон Из Облаков','demo-release-04.torrent',4775587040,250,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 8 из 8\n[b]Продолжительность:[/b] 1 ч 28 мин\n[b]Режиссер:[/b] Хосино Ая\n[b]Описание:[/b] Главная героиня работает на воздушной почте и однажды получает письмо, адресованное человеку, исчезнувшему много лет назад.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] BDRip 1080p\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '4db6308c68350458e10f2c718013c2048a2bd896','демо,Аниме,приключения,фантастика,сезон',6,8,'2026-04-24 00:42:41','8.gif','0',114,'2026-04-24 02:28:45','8_0.gif','','','','',1,'special','russian,english','japanese,russian','adventure,fantasy,drama','licensed,hevc','japan','single',1),(9,'0','[DEMO] Клинок Летнего Дождя','demo-release-05.torrent',5832993963,212,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 10 из 10\n[b]Продолжительность:[/b] 24 мин\n[b]Режиссер:[/b] Морита Сюн\n[b]Описание:[/b] Команда курьеров на магнитной железной дороге сталкивается с таинственным пассажиром, который знает о них больше, чем положено.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 1080p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '30eebe37fa51637714434857bc03d63d0f48a0e8','демо,Аниме,приключения,фантастика,сезон',6,9,'2026-04-23 23:41:41','9.gif','0',32,'2026-04-24 00:21:04','9_0.gif','','','','',1,'tv','russian,english','japanese,russian','fantasy,drama,comedy','licensed','japan','single',2),(10,'0','[DEMO] Пульс Стеклянной Башни','demo-release-06.torrent',20171784759,65,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 1 из 1\n[b]Продолжительность:[/b] 25 мин\n[b]Режиссер:[/b] Окада Рина\n[b]Описание:[/b] Молодой механик собирает устройство для записи снов, и очень быстро становится понятно, что некоторые чужие сны совсем не хотят оставаться снами.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 720p\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '1f1fb29f41bfcc3b96a75d6441cde6cbaea5e924','демо,Аниме,приключения,фантастика,сезон',6,10,'2026-04-23 22:40:41','10.gif','0',113,'2026-04-24 00:00:06','10_0.gif','','','','',1,'movie','russian,english','japanese,russian','drama,comedy,science_fiction','licensed,hevc','japan','single',1),(11,'0','[DEMO] Последний Кадр Рассвета','demo-release-07.torrent',5068603914,452,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 13 из 13\n[b]Продолжительность:[/b] 47 мин\n[b]Режиссер:[/b] Фудзивара Кэй\n[b]Описание:[/b] История о команде школьников, которые случайно открывают доступ к закрытому архиву воспоминаний и пытаются понять, почему город начал забывать собственное прошлое.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] WEB-DL\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '1bd0b0c8f3435b14f7fc2fa2888321d19325810a','демо,Аниме,приключения,фантастика,сезон',6,5,'2026-04-23 21:39:41','11.gif','0',30,'2026-04-23 23:35:47','11_0.gif','','','','',0,'ova','russian,english','japanese,russian','adventure,fantasy,drama','licensed','japan','single',1),(12,'0','[DEMO] Радио На Краю Моря','demo-release-08.torrent',2850339125,301,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 8 из 8\n[b]Продолжительность:[/b] 1 ч 28 мин\n[b]Режиссер:[/b] Хосино Ая\n[b]Описание:[/b] Небольшая студия озвучки получает шанс спасти любимый сериал, но для этого героям приходится объединиться с людьми, которых они раньше обходили стороной.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] BDRip 1080p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '65b326991d95e3b486d1d7809cc9812a0e017069','демо,Аниме,приключения,фантастика,сезон',6,6,'2026-04-23 20:38:41','12.gif','0',85,'2026-04-23 23:28:40','12_0.gif','','','','',0,'special','russian,english','japanese,russian','fantasy,drama,comedy','licensed,hevc','japan','single',2),(13,'0','[DEMO] Хроники Янтарной Станции','demo-release-09.torrent',5024154571,163,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 10 из 10\n[b]Продолжительность:[/b] 24 мин\n[b]Режиссер:[/b] Морита Сюн\n[b]Описание:[/b] После странного метеоритного дождя привычные маршруты города меняются, а каждая ночь приносит новые правила и новые обещания.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 1080p\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '76e728da8268d8eeca8385e2af254a3769548f13','демо,Аниме,приключения,фантастика,сезон',6,7,'2026-04-23 19:37:41','13.gif','0',48,'2026-04-23 21:43:11','13_0.gif','','','','',0,'tv','russian,english','japanese,russian','drama,comedy,science_fiction','licensed','japan','single',3),(14,'0','[DEMO] Тетрадь Полярного Ветра','demo-release-10.torrent',8813668612,480,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 1 из 1\n[b]Продолжительность:[/b] 25 мин\n[b]Режиссер:[/b] Окада Рина\n[b]Описание:[/b] Главная героиня работает на воздушной почте и однажды получает письмо, адресованное человеку, исчезнувшему много лет назад.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 720p\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'd26a7e5f1e2f7578f4119bc7cab2530dbd136725','демо,Аниме,приключения,фантастика,сезон',6,8,'2026-04-23 18:36:41','14.gif','0',91,'2026-04-23 22:23:13','14_0.gif','','','','',0,'movie','russian,english','japanese,russian','adventure,fantasy,drama','licensed,hevc','japan','single',1),(15,'0','[DEMO] Дом Для Заблудших Звезд','demo-release-11.torrent',3949678288,511,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 13 из 13\n[b]Продолжительность:[/b] 47 мин\n[b]Режиссер:[/b] Фудзивара Кэй\n[b]Описание:[/b] Команда курьеров на магнитной железной дороге сталкивается с таинственным пассажиром, который знает о них больше, чем положено.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] WEB-DL\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'f925617bff4e862f3789ac7c8a8a688c285afd53','демо,Аниме,приключения,фантастика,сезон',6,9,'2026-04-23 17:35:41','15.gif','0',14,'2026-04-23 21:05:57','15_0.gif','','','','',0,'ova','russian,english','japanese,russian','fantasy,drama,comedy','licensed','japan','single',2),(16,'0','[DEMO] Фонарь На Перроне 7','demo-release-12.torrent',6146093738,500,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 8 из 8\n[b]Продолжительность:[/b] 1 ч 28 мин\n[b]Режиссер:[/b] Хосино Ая\n[b]Описание:[/b] Молодой механик собирает устройство для записи снов, и очень быстро становится понятно, что некоторые чужие сны совсем не хотят оставаться снами.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] BDRip 1080p\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'afe8ebcf6355bdc7e92b26ea38135107d9b5ac64','демо,Аниме,приключения,фантастика,сезон',6,10,'2026-04-23 16:34:41','16.gif','0',41,'2026-04-23 16:56:30','16_0.gif','','','','',0,'special','russian,english','japanese,russian','drama,comedy,science_fiction','licensed,hevc','japan','single',3),(17,'0','[DEMO] Сигнал Из Лунной Бухты','demo-release-13.torrent',4893986394,528,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 10 из 10\n[b]Продолжительность:[/b] 24 мин\n[b]Режиссер:[/b] Морита Сюн\n[b]Описание:[/b] История о команде школьников, которые случайно открывают доступ к закрытому архиву воспоминаний и пытаются понять, почему город начал забывать собственное прошлое.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 1080p\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '0ed255a596d6cd9ed91dfdd473ffee2c14d647f4','демо,Аниме,приключения,фантастика,сезон',6,5,'2026-04-23 15:33:41','17.gif','0',53,'2026-04-23 19:08:53','17_0.gif','','','','',0,'tv','russian,english','japanese,russian','adventure,fantasy,drama','licensed','japan','single',1),(18,'0','[DEMO] Механика Снов','demo-release-14.torrent',10088854921,282,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 1 из 1\n[b]Продолжительность:[/b] 25 мин\n[b]Режиссер:[/b] Окада Рина\n[b]Описание:[/b] Небольшая студия озвучки получает шанс спасти любимый сериал, но для этого героям приходится объединиться с людьми, которых они раньше обходили стороной.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 720p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '0fcc5cd2b081b5402aeb8001e44082bc82a96679','демо,Аниме,приключения,фантастика,сезон',6,6,'2026-04-23 14:32:41','18.gif','0',89,'2026-04-23 18:03:06','18_0.gif','','','','',0,'movie','russian,english','japanese,russian','fantasy,drama,comedy','licensed,hevc','japan','single',1),(19,'0','[DEMO] Письма С Поднебесья','demo-release-15.torrent',8032386011,420,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 13 из 13\n[b]Продолжительность:[/b] 47 мин\n[b]Режиссер:[/b] Фудзивара Кэй\n[b]Описание:[/b] После странного метеоритного дождя привычные маршруты города меняются, а каждая ночь приносит новые правила и новые обещания.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] WEB-DL\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'e264a4cc9d9514a4100d3e9609326678770732dc','демо,Аниме,приключения,фантастика,сезон',6,7,'2026-04-23 13:31:41','19.gif','0',67,'2026-04-23 17:29:04','19_0.gif','','','','',0,'ova','russian,english','japanese,russian','drama,comedy,science_fiction','licensed','japan','single',3),(20,'0','[DEMO] Второе Июльское Небо','demo-release-16.torrent',4766837278,468,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 8 из 8\n[b]Продолжительность:[/b] 1 ч 28 мин\n[b]Режиссер:[/b] Хосино Ая\n[b]Описание:[/b] Главная героиня работает на воздушной почте и однажды получает письмо, адресованное человеку, исчезнувшему много лет назад.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] BDRip 1080p\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '641165203d3306f68a05372d33bd04cf547e21dd','демо,Аниме,приключения,фантастика,сезон',6,8,'2026-04-23 12:30:41','20.gif','0',98,'2026-04-23 16:02:37','20_0.gif','','','','',0,'special','russian,english','japanese,russian','adventure,fantasy,drama','licensed,hevc','japan','single',1),(21,'0','[DEMO] Маршрут До Созвездия','demo-release-17.torrent',7848312060,97,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 10 из 10\n[b]Продолжительность:[/b] 24 мин\n[b]Режиссер:[/b] Морита Сюн\n[b]Описание:[/b] Команда курьеров на магнитной железной дороге сталкивается с таинственным пассажиром, который знает о них больше, чем положено.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 1080p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '2ca8848b5be54830442cd9ffc1a470ecf8630fc3','демо,Аниме,приключения,фантастика,сезон',6,9,'2026-04-23 11:29:41','21.gif','0',93,'2026-04-23 14:35:33','21_0.gif','','','','',0,'tv','russian,english','japanese,russian','fantasy,drama,comedy','licensed','japan','single',2),(22,'0','[DEMO] Ласточка И Часовщик','demo-release-18.torrent',9014979758,189,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2026\n[b]Количество эпизодов:[/b] 1 из 1\n[b]Продолжительность:[/b] 25 мин\n[b]Режиссер:[/b] Окада Рина\n[b]Описание:[/b] Молодой механик собирает устройство для записи снов, и очень быстро становится понятно, что некоторые чужие сны совсем не хотят оставаться снами.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] 720p\n[b]Видео:[/b] H.264, 1280x720, ~3500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary '99e749a48f58f01f6b6c69c9b1ed7b49855afaf1','демо,Аниме,приключения,фантастика,сезон',6,10,'2026-04-23 10:28:41','22.gif','0',104,'2026-04-23 11:00:27','22_0.gif','','','','',0,'movie','russian,english','japanese,russian','drama,comedy,science_fiction','licensed,hevc','japan','single',1),(23,'0','[DEMO] Эхо В Оранжерее','demo-release-19.torrent',4486253368,496,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2024\n[b]Количество эпизодов:[/b] 13 из 13\n[b]Продолжительность:[/b] 47 мин\n[b]Режиссер:[/b] Фудзивара Кэй\n[b]Описание:[/b] История о команде школьников, которые случайно открывают доступ к закрытому архиву воспоминаний и пытаются понять, почему город начал забывать собственное прошлое.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] WEB-DL\n[b]Видео:[/b] H.264, 1920x1080, ~6500 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'abd7c99239c79910d35aa6033ae831102fa2f791','демо,Аниме,приключения,фантастика,сезон',6,5,'2026-04-23 09:27:41','23.gif','0',41,'2026-04-23 11:13:39','23_0.gif','','','','',0,'ova','russian,english','japanese,russian','adventure,fantasy,drama','licensed','japan','single',1),(24,'0','[DEMO] Северный Экспресс До Весны','demo-release-20.torrent',3967058750,267,'[b]Тип:[/b]\n[b]Жанр:[/b]\n[b]Год выхода:[/b] 2025\n[b]Количество эпизодов:[/b] 8 из 8\n[b]Продолжительность:[/b] 1 ч 28 мин\n[b]Режиссер:[/b] Хосино Ая\n[b]Описание:[/b] Небольшая студия озвучки получает шанс спасти любимый сериал, но для этого героям приходится объединиться с людьми, которых они раньше обходили стороной.\n\n[u]Дополнительно[/u]\n[b]Формат:[/b] MKV\n[b]Качество:[/b] BDRip 1080p\n[b]Видео:[/b] H.265, 1920x1080, ~4200 Кбит/с\n[b]Аудио:[/b]\n[b]Субтитры:[/b]\n[b]Страна:[/b]',_binary 'a456f2ce50d07c582e37e0a09e2a492bc7c816fe','демо,Аниме,приключения,фантастика,сезон',6,6,'2026-04-23 08:26:41','24.gif','0',102,'2026-04-23 11:40:42','24_0.gif','','','','',0,'special','russian,english','japanese,russian','fantasy,drama,comedy','licensed,hevc','japan','single',2);
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
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers` WRITE;
/*!40000 ALTER TABLE `trackers` DISABLE KEYS */;
INSERT INTO `trackers` VALUES (5,4,'localhost',0,0,1777005491,''),(6,4,'http://tr2.torrent4me.com/ann?uk=cAETnuUKbT',0,0,1776960630,'failed:no_benc_result_or_timeout_announce'),(7,4,'http://retracker.local/announce',0,0,1777005627,'failed:no_benc_result_or_timeout_announce'),(8,5,'localhost',2,0,1777006002,''),(9,6,'localhost',3,1,1777006002,''),(10,7,'localhost',4,2,1777006002,''),(11,8,'localhost',5,0,1777006002,''),(12,9,'localhost',2,1,1777006002,''),(13,10,'localhost',3,2,1777006002,''),(14,11,'localhost',4,0,1777006002,''),(15,12,'localhost',5,1,1777006002,''),(16,13,'localhost',2,2,1777006002,''),(17,14,'localhost',3,0,1777006002,''),(18,15,'localhost',4,1,1777006002,''),(19,16,'localhost',5,2,1777006002,''),(20,17,'localhost',2,0,1777006002,''),(21,18,'localhost',3,1,1777006002,''),(22,19,'localhost',4,2,1777006002,''),(23,20,'localhost',5,0,1777006002,''),(24,21,'localhost',2,1,1777006002,''),(25,22,'localhost',3,2,1777006002,''),(26,23,'localhost',4,0,1777006002,''),(27,24,'localhost',5,1,1777006003,'');
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
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'jenaDI','','bitsteep@gmail.com','6f08171653dfa90991f0c9590ebf456d','2Asl11Svgp9eHMY41RUVlouySbLZ6x4d',2130706433,6,'2026-04-22 17:12:43','2011-08-13 19:33:21','137583ad9f348f10a6fb93b9168e6955',0,0,0,0,27.78,1,NULL,'',0,1,0,'','',0,0,3,0,0,1),(2,'nickmsk9','2_1776875030_a502cc0d.jpg','nickmsk9@icloud.com','$2y$12$6z/DhdY1pyD2zK237zWRt.XXE7xsNcgujF2yV02ENYZJtfqrG4GXG','',-1407975423,6,'2026-04-23 16:15:25','2026-04-15 15:18:44','b3700e4caf42228f8e101e3acfb10bb9',154178051871,123,1,233,277.8,1,'1995-03-09','тут я пишу описание о себе',0,0,0,'','',0,0,0,0,0,1),(4,'debuguser','','debuguser@example.com','d94b09df234cb957607ebed14e2e1175','7bb9e755437cb34ee1ce9048d1376049',-1062715135,1,'2026-04-21 15:05:24','2026-04-17 18:32:55','02ced8546fa261d4b5f14dec2cf51a59',0,0,1,0,27.78,1,NULL,'',0,1,0,'','',0,0,1,0,0,1),(5,'AkiSora','','akisora@demo.local','$2y$12$13Q3gTxM9dlokDQYpxkV8u6cv8XJNv3lz5z1ErMD/G7MZGzNIjDmG','',169082891,1,'2026-04-24 04:44:47','2026-03-10 04:46:41','a217c5ee254e83bd81ee2990b2f39e46',248034361344,17179869184,0,202,21.5,1,'1998-03-12','Люблю сезонные онгоинги, коллекционирую любимые опенинги и чаще всего отвечаю в комментариях вечером.',1,1,0,'','',0,0,0,0,21.5,1),(6,'MioRain','','miorain@demo.local','$2y$12$pk1lTipP7QKpaN706nQvhuCdNgPSw3o89TsHBAgaM2omc/VvEhoES','',169082892,1,'2026-04-24 04:30:29','2026-02-27 04:46:41','45cb04516b59322561ce90bf0ea392dc',219043332096,48318382080,0,99,9.8,1,'1999-07-19','Собираю аккуратные релизы с субтитрами и проверяю, чтобы в описании было всё по делу.',1,1,0,'','',0,0,0,0,9.8,1),(7,'RenTori','','rentori@demo.local','$2y$12$W5rxMC5RlRMKc.Ad9kFC3OV.Voi8j0zUx9Vqq9PI3cJnOhsG9Mum6','',169082893,1,'2026-04-24 04:22:05','2026-02-16 04:46:41','28a3317599057a5d08cfecfbf124bcf5',136365211648,39728447488,0,195,24.6,1,'1997-11-05','Слежу за новыми фильмами и спецвыпусками, люблю быстрые отзывы без лишнего шума.',1,1,0,'','',0,0,0,0,24.6,1),(8,'YukiNova','','yukinova@demo.local','$2y$12$vg3Ie6CU2Q1W3lJKONR2fegsiGgGm4S7S3a.4XJCZHgnBPWSPkXpW','',169082894,1,'2026-04-24 04:18:58','2026-02-05 04:46:41','857fea14026d59549178ae99da5d9a95',201863462912,44023414784,0,336,14.1,1,'2000-01-27','Смотрю приключения и фантастику, обычно проверяю стену и закладки утром.',1,1,0,'','',0,0,0,0,14.1,1),(9,'KaiZen','','kaizen@demo.local','$2y$12$70.Li/6QYIkJWTuEe6zqie5paac.Ti8rGSqBWFWlBYf1tluGEN83m','',169082895,1,'2026-04-24 04:07:18','2026-01-25 04:46:41','9e722e858e3cd3e8d14d0c0b1fa63df6',97710505984,25769803776,0,256,24.3,1,'1996-05-08','Тестовый пользователь для проверки рейтингов, комментариев и списка активных раздач.',1,1,0,'','',0,0,0,0,24.3,1),(10,'NamiFox','','namifox@demo.local','$2y$12$lEBD6y53BdJuZuFFbD0.6OahYd7GOjimXTjUfnrBHpMJMC7zQG.pa','',169082896,1,'2026-04-24 03:09:44','2026-01-14 04:46:41','b9feffb7d138e319f492ca95cec48a6a',67645734912,37580963840,0,416,27.5,1,'2001-09-14','Чаще всего отмечаю релизы в закладки и отвечаю коротко, но по существу.',1,1,0,'','',0,0,0,0,27.5,1);
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

