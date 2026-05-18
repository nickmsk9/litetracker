-- LiteTracker Wave P3: mail InnoDB migration plan.
--
-- Scope: mail table only.
-- Duplicate-safe: the procedure converts only when mail is not already InnoDB.
-- Safety: this script runs preflight SELECTs first and aborts the migration when
-- zero DATETIME values are present.
--
-- Operational note:
-- MyISAM -> InnoDB rebuilds the table. Run during a maintenance window after a
-- fresh backup, especially on production datasets with large mail history.
--
-- Rollback note:
--   ALTER TABLE `mail` ENGINE=MyISAM;
-- Rollback is not recommended after production writes unless you have a tested
-- backup/restore path.

SELECT
	'mail_engine' AS check_name,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME = 'mail';

SELECT
	'mail_data_preflight' AS check_name,
	COUNT(*) AS rows_total,
	SUM(CASE WHEN CAST(`date` AS CHAR) = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS zero_dates,
	SUM(CASE WHEN `date` IS NULL THEN 1 ELSE 0 END) AS null_dates,
	MAX(CHAR_LENGTH(`name`)) AS max_subject_chars,
	MAX(CHAR_LENGTH(`text`)) AS max_text_chars,
	AVG(CHAR_LENGTH(`text`)) AS avg_text_chars
FROM `mail`;

SELECT
	'mail_indexes' AS check_name,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'mail'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p3_convert_mail_to_innodb//
CREATE PROCEDURE lt_p3_convert_mail_to_innodb()
BEGIN
	DECLARE v_engine VARCHAR(64) DEFAULT '';
	DECLARE v_zero_dates BIGINT DEFAULT 0;

	SELECT COALESCE(t.ENGINE, '')
	INTO v_engine
	FROM information_schema.tables AS t
	WHERE t.TABLE_SCHEMA = DATABASE()
	  AND t.TABLE_NAME = 'mail'
	LIMIT 1;

	IF v_engine = '' THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mail table not found';
	END IF;

	SELECT COUNT(*)
	INTO v_zero_dates
	FROM `mail`
	WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00';

	IF v_zero_dates > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mail has zero DATETIME rows; clean data before InnoDB migration';
	END IF;

	IF UPPER(v_engine) <> 'INNODB' THEN
		ALTER TABLE `mail` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
	END IF;
END//

CALL lt_p3_convert_mail_to_innodb()//

DROP PROCEDURE IF EXISTS lt_p3_convert_mail_to_innodb//

DELIMITER ;

SELECT
	'mail_engine_after' AS check_name,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME = 'mail';
