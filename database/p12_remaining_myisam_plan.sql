-- LiteTracker Wave P12: remaining MyISAM audit and migration map.
--
-- AUDIT ONLY. Do not apply migrations from this file.
--
-- Allowed statements only:
--   SHOW TABLE STATUS, SHOW FULL COLUMNS, SHOW INDEX, EXPLAIN, COUNT,
--   and information_schema SELECT queries.
--
-- Hot tables remain deferred:
--   peers, trackers, snatched, torrents, users

SELECT 'P12 sql mode' AS section;
SELECT @@sql_mode AS sql_mode;

SELECT 'P12 remaining MyISAM tables' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_ROWS,
	t.AVG_ROW_LENGTH,
	t.DATA_LENGTH,
	t.INDEX_LENGTH,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb,
	t.TABLE_COLLATION,
	t.ROW_FORMAT
FROM information_schema.TABLES AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.ENGINE = 'MyISAM'
ORDER BY (t.DATA_LENGTH + t.INDEX_LENGTH) DESC, t.TABLE_NAME;

SELECT 'P12 remaining MyISAM table status' AS section;
SHOW TABLE STATUS
WHERE Engine = 'MyISAM';

SELECT 'P12 full columns: peers' AS section;
SHOW FULL COLUMNS FROM `peers`;
SELECT 'P12 full columns: trackers' AS section;
SHOW FULL COLUMNS FROM `trackers`;
SELECT 'P12 full columns: snatched' AS section;
SHOW FULL COLUMNS FROM `snatched`;
SELECT 'P12 full columns: torrents' AS section;
SHOW FULL COLUMNS FROM `torrents`;
SELECT 'P12 full columns: users' AS section;
SHOW FULL COLUMNS FROM `users`;

SELECT 'P12 indexes: peers' AS section;
SHOW INDEX FROM `peers`;
SELECT 'P12 indexes: trackers' AS section;
SHOW INDEX FROM `trackers`;
SELECT 'P12 indexes: snatched' AS section;
SHOW INDEX FROM `snatched`;
SELECT 'P12 indexes: torrents' AS section;
SHOW INDEX FROM `torrents`;
SELECT 'P12 indexes: users' AS section;
SHOW INDEX FROM `users`;

SELECT 'P12 date/default/float/strict blockers' AS section;
SELECT
	c.TABLE_NAME,
	c.COLUMN_NAME,
	c.DATA_TYPE,
	c.COLUMN_TYPE,
	c.IS_NULLABLE,
	c.COLUMN_DEFAULT,
	c.CHARACTER_SET_NAME,
	c.COLLATION_NAME
FROM information_schema.COLUMNS AS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users')
  AND (
	c.DATA_TYPE IN ('date', 'datetime', 'timestamp', 'float', 'double', 'real')
	OR (c.DATA_TYPE IN ('varchar', 'text', 'tinytext', 'enum') AND c.CHARACTER_SET_NAME IS NOT NULL)
	OR (c.IS_NULLABLE = 'NO' AND c.COLUMN_DEFAULT IS NULL AND c.EXTRA NOT LIKE '%auto_increment%')
  )
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;

SELECT 'P12 zero/null date checks' AS section;
SELECT
	'peers.started' AS column_name,
	COUNT(*) AS row_count,
	COALESCE(SUM(CAST(`started` AS CHAR) = '0000-00-00 00:00:00'), 0) AS zero_count,
	COALESCE(SUM(`started` IS NULL), 0) AS null_count,
	MIN(NULLIF(CAST(`started` AS CHAR), '0000-00-00 00:00:00')) AS min_nonzero_value,
	MAX(NULLIF(CAST(`started` AS CHAR), '0000-00-00 00:00:00')) AS max_nonzero_value
