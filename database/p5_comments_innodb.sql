-- LiteTracker Wave P5: comments InnoDB + index readiness.
--
-- Scope:
--   comments_torrents, comments_users, comments_news, comments_faq,
--   comment_reactions, comment_pins, comment_edit_history,
--   comments_reports, comments_users_reports.
--
-- Explicitly out of scope:
--   peers, trackers, snatched, torrents, users, sessions, mail.
--
-- Safety:
--   - Preflight SELECTs run before changes.
--   - The script aborts if any checked DATETIME column contains zero dates.
--   - Nullable DATETIME columns may be NULL; NULL is not treated as corruption.
--   - Engine changes are guarded and no-op when a table is already InnoDB.
--   - Index changes are additive and guarded by information_schema.
--   - No indexes or columns are dropped.
--   - No charset/collation changes are made; cp1251 text remains cp1251.
--
-- Rollback examples:
--   ALTER TABLE `comments_torrents` ENGINE=MyISAM;
--   ALTER TABLE `comments_torrents` DROP INDEX `idx_comments_torrents_date_id`;
--   ALTER TABLE `comments_torrents` DROP INDEX `idx_comments_torrents_object_date_id`;
--   ALTER TABLE `comments_torrents` DROP INDEX `idx_comments_torrents_object_user_date_id`;
--   ALTER TABLE `comments_users` DROP INDEX `idx_comments_users_object_user_date_id`;
--   ALTER TABLE `comments_news` DROP INDEX `idx_comments_news_object_user_date_id`;
--   ALTER TABLE `comments_faq` DROP INDEX `idx_comments_faq_object_user_date_id`;

SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN (
	'comments_torrents', 'comments_users', 'comments_news', 'comments_faq',
	'comment_reactions', 'comment_pins', 'comment_edit_history',
	'comments_reports', 'comments_users_reports'
  )
ORDER BY t.TABLE_NAME;

SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN (
	'comments_torrents', 'comments_users', 'comments_news', 'comments_faq',
	'comment_reactions', 'comment_pins', 'comment_edit_history',
	'comments_reports', 'comments_users_reports'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

DROP TEMPORARY TABLE IF EXISTS lt_p5_zero_date_check;
CREATE TEMPORARY TABLE lt_p5_zero_date_check (
	column_name VARCHAR(128) NOT NULL,
	zero_count BIGINT NOT NULL
);

INSERT INTO lt_p5_zero_date_check
SELECT 'comment_edit_history.edited_at', COUNT(*) FROM comment_edit_history WHERE CAST(edited_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comment_pins.pinned_at', COUNT(*) FROM comment_pins WHERE CAST(pinned_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comment_reactions.created_at', COUNT(*) FROM comment_reactions WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comment_reactions.updated_at', COUNT(*) FROM comment_reactions WHERE CAST(updated_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_faq.date', COUNT(*) FROM comments_faq WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_faq.date_edit', COUNT(*) FROM comments_faq WHERE CAST(date_edit AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_news.date', COUNT(*) FROM comments_news WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_news.date_edit', COUNT(*) FROM comments_news WHERE CAST(date_edit AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_news.deleted_at', COUNT(*) FROM comments_news WHERE CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_reports.created_at', COUNT(*) FROM comments_reports WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_reports.resolved_at', COUNT(*) FROM comments_reports WHERE CAST(resolved_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_torrents.date', COUNT(*) FROM comments_torrents WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_torrents.date_edit', COUNT(*) FROM comments_torrents WHERE CAST(date_edit AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_torrents.deleted_at', COUNT(*) FROM comments_torrents WHERE CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users.date', COUNT(*) FROM comments_users WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users.date_edit', COUNT(*) FROM comments_users WHERE CAST(date_edit AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users.deleted_at', COUNT(*) FROM comments_users WHERE CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users_reports.created_at', COUNT(*) FROM comments_users_reports WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users_reports.resolved_at', COUNT(*) FROM comments_users_reports WHERE CAST(resolved_at AS CHAR) = '0000-00-00 00:00:00';

SELECT * FROM lt_p5_zero_date_check ORDER BY column_name;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p5_abort_on_zero_dates//
CREATE PROCEDURE lt_p5_abort_on_zero_dates()
BEGIN
	DECLARE v_zero_dates BIGINT DEFAULT 0;

	SELECT COALESCE(SUM(zero_count), 0)
	INTO v_zero_dates
	FROM lt_p5_zero_date_check;

	IF v_zero_dates > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P5 aborted: zero DATETIME values found in comment tables';
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p5_convert_to_innodb_if_needed//
CREATE PROCEDURE lt_p5_convert_to_innodb_if_needed(IN p_table_name VARCHAR(128))
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
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P5 aborted: candidate table not found';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		SET v_sql = CONCAT('ALTER TABLE `', REPLACE(p_table_name, '`', '``'), '` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC');
		SET @lt_p5_sql = v_sql;
		PREPARE lt_p5_stmt FROM @lt_p5_sql;
		EXECUTE lt_p5_stmt;
		DEALLOCATE PREPARE lt_p5_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p5_add_index_if_missing//
CREATE PROCEDURE lt_p5_add_index_if_missing(
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
		SET @lt_p5_sql = p_index_sql;
		PREPARE lt_p5_stmt FROM @lt_p5_sql;
		EXECUTE lt_p5_stmt;
		DEALLOCATE PREPARE lt_p5_stmt;
	END IF;
END//

CALL lt_p5_abort_on_zero_dates()//

CALL lt_p5_convert_to_innodb_if_needed('comments_torrents')//
CALL lt_p5_convert_to_innodb_if_needed('comments_users')//
CALL lt_p5_convert_to_innodb_if_needed('comments_news')//
CALL lt_p5_convert_to_innodb_if_needed('comments_faq')//
CALL lt_p5_convert_to_innodb_if_needed('comment_reactions')//
CALL lt_p5_convert_to_innodb_if_needed('comment_pins')//
CALL lt_p5_convert_to_innodb_if_needed('comment_edit_history')//
CALL lt_p5_convert_to_innodb_if_needed('comments_reports')//
CALL lt_p5_convert_to_innodb_if_needed('comments_users_reports')//

CALL lt_p5_add_index_if_missing(
	'comments_torrents',
	'idx_comments_torrents_date_id',
	'ALTER TABLE `comments_torrents` ADD KEY `idx_comments_torrents_date_id` (`date`, `id`)'
)//

CALL lt_p5_add_index_if_missing(
	'comments_torrents',
	'idx_comments_torrents_object_date_id',
	'ALTER TABLE `comments_torrents` ADD KEY `idx_comments_torrents_object_date_id` (`id_torrents`, `date`, `id`)'
)//

CALL lt_p5_add_index_if_missing(
	'comments_torrents',
	'idx_comments_torrents_object_user_date_id',
	'ALTER TABLE `comments_torrents` ADD KEY `idx_comments_torrents_object_user_date_id` (`id_torrents`, `id_user`, `date`, `id`)'
)//

CALL lt_p5_add_index_if_missing(
	'comments_users',
	'idx_comments_users_object_user_date_id',
	'ALTER TABLE `comments_users` ADD KEY `idx_comments_users_object_user_date_id` (`id_users`, `id_user`, `date`, `id`)'
)//

CALL lt_p5_add_index_if_missing(
	'comments_news',
	'idx_comments_news_object_user_date_id',
	'ALTER TABLE `comments_news` ADD KEY `idx_comments_news_object_user_date_id` (`id_news`, `id_user`, `date`, `id`)'
)//

CALL lt_p5_add_index_if_missing(
	'comments_faq',
	'idx_comments_faq_object_user_date_id',
	'ALTER TABLE `comments_faq` ADD KEY `idx_comments_faq_object_user_date_id` (`id_faq`, `id_user`, `date`, `id`)'
)//

DROP PROCEDURE IF EXISTS lt_p5_add_index_if_missing//
DROP PROCEDURE IF EXISTS lt_p5_convert_to_innodb_if_needed//
DROP PROCEDURE IF EXISTS lt_p5_abort_on_zero_dates//

DELIMITER ;

SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN (
	'comments_torrents', 'comments_users', 'comments_news', 'comments_faq',
	'comment_reactions', 'comment_pins', 'comment_edit_history',
	'comments_reports', 'comments_users_reports'
  )
ORDER BY t.TABLE_NAME;

SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN (
	'comments_torrents', 'comments_users', 'comments_news', 'comments_faq'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;
