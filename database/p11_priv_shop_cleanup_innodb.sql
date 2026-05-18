-- LiteTracker Wave P11: priv/shop zero-date cleanup + InnoDB rehearsal.
--
-- Scope:
--   priv.DATE
--   shop.date
--
-- Hot tables explicitly out of scope and not altered here:
--   peers, trackers, snatched, torrents, users
--
-- Production warning:
--   This script is for local rehearsal until production preflight is reviewed.
--   Do not run in production without a full database backup and table dumps:
--
--   mysqldump --single-transaction --routines --triggers DB_NAME priv shop \
--     > p11_priv_shop.before_cleanup_innodb.sql
--
-- Normalization decision:
--   priv.DATE and shop.date zero values are legacy "created at unknown"
--   placeholders. They are normalized to NULL, not a future/past sentinel.
--
-- Reversibility:
--   Data rollback requires the table dump above. Engine rollback examples:
--     ALTER TABLE `priv` ENGINE=MyISAM;
--     ALTER TABLE `shop` ENGINE=MyISAM;
--   To restore the exact prior zero-date values after rehearsal:
--     UPDATE `priv` SET `DATE` = '0000-00-00 00:00:00' WHERE `DATE` IS NULL;
--     UPDATE `shop` SET `date` = '0000-00-00 00:00:00' WHERE `date` IS NULL;
--   That restore requires a permissive sql_mode and is not recommended for
--   production after the cleanup is accepted.

SELECT 'P11 preflight table status' AS section;
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
  AND t.TABLE_NAME IN ('priv', 'shop')
ORDER BY t.TABLE_NAME;

SELECT 'P11 preflight date columns' AS section;
SELECT
	c.TABLE_NAME,
	c.COLUMN_NAME,
	c.COLUMN_TYPE,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT,
	c.EXTRA
FROM information_schema.columns AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND (
	(c.TABLE_NAME = 'priv' AND c.COLUMN_NAME = 'DATE')
	OR (c.TABLE_NAME = 'shop' AND c.COLUMN_NAME = 'date')
  )
ORDER BY c.TABLE_NAME, c.COLUMN_NAME;

