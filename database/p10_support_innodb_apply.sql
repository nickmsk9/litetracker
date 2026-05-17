-- LiteTracker Wave P10: remaining non-hot support MyISAM tables.
--
-- Scope candidates:
--   news, torrent_views, search_query, priv, categories, shop, tags,
--   bans, chat, faq, polls_voting.
--
-- Hot tables explicitly out of scope:
--   peers, trackers, snatched, torrents, users.
--
-- Production note:
--   Do not run in production without full DB backup and table-specific dumps:
--   mysqldump --single-transaction --routines --triggers DB_NAME \
--     news torrent_views search_query categories tags bans chat faq polls_voting \
--     > p10_support.before_innodb.sql
--
-- Deferred in P10:
--   priv: real zero DATE values exist locally.
--   shop: real zero date values exist locally.
--
-- Safety:
--   - Preflight SELECTs run before the ALTERs.
--   - Approved-table conversion aborts on zero/null DATETIME values.
--   - bans.date legacy zero default is dropped before conversion if present.
--   - Engine conversion is guarded and no-op when already InnoDB.
--   - Additive indexes are guarded.
--   - No destructive DROP.
--   - No charset/collation conversion.
--
-- Rollback examples:
--   ALTER TABLE `news` ENGINE=MyISAM;
--   ALTER TABLE `torrent_views` ENGINE=MyISAM;
--   ALTER TABLE `search_query` ENGINE=MyISAM;
--   ALTER TABLE `categories` ENGINE=MyISAM;
--   ALTER TABLE `tags` ENGINE=MyISAM;
--   ALTER TABLE `bans` ENGINE=MyISAM;
--   ALTER TABLE `chat` ENGINE=MyISAM;
--   ALTER TABLE `faq` ENGINE=MyISAM;
--   ALTER TABLE `polls_voting` ENGINE=MyISAM;
--   DROP INDEX `idx_tags_category_name` ON `tags`;
--   DROP INDEX `idx_faq_added` ON `faq`;

SELECT 'P10 candidate table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	t.DATA_LENGTH,
	t.INDEX_LENGTH,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN (
	'news', 'torrent_views', 'search_query', 'priv', 'categories', 'shop',
	'tags', 'bans', 'chat', 'faq', 'polls_voting'
  )
ORDER BY t.TABLE_NAME;

