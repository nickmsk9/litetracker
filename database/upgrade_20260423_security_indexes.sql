ALTER TABLE users
  MODIFY password varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  MODIFY password_code varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_banned_added'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_banned_added (banned, added)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_category_banned_added'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_category_banned_added (id_category, banned, added)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_user_added'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_user_added (id_user, added)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_news_added'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_news_added (news, added)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_type'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_type (type)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_content_type'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_content_type (content_type)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'torrents' AND index_name = 'idx_torrents_name'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE torrents ADD KEY idx_torrents_name (name(191))', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

OPTIMIZE TABLE users, torrents, trackers, peers, sessions, comments_news, comments_torrents, comments_users;
