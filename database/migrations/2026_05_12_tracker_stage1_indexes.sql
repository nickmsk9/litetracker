-- ===================================================================
-- LiteTracker - Stage 1: Safe Tracker Indexes
-- Date: 2026-05-12
-- Purpose: Add missing indexes for announce query optimization
-- Risk: LOW (new indexes only, no data modification)
-- Reversible: Yes (can DROP indexes if needed)
-- ===================================================================

-- Optimize peers table for announce queries
-- Note: IF NOT EXISTS for indexes is not supported in MySQL 8.x (only MariaDB).
-- Run only if indexes are missing (verify with SHOW INDEX FROM peers).
ALTER TABLE peers ADD INDEX idx_torrent_userid      (torrent, userid);
ALTER TABLE peers ADD INDEX idx_torrent_passkey     (torrent, passkey);
ALTER TABLE peers ADD INDEX idx_torrent_last_action (torrent, last_action);

-- Optimize snatched table for user stats queries
ALTER TABLE snatched ADD INDEX idx_userid_finished (userid, finished);

-- Optimize users table for passkey lookups
ALTER TABLE users ADD INDEX idx_users_passkey (passkey);

-- Verify indexes created
-- SELECT * FROM information_schema.STATISTICS WHERE TABLE_NAME='peers' AND TABLE_SCHEMA=DATABASE();
