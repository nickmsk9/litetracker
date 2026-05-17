-- LiteTracker Wave P7: mail InnoDB local rehearsal / production-safe migration.
--
-- Scope: mail table only.
--
-- Production note:
--   Do not run in production without:
--   1. full database backup;
--   2. table-specific dump:
--      mysqldump --single-transaction --routines --triggers DB_NAME mail > mail.before_innodb.sql
--   3. maintenance window approval;
--   4. fresh P7 preflight output reviewed.
--
-- Safety:
--   - Preflight SELECTs run before the ALTER.
--   - The procedure aborts if zero/null date rows are found.
--   - The procedure aborts if the table is unexpectedly large for local rehearsal.
--   - Engine conversion is guarded and no-op if mail is already InnoDB.
--   - No destructive index drops are included.
--
-- Rollback note:
--   ALTER TABLE `mail` ENGINE=MyISAM;
-- Prefer restore from backup over rollback after production writes.

SELECT 'P7 mail preflight status' AS section;
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
  AND t.TABLE_NAME = 'mail';

SELECT 'P7 mail data preflight' AS section;
SELECT
	COUNT(*) AS rows_total,
	SUM(CASE WHEN CAST(`date` AS CHAR) = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS zero_dates,
	SUM(CASE WHEN `date` IS NULL THEN 1 ELSE 0 END) AS null_dates,
	MAX(CHAR_LENGTH(`name`)) AS max_subject_chars,
	MAX(CHAR_LENGTH(`text`)) AS max_text_chars,
	AVG(CHAR_LENGTH(`text`)) AS avg_text_chars
FROM `mail`;

SELECT 'P7 mail indexes before' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'mail'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P7 mail duplicate/equivalent index shapes before' AS section;
SELECT
	table_name,
	COUNT(*) AS index_count,
	COUNT(DISTINCT columns_in_order) AS distinct_index_shapes
FROM (
	SELECT
		s.TABLE_NAME AS table_name,
		s.INDEX_NAME,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.statistics AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME = 'mail'
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS index_shapes
GROUP BY table_name
HAVING index_count <> distinct_index_shapes;

SELECT 'P7 EXPLAIN inbox summary before' AS section;
EXPLAIN SELECT
	partner_id,
	MAX(`date`) AS last_date,
	SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY `date` DESC, id DESC), ',', 1) AS last_message_id,
	COUNT(*) AS total_messages,
	SUM(unread) AS unread_messages
FROM (
	SELECT id, `date`, id_user_out AS partner_id, IF(reading = 0, 1, 0) AS unread
	FROM mail
	WHERE id_user_in = 1 AND delete_in = 0
	UNION ALL
	SELECT id, `date`, id_user_in AS partner_id, 0 AS unread
	FROM mail
	WHERE id_user_out = 1 AND delete_out = 0
) AS visible_mail
GROUP BY partner_id
ORDER BY (unread_messages > 0) DESC, last_date DESC, last_message_id DESC;

SELECT 'P7 EXPLAIN dialog before' AS section;
EXPLAIN SELECT *
FROM (
	SELECT
		m.*,
		u.name AS sender_name,
		u.class AS sender_class,
		u.avatar AS sender_avatar
	FROM mail AS m
	LEFT JOIN users AS u ON u.id = m.id_user_out
	WHERE (
		(m.id_user_in = 1 AND m.id_user_out = 2 AND m.delete_in = 0)
		OR (m.id_user_out = 1 AND m.id_user_in = 2 AND m.delete_out = 0)
	)
	ORDER BY m.`date` DESC, m.id DESC
	LIMIT 10
) AS conversation_slice
ORDER BY `date` ASC, id ASC;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p7_convert_mail_to_innodb//
CREATE PROCEDURE lt_p7_convert_mail_to_innodb()
BEGIN
	DECLARE v_engine VARCHAR(64) DEFAULT '';
	DECLARE v_rows BIGINT DEFAULT 0;
	DECLARE v_zero_dates BIGINT DEFAULT 0;
	DECLARE v_null_dates BIGINT DEFAULT 0;
	DECLARE v_size BIGINT DEFAULT 0;

	SELECT COALESCE(t.ENGINE, ''), COALESCE(t.TABLE_ROWS, 0), COALESCE(t.DATA_LENGTH + t.INDEX_LENGTH, 0)
	INTO v_engine, v_rows, v_size
	FROM information_schema.tables AS t
	WHERE t.TABLE_SCHEMA = DATABASE()
	  AND t.TABLE_NAME = 'mail'
	LIMIT 1;

	IF v_engine = '' THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P7 aborted: mail table not found';
	END IF;

	SELECT
		SUM(CASE WHEN CAST(`date` AS CHAR) = '0000-00-00 00:00:00' THEN 1 ELSE 0 END),
		SUM(CASE WHEN `date` IS NULL THEN 1 ELSE 0 END)
	INTO v_zero_dates, v_null_dates
	FROM `mail`;

	IF COALESCE(v_zero_dates, 0) > 0 OR COALESCE(v_null_dates, 0) > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P7 aborted: mail has zero/null date rows';
	END IF;

	IF v_rows > 100000 OR v_size > 1073741824 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P7 aborted: mail is too large for local rehearsal guard';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		ALTER TABLE `mail` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
	END IF;
END//

CALL lt_p7_convert_mail_to_innodb()//

DROP PROCEDURE IF EXISTS lt_p7_convert_mail_to_innodb//

DELIMITER ;

SELECT 'P7 mail postflight status' AS section;
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
  AND t.TABLE_NAME = 'mail';

SELECT 'P7 mail indexes after' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'mail'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P7 EXPLAIN inbox summary after' AS section;
EXPLAIN SELECT
	partner_id,
	MAX(`date`) AS last_date,
	SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY `date` DESC, id DESC), ',', 1) AS last_message_id,
	COUNT(*) AS total_messages,
	SUM(unread) AS unread_messages
FROM (
	SELECT id, `date`, id_user_out AS partner_id, IF(reading = 0, 1, 0) AS unread
	FROM mail
	WHERE id_user_in = 1 AND delete_in = 0
	UNION ALL
	SELECT id, `date`, id_user_in AS partner_id, 0 AS unread
	FROM mail
	WHERE id_user_out = 1 AND delete_out = 0
) AS visible_mail
GROUP BY partner_id
ORDER BY (unread_messages > 0) DESC, last_date DESC, last_message_id DESC;

SELECT 'P7 EXPLAIN dialog after' AS section;
EXPLAIN SELECT *
FROM (
	SELECT
		m.*,
		u.name AS sender_name,
		u.class AS sender_class,
		u.avatar AS sender_avatar
	FROM mail AS m
	LEFT JOIN users AS u ON u.id = m.id_user_out
	WHERE (
		(m.id_user_in = 1 AND m.id_user_out = 2 AND m.delete_in = 0)
		OR (m.id_user_out = 1 AND m.id_user_in = 2 AND m.delete_out = 0)
	)
	ORDER BY m.`date` DESC, m.id DESC
	LIMIT 10
) AS conversation_slice
ORDER BY `date` ASC, id ASC;
