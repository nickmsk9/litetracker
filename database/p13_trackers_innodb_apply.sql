-- LiteTracker Wave P13: trackers InnoDB rehearsal.
--
-- Scope:
--   trackers
--
-- Explicitly out of scope:
--   peers, snatched, torrents, users, sessions, mail, PHP logic
--
-- Production warning:
--   This script is prepared for local/staging rehearsal first. Do not run in
--   production without full DB backup, a table dump, production EXPLAIN review,
--   and a quiet maintenance window.
--
-- Backup/dump notes:
--   mysqldump --single-transaction --routines --triggers DB_NAME trackers \
--     > p13_trackers.before_innodb.sql
--
-- Rollback examples:
--   ALTER TABLE `trackers` ENGINE=MyISAM;
--   ALTER TABLE `trackers` ADD UNIQUE KEY `id` (`id`);
--
-- Remote scan index note:
--   update.peers.php has a remote scan:
--     tracker <> 'localhost' AND lastchecked < ... ORDER BY torrents.id DESC
--   Local EXPLAIN sees only 5 tracker rows and does not justify a new index.
--   Candidate for production review only:
--     -- ALTER TABLE `trackers` ADD KEY `idx_trackers_lastchecked_tracker_torrent` (`lastchecked`, `tracker`, `torrent`);

SELECT 'P13 preflight table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	t.AVG_ROW_LENGTH,
	t.DATA_LENGTH,
	t.INDEX_LENGTH,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.TABLES AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME = 'trackers';

SELECT 'P13 preflight columns' AS section;
SELECT
	c.COLUMN_NAME,
	c.COLUMN_TYPE,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT,
	c.CHARACTER_SET_NAME,
	c.COLLATION_NAME,
	c.EXTRA
FROM information_schema.COLUMNS AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME = 'trackers'
ORDER BY c.ORDINAL_POSITION;

SELECT 'P13 preflight indexes' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.STATISTICS AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'trackers'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P13 duplicate data checks' AS section;
SELECT
	COUNT(*) AS duplicate_torrent_tracker_groups
FROM (
	SELECT torrent, tracker
	FROM trackers
	GROUP BY torrent, tracker
	HAVING COUNT(*) > 1
) AS duplicate_groups;

SELECT 'P13 duplicate index shape checks' AS section;
SELECT
	index_shapes.columns_in_order,
	COUNT(*) AS index_count,
	GROUP_CONCAT(index_shapes.INDEX_NAME ORDER BY index_shapes.INDEX_NAME) AS index_names
FROM (
	SELECT
		s.INDEX_NAME,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.STATISTICS AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME = 'trackers'
	GROUP BY s.INDEX_NAME
) AS index_shapes
GROUP BY index_shapes.columns_in_order
HAVING COUNT(*) > 1;

SELECT 'P13 zero/default/nullable checks' AS section;
SELECT
	COUNT(*) AS row_count,
	COALESCE(SUM(lastchecked = 0), 0) AS zero_lastchecked,
	MIN(lastchecked) AS min_lastchecked,
	MAX(lastchecked) AS max_lastchecked,
	COALESCE(SUM(torrent IS NULL), 0) AS null_torrent,
	COALESCE(SUM(tracker IS NULL OR tracker = ''), 0) AS empty_tracker,
	COALESCE(SUM(state IS NULL), 0) AS null_state
FROM trackers;

SELECT 'P13 strict-mode column blockers' AS section;
SELECT
	c.COLUMN_NAME,
	c.COLUMN_TYPE,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT
FROM information_schema.COLUMNS AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME = 'trackers'
  AND c.IS_NULLABLE = 'NO'
  AND c.COLUMN_DEFAULT IS NULL
  AND c.EXTRA NOT LIKE '%auto_increment%'
ORDER BY c.ORDINAL_POSITION;

SELECT 'P13 EXPLAIN localhost tracker query/update' AS section;
EXPLAIN SELECT id FROM trackers WHERE torrent = 1 AND tracker = 'localhost' LIMIT 1;
EXPLAIN UPDATE trackers
	SET seeders = seeders,
	    leechers = leechers,
	    lastchecked = lastchecked,
	    state = state
	WHERE torrent = 1
	  AND tracker = 'localhost';

SELECT 'P13 EXPLAIN single torrent remote update scan' AS section;
EXPLAIN SELECT torrents.infohash, trackers.tracker
FROM trackers
LEFT JOIN torrents ON torrents.id = trackers.torrent
WHERE trackers.torrent = 1
  AND trackers.tracker <> 'localhost';

SELECT 'P13 EXPLAIN global remote tracker scan' AS section;
EXPLAIN SELECT torrents.id, torrents.infohash, trackers.tracker
FROM trackers
LEFT JOIN torrents ON torrents.id = trackers.torrent
WHERE trackers.lastchecked < 9999999999
  AND trackers.tracker <> 'localhost'
ORDER BY torrents.id DESC
LIMIT 30;

