
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
INSERT INTO `cron` VALUES ('autoclean_interval',1000),('autoclean_last',1777010991),('multi_remote',1),('remotecheck_interval',600),('remote_torrents',30),('remotepeers_cleantime',10800),('remote_lastchecked',0),('in_remotecheck',0),('num_checked',294),('last_remotecheck',1777011051),('multi_timeout',100);
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
INSERT INTO `peers` VALUES (1,5,'-LTDEMO-01c5e63638f4','10.20.0.11',51001,9780928760,8168326342,0,0,0,1,'2026-04-24 04:10:05','2026-04-24 05:42:15','2026-04-24 05:31:27',1,5,'LiteTracker Demo Seeder',1777009335,'a217c5ee254e83bd81ee2990b2f39e46'),(2,5,'-LTDEMO-3f1d13ace0c4','10.20.0.12',51002,10756923193,8168326342,0,0,0,1,'2026-04-24 04:15:19','2026-04-24 05:45:55','2026-04-24 05:31:38',1,6,'LiteTracker Demo Seeder',1777009555,'45cb04516b59322561ce90bf0ea392dc');
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
) ENGINE=MyISAM AUTO_INCREMENT=466 DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (465,'7d309206f505ba04a474932e24db3ab8',-1,'2026-04-24 09:16:36',-1407975423,'curl/8.7.1','/index.php'),(464,'bd5acabe04dba4e9a6cec7200fd2f936',-1,'2026-04-24 09:16:31',0,'','Standard input code'),(463,'327fb181b89efb486356504609053ca2',-1,'2026-04-24 06:15:27',-1407975423,'curl/8.7.1','/browse.php'),(462,'91a12d665ee46e175d6921b6e23f5908',-1,'2026-04-24 06:15:27',-1407975423,'curl/8.7.1','/index.php'),(461,'71a04e0af3499cafdf79524fa85012f0',-1,'2026-04-24 06:13:20',-1407975423,'curl/8.7.1','/index.php'),(460,'38b3c8820200bf218df5fdd0a39a2be5',-1,'2026-04-24 06:13:20',-1407975423,'curl/8.7.1','/browse.php'),(459,'e2105be2f4e54d628b8ed45201f723a9',-1,'2026-04-24 06:13:04',-1407975423,'curl/8.7.1','/signup.php'),(458,'27e07fa12975494f71442993c123db69',-1,'2026-04-24 06:12:55',-1407975423,'curl/8.7.1','/signup.php'),(457,'b4996d7dbda398b40b5db4dca7db0cdb',-1,'2026-04-24 06:12:50',-1407975423,'curl/8.7.1','/signup.php'),(456,'c1005ef1b86571d8483d1ccfebb20f1c',-1,'2026-04-24 06:14:48',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(455,'dc109721e7bea2462b6ccde03d1d37de',-1,'2026-04-24 04:46:59',0,'','Standard input code'),(454,'7457cd27b1ab6a92be62781bc4e7a6a2',-1,'2026-04-24 04:46:59',0,'','Standard input code'),(453,'5a1ecee1d4b4e0e9de577e31209e09e9',-1,'2026-04-24 04:46:49',0,'','Standard input code'),(451,'04deaf1b5e754f9dd5a9f539d895cfe3',7,'2026-04-24 04:39:55',169082893,'LiteTracker Demo Seeder','/index.php'),(452,'b7e5be1ed5c358b8f8090ddeb659932a',8,'2026-04-24 04:43:41',169082894,'LiteTracker Demo Seeder','/index.php'),(450,'54f16a615896cc24b92c1609b38b791f',6,'2026-04-24 04:44:28',169082892,'LiteTracker Demo Seeder','/index.php'),(449,'385080cb3991019629809a335a174d0b',5,'2026-04-24 04:45:45',169082891,'LiteTracker Demo Seeder','/index.php'),(448,'aee8c7a623f0b040bce374bba1dca03c',-1,'2026-04-24 04:46:41',2130706433,'','/var/www/html/scripts/seed_demo_activity.php'),(447,'622bf2fb04168b6c04f4050660765b67',-1,'2026-04-24 04:43:53',0,'','Standard input code'),(446,'01acdbac02e1c0bd46685b82c180c1e9',-1,'2026-04-24 04:43:21',0,'','Standard input code'),(445,'3e6652ce56bb8bfa54715568c5c85f50',-1,'2026-04-24 04:43:21',0,'','Standard input code'),(444,'d9d50d56dc0194d053391dc107155d38',-1,'2026-04-24 04:47:32',-1062715135,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/browse.php'),(443,'0f41647299e87f64787273bab23ca8d5',2,'2026-04-23 16:18:46',-1407975423,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','/edit.php');
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
INSERT INTO `trackers` VALUES (5,4,'localhost',0,0,1777010991,''),(6,4,'http://tr2.torrent4me.com/ann?uk=cAETnuUKbT',0,0,1776960630,'failed:no_benc_result_or_timeout_announce'),(7,4,'http://retracker.local/announce',0,0,1777005627,'failed:no_benc_result_or_timeout_announce'),(8,5,'localhost',2,0,1777010991,''),(9,6,'localhost',0,0,1777010991,''),(10,7,'localhost',0,0,1777010991,''),(11,8,'localhost',0,0,1777010991,''),(12,9,'localhost',0,0,1777010991,''),(13,10,'localhost',0,0,1777010991,''),(14,11,'localhost',0,0,1777010991,''),(15,12,'localhost',0,0,1777010991,''),(16,13,'localhost',0,0,1777010991,''),(17,14,'localhost',0,0,1777010991,''),(18,15,'localhost',0,0,1777010991,''),(19,16,'localhost',0,0,1777010991,''),(20,17,'localhost',0,0,1777010991,''),(21,18,'localhost',0,0,1777010991,''),(22,19,'localhost',0,0,1777010991,''),(23,20,'localhost',0,0,1777010991,''),(24,21,'localhost',0,0,1777010991,''),(25,22,'localhost',0,0,1777010991,''),(26,23,'localhost',0,0,1777010991,''),(27,24,'localhost',0,0,1777010991,'');
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
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'jenaDI','','bitsteep@gmail.com','6f08171653dfa90991f0c9590ebf456d','2Asl11Svgp9eHMY41RUVlouySbLZ6x4d',2130706433,6,'2026-04-22 17:12:43','2011-08-13 19:33:21','137583ad9f348f10a6fb93b9168e6955',0,0,0,0,27.78,1,NULL,'',0,1,0,'','',0,0,3,0,0,1),(2,'nickmsk9','2_1776875030_a502cc0d.jpg','nickmsk9@icloud.com','$2y$12$6z/DhdY1pyD2zK237zWRt.XXE7xsNcgujF2yV02ENYZJtfqrG4GXG','',-1407975423,6,'2026-04-24 06:08:56','2026-04-15 15:18:44','b3700e4caf42228f8e101e3acfb10bb9',154178051871,123,1,233,305.58,1,'1995-03-09','тут я пишу описание о себе',0,0,0,'','',0,0,0,0,0,1),(4,'debuguser','','debuguser@example.com','d94b09df234cb957607ebed14e2e1175','7bb9e755437cb34ee1ce9048d1376049',-1062715135,1,'2026-04-21 15:05:24','2026-04-17 18:32:55','02ced8546fa261d4b5f14dec2cf51a59',0,0,1,0,27.78,1,NULL,'',0,1,0,'','',0,0,1,0,0,1),(5,'AkiSora','','akisora@demo.local','$2y$12$13Q3gTxM9dlokDQYpxkV8u6cv8XJNv3lz5z1ErMD/G7MZGzNIjDmG','',169082891,1,'2026-04-24 04:44:47','2026-03-10 04:46:41','a217c5ee254e83bd81ee2990b2f39e46',248034361344,17179869184,0,202,21.5,1,'1998-03-12','Люблю сезонные онгоинги, коллекционирую любимые опенинги и чаще всего отвечаю в комментариях вечером.',1,1,0,'','',0,0,0,0,21.5,1),(6,'MioRain','','miorain@demo.local','$2y$12$pk1lTipP7QKpaN706nQvhuCdNgPSw3o89TsHBAgaM2omc/VvEhoES','',169082892,1,'2026-04-24 04:30:29','2026-02-27 04:46:41','45cb04516b59322561ce90bf0ea392dc',219043332096,48318382080,0,99,9.8,1,'1999-07-19','Собираю аккуратные релизы с субтитрами и проверяю, чтобы в описании было всё по делу.',1,1,0,'','',0,0,0,0,9.8,1),(7,'RenTori','','rentori@demo.local','$2y$12$W5rxMC5RlRMKc.Ad9kFC3OV.Voi8j0zUx9Vqq9PI3cJnOhsG9Mum6','',169082893,1,'2026-04-24 04:22:05','2026-02-16 04:46:41','28a3317599057a5d08cfecfbf124bcf5',136365211648,39728447488,0,195,24.6,1,'1997-11-05','Слежу за новыми фильмами и спецвыпусками, люблю быстрые отзывы без лишнего шума.',1,1,0,'','',0,0,0,0,24.6,1),(8,'YukiNova','','yukinova@demo.local','$2y$12$vg3Ie6CU2Q1W3lJKONR2fegsiGgGm4S7S3a.4XJCZHgnBPWSPkXpW','',169082894,1,'2026-04-24 04:18:58','2026-02-05 04:46:41','857fea14026d59549178ae99da5d9a95',201863462912,44023414784,0,336,14.1,1,'2000-01-27','Смотрю приключения и фантастику, обычно проверяю стену и закладки утром.',1,1,0,'','',0,0,0,0,14.1,1),(9,'KaiZen','','kaizen@demo.local','$2y$12$70.Li/6QYIkJWTuEe6zqie5paac.Ti8rGSqBWFWlBYf1tluGEN83m','',169082895,1,'2026-04-24 04:07:18','2026-01-25 04:46:41','9e722e858e3cd3e8d14d0c0b1fa63df6',97710505984,25769803776,0,256,24.3,1,'1996-05-08','Тестовый пользователь для проверки рейтингов, комментариев и списка активных раздач.',1,1,0,'','',0,0,0,0,24.3,1),(10,'NamiFox','','namifox@demo.local','$2y$12$lEBD6y53BdJuZuFFbD0.6OahYd7GOjimXTjUfnrBHpMJMC7zQG.pa','',169082896,1,'2026-04-24 03:09:44','2026-01-14 04:46:41','b9feffb7d138e319f492ca95cec48a6a',67645734912,37580963840,0,416,27.5,1,'2001-09-14','Чаще всего отмечаю релизы в закладки и отвечаю коротко, но по существу.',1,1,0,'','',0,0,0,0,27.5,1);
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

