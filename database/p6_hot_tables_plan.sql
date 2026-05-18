-- LiteTracker Wave P6: hot-table production readiness audit plan.
--
-- AUDIT ONLY. This file intentionally performs no ALTER/UPDATE/DELETE.
--
-- Hot/risky tables in scope:
--   peers, trackers, snatched, torrents, users, sessions, mail
--
-- Forbidden in P6:
--   - no ALTER TABLE execution;
--   - no migration apply;
--   - no PHP/code changes.
--
-- Production usage:
--   mysql -u... -p... lite < database/p6_hot_tables_plan.sql
--
-- Suggested ALTERs are listed as comments near the end. Do not uncomment until
-- backup, row counts, EXPLAIN output, and maintenance window are approved.

SELECT 'P6 hot table status' AS section;
SHOW TABLE STATUS
WHERE Name IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail');

SELECT 'P6 largest tables' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_COLLATION,
	t.TABLE_ROWS,
	t.DATA_LENGTH,
	t.INDEX_LENGTH,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
ORDER BY (t.DATA_LENGTH + t.INDEX_LENGTH) DESC, t.TABLE_NAME
LIMIT 30;

SELECT 'P6 remaining MyISAM tables' AS section;
SELECT
	t.TABLE_NAME,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
	t.TABLE_COLLATION
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.ENGINE = 'MyISAM'
ORDER BY (t.DATA_LENGTH + t.INDEX_LENGTH) DESC, t.TABLE_NAME;

SELECT 'P6 hot table indexes' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail')
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P6 hot table column charset/collation' AS section;
SELECT
	c.TABLE_NAME,
	c.COLUMN_NAME,
	c.COLUMN_TYPE,
	c.CHARACTER_SET_NAME,
	c.COLLATION_NAME,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT
FROM information_schema.columns AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail')
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;

SELECT 'P6 zero-date and nullable-date checks' AS section;
SELECT 'mail.date' AS column_name, COUNT(*) AS zero_or_null_count, SUM(`date` IS NULL) AS null_count FROM mail WHERE CAST(`date` AS CHAR) = '0000-00-00 00:00:00' OR `date` IS NULL
UNION ALL SELECT 'peers.started', COUNT(*), SUM(started IS NULL) FROM peers WHERE CAST(started AS CHAR) = '0000-00-00 00:00:00' OR started IS NULL
UNION ALL SELECT 'peers.last_action', COUNT(*), SUM(last_action IS NULL) FROM peers WHERE CAST(last_action AS CHAR) = '0000-00-00 00:00:00' OR last_action IS NULL
UNION ALL SELECT 'peers.prev_action', COUNT(*), SUM(prev_action IS NULL) FROM peers WHERE CAST(prev_action AS CHAR) = '0000-00-00 00:00:00' OR prev_action IS NULL
UNION ALL SELECT 'sessions.last_access', COUNT(*), SUM(last_access IS NULL) FROM sessions WHERE CAST(last_access AS CHAR) = '0000-00-00 00:00:00' OR last_access IS NULL
UNION ALL SELECT 'torrents.added', COUNT(*), SUM(added IS NULL) FROM torrents WHERE CAST(added AS CHAR) = '0000-00-00 00:00:00' OR added IS NULL
UNION ALL SELECT 'torrents.last_action', COUNT(*), SUM(last_action IS NULL) FROM torrents WHERE CAST(last_action AS CHAR) = '0000-00-00 00:00:00' OR last_action IS NULL
UNION ALL SELECT 'torrents.reviewed_at', COUNT(*), SUM(reviewed_at IS NULL) FROM torrents WHERE CAST(reviewed_at AS CHAR) = '0000-00-00 00:00:00' OR reviewed_at IS NULL
UNION ALL SELECT 'torrents.submitted_at', COUNT(*), SUM(submitted_at IS NULL) FROM torrents WHERE CAST(submitted_at AS CHAR) = '0000-00-00 00:00:00' OR submitted_at IS NULL
UNION ALL SELECT 'torrents.hidden_at', COUNT(*), SUM(hidden_at IS NULL) FROM torrents WHERE CAST(hidden_at AS CHAR) = '0000-00-00 00:00:00' OR hidden_at IS NULL
UNION ALL SELECT 'torrents.deleted_at', COUNT(*), SUM(deleted_at IS NULL) FROM torrents WHERE CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00' OR deleted_at IS NULL
UNION ALL SELECT 'users.last_access', COUNT(*), SUM(last_access IS NULL) FROM users WHERE CAST(last_access AS CHAR) = '0000-00-00 00:00:00' OR last_access IS NULL
UNION ALL SELECT 'users.added', COUNT(*), SUM(added IS NULL) FROM users WHERE CAST(added AS CHAR) = '0000-00-00 00:00:00' OR added IS NULL
UNION ALL SELECT 'users.birthday_date', COUNT(*), SUM(birthday_date IS NULL) FROM users WHERE CAST(birthday_date AS CHAR) = '0000-00-00' OR birthday_date IS NULL;

