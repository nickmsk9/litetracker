-- ============================================================
-- Stage 3: tracker accounting safety & integer overflow fixes
-- ============================================================
-- Safe to apply on live data:
--   * no data deletion
--   * no destructive changes
--   * MODIFY COLUMN is in-place on MyISAM (online-ish for small tables)
-- Tested on MySQL 8.4 / MyISAM
-- ============================================================

-- ============================================================
-- 1. INTEGER OVERFLOW FIX: torrents.downloaded and torrents.completed
--
-- Current type: INT NOT NULL (max ~2.1 billion)
-- Risk: popular torrents can exceed 2^31-1 downloads/completions
--       at which point MySQL wraps to negative (signed) or caps (unsigned)
-- Fix: BIGINT UNSIGNED (max ~18.4 * 10^18)
-- ============================================================

ALTER TABLE `torrents`
  MODIFY COLUMN `downloaded` BIGINT UNSIGNED NOT NULL DEFAULT '0',
  MODIFY COLUMN `completed`  BIGINT UNSIGNED NOT NULL DEFAULT '0';

-- ============================================================
-- 2. SIZE COLUMN SAFETY: torrents.size
--
-- Current type: BIGINT NOT NULL (signed) - allows negatives in theory
-- Fix: BIGINT UNSIGNED NOT NULL - matches peers.to_go semantics
-- Safe: existing value (1565294090) is positive
-- ============================================================

ALTER TABLE `torrents`
  MODIFY COLUMN `size` BIGINT UNSIGNED NOT NULL DEFAULT '0';

-- ============================================================
-- 3. USERS UPLOAD/DOWNLOAD: users.uploaded, users.downloaded
--
-- Current type: BIGINT NOT NULL (signed, max ~9.2 * 10^18)
-- Risk: VERY LOW - signed BIGINT max is 9.2 * 10^18, far above any realistic tracker
-- Existing data check: max values seen are ~130GB (130350163636) - safe
-- Action: NO CHANGE - signed BIGINT is fine, changing to UNSIGNED
--          would be disruptive and provides no practical benefit
-- ============================================================

-- (no ALTER for users.uploaded/downloaded - signed BIGINT is sufficient)


-- ============================================================
-- 4. DIAGNOSTIC: Peer duplicate detection
--
-- The peers table has UNIQUE KEY (torrent, peer_id) which prevents
-- strict duplicates on that combination. However, the same user
-- can have multiple peer entries for the same torrent (different peer_ids).
-- The announce.php already limits this via passkey check (max 1 leecher / 3 seeders).
--
-- Run these queries to check for anomalies before cleanup:
-- ============================================================

/*
-- Peers: same user/torrent combo with multiple peer_ids (normal but check count)
SELECT torrent, userid, COUNT(*) AS cnt
FROM peers
WHERE userid > 0
GROUP BY torrent, userid
HAVING cnt > 3
ORDER BY cnt DESC
LIMIT 50;

-- Peers: orphaned entries (no matching torrent)
SELECT p.id, p.torrent, p.userid, p.last_action
FROM peers p
LEFT JOIN torrents t ON p.torrent = t.id
WHERE t.id IS NULL
LIMIT 50;

-- Peers: orphaned entries (no matching user)
SELECT p.id, p.torrent, p.userid, p.last_action
FROM peers p
LEFT JOIN users u ON p.userid = u.id
WHERE p.userid > 0 AND u.id IS NULL
LIMIT 50;

-- Snatched: check for consistency (snatched.finished=1 but torrents.completed mismatch)
SELECT torrent, COUNT(*) AS real_completed
FROM snatched
WHERE finished = 1
GROUP BY torrent
ORDER BY real_completed DESC
LIMIT 20;

-- Compare with torrents.completed:
SELECT t.id, t.name, t.completed AS stored_completed,
       COUNT(s.id) AS real_completed
FROM torrents t
LEFT JOIN snatched s ON t.id = s.torrent AND s.finished = 1
GROUP BY t.id
HAVING ABS(stored_completed - real_completed) > 0
LIMIT 20;
*/


-- ============================================================
-- 5. DEDUPLICATION NOTE: peers unique constraint
--
-- peers has UNIQUE (torrent, peer_id) - adequate for BitTorrent.
-- peer_id is generated per-session by the client, so same peer_id
-- from different IPs is extremely rare in practice.
--
-- Adding (torrent, peer_id, ip, port) UNIQUE is NOT recommended:
--   - Would prevent reconnect on IP change mid-session
--   - Adds write overhead on every announce
--   - Existing code uses "UPDATE ... WHERE peer_id=X" not "WHERE peer_id+ip=X"
--
-- snatched already has UNIQUE (torrent, userid) - correct.
-- ============================================================


-- ============================================================
-- 6. INNODB CONVERSION NOTE (NOT APPLIED - READ BEFORE ENABLING)
--
-- Converting tracker tables to InnoDB enables proper transactions,
-- which would improve accounting atomicity significantly.
--
-- Risk assessment:
--   peers, trackers: HIGH WRITE VOLUME - InnoDB row locking is better
--                    than MyISAM table locking under concurrency
--   snatched: MODERATE WRITE - InnoDB safer for accounting
--   torrents: READ-HEAVY - InnoDB fine
--
-- To convert (run ONLY after backup and during low-traffic window):
-- ============================================================

/*
-- Backup first:
--   mysqldump -u root lite peers trackers snatched torrents > backup_before_innodb.sql

ALTER TABLE `peers`     ENGINE = InnoDB;
ALTER TABLE `trackers`  ENGINE = InnoDB;
ALTER TABLE `snatched`  ENGINE = InnoDB;
ALTER TABLE `torrents`  ENGINE = InnoDB;

-- After InnoDB conversion, the announce.php can wrap completed accounting
-- in a transaction to prevent race conditions. See announce.php comments.
*/