SELECT 'P11 preflight indexes' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('priv', 'shop')
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P11 preflight zero/null/min/max date counts' AS section;
SELECT
	'priv.DATE' AS column_name,
	COUNT(*) AS row_count,
	COALESCE(SUM(CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00'), 0) AS zero_date_count,
	COALESCE(SUM(`DATE` IS NULL), 0) AS null_date_count,
	MIN(NULLIF(CAST(`DATE` AS CHAR), '0000-00-00 00:00:00')) AS min_nonzero_date,
	MAX(NULLIF(CAST(`DATE` AS CHAR), '0000-00-00 00:00:00')) AS max_nonzero_date
FROM `priv`
UNION ALL
SELECT
	'shop.date',
	COUNT(*),
	COALESCE(SUM(CAST(`date` AS CHAR) = '0000-00-00 00:00:00'), 0),
	COALESCE(SUM(`date` IS NULL), 0),
	MIN(NULLIF(CAST(`date` AS CHAR), '0000-00-00 00:00:00')),
	MAX(NULLIF(CAST(`date` AS CHAR), '0000-00-00 00:00:00'))
FROM `shop`;

SELECT 'P11 preflight affected rows' AS section;
SELECT id, NAME, CAST(`DATE` AS CHAR) AS date_value
FROM `priv`
WHERE CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00'
ORDER BY id;
SELECT id, name, CAST(`date` AS CHAR) AS date_value
FROM `shop`
WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00'
ORDER BY id;

SELECT 'P11 EXPLAIN probes before' AS section;
EXPLAIN SELECT * FROM `priv` WHERE id = 1 LIMIT 1;
EXPLAIN SELECT * FROM `priv` WHERE SIGNUP = 1 ORDER BY id ASC LIMIT 1;
EXPLAIN SELECT * FROM `priv` WHERE id > 0 ORDER BY id DESC;
EXPLAIN SELECT * FROM `shop` ORDER BY `date` DESC;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p11_require_table//
CREATE PROCEDURE lt_p11_require_table(IN p_table_name VARCHAR(128))
BEGIN
	IF NOT EXISTS (
		SELECT 1
		FROM information_schema.tables
		WHERE table_schema = DATABASE()
		  AND table_name = p_table_name
		LIMIT 1
	) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P11 aborted: required table is missing';
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p11_make_datetime_nullable_if_needed//
CREATE PROCEDURE lt_p11_make_datetime_nullable_if_needed(
	IN p_table_name VARCHAR(128),
	IN p_column_name VARCHAR(128)
)
BEGIN
	DECLARE v_column_type VARCHAR(128) DEFAULT NULL;
	DECLARE v_is_nullable VARCHAR(3) DEFAULT NULL;
	DECLARE v_sql TEXT;

	SELECT c.COLUMN_TYPE, c.IS_NULLABLE
	INTO v_column_type, v_is_nullable
	FROM information_schema.columns AS c
	WHERE c.TABLE_SCHEMA = DATABASE()
	  AND c.TABLE_NAME = p_table_name
	  AND c.COLUMN_NAME = p_column_name
	LIMIT 1;

	IF v_column_type IS NULL THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P11 aborted: required date column is missing';
	END IF;

	IF LOWER(v_column_type) <> 'datetime' THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P11 aborted: date column is not datetime';
	END IF;

	IF v_is_nullable <> 'YES' THEN
		SET v_sql = CONCAT(
			'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
			'` MODIFY COLUMN `', REPLACE(p_column_name, '`', '``'),
			'` datetime NULL DEFAULT NULL'
		);
		SET @lt_p11_sql = v_sql;
		PREPARE lt_p11_stmt FROM @lt_p11_sql;
		EXECUTE lt_p11_stmt;
		DEALLOCATE PREPARE lt_p11_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p11_convert_to_innodb_if_needed//
CREATE PROCEDURE lt_p11_convert_to_innodb_if_needed(IN p_table_name VARCHAR(128))
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
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P11 aborted: table not found before engine conversion';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		SET v_sql = CONCAT(
			'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
			'` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC'
		);
		SET @lt_p11_sql = v_sql;
		PREPARE lt_p11_stmt FROM @lt_p11_sql;
		EXECUTE lt_p11_stmt;
		DEALLOCATE PREPARE lt_p11_stmt;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p11_abort_on_remaining_zero_dates//
CREATE PROCEDURE lt_p11_abort_on_remaining_zero_dates()
BEGIN
	DECLARE v_zero_dates BIGINT DEFAULT 0;

	SELECT
		(SELECT COUNT(*) FROM `priv` WHERE CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00')
		+ (SELECT COUNT(*) FROM `shop` WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00')
	INTO v_zero_dates;

	IF COALESCE(v_zero_dates, 0) > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P11 aborted: zero-date values remain';
	END IF;
END//

DELIMITER ;

CALL lt_p11_require_table('priv');
CALL lt_p11_require_table('shop');

DROP TEMPORARY TABLE IF EXISTS lt_p11_priv_zero_ids;
DROP TEMPORARY TABLE IF EXISTS lt_p11_shop_zero_ids;

CREATE TEMPORARY TABLE lt_p11_priv_zero_ids (
	id int unsigned NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

CREATE TEMPORARY TABLE lt_p11_shop_zero_ids (
	id int unsigned NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO lt_p11_priv_zero_ids (id)
SELECT id
FROM `priv`
WHERE CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00';

INSERT INTO lt_p11_shop_zero_ids (id)
SELECT id
FROM `shop`
WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00';

SELECT 'P11 captured zero-date row ids' AS section;
SELECT 'priv.DATE' AS column_name, COUNT(*) AS captured_rows FROM lt_p11_priv_zero_ids
UNION ALL
SELECT 'shop.date', COUNT(*) FROM lt_p11_shop_zero_ids;

-- MySQL strict/no-zero-date modes can reject an ALTER while invalid zero
-- dates still exist in a NOT NULL datetime column. Move only the captured
-- zero-date rows through a valid temporary value before making the columns
-- nullable, then set those same captured rows to NULL.
UPDATE `priv` AS p
INNER JOIN lt_p11_priv_zero_ids AS z ON z.id = p.id
SET p.`DATE` = '1000-01-01 00:00:00'
WHERE CAST(p.`DATE` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `shop` AS s
INNER JOIN lt_p11_shop_zero_ids AS z ON z.id = s.id
SET s.`date` = '1000-01-01 00:00:00'
WHERE CAST(s.`date` AS CHAR) = '0000-00-00 00:00:00';

CALL lt_p11_make_datetime_nullable_if_needed('priv', 'DATE');
CALL lt_p11_make_datetime_nullable_if_needed('shop', 'date');

UPDATE `priv` AS p
INNER JOIN lt_p11_priv_zero_ids AS z ON z.id = p.id
SET p.`DATE` = NULL
WHERE p.`DATE` = '1000-01-01 00:00:00';

UPDATE `shop` AS s
INNER JOIN lt_p11_shop_zero_ids AS z ON z.id = s.id
SET s.`date` = NULL
WHERE s.`date` = '1000-01-01 00:00:00';

CALL lt_p11_abort_on_remaining_zero_dates();

CALL lt_p11_convert_to_innodb_if_needed('priv');
CALL lt_p11_convert_to_innodb_if_needed('shop');

DROP TEMPORARY TABLE IF EXISTS lt_p11_priv_zero_ids;
DROP TEMPORARY TABLE IF EXISTS lt_p11_shop_zero_ids;

DROP PROCEDURE IF EXISTS lt_p11_abort_on_remaining_zero_dates;
DROP PROCEDURE IF EXISTS lt_p11_convert_to_innodb_if_needed;
DROP PROCEDURE IF EXISTS lt_p11_make_datetime_nullable_if_needed;
DROP PROCEDURE IF EXISTS lt_p11_require_table;

SELECT 'P11 postflight table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME IN ('priv', 'shop')
ORDER BY t.TABLE_NAME;

SELECT 'P11 postflight date columns' AS section;
SELECT
	c.TABLE_NAME,
	c.COLUMN_NAME,
	c.COLUMN_TYPE,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT,
	c.EXTRA
FROM information_schema.columns AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND (
	(c.TABLE_NAME = 'priv' AND c.COLUMN_NAME = 'DATE')
	OR (c.TABLE_NAME = 'shop' AND c.COLUMN_NAME = 'date')
  )
ORDER BY c.TABLE_NAME, c.COLUMN_NAME;

SELECT 'P11 postflight zero/null/min/max date counts' AS section;
SELECT
	'priv.DATE' AS column_name,
	COUNT(*) AS row_count,
	COALESCE(SUM(CAST(`DATE` AS CHAR) = '0000-00-00 00:00:00'), 0) AS zero_date_count,
	COALESCE(SUM(`DATE` IS NULL), 0) AS null_date_count,
	MIN(NULLIF(CAST(`DATE` AS CHAR), '0000-00-00 00:00:00')) AS min_nonzero_date,
	MAX(NULLIF(CAST(`DATE` AS CHAR), '0000-00-00 00:00:00')) AS max_nonzero_date
FROM `priv`
UNION ALL
SELECT
	'shop.date',
	COUNT(*),
	COALESCE(SUM(CAST(`date` AS CHAR) = '0000-00-00 00:00:00'), 0),
	COALESCE(SUM(`date` IS NULL), 0),
	MIN(NULLIF(CAST(`date` AS CHAR), '0000-00-00 00:00:00')),
	MAX(NULLIF(CAST(`date` AS CHAR), '0000-00-00 00:00:00'))
FROM `shop`;

SELECT 'P11 postflight indexes' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('priv', 'shop')
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P11 EXPLAIN probes after' AS section;
EXPLAIN SELECT * FROM `priv` WHERE id = 1 LIMIT 1;
EXPLAIN SELECT * FROM `priv` WHERE SIGNUP = 1 ORDER BY id ASC LIMIT 1;
EXPLAIN SELECT * FROM `priv` WHERE id > 0 ORDER BY id DESC;
EXPLAIN SELECT * FROM `shop` ORDER BY `date` DESC;
