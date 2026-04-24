-- LiteTracker engine optimization indexes for PHP 8+ modernization pass.

SET @old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = '';

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND INDEX_NAME = 'idx_books_user_torrent');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE books ADD KEY idx_books_user_torrent (id_user, id_torrent)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments_torrents' AND INDEX_NAME = 'idx_comments_torrents_object_date');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE comments_torrents ADD KEY idx_comments_torrents_object_date (id_torrents, date)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments_torrents' AND INDEX_NAME = 'idx_comments_torrents_parent');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE comments_torrents ADD KEY idx_comments_torrents_parent (parent_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND INDEX_NAME = 'idx_news_date');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE news ADD KEY idx_news_date (date)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions' AND INDEX_NAME = 'idx_sessions_user_access');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE sessions ADD KEY idx_sessions_user_access (user_id, last_access)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions' AND INDEX_NAME = 'idx_sessions_last_access');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE sessions ADD KEY idx_sessions_last_access (last_access)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'search_query' AND INDEX_NAME = 'idx_search_query_user_last');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE search_query ADD KEY idx_search_query_user_last (id_user, last_date)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET SESSION sql_mode = @old_sql_mode;