FROM `peers`
UNION ALL
SELECT 'peers.last_action', COUNT(*), COALESCE(SUM(CAST(`last_action` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`last_action` IS NULL), 0), MIN(NULLIF(CAST(`last_action` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`last_action` AS CHAR), '0000-00-00 00:00:00')) FROM `peers`
UNION ALL
SELECT 'peers.prev_action', COUNT(*), COALESCE(SUM(CAST(`prev_action` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`prev_action` IS NULL), 0), MIN(NULLIF(CAST(`prev_action` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`prev_action` AS CHAR), '0000-00-00 00:00:00')) FROM `peers`
UNION ALL
SELECT 'torrents.added', COUNT(*), COALESCE(SUM(CAST(`added` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`added` IS NULL), 0), MIN(NULLIF(CAST(`added` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`added` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'torrents.last_action', COUNT(*), COALESCE(SUM(CAST(`last_action` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`last_action` IS NULL), 0), MIN(NULLIF(CAST(`last_action` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`last_action` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'torrents.reviewed_at', COUNT(*), COALESCE(SUM(CAST(`reviewed_at` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`reviewed_at` IS NULL), 0), MIN(NULLIF(CAST(`reviewed_at` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`reviewed_at` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'torrents.submitted_at', COUNT(*), COALESCE(SUM(CAST(`submitted_at` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`submitted_at` IS NULL), 0), MIN(NULLIF(CAST(`submitted_at` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`submitted_at` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'torrents.hidden_at', COUNT(*), COALESCE(SUM(CAST(`hidden_at` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`hidden_at` IS NULL), 0), MIN(NULLIF(CAST(`hidden_at` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`hidden_at` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'torrents.deleted_at', COUNT(*), COALESCE(SUM(CAST(`deleted_at` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`deleted_at` IS NULL), 0), MIN(NULLIF(CAST(`deleted_at` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`deleted_at` AS CHAR), '0000-00-00 00:00:00')) FROM `torrents`
UNION ALL
SELECT 'users.last_access', COUNT(*), COALESCE(SUM(CAST(`last_access` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`last_access` IS NULL), 0), MIN(NULLIF(CAST(`last_access` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`last_access` AS CHAR), '0000-00-00 00:00:00')) FROM `users`
UNION ALL
SELECT 'users.added', COUNT(*), COALESCE(SUM(CAST(`added` AS CHAR) = '0000-00-00 00:00:00'), 0), COALESCE(SUM(`added` IS NULL), 0), MIN(NULLIF(CAST(`added` AS CHAR), '0000-00-00 00:00:00')), MAX(NULLIF(CAST(`added` AS CHAR), '0000-00-00 00:00:00')) FROM `users`
UNION ALL
SELECT 'users.birthday_date', COUNT(*), COALESCE(SUM(CAST(`birthday_date` AS CHAR) = '0000-00-00'), 0), COALESCE(SUM(`birthday_date` IS NULL), 0), MIN(NULLIF(CAST(`birthday_date` AS CHAR), '0000-00-00')), MAX(NULLIF(CAST(`birthday_date` AS CHAR), '0000-00-00')) FROM `users`;

SELECT 'P12 integer epoch zero checks' AS section;
SELECT 'trackers.lastchecked' AS column_name, COUNT(*) AS row_count, COALESCE(SUM(`lastchecked` = 0), 0) AS zero_count FROM `trackers`
UNION ALL
SELECT 'snatched.startedat', COUNT(*), COALESCE(SUM(`startedat` = 0), 0) FROM `snatched`
UNION ALL
SELECT 'snatched.completedat', COUNT(*), COALESCE(SUM(`completedat` = 0), 0) FROM `snatched`;

SELECT 'P12 duplicate business-key checks' AS section;
SELECT 'users.name' AS check_name, COUNT(*) AS duplicate_groups FROM (SELECT `name` FROM `users` GROUP BY `name` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'users.email', COUNT(*) FROM (SELECT `email` FROM `users` GROUP BY `email` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'users.passkey_nonempty', COUNT(*) FROM (SELECT `passkey` FROM `users` WHERE `passkey` <> '' GROUP BY `passkey` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'torrents.infohash', COUNT(*) FROM (SELECT `infohash` FROM `torrents` GROUP BY `infohash` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'trackers.torrent_tracker', COUNT(*) FROM (SELECT `torrent`, `tracker` FROM `trackers` GROUP BY `torrent`, `tracker` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'snatched.torrent_userid', COUNT(*) FROM (SELECT `torrent`, `userid` FROM `snatched` GROUP BY `torrent`, `userid` HAVING COUNT(*) > 1) AS d
UNION ALL
SELECT 'peers.torrent_peer_id', COUNT(*) FROM (SELECT `torrent`, `peer_id` FROM `peers` GROUP BY `torrent`, `peer_id` HAVING COUNT(*) > 1) AS d;

SELECT 'P12 empty business-key checks' AS section;
SELECT
	'users' AS table_name,
	COALESCE(SUM(`name` = ''), 0) AS empty_name,
	COALESCE(SUM(`email` = ''), 0) AS empty_email,
	COALESCE(SUM(`passkey` = ''), 0) AS empty_passkey
FROM `users`;

SELECT 'P12 equivalent index shapes' AS section;
SELECT
	index_shapes.TABLE_NAME,
	index_shapes.columns_in_order,
	COUNT(*) AS index_count,
	GROUP_CONCAT(index_shapes.INDEX_NAME ORDER BY index_shapes.INDEX_NAME) AS index_names
FROM (
	SELECT
		s.TABLE_NAME,
		s.INDEX_NAME,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.STATISTICS AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users')
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS index_shapes
GROUP BY index_shapes.TABLE_NAME, index_shapes.columns_in_order
HAVING COUNT(*) > 1
ORDER BY index_shapes.TABLE_NAME, index_shapes.columns_in_order;

SELECT 'P12 prefix-covered indexes, review only' AS section;
SELECT
	a.TABLE_NAME,
	a.INDEX_NAME AS shorter_index,
	a.columns_in_order AS shorter_columns,
	b.INDEX_NAME AS covering_index,
	b.columns_in_order AS covering_columns
FROM (
	SELECT
		s.TABLE_NAME,
		s.INDEX_NAME,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.STATISTICS AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users')
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS a
JOIN (
	SELECT
		s.TABLE_NAME,
		s.INDEX_NAME,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.STATISTICS AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users')
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS b
  ON a.TABLE_NAME = b.TABLE_NAME
 AND a.INDEX_NAME <> b.INDEX_NAME
 AND CONCAT(b.columns_in_order, ',') LIKE CONCAT(a.columns_in_order, ',%')
ORDER BY a.TABLE_NAME, a.INDEX_NAME, b.INDEX_NAME;

SELECT 'P12 EXPLAIN users' AS section;
EXPLAIN SELECT id FROM `users` WHERE `name` = 'admin' LIMIT 1;
EXPLAIN SELECT id FROM `users` WHERE `email` = 'admin@admin.com' LIMIT 1;
EXPLAIN SELECT id, uploaded, downloaded, class FROM `users` WHERE `passkey` = 'p12_probe' LIMIT 1;

SELECT 'P12 EXPLAIN torrents' AS section;
EXPLAIN SELECT * FROM `torrents` WHERE `status` = 'approved' AND `banned` = '0' ORDER BY `added` DESC LIMIT 20;
EXPLAIN SELECT * FROM `torrents` WHERE `id_category` = 1 AND `banned` = '0' ORDER BY `added` DESC LIMIT 20;
EXPLAIN SELECT * FROM `torrents` WHERE `id_user` = 1 ORDER BY `added` DESC LIMIT 20;

SELECT 'P12 EXPLAIN trackers' AS section;
EXPLAIN SELECT * FROM `trackers` WHERE `torrent` = 1 ORDER BY `tracker`;
EXPLAIN SELECT * FROM `trackers` WHERE `tracker` <> 'localhost' AND `lastchecked` < 9999999999 ORDER BY `torrent` DESC LIMIT 30;

SELECT 'P12 EXPLAIN snatched' AS section;
EXPLAIN SELECT * FROM `snatched` WHERE `torrent` = 1 AND `userid` = 1 LIMIT 1;
EXPLAIN SELECT * FROM `snatched` WHERE `userid` = 1 AND `finished` = 1;

SELECT 'P12 EXPLAIN peers' AS section;
EXPLAIN SELECT * FROM `peers` WHERE `torrent` = 1;
EXPLAIN SELECT * FROM `peers` WHERE `torrent` = 1 AND `seeder` = 1;
EXPLAIN SELECT * FROM `peers` WHERE `torrent` = 1 AND `passkey` = 'p12_probe' LIMIT 1;
EXPLAIN SELECT * FROM `peers` WHERE `torrent` = 1 AND `peer_id` = 'p12_probe' LIMIT 1;
EXPLAIN SELECT id FROM `peers` WHERE `last_action` < '2026-01-01 00:00:00' ORDER BY `last_action` ASC LIMIT 500;

-- Migration examples only. Keep commented until a later wave approves one.
--
-- Trackers rehearsal candidate:
-- ALTER TABLE `trackers` DROP INDEX `id`;
-- ALTER TABLE `trackers` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
--
-- Later hot waves:
-- ALTER TABLE `snatched` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `torrents` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `users` MODIFY `bonus` DECIMAL(12,4) NOT NULL DEFAULT 0;
-- ALTER TABLE `users` MODIFY `voice` DECIMAL(12,4) NOT NULL DEFAULT 0;
-- ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_name` (`name`);
-- ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_email` (`email`);
-- ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_passkey` (`passkey`);
-- ALTER TABLE `users` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
-- ALTER TABLE `peers` ENGINE=InnoDB, ROW_FORMAT=DYNAMIC;