SELECT 'P13 EXPLAIN browse/index aggregate' AS section;
EXPLAIN SELECT t.id,
	COALESCE(SUM(tr.seeders), 0) AS seeders,
	COALESCE(SUM(tr.leechers), 0) AS leechers,
	COALESCE(SUM(CASE WHEN tr.tracker <> 'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count
FROM torrents AS t
LEFT JOIN trackers AS tr ON tr.torrent = t.id
WHERE t.status = 'approved'
GROUP BY t.id
ORDER BY t.added DESC
LIMIT 20;

SELECT 'P13 EXPLAIN details tracker summary' AS section;
EXPLAIN SELECT
	SUM(CASE WHEN tracker = 'localhost' THEN GREATEST(seeders, 0) ELSE 0 END) AS local_seeders_count,
	SUM(CASE WHEN tracker = 'localhost' THEN GREATEST(leechers, 0) ELSE 0 END) AS local_leechers_count,
	SUM(CASE WHEN tracker <> 'localhost' THEN GREATEST(seeders, 0) ELSE 0 END) AS external_seeders_count,
	SUM(CASE WHEN tracker <> 'localhost' THEN GREATEST(leechers, 0) ELSE 0 END) AS external_leechers_count,
	SUM(CASE WHEN tracker <> 'localhost' THEN 1 ELSE 0 END) AS external_tracker_count
FROM trackers
WHERE torrent = 1;

EXPLAIN SELECT tracker, GREATEST(seeders, 0) AS seeders, GREATEST(leechers, 0) AS leechers, lastchecked, state
FROM trackers
WHERE tracker <> 'localhost'
  AND torrent = 1
ORDER BY seeders DESC, leechers DESC, tracker ASC;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_p13_abort_if_not_safe//
CREATE PROCEDURE lt_p13_abort_if_not_safe()
BEGIN
	DECLARE v_table_count BIGINT DEFAULT 0;
	DECLARE v_duplicate_groups BIGINT DEFAULT 0;

	SELECT COUNT(*)
	INTO v_table_count
	FROM information_schema.TABLES
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'trackers'
	LIMIT 1;

	IF COALESCE(v_table_count, 0) <> 1 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P13 aborted: trackers table not found';
	END IF;

	SELECT COUNT(*)
	INTO v_duplicate_groups
	FROM (
		SELECT torrent, tracker
		FROM trackers
		GROUP BY torrent, tracker
		HAVING COUNT(*) > 1
	) AS duplicate_groups;

	IF COALESCE(v_duplicate_groups, 0) > 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'P13 aborted: duplicate torrent/tracker rows exist';
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p13_drop_redundant_unique_id_if_present//
CREATE PROCEDURE lt_p13_drop_redundant_unique_id_if_present()
BEGIN
	DECLARE v_has_primary_id BIGINT DEFAULT 0;
	DECLARE v_has_unique_id BIGINT DEFAULT 0;

	SELECT COUNT(*)
	INTO v_has_primary_id
	FROM information_schema.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'trackers'
	  AND INDEX_NAME = 'PRIMARY'
	  AND COLUMN_NAME = 'id';

	SELECT COUNT(*)
	INTO v_has_unique_id
	FROM information_schema.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'trackers'
	  AND INDEX_NAME = 'id'
	  AND NON_UNIQUE = 0
	  AND COLUMN_NAME = 'id';

	IF COALESCE(v_has_primary_id, 0) = 1 AND COALESCE(v_has_unique_id, 0) = 1 THEN
		ALTER TABLE `trackers` DROP INDEX `id`;
	END IF;
END//

DROP PROCEDURE IF EXISTS lt_p13_convert_trackers_to_innodb_if_needed//
CREATE PROCEDURE lt_p13_convert_trackers_to_innodb_if_needed()
BEGIN
	DECLARE v_engine VARCHAR(64) DEFAULT '';

	SELECT COALESCE(ENGINE, '')
	INTO v_engine
	FROM information_schema.TABLES
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'trackers'
	LIMIT 1;

	IF UPPER(v_engine) <> 'INNODB' THEN
		ALTER TABLE `trackers` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
	END IF;
END//

CALL lt_p13_abort_if_not_safe()//
CALL lt_p13_drop_redundant_unique_id_if_present()//
CALL lt_p13_convert_trackers_to_innodb_if_needed()//

DROP PROCEDURE IF EXISTS lt_p13_convert_trackers_to_innodb_if_needed//
DROP PROCEDURE IF EXISTS lt_p13_drop_redundant_unique_id_if_present//
DROP PROCEDURE IF EXISTS lt_p13_abort_if_not_safe//

DELIMITER ;

SELECT 'P13 postflight table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.ROW_FORMAT,
	t.TABLE_ROWS,
	t.AVG_ROW_LENGTH,
	t.DATA_LENGTH,
	t.INDEX_LENGTH,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.TABLES AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_NAME = 'trackers';

SELECT 'P13 postflight indexes' AS section;
SELECT
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.STATISTICS AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME = 'trackers'
GROUP BY s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.INDEX_NAME;

SELECT 'P13 postflight data checks' AS section;
SELECT
	COUNT(*) AS row_count,
	COUNT(DISTINCT CONCAT(torrent, '\0', tracker)) AS distinct_torrent_tracker,
	COALESCE(SUM(lastchecked = 0), 0) AS zero_lastchecked
FROM trackers;

SELECT 'P13 postflight global remote tracker scan EXPLAIN' AS section;
EXPLAIN SELECT torrents.id, torrents.infohash, trackers.tracker
FROM trackers
LEFT JOIN torrents ON torrents.id = trackers.torrent
WHERE trackers.lastchecked < 9999999999
  AND trackers.tracker <> 'localhost'
ORDER BY torrents.id DESC
LIMIT 30;
