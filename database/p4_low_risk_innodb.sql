-- LiteTracker Wave P4: low-risk support table InnoDB migration.
--
-- Scope:
--   birthday_rewards, books, comment_edit_history, comment_pins,
--   comments_reports, comments_users_reports, confirm, cron, files, forgot,
--   friends, moderation_log, polls, polls_questions, retrackers,
--   torrent_ratings, user_admin_notes, users_blacklist.
--
-- Explicitly out of scope:
--   peers, trackers, snatched, torrents, users, sessions,
--   comments_torrents, comments_users.
--
-- Safety:
--   - Preflight SELECTs run before changes.
--   - The script aborts if any checked DATETIME column contains zero dates.
--   - Engine changes are guarded and no-op when a table is already InnoDB.
--   - Index changes are additive and guarded by information_schema.
--   - No indexes are dropped in this migration.
--
-- Rollback examples:
--   ALTER TABLE `books` ENGINE=MyISAM;
--   ALTER TABLE `forgot` DROP INDEX `idx_forgot_code`;
--   ALTER TABLE `forgot` DROP INDEX `idx_forgot_email`;
--   ALTER TABLE `forgot` DROP INDEX `idx_forgot_date`;
--   ALTER TABLE `retrackers` DROP INDEX `idx_retrackers_sort`;
--   ALTER TABLE `books` DROP INDEX `idx_books_torrent_user`;
--   ALTER TABLE `friends` DROP INDEX `idx_friends_friend_status`;
--   ALTER TABLE `polls_questions` DROP INDEX `idx_polls_questions_poll`;

SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN (
	'birthday_rewards', 'books', 'comment_edit_history', 'comment_pins',
	'comments_reports', 'comments_users_reports', 'confirm', 'cron', 'files',
	'forgot', 'friends', 'moderation_log', 'polls', 'polls_questions',
	'retrackers', 'torrent_ratings', 'user_admin_notes', 'users_blacklist'
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
	'birthday_rewards', 'books', 'comment_edit_history', 'comment_pins',
	'comments_reports', 'comments_users_reports', 'confirm', 'cron', 'files',
	'forgot', 'friends', 'moderation_log', 'polls', 'polls_questions',
	'retrackers', 'torrent_ratings', 'user_admin_notes', 'users_blacklist'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

DROP TEMPORARY TABLE IF EXISTS lt_p4_zero_date_check;
CREATE TEMPORARY TABLE lt_p4_zero_date_check (
	column_name VARCHAR(128) NOT NULL,
	zero_count BIGINT NOT NULL
);

INSERT INTO lt_p4_zero_date_check
SELECT 'birthday_rewards.created_at', COUNT(*) FROM birthday_rewards WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'books.date', COUNT(*) FROM books WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comment_edit_history.edited_at', COUNT(*) FROM comment_edit_history WHERE CAST(edited_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comment_pins.pinned_at', COUNT(*) FROM comment_pins WHERE CAST(pinned_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_reports.created_at', COUNT(*) FROM comments_reports WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_reports.resolved_at', COUNT(*) FROM comments_reports WHERE CAST(resolved_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users_reports.created_at', COUNT(*) FROM comments_users_reports WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'comments_users_reports.resolved_at', COUNT(*) FROM comments_users_reports WHERE CAST(resolved_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'confirm.date', COUNT(*) FROM confirm WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'forgot.date', COUNT(*) FROM forgot WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'friends.date', COUNT(*) FROM friends WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'moderation_log.created_at', COUNT(*) FROM moderation_log WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'polls.date', COUNT(*) FROM polls WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'torrent_ratings.date', COUNT(*) FROM torrent_ratings WHERE CAST(date AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'user_admin_notes.created_at', COUNT(*) FROM user_admin_notes WHERE CAST(created_at AS CHAR) = '0000-00-00 00:00:00'
UNION ALL SELECT 'users_blacklist.date_added', COUNT(*) FROM users_blacklist WHERE CAST(date_added AS CHAR) = '0000-00-00 00:00:00';

SELECT * FROM lt_p4_zero_date_check ORDER BY column_name;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p4_abort_on_zero_dates//
CREATE PROCEDURE lt_p4_abort_on_zero_dates()
BEGIN
	DECLARE v_zero_dates BIGINT DEFAULT 0;

	SELECT COALESCE(SUM(zero_count), 0)
	INTO v_zero_dates
	FROM lt_p4_zero_date_check;

	IF v_zero_dates > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P4 aborted: zero DATETIME values found in candidate support tables';
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p4_convert_to_innodb_if_needed//
CREATE PROCEDURE lt_p4_convert_to_innodb_if_needed(IN p_table_name VARCHAR(128))
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
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P4 aborted: candidate table not found';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		SET v_sql = CONCAT('ALTER TABLE `', REPLACE(p_table_name, '`', '``'), '` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC');
		SET @lt_p4_sql = v_sql;
		PREPARE lt_p4_stmt FROM @lt_p4_sql;
		EXECUTE lt_p4_stmt;
		DEALLOCATE PREPARE lt_p4_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p4_add_index_if_missing//
CREATE PROCEDURE lt_p4_add_index_if_missing(
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
		SET @lt_p4_sql = p_index_sql;
		PREPARE lt_p4_stmt FROM @lt_p4_sql;
		EXECUTE lt_p4_stmt;
		DEALLOCATE PREPARE lt_p4_stmt;
	END IF;
END//

CALL lt_p4_abort_on_zero_dates()//

CALL lt_p4_convert_to_innodb_if_needed('birthday_rewards')//
CALL lt_p4_convert_to_innodb_if_needed('books')//
CALL lt_p4_convert_to_innodb_if_needed('comment_edit_history')//
CALL lt_p4_convert_to_innodb_if_needed('comment_pins')//
CALL lt_p4_convert_to_innodb_if_needed('comments_reports')//
CALL lt_p4_convert_to_innodb_if_needed('comments_users_reports')//
CALL lt_p4_convert_to_innodb_if_needed('confirm')//
CALL lt_p4_convert_to_innodb_if_needed('cron')//
CALL lt_p4_convert_to_innodb_if_needed('files')//
CALL lt_p4_convert_to_innodb_if_needed('forgot')//
CALL lt_p4_convert_to_innodb_if_needed('friends')//
CALL lt_p4_convert_to_innodb_if_needed('moderation_log')//
CALL lt_p4_convert_to_innodb_if_needed('polls')//
CALL lt_p4_convert_to_innodb_if_needed('polls_questions')//
CALL lt_p4_convert_to_innodb_if_needed('retrackers')//
CALL lt_p4_convert_to_innodb_if_needed('torrent_ratings')//
CALL lt_p4_convert_to_innodb_if_needed('user_admin_notes')//
CALL lt_p4_convert_to_innodb_if_needed('users_blacklist')//

CALL lt_p4_add_index_if_missing(
	'forgot',
	'idx_forgot_code',
	'ALTER TABLE `forgot` ADD KEY `idx_forgot_code` (`code`)'
)//

CALL lt_p4_add_index_if_missing(
	'forgot',
	'idx_forgot_email',
	'ALTER TABLE `forgot` ADD KEY `idx_forgot_email` (`email`)'
)//

CALL lt_p4_add_index_if_missing(
	'forgot',
	'idx_forgot_date',
	'ALTER TABLE `forgot` ADD KEY `idx_forgot_date` (`date`)'
)//

CALL lt_p4_add_index_if_missing(
	'retrackers',
	'idx_retrackers_sort',
	'ALTER TABLE `retrackers` ADD KEY `idx_retrackers_sort` (`sort`)'
)//

CALL lt_p4_add_index_if_missing(
	'books',
	'idx_books_torrent_user',
	'ALTER TABLE `books` ADD KEY `idx_books_torrent_user` (`id_torrent`, `id_user`)'
)//

CALL lt_p4_add_index_if_missing(
	'friends',
	'idx_friends_friend_status',
	'ALTER TABLE `friends` ADD KEY `idx_friends_friend_status` (`friendid`, `status`)'
)//

CALL lt_p4_add_index_if_missing(
	'polls_questions',
	'idx_polls_questions_poll',
	'ALTER TABLE `polls_questions` ADD KEY `idx_polls_questions_poll` (`id_poll`)'
)//

DROP PROCEDURE IF EXISTS lt_p4_add_index_if_missing//
DROP PROCEDURE IF EXISTS lt_p4_convert_to_innodb_if_needed//
DROP PROCEDURE IF EXISTS lt_p4_abort_on_zero_dates//

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
	'birthday_rewards', 'books', 'comment_edit_history', 'comment_pins',
	'comments_reports', 'comments_users_reports', 'confirm', 'cron', 'files',
	'forgot', 'friends', 'moderation_log', 'polls', 'polls_questions',
	'retrackers', 'torrent_ratings', 'user_admin_notes', 'users_blacklist'
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
	'books', 'forgot', 'friends', 'polls_questions', 'retrackers'
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;