SELECT 'P6 duplicate data checks for unique migration readiness' AS section;
SELECT 'users.name' AS check_name, name AS duplicate_value, COUNT(*) AS duplicate_count FROM users GROUP BY name HAVING COUNT(*) > 1
UNION ALL SELECT 'users.email', email, COUNT(*) FROM users GROUP BY email HAVING COUNT(*) > 1
UNION ALL SELECT 'users.passkey', passkey, COUNT(*) FROM users WHERE passkey <> '' GROUP BY passkey HAVING COUNT(*) > 1
UNION ALL SELECT 'torrents.infohash', HEX(infohash), COUNT(*) FROM torrents GROUP BY infohash HAVING COUNT(*) > 1
UNION ALL SELECT 'trackers.torrent_tracker', CONCAT(torrent, ':', tracker), COUNT(*) FROM trackers GROUP BY torrent, tracker HAVING COUNT(*) > 1
UNION ALL SELECT 'snatched.torrent_userid', CONCAT(torrent, ':', userid), COUNT(*) FROM snatched GROUP BY torrent, userid HAVING COUNT(*) > 1
UNION ALL SELECT 'sessions.session_id', session_id, COUNT(*) FROM sessions GROUP BY session_id HAVING COUNT(*) > 1
UNION ALL SELECT 'peers.torrent_peer_id', CONCAT(torrent, ':', peer_id), COUNT(*) FROM peers GROUP BY torrent, peer_id HAVING COUNT(*) > 1;

SELECT 'P6 duplicate/equivalent indexes' AS section;
SELECT
	table_name,
	index_count,
	distinct_index_shapes
FROM (
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
		  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail')
		GROUP BY s.TABLE_NAME, s.INDEX_NAME
	) AS index_shapes
	GROUP BY table_name
) AS duplicate_index_counts
WHERE index_count <> distinct_index_shapes;

SELECT 'P6 prefix-covered indexes - review only' AS section;
SELECT
	a.table_name,
	a.index_name AS shorter_index,
	a.columns_in_order AS shorter_columns,
	b.index_name AS covering_index,
	b.columns_in_order AS covering_columns
FROM (
	SELECT
		s.TABLE_NAME AS table_name,
		s.INDEX_NAME AS index_name,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.statistics AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail')
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS a
JOIN (
	SELECT
		s.TABLE_NAME AS table_name,
		s.INDEX_NAME AS index_name,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.statistics AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'sessions', 'mail')
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS b
  ON a.table_name = b.table_name
 AND a.index_name <> b.index_name
 AND CONCAT(b.columns_in_order, ',') LIKE CONCAT(a.columns_in_order, ',%')
ORDER BY a.table_name, a.index_name, b.index_name;

SELECT 'P6 EXPLAIN probes' AS section;
EXPLAIN SELECT * FROM sessions WHERE session_id = 'p6_probe' LIMIT 1;
EXPLAIN SELECT * FROM sessions WHERE user_id = 1 ORDER BY last_access DESC LIMIT 20;
EXPLAIN SELECT * FROM mail WHERE id_user_in = 1 AND delete_in = 0 ORDER BY date DESC, id DESC LIMIT 20;
EXPLAIN SELECT * FROM peers WHERE torrent = 1 AND seeder = 1;
EXPLAIN SELECT * FROM peers WHERE torrent = 1 AND passkey = 'p6_probe' LIMIT 1;
EXPLAIN SELECT * FROM trackers WHERE torrent = 1 ORDER BY tracker;
EXPLAIN SELECT * FROM snatched WHERE torrent = 1 AND userid = 1 LIMIT 1;
EXPLAIN SELECT * FROM torrents WHERE status = 'approved' AND banned = '0' ORDER BY added DESC LIMIT 20;
EXPLAIN SELECT * FROM users WHERE passkey = 'p6_probe' LIMIT 1;

-- Suggested migration order after production review:
--
-- Low/medium risk:
--   sessions first, if production sessions table is small or can be cleared.
--   mail next, using the P3 plan after redundant index review.
--
-- Medium/high risk:
--   trackers, snatched, torrents, users only after announce/download/profile
--   smoke plans and rollback have been rehearsed.
--
-- Do not touch yet:
--   peers, unless announce write rate is low or an online schema migration tool
--   is available.
--
-- Suggested ALTERs, intentionally commented out:
--
-- ALTER TABLE `sessions` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `mail` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `trackers` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `snatched` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `torrents` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `users` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `peers` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
