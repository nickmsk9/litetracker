-- LiteTracker Wave P9: sessions InnoDB local rehearsal / production-safe migration.
--
-- Scope: sessions table only.
--
-- Production note:
--   Do not run in production without:
--   1. full database backup;
--   2. table-specific dump:
--      mysqldump --single-transaction --routines --triggers DB_NAME sessions > sessions.before_innodb.sql
--   3. maintenance window approval;
--   4. decision on whether stale sessions may be cleared before migration.
--
-- Optional cleanup recommendation only, intentionally not executed:
--   SELECT COUNT(*) FROM sessions WHERE last_access < (NOW() - INTERVAL 7 DAY);
--   DELETE FROM sessions WHERE last_access < (NOW() - INTERVAL 7 DAY);
--
-- Safety:
--   - Preflight SELECTs run before the ALTER.
--   - The procedure aborts if zero/null last_access rows are found.
--   - The procedure drops the legacy zero DATETIME default on last_access before
--     conversion because MySQL 8.4 rejects it for InnoDB DDL.
--   - The procedure aborts if the table is unexpectedly large for local rehearsal.
--   - Engine conversion is guarded and no-op if sessions is already InnoDB.
--   - No destructive index drops are included.
--
-- Rollback note:
--   ALTER TABLE `sessions` ENGINE=MyISAM;
--   ALTER TABLE `sessions` ALTER COLUMN `last_access` SET DEFAULT '0000-00-00 00:00:00';
-- For production, prefer restore from backup if session continuity matters.

SELECT 'P9 sessions preflight status' AS section;
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
  AND t.TABLE_NAME = 'sessions';

SELECT 'P9 sessions data preflight' AS section;
SELECT
	COUNT(*) AS rows_total,
	SUM(CASE WHEN CAST(last_access AS CHAR) = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS zero_last_access,
	SUM(CASE WHEN last_access IS NULL THEN 1 ELSE 0 END) AS null_last_access,
	MIN(last_access) AS min_last_access,
	MAX(last_access) AS max_last_access,
	MAX(CHAR_LENGTH(session_id)) AS max_session_id_chars,
	MAX(CHAR_LENGTH(user_agent)) AS max_user_agent_chars,
	MAX(CHAR_LENGTH(php_self)) AS max_php_self_chars,
	AVG(CHAR_LENGTH(user_agent)) AS avg_user_agent_chars,
	AVG(CHAR_LENGTH(php_self)) AS avg_php_self_chars
FROM sessions;

SELECT 'P9 sessions cleanup candidates - report only' AS section;
SELECT
	SUM(CASE WHEN last_access < (NOW() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS stale_7d,
	SUM(CASE WHEN last_access < (NOW() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS stale_30d
FROM sessions;

SELECT 'P9 sessions per-user distribution' AS section;
SELECT user_id, COUNT(*) AS sessions_per_user
FROM sessions
GROUP BY user_id
ORDER BY sessions_per_user DESC
LIMIT 20;

SELECT 'P9 sessions indexes before' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'sessions'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P9 sessions duplicate/equivalent index shapes before' AS section;
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
	  AND s.TABLE_NAME = 'sessions'
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS index_shapes
GROUP BY table_name
HAVING index_count <> distinct_index_shapes;

SELECT 'P9 EXPLAIN session touch/read before' AS section;
EXPLAIN SELECT *
FROM sessions
WHERE session_id = 'p9_probe'
LIMIT 1;

EXPLAIN SELECT user_id
FROM sessions
WHERE user_id = 1
  AND last_access >= (NOW() - INTERVAL 15 MINUTE)
LIMIT 1;

EXPLAIN SELECT s.*, u.name, u.class
FROM sessions AS s
LEFT JOIN users AS u ON u.id = s.user_id
ORDER BY s.last_access DESC
LIMIT 40;

EXPLAIN SELECT DISTINCT user_id
FROM sessions
WHERE user_id > 0
  AND last_access >= (NOW() - INTERVAL 1 HOUR);

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p9_convert_sessions_to_innodb//
CREATE PROCEDURE lt_p9_convert_sessions_to_innodb()
BEGIN
	DECLARE v_engine VARCHAR(64) DEFAULT '';
	DECLARE v_rows BIGINT DEFAULT 0;
	DECLARE v_zero_dates BIGINT DEFAULT 0;
	DECLARE v_null_dates BIGINT DEFAULT 0;
	DECLARE v_size BIGINT DEFAULT 0;
	DECLARE v_last_access_default TEXT DEFAULT NULL;

	SELECT COALESCE(t.ENGINE, ''), COALESCE(t.TABLE_ROWS, 0), COALESCE(t.DATA_LENGTH + t.INDEX_LENGTH, 0)
	INTO v_engine, v_rows, v_size
	FROM information_schema.tables AS t
	WHERE t.TABLE_SCHEMA = DATABASE()
	  AND t.TABLE_NAME = 'sessions'
	LIMIT 1;

	IF v_engine = '' THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P9 aborted: sessions table not found';
	END IF;

	SELECT
		SUM(CASE WHEN CAST(last_access AS CHAR) = '0000-00-00 00:00:00' THEN 1 ELSE 0 END),
		SUM(CASE WHEN last_access IS NULL THEN 1 ELSE 0 END)
	INTO v_zero_dates, v_null_dates
	FROM sessions;

	IF COALESCE(v_zero_dates, 0) > 0 OR COALESCE(v_null_dates, 0) > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P9 aborted: sessions has zero/null last_access rows';
	END IF;

	IF v_rows > 100000 OR v_size > 536870912 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P9 aborted: sessions is too large for local rehearsal guard';
	END IF;

	SELECT COLUMN_DEFAULT
	INTO v_last_access_default
	FROM information_schema.columns
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'sessions'
	  AND COLUMN_NAME = 'last_access'
	LIMIT 1;

	IF v_last_access_default = '0000-00-00 00:00:00' THEN
		ALTER TABLE `sessions` ALTER COLUMN `last_access` DROP DEFAULT;
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		ALTER TABLE `sessions` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
	END IF;
END//

CALL lt_p9_convert_sessions_to_innodb()//

DROP PROCEDURE IF EXISTS lt_p9_convert_sessions_to_innodb//

DELIMITER ;

SELECT 'P9 sessions postflight status' AS section;
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
  AND t.TABLE_NAME = 'sessions';

SELECT 'P9 sessions indexes after' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'sessions'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P9 EXPLAIN session touch/read after' AS section;
EXPLAIN SELECT *
FROM sessions
WHERE session_id = 'p9_probe'
LIMIT 1;

EXPLAIN SELECT user_id
FROM sessions
WHERE user_id = 1
  AND last_access >= (NOW() - INTERVAL 15 MINUTE)
LIMIT 1;

EXPLAIN SELECT s.*, u.name, u.class
FROM sessions AS s
LEFT JOIN users AS u ON u.id = s.user_id
ORDER BY s.last_access DESC
LIMIT 40;

EXPLAIN SELECT DISTINCT user_id
FROM sessions
WHERE user_id > 0
  AND last_access >= (NOW() - INTERVAL 1 HOUR);