SELECT 'P10 candidate indexes before' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN (
	'news', 'torrent_views', 'search_query', 'priv', 'categories', 'shop',
	'tags', 'bans', 'chat', 'faq', 'polls_voting'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P10 zero/null date preflight' AS section;
SELECT 'bans.date' AS column_name, COUNT(*) AS zero_or_null_count, SUM(`date` IS NULL) AS null_count FROM bans WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'categories.date', COUNT(*), SUM(`date` IS NULL) FROM categories WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'chat.date', COUNT(*), SUM(`date` IS NULL) FROM chat WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'faq.added', COUNT(*), SUM(added IS NULL) FROM faq WHERE CAST(added AS CHAR) = '0000-00-00 00:00:00' OR added IS NULL
UNION ALL SELECT 'faq.last_edit', COUNT(*), SUM(last_edit IS NULL) FROM faq WHERE CAST(last_edit AS CHAR) = '0000-00-00 00:00:00' OR last_edit IS NULL
UNION ALL SELECT 'news.date', COUNT(*), SUM(`date` IS NULL) FROM news WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'polls_voting.date', COUNT(*), SUM(`date` IS NULL) FROM polls_voting WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'priv.DATE', COUNT(*), SUM(`DATE` IS NULL) FROM priv WHERE CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00' OR `DATE` IS NULL
UNION ALL SELECT 'search_query.last_date', COUNT(*), SUM(last_date IS NULL) FROM search_query WHERE CAST(last_date AS CHAR) = '0000-00-00 00:00:00' OR last_date IS NULL
UNION ALL SELECT 'shop.date', COUNT(*), SUM(`date` IS NULL) FROM shop WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'torrent_views.date', COUNT(*), SUM(`date` IS NULL) FROM torrent_views WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL;

SELECT 'P10 EXPLAIN probes before' AS section;
EXPLAIN SELECT * FROM news ORDER BY date DESC LIMIT 10;
EXPLAIN SELECT COUNT(*) AS cnt FROM torrent_views WHERE torrent_id = 1;
EXPLAIN SELECT * FROM search_query ORDER BY last_date DESC LIMIT 10;
EXPLAIN SELECT * FROM categories ORDER BY name ASC;
EXPLAIN SELECT name FROM tags WHERE category = 1 ORDER BY name ASC;
EXPLAIN SELECT * FROM bans WHERE '123' >= first AND '123' <= last;
EXPLAIN SELECT * FROM faq ORDER BY added DESC;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p10_convert_to_innodb_if_needed//
CREATE PROCEDURE lt_p10_convert_to_innodb_if_needed(IN p_table_name VARCHAR(128))
BEGIN
	DECLARE v_engine VARCHAR(64) DEFAULT '';
	DECLARE v_sql TEXT;

	SELECT COALESCE(t.ENGINE, '')
	INTO v_engine
	FROM information_schema.tables AS t
	WHERE t.TABLE_SCHEMA = DATABASE()
	  AND t.TABLE_NAME = p_table_name
	LIMIT 1;

	IF v_engine = '' THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P10 aborted: candidate table not found';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		SET v_sql = CONCAT('ALTER TABLE `', REPLACE(p_table_name, '`', '``'), '` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC');
		SET @lt_p10_sql = v_sql;
		PREPARE lt_p10_stmt FROM @lt_p10_sql;
		EXECUTE lt_p10_stmt;
		DEALLOCATE PREPARE lt_p10_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p10_add_index_if_missing//
CREATE PROCEDURE lt_p10_add_index_if_missing(
	IN p_table_name VARCHAR(128),
	IN p_index_name VARCHAR(128),
	IN p_index_sql TEXT
)
BEGIN
	IF NOT EXISTS (
		SELECT 1
		FROM information_schema.statistics
		WHERE table_schema = DATABASE()
		  AND table_name = p_table_name
		  AND index_name = p_index_name
		LIMIT 1
	) THEN
		SET @lt_p10_sql = p_index_sql;
		PREPARE lt_p10_stmt FROM @lt_p10_sql;
		EXECUTE lt_p10_stmt;
		DEALLOCATE PREPARE lt_p10_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p10_abort_on_approved_zero_dates//
CREATE PROCEDURE lt_p10_abort_on_approved_zero_dates()
BEGIN
	DECLARE v_bad_dates BIGINT DEFAULT 0;

	SELECT
		(SELECT COUNT(*) FROM bans WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
		+ (SELECT COUNT(*) FROM categories WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
		+ (SELECT COUNT(*) FROM chat WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
		+ (SELECT COUNT(*) FROM faq WHERE CAST(added AS CHAR) = '0000-00-00 00:00:00' OR added IS NULL OR CAST(last_edit AS CHAR) = '0000-00-00 00:00:00' OR last_edit IS NULL)
		+ (SELECT COUNT(*) FROM news WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
		+ (SELECT COUNT(*) FROM polls_voting WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
		+ (SELECT COUNT(*) FROM search_query WHERE CAST(last_date AS CHAR) = '0000-00-00 00:00:00' OR last_date IS NULL)
		+ (SELECT COUNT(*) FROM torrent_views WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL)
	INTO v_bad_dates;

	IF COALESCE(v_bad_dates, 0) > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P10 aborted: approved tables contain zero/null date values';
	END IF;
END//

CALL lt_p10_abort_on_approved_zero_dates()//

ALTER TABLE `bans` ALTER COLUMN `date` DROP DEFAULT//

CALL lt_p10_convert_to_innodb_if_needed('news')//
CALL lt_p10_convert_to_innodb_if_needed('torrent_views')//
CALL lt_p10_convert_to_innodb_if_needed('search_query')//
CALL lt_p10_convert_to_innodb_if_needed('categories')//
CALL lt_p10_convert_to_innodb_if_needed('tags')//
CALL lt_p10_convert_to_innodb_if_needed('bans')//
CALL lt_p10_convert_to_innodb_if_needed('chat')//
CALL lt_p10_convert_to_innodb_if_needed('faq')//
CALL lt_p10_convert_to_innodb_if_needed('polls_voting')//

CALL lt_p10_add_index_if_missing(
	'tags',
	'idx_tags_category_name',
	'ALTER TABLE `tags` ADD KEY `idx_tags_category_name` (`category`, `name`)'
)//

CALL lt_p10_add_index_if_missing(
	'faq',
	'idx_faq_added',
	'ALTER TABLE `faq` ADD KEY `idx_faq_added` (`added`)'
)//

DROP PROCEDURE IF EXISTS lt_p10_abort_on_approved_zero_dates//
DROP PROCEDURE IF EXISTS lt_p10_add_index_if_missing//
DROP PROCEDURE IF EXISTS lt_p10_convert_to_innodb_if_needed//

DELIMITER ;

SELECT 'P10 postflight table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN (
	'news', 'torrent_views', 'search_query', 'priv', 'categories', 'shop',
	'tags', 'bans', 'chat', 'faq', 'polls_voting'
  )
ORDER BY t.TABLE_NAME;

SELECT 'P10 postflight indexes' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN (
	'news', 'torrent_views', 'search_query', 'priv', 'categories', 'shop',
	'tags', 'bans', 'chat', 'faq', 'polls_voting'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;
