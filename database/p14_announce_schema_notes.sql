-- LiteTracker P14 announce schema notes
-- Report-only SQL. Do not run as a migration.
-- Allowed shape: SHOW/SELECT/EXPLAIN only. Suggested ALTER examples are comments.

SELECT 'P14 table status' AS section;
SHOW TABLE STATUS WHERE Name IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'bans', 'retrackers');

SELECT 'P14 key columns' AS section;
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLLATION_NAME, COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users', 'bans', 'retrackers')
  AND COLUMN_NAME IN (
    'id', 'torrent', 'tracker', 'seeders', 'leechers', 'lastchecked', 'state',
    'peer_id', 'ip', 'port', 'uploaded', 'downloaded', 'uploadoffset', 'downloadoffset',
    'to_go', 'seeder', 'started', 'last_action', 'connectable', 'userid', 'agent',
    'finishedat', 'passkey', 'infohash', 'banned', 'size', 'added', 'completed',
    'completedat', 'startedat', 'finished', 'name', 'email', 'class', 'slots',
    'first', 'last', 'announce_url', 'mask', 'sort'
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT 'P14 indexes: peers' AS section;
SHOW INDEX FROM peers;

SELECT 'P14 indexes: trackers' AS section;
SHOW INDEX FROM trackers;

SELECT 'P14 indexes: snatched' AS section;
SHOW INDEX FROM snatched;

SELECT 'P14 indexes: torrents' AS section;
SHOW INDEX FROM torrents;

SELECT 'P14 indexes: users' AS section;
SHOW INDEX FROM users;

SELECT 'P14 indexes: bans' AS section;
SHOW INDEX FROM bans;

SELECT 'P14 indexes: retrackers' AS section;
SHOW INDEX FROM retrackers;

SELECT 'P14 duplicate and null checks' AS section;

SELECT 'peers duplicate torrent/peer_id' AS check_name, COUNT(*) AS duplicate_groups
FROM (
  SELECT torrent, peer_id
  FROM peers
  GROUP BY torrent, peer_id
  HAVING COUNT(*) > 1
) AS d;

SELECT 'peers zero/null date checks' AS check_name,
       SUM(started = '0000-00-00 00:00:00') AS started_zero,
       SUM(last_action = '0000-00-00 00:00:00') AS last_action_zero,
       SUM(started IS NULL) AS started_null,
       SUM(last_action IS NULL) AS last_action_null
FROM peers;

SELECT 'trackers duplicate torrent/tracker' AS check_name, COUNT(*) AS duplicate_groups
FROM (
  SELECT torrent, tracker
  FROM trackers
  GROUP BY torrent, tracker
  HAVING COUNT(*) > 1
) AS d;

SELECT 'snatched duplicate torrent/userid' AS check_name, COUNT(*) AS duplicate_groups
FROM (
  SELECT torrent, userid
  FROM snatched
  GROUP BY torrent, userid
  HAVING COUNT(*) > 1
) AS d;

SELECT 'snatched nullable/epoch checks' AS check_name,
       SUM(userid IS NULL) AS userid_null,
       SUM(startedat = 0) AS startedat_zero,
       SUM(completedat = 0) AS completedat_zero,
       SUM(finished NOT IN (0, 1)) AS finished_other
FROM snatched;

SELECT 'torrents duplicate infohash' AS check_name, COUNT(*) AS duplicate_groups
FROM (
  SELECT infohash
  FROM torrents
  GROUP BY infohash
  HAVING COUNT(*) > 1
) AS d;

SELECT 'torrents date checks' AS check_name,
       SUM(added = '0000-00-00 00:00:00') AS added_zero,
       SUM(last_action = '0000-00-00 00:00:00') AS last_action_zero,
       SUM(added IS NULL) AS added_null,
       SUM(last_action IS NULL) AS last_action_null
FROM torrents;

SELECT 'users passkey checks' AS check_name,
       COUNT(*) AS users_total,
       SUM(passkey IS NULL OR passkey = '') AS empty_passkey,
       COUNT(DISTINCT passkey) AS distinct_passkeys
FROM users;

SELECT 'users duplicate nonempty passkey' AS check_name, COUNT(*) AS duplicate_groups
FROM (
  SELECT passkey
  FROM users
  WHERE passkey IS NOT NULL AND passkey <> ''
  GROUP BY passkey
  HAVING COUNT(*) > 1
) AS d;

SELECT 'users duplicate name/email candidates' AS check_name,
       (SELECT COUNT(*) FROM (SELECT name FROM users WHERE name <> '' GROUP BY name HAVING COUNT(*) > 1) AS n) AS duplicate_names,
       (SELECT COUNT(*) FROM (SELECT email FROM users WHERE email <> '' GROUP BY email HAVING COUNT(*) > 1) AS e) AS duplicate_emails;

SELECT 'bans invalid ranges' AS check_name, COUNT(*) AS invalid_ranges
FROM bans
WHERE first IS NOT NULL AND last IS NOT NULL AND first > last;

SELECT 'retrackers empty announce_url' AS check_name, COUNT(*) AS empty_urls
FROM retrackers
WHERE announce_url IS NULL OR announce_url = '';

SELECT 'P14 schema drift check: users.slots used by announce_fetch_user_by_passkey' AS section;
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'slots';

-- Current code attempts this shape, but leave it commented so this report
-- remains runnable when users.slots is absent:
-- EXPLAIN SELECT id, slots FROM users WHERE passkey = 'p14_probe_passkey_0000000000000000' LIMIT 1;

SELECT 'P14 EXPLAIN: announce user stats lookup' AS section;
EXPLAIN SELECT id, uploaded, downloaded, class FROM users WHERE passkey = 'p14_probe_passkey_0000000000000000' LIMIT 1;

SELECT 'P14 EXPLAIN: IP ban lookup' AS section;
EXPLAIN SELECT *
FROM bans
WHERE '0' >= first AND '0' <= last;

SELECT 'P14 EXPLAIN: torrent lookup as current code builds it' AS section;
EXPLAIN SELECT torrents.id, torrents.banned, torrents.size,
               (trackers.seeders + trackers.leechers) AS numpeers,
               UNIX_TIMESTAMP(torrents.added) AS ts
FROM torrents
LEFT JOIN trackers ON torrents.id = trackers.torrent
WHERE torrents.infohash = '0000000000000000000000000000000000000000'
  AND trackers.tracker = 'localhost'
LIMIT 1;

SELECT 'P14 EXPLAIN: torrent lookup with binary hash comparison candidate' AS section;
EXPLAIN SELECT torrents.id, torrents.banned, torrents.size,
               (trackers.seeders + trackers.leechers) AS numpeers,
               UNIX_TIMESTAMP(torrents.added) AS ts
FROM torrents
LEFT JOIN trackers ON torrents.id = trackers.torrent
WHERE torrents.infohash = UNHEX('0000000000000000000000000000000000000000')
  AND trackers.tracker = 'localhost'
LIMIT 1;

SELECT 'P14 EXPLAIN: peer pool' AS section;
EXPLAIN SELECT seeder, peer_id, ip, port, uploaded, downloaded, userid,
               UNIX_TIMESTAMP(last_action) AS prevts,
               UNIX_TIMESTAMP(NOW()) AS nowts,
               last_action
FROM peers
WHERE torrent = 1
ORDER BY last_action DESC
LIMIT 200;

SELECT 'P14 EXPLAIN: self peer' AS section;
EXPLAIN SELECT seeder, peer_id, ip, port, uploaded, downloaded, userid,
               UNIX_TIMESTAMP(last_action) AS prevts,
               UNIX_TIMESTAMP(NOW()) AS nowts,
               last_action
FROM peers
WHERE torrent = 1 AND peer_id = '-P14PROBE-1234567890'
LIMIT 1;

SELECT 'P14 EXPLAIN: passkey slot count' AS section;
EXPLAIN SELECT COUNT(*) AS cnt
FROM peers
WHERE torrent = 1 AND passkey = 'p14_probe_passkey_0000000000000000';

SELECT 'P14 EXPLAIN: snatched completion lookup' AS section;
EXPLAIN SELECT finished
FROM snatched
WHERE torrent = 1 AND userid = 1
LIMIT 1;

SELECT 'P14 EXPLAIN: local tracker counter lookup' AS section;
EXPLAIN SELECT seeders, leechers
FROM trackers
WHERE torrent = 1 AND tracker = 'localhost';

SELECT 'P14 EXPLAIN: scrape current query without local tracker filter' AS section;
EXPLAIN SELECT torrents.id, torrents.infohash, torrents.completed, trackers.seeders, trackers.leechers
FROM torrents
LEFT JOIN trackers ON torrents.id = trackers.torrent
WHERE torrents.infohash = '0000000000000000000000000000000000000000'
LIMIT 1;

SELECT 'P14 EXPLAIN: scrape candidate with local tracker filter' AS section;
EXPLAIN SELECT torrents.id, torrents.infohash, torrents.completed, trackers.seeders, trackers.leechers
FROM torrents
LEFT JOIN trackers ON torrents.id = trackers.torrent AND trackers.tracker = 'localhost'
WHERE torrents.infohash = '0000000000000000000000000000000000000000'
LIMIT 1;

SELECT 'P14 EXPLAIN: remote tracker batch scan' AS section;
EXPLAIN SELECT torrents.id, torrents.infohash, trackers.tracker
FROM trackers
LEFT JOIN torrents ON torrents.id = trackers.torrent
WHERE trackers.lastchecked < UNIX_TIMESTAMP() - 3600
  AND trackers.tracker <> 'localhost'
ORDER BY torrents.id DESC
LIMIT 50;

SELECT 'P14 EXPLAIN: autoclean stale peer scan' AS section;
EXPLAIN SELECT id
FROM peers
WHERE last_action < '2000-01-01 00:00:00'
ORDER BY last_action ASC
LIMIT 500;

SELECT 'P14 EXPLAIN: autoclean peer recount' AS section;
EXPLAIN SELECT torrent, seeder, COUNT(*) AS c
FROM peers
GROUP BY torrent, seeder;

SELECT 'P14 EXPLAIN: details peers page' AS section;
EXPLAIN SELECT *
FROM peers
WHERE torrent = 1
ORDER BY seeder DESC;

-- Suggested future examples only. Do not execute in P14.
-- ALTER TABLE users ADD UNIQUE KEY uq_users_passkey (passkey);
-- ALTER TABLE snatched MODIFY userid int NOT NULL DEFAULT 0;
-- ALTER TABLE snatched MODIFY completedat int NOT NULL DEFAULT 0;
-- ALTER TABLE trackers ADD KEY idx_trackers_remote_scan (tracker, lastchecked, torrent);
-- ALTER TABLE peers ENGINE=InnoDB;
