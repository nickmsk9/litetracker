-- =============================================================
-- Wave A: MyISAM → InnoDB migration plan
-- =============================================================
-- Purpose   : Convert all MyISAM tables to InnoDB for ACID
--             transactions, row-level locking, crash recovery.
-- Execution : Run MANUALLY — do NOT auto-execute from PHP.
-- Strategy  : Tables are grouped by risk; convert one group,
--             verify, then proceed to the next.
-- Rollback  : Each ALTER TABLE can be reversed with the
--             matching ROLLBACK statement below (Engine=MyISAM).
-- Notes     : The `peers` table is busiest; convert last, during
--             low-traffic maintenance window. Always back up first.
-- =============================================================

-- -------------------------------------------------------------
-- PRE-FLIGHT CHECKS (run before converting anything)
-- -------------------------------------------------------------
-- 1. Full backup: mysqldump --single-transaction lite > backup.sql
-- 2. Check no open transactions on target table:
--    SELECT * FROM information_schema.INNODB_TRX;
-- 3. Verify disk space: InnoDB uses ~2× more space than MyISAM.
-- 4. Schedule during low-traffic window; ALTER TABLE locks table.

-- =============================================================
-- GROUP 1: Low-risk reference/config tables (run first)
-- =============================================================

ALTER TABLE `bans`              ENGINE=InnoDB;
ALTER TABLE `categories`        ENGINE=InnoDB;
ALTER TABLE `cron`              ENGINE=InnoDB;
ALTER TABLE `faq`               ENGINE=InnoDB;
ALTER TABLE `polls`             ENGINE=InnoDB;
ALTER TABLE `polls_questions`   ENGINE=InnoDB;
ALTER TABLE `polls_voting`      ENGINE=InnoDB;
ALTER TABLE `priv`              ENGINE=InnoDB;
ALTER TABLE `retrackers`        ENGINE=InnoDB;
ALTER TABLE `schema_migrations` ENGINE=InnoDB;
ALTER TABLE `shop`              ENGINE=InnoDB;
ALTER TABLE `tags`              ENGINE=InnoDB;

-- =============================================================
-- GROUP 2: User/auth tables
-- =============================================================

ALTER TABLE `users`            ENGINE=InnoDB;
ALTER TABLE `users_blacklist`  ENGINE=InnoDB;
ALTER TABLE `bans`             ENGINE=InnoDB;
ALTER TABLE `confirm`          ENGINE=InnoDB;
ALTER TABLE `forgot`           ENGINE=InnoDB;
ALTER TABLE `friends`          ENGINE=InnoDB;
ALTER TABLE `mail`             ENGINE=InnoDB;
ALTER TABLE `sessions`         ENGINE=InnoDB;
ALTER TABLE `user_admin_notes` ENGINE=InnoDB;

-- =============================================================
-- GROUP 3: Content/torrent tables
-- =============================================================

ALTER TABLE `books`            ENGINE=InnoDB;
ALTER TABLE `files`            ENGINE=InnoDB;
ALTER TABLE `news`             ENGINE=InnoDB;
ALTER TABLE `snatched`         ENGINE=InnoDB;
ALTER TABLE `torrent_ratings`  ENGINE=InnoDB;
ALTER TABLE `torrent_views`    ENGINE=InnoDB;
ALTER TABLE `torrents`         ENGINE=InnoDB;
ALTER TABLE `trackers`         ENGINE=InnoDB;

-- =============================================================
-- GROUP 4: Comment & moderation tables
-- =============================================================

ALTER TABLE `comment_edit_history`  ENGINE=InnoDB;
ALTER TABLE `comment_pins`          ENGINE=InnoDB;
ALTER TABLE `comment_reactions`     ENGINE=InnoDB;
ALTER TABLE `comments_faq`          ENGINE=InnoDB;
ALTER TABLE `comments_news`         ENGINE=InnoDB;
ALTER TABLE `comments_reports`      ENGINE=InnoDB;
ALTER TABLE `comments_torrents`     ENGINE=InnoDB;
ALTER TABLE `comments_users`        ENGINE=InnoDB;
ALTER TABLE `comments_users_reports`ENGINE=InnoDB;
ALTER TABLE `moderation_log`        ENGINE=InnoDB;
ALTER TABLE `notifications`         ENGINE=InnoDB;

-- =============================================================
-- GROUP 5: Activity/analytics tables (convert last)
-- =============================================================

ALTER TABLE `birthday_rewards` ENGINE=InnoDB;
ALTER TABLE `chat`             ENGINE=InnoDB;
ALTER TABLE `search_query`     ENGINE=InnoDB;

-- =============================================================
-- GROUP 6: peers — highest-write table (maintenance window only)
-- =============================================================
-- WARNING: This table is written on every tracker announce.
-- Convert during a maintenance window with announce disabled,
-- or use pt-online-schema-change for zero-downtime conversion.
--
-- Option A (maintenance window):
--   1. Disable announce (set maintenance mode or block /announce.php)
--   2. ALTER TABLE `peers` ENGINE=InnoDB;
--   3. Re-enable announce
--
-- Option B (zero-downtime with Percona Toolkit):
--   pt-online-schema-change --alter "ENGINE=InnoDB" D=lite,t=peers \
--     --execute --no-drop-old-table

-- ALTER TABLE `peers` ENGINE=InnoDB;  -- uncomment after step above

-- =============================================================
-- ROLLBACK STATEMENTS (use if any group causes problems)
-- =============================================================
-- To revert a specific table to MyISAM:
--   ALTER TABLE `<tablename>` ENGINE=MyISAM;
--
-- To revert all at once (emergency):
--   SELECT CONCAT('ALTER TABLE `', table_name, '` ENGINE=MyISAM;')
--   FROM information_schema.TABLES
--   WHERE table_schema = 'lite'
--     AND engine = 'InnoDB';
-- Then copy and run the output.

-- =============================================================
-- CHARSET CLEANUP (optional, run after InnoDB conversion)
-- Standardise all tables to utf8mb4/unicode_ci
-- =============================================================
-- Run this only after successful InnoDB conversion and smoke test.
--
-- SELECT CONCAT(
--   'ALTER TABLE `', table_name, '`'
--   ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
-- )
-- FROM information_schema.TABLES
-- WHERE table_schema = 'lite'
--   AND (character_set_name != 'utf8mb4'
--        OR table_collation != 'utf8mb4_unicode_ci');
