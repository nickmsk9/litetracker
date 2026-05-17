-- LiteTracker P25: snatched InnoDB rehearsal
--
-- Production notes:
-- 1. Take a logical backup before running:
--    mysqldump --single-transaction --routines --triggers <database> snatched > snatched_pre_p25.sql
-- 2. Run the preflight SELECTs first and review all guard values.
-- 3. This file changes only the snatched table engine. It does not alter columns,
--    indexes, accounting formulas, or announce transaction behavior.
-- 4. Rollback, if needed:
--    ALTER TABLE snatched ENGINE=MyISAM;
--    or restore the table from the preflight dump.

-- Current table shape.
SHOW TABLE STATUS LIKE 'snatched';
SHOW CREATE TABLE snatched;
SHOW INDEX FROM snatched;

-- Current row/data profile.
SELECT COUNT(*) AS row_count FROM snatched;

-- Duplicate `(torrent, userid)` groups must be zero before InnoDB rehearsal/apply.
SELECT
	COUNT(*) AS duplicate_key_groups,
	COALESCE(SUM(c - 1), 0) AS duplicate_extra_rows
FROM (
	SELECT torrent, userid, COUNT(*) AS c
	FROM snatched
	GROUP BY torrent, userid
	HAVING c > 1
) AS duplicate_snatched;

-- NULL userid must be zero. InnoDB unique indexes permit multiple NULLs, so do not
-- proceed if production data contains NULL userid rows.
SELECT COUNT(*) AS null_userid FROM snatched WHERE userid IS NULL;

-- Epoch sanity. startedat/completedat are expected to remain integer epochs.
SELECT
	MIN(startedat) AS min_startedat,
	MAX(startedat) AS max_startedat,
	MIN(completedat) AS min_completedat,
	MAX(completedat) AS max_completedat,
	COALESCE(SUM(startedat <= 0), 0) AS startedat_zero_or_negative,
	COALESCE(SUM(completedat < 0), 0) AS completedat_negative,
	COALESCE(SUM(completedat = 0), 0) AS completedat_zero
FROM snatched;

SELECT finished, COUNT(*) AS rows_count
FROM snatched
GROUP BY finished
ORDER BY finished;

SELECT
	MIN(uploaded) AS min_uploaded,
	MAX(uploaded) AS max_uploaded,
	MIN(downloaded) AS min_downloaded,
	MAX(downloaded) AS max_downloaded,
	COALESCE(SUM(uploaded < 0), 0) AS uploaded_negative,
	COALESCE(SUM(downloaded < 0), 0) AS downloaded_negative
FROM snatched;

SELECT
	COLUMN_NAME,
	DATA_TYPE,
	COLUMN_TYPE,
	IS_NULLABLE,
	COLUMN_DEFAULT,
	CHARACTER_SET_NAME,
	COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
	AND TABLE_NAME = 'snatched'
ORDER BY ORDINAL_POSITION;

-- Guarded local rehearsal/apply. Adjust @p25_max_rehearsal_rows only after review.
SET @p25_max_rehearsal_rows := 1000000;
SET @p25_row_count := (SELECT COUNT(*) FROM snatched);
SET @p25_duplicate_key_groups := (
	SELECT COUNT(*)
	FROM (
		SELECT torrent, userid, COUNT(*) AS c
		FROM snatched
		GROUP BY torrent, userid
		HAVING c > 1
	) AS duplicate_snatched_guard
);
SET @p25_null_userid := (SELECT COUNT(*) FROM snatched WHERE userid IS NULL);
SET @p25_epoch_type_blockers := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = 'snatched'
		AND COLUMN_NAME IN ('startedat', 'completedat')
		AND DATA_TYPE <> 'int'
);
SET @p25_unique_snatch_columns := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = 'snatched'
		AND INDEX_NAME = 'snatch'
		AND NON_UNIQUE = 0
		AND COLUMN_NAME IN ('torrent', 'userid')
);

DELIMITER //
DROP PROCEDURE IF EXISTS p25_guard_snatched_innodb//
CREATE PROCEDURE p25_guard_snatched_innodb()
BEGIN
	IF @p25_duplicate_key_groups <> 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P25 guard failed: duplicate (torrent, userid) groups exist';
	END IF;

	IF @p25_null_userid <> 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P25 guard failed: userid IS NULL rows exist';
	END IF;

	IF @p25_epoch_type_blockers <> 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P25 guard failed: startedat/completedat are not int epoch columns';
	END IF;

	IF @p25_unique_snatch_columns <> 2 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P25 guard failed: unique snatch(torrent, userid) index missing';
	END IF;

	IF @p25_row_count > @p25_max_rehearsal_rows THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P25 guard failed: row count exceeds rehearsal threshold';
	END IF;
END//
DELIMITER ;

CALL p25_guard_snatched_innodb();
DROP PROCEDURE p25_guard_snatched_innodb;

ALTER TABLE snatched ENGINE=InnoDB;

-- Postflight.
SHOW TABLE STATUS LIKE 'snatched';
SHOW CREATE TABLE snatched;
SHOW INDEX FROM snatched;
