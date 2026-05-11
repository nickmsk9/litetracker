-- ============================================================
-- Stage 4: details.php query optimization indexes
-- ============================================================
-- Safe: additive only, no data modification
-- Applied on MySQL 8.4 / MyISAM
-- ============================================================

-- files(id_torrent): used in details.php for file list queries
-- SELECT * FROM files WHERE id_torrent = X ORDER BY id
ALTER TABLE `files` ADD INDEX `idx_files_torrent` (`id_torrent`);

-- ============================================================
-- Already present (no action needed):
--   comments_torrents: idx_comments_torrents_object_date (id_torrents, date)
--   comments_torrents: idx_comments_torrents_deleted     (is_deleted, date)
--   comment_reactions: comment_reaction                  (context_type, comment_id, reaction)
--   comment_reactions: user_comment_reaction             (context_type, comment_id, user_id) UNIQUE
--   comment_pins:      context_pin                       (context_type, context_id) UNIQUE
--   trackers:          torrent                           (torrent, tracker)
--   torrent_views:     torrent_id                        (torrent_id)
--   torrent_views:     torrent_visitor                   (torrent_id, visitor_hash) UNIQUE
--   books:             idx_books_user_torrent            (id_user, id_torrent)
-- ============================================================
