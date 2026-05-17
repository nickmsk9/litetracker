-- LiteTracker Wave P8: redundant index audit plan.
--
-- AUDIT ONLY.
-- No executable DROP statements are present in this file.
--
-- Scope:
--   mail, comments_* tables, comment_* support tables, notifications,
--   search_query, and support tables changed in P4/P5.
--
-- Hot tables peers/trackers/snatched/torrents/users are intentionally not
-- included for drop recommendations in P8.

SELECT 'P8 scoped table status' AS section;
SELECT
	t.TABLE_NAME,
	t.ENGINE,
	t.TABLE_ROWS,
	ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 4) AS size_mb
FROM information_schema.tables AS t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND (
	t.TABLE_NAME = 'mail'
	OR t.TABLE_NAME LIKE 'comments\_%'
	OR t.TABLE_NAME IN (
		'comment_reactions', 'comment_pins', 'comment_edit_history',
		'notifications', 'search_query',
		'birthday_rewards', 'books', 'confirm', 'cron', 'files', 'forgot',
		'friends', 'moderation_log', 'polls', 'polls_questions', 'retrackers',
		'torrent_ratings', 'user_admin_notes', 'users_blacklist'
	)
  )
ORDER BY t.TABLE_NAME;

SELECT 'P8 scoped SHOW INDEX equivalent' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND (
	s.TABLE_NAME = 'mail'
	OR s.TABLE_NAME LIKE 'comments\_%'
	OR s.TABLE_NAME IN (
		'comment_reactions', 'comment_pins', 'comment_edit_history',
		'notifications', 'search_query',
		'birthday_rewards', 'books', 'confirm', 'cron', 'files', 'forgot',
		'friends', 'moderation_log', 'polls', 'polls_questions', 'retrackers',
		'torrent_ratings', 'user_admin_notes', 'users_blacklist'
	)
  )
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P8 duplicate and left-prefix candidates' AS section;
SELECT
	a.table_name,
	a.index_name AS candidate_index,
	a.non_unique AS candidate_non_unique,
	a.columns_in_order AS candidate_columns,
	b.index_name AS covering_index,
	b.non_unique AS covering_non_unique,
	b.columns_in_order AS covering_columns
FROM (
	SELECT
		s.TABLE_NAME AS table_name,
		s.INDEX_NAME AS index_name,
		MAX(s.NON_UNIQUE) AS non_unique,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.statistics AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND (
		s.TABLE_NAME = 'mail'
		OR s.TABLE_NAME LIKE 'comments\_%'
		OR s.TABLE_NAME IN (
			'comment_reactions', 'comment_pins', 'comment_edit_history',
			'notifications', 'search_query',
			'birthday_rewards', 'books', 'confirm', 'cron', 'files', 'forgot',
			'friends', 'moderation_log', 'polls', 'polls_questions', 'retrackers',
			'torrent_ratings', 'user_admin_notes', 'users_blacklist'
		)
	  )
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS a
JOIN (
	SELECT
		s.TABLE_NAME AS table_name,
		s.INDEX_NAME AS index_name,
		MAX(s.NON_UNIQUE) AS non_unique,
		GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
	FROM information_schema.statistics AS s
	WHERE s.TABLE_SCHEMA = DATABASE()
	  AND (
		s.TABLE_NAME = 'mail'
		OR s.TABLE_NAME LIKE 'comments\_%'
		OR s.TABLE_NAME IN (
			'comment_reactions', 'comment_pins', 'comment_edit_history',
			'notifications', 'search_query',
			'birthday_rewards', 'books', 'confirm', 'cron', 'files', 'forgot',
			'friends', 'moderation_log', 'polls', 'polls_questions', 'retrackers',
			'torrent_ratings', 'user_admin_notes', 'users_blacklist'
		)
	  )
	GROUP BY s.TABLE_NAME, s.INDEX_NAME
) AS b
  ON a.table_name = b.table_name
 AND a.index_name <> b.index_name
 AND CONCAT(b.columns_in_order, ',') LIKE CONCAT(a.columns_in_order, ',%')
ORDER BY a.table_name, a.index_name, b.index_name;

SELECT 'P8 hot tables noticed but excluded from drop recommendations' AS section;
SELECT
	s.TABLE_NAME,
	s.INDEX_NAME,
	s.NON_UNIQUE,
	GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS columns_in_order
FROM information_schema.statistics AS s
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('peers', 'trackers', 'snatched', 'torrents', 'users')
GROUP BY s.TABLE_NAME, s.INDEX_NAME, s.NON_UNIQUE
ORDER BY s.TABLE_NAME, s.INDEX_NAME;

SELECT 'P8 EXPLAIN evidence probes before any future drop' AS section;

EXPLAIN SELECT * FROM mail
WHERE id_user_in = 1 AND delete_in = 0 AND reading = 0
ORDER BY date DESC, id DESC
LIMIT 20;

EXPLAIN SELECT * FROM mail
WHERE id_user_out = 1 AND delete_out = 0
ORDER BY date DESC, id DESC
LIMIT 20;

EXPLAIN SELECT * FROM (
	SELECT m.*
	FROM mail AS m
	WHERE (
		(m.id_user_in = 1 AND m.id_user_out = 2 AND m.delete_in = 0)
		OR (m.id_user_out = 1 AND m.id_user_in = 2 AND m.delete_out = 0)
	)
	ORDER BY m.date DESC, m.id DESC
	LIMIT 10
) AS conversation_slice
ORDER BY date ASC, id ASC;

EXPLAIN SELECT *
FROM comments_torrents
WHERE id_torrents = 1
ORDER BY date ASC, id ASC
LIMIT 20;

EXPLAIN SELECT COUNT(*) AS cnt, COALESCE(SUM(rating), 0) AS total_rating
FROM torrent_ratings
WHERE torrent_id = 1;

EXPLAIN SELECT id
FROM confirm
WHERE id_user = 1
LIMIT 1;

EXPLAIN SELECT COUNT(*) AS c
FROM notifications
WHERE user_id = 1 AND is_read = 0 AND is_archived = 0;

EXPLAIN SELECT *
FROM notifications
WHERE user_id = 1 AND is_archived = 0
ORDER BY id DESC
LIMIT 20;

EXPLAIN SELECT *
FROM search_query
WHERE id_user = 1
ORDER BY last_date DESC
LIMIT 10;

-- Candidate DROP statements for a future apply wave.
-- They are intentionally commented out in P8.
--
-- Safer candidates after production EXPLAIN confirms replacement indexes:
-- DROP INDEX `idx_mail_conversation_in` ON `mail`;
-- DROP INDEX `idx_mail_conversation_out` ON `mail`;
-- DROP INDEX `idx_mail_in_visible_read_date` ON `mail`;
-- DROP INDEX `idx_mail_out_visible_date` ON `mail`;
-- DROP INDEX `idx_comments_torrents_object_date` ON `comments_torrents`;
--
-- Risky candidates. Keep report-only until stronger production evidence:
-- DROP INDEX `torrent_rating` ON `torrent_ratings`;
--
-- Destructive duplicate unique cleanup. Do not apply without DDL review:
-- DROP INDEX `id` ON `confirm`;
