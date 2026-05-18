# LiteTracker Wave P8 Redundant Index Audit

## Scope

Audited:

- `mail`
- `comments_*`
- `comment_reactions`, `comment_pins`, `comment_edit_history`
- `notifications`
- `search_query`
- support tables changed in P4/P5

Hot tables `peers`, `trackers`, `snatched`, `torrents`, `users` were only listed separately. P8 does not make drop recommendations for them.

## Safe Candidates

These look safe to consider in a later apply wave, but still require production `EXPLAIN` before dropping.

| Table | Candidate index | Covered by | Why likely redundant | Risk | Evidence needed |
|---|---|---|---|---|---|
| `mail` | `idx_mail_conversation_in` | `idx_mail_in_out_visible_date_id` | Exact same column sequence: `id_user_in,id_user_out,delete_in,date,id`. | Low/medium | Production EXPLAIN for dialog count, latest slice, load-all, restore/delete paths. |
| `mail` | `idx_mail_conversation_out` | `idx_mail_out_in_visible_date_id` | Exact same column sequence: `id_user_out,id_user_in,delete_out,date,id`. | Low/medium | Same dialog-path EXPLAIN evidence. |
| `mail` | `idx_mail_in_visible_read_date` | `idx_mail_in_visible_read_date_id` | Left-prefix covered by same columns plus `id`. | Low | EXPLAIN unread count, inbox incoming branch, mark-read queries. |
| `mail` | `idx_mail_out_visible_date` | `idx_mail_out_visible_date_id` | Left-prefix covered by same columns plus `id`. | Low | EXPLAIN outgoing branch and conversation list queries. |
| `comments_torrents` | `idx_comments_torrents_object_date` | `idx_comments_torrents_object_date_id` | Left-prefix covered by same columns plus `id`; render queries order by `date,id`. | Low/medium | Production EXPLAIN for details comments, AJAX stream, threaded list. |

## Risky Candidates

| Table | Candidate index | Covered by | Why risky | Evidence needed |
|---|---|---|---|---|
| `torrent_ratings` | `torrent_rating` | `torrent_user` | `torrent_user(torrent_id,user_id)` covers `torrent_id`, but rating aggregate queries may be sensitive to narrower index size on production. | `EXPLAIN ANALYZE` for rating stats on high-vote torrents; compare handler reads and latency before/after on staging. |
| `confirm` | unique `id` | `PRIMARY(id)` | Duplicate unique index, but it is a destructive unique-index cleanup on legacy signup table. | Confirm table remains unused/empty in production; DDL review; staging apply. |

## Do-not-drop Indexes

Keep these unless a later targeted audit proves otherwise:

- all `PRIMARY` indexes;
- all unique business constraints:
  - `birthday_rewards.user_year`
  - `comment_pins.context_pin`
  - `comment_reactions.user_comment_reaction`
  - `friends.userfriend`
  - `torrent_ratings.torrent_user`
  - `users_blacklist.user_blocked_unique`
  - `confirm.id_user`
- `notifications` indexes: no obvious duplicate; EXPLAIN shows different paths using `user_read_created`, `idx_notifications_user_archive_id`, and related indexes.
- `search_query` indexes: no obvious duplicate; `idx_search_query_user_last` supports user recent suggestions, `idx_search_query_last` supports global/admin ordering, text prefix index supports search text lookup.
- report/moderation indexes on `comments_reports`, `comments_users_reports`, `moderation_log`.
- parent/thread indexes on comments.
- `books(id_user,id_torrent)` and `books(id_torrent,id_user)` both support opposite lookup directions.

## Hot Tables Noticed But Excluded

P8 noticed likely redundancy outside scope:

- `trackers` has `PRIMARY(id)` plus unique `id(id)`;
- `peers.torrent` is prefix-covered by several compound indexes.

No drop plan is proposed for these in P8 because hot announce tables are excluded.

## Evidence Needed Before Apply

Before any drop migration:

1. Run `database/p8_redundant_indexes_plan.sql` on production.
2. Save `SHOW INDEX` output.
3. Save `EXPLAIN` / preferably `EXPLAIN ANALYZE` for every query listed in the SQL plan.
4. Check slow query log for candidate table/index names.
5. Stage the exact `DROP INDEX` set and rerun smoke tests.
6. Drop one table's candidate indexes per migration, not all at once.
7. Keep rollback SQL ready to recreate indexes.

## SQL Plan

Created `database/p8_redundant_indexes_plan.sql`.

The file contains:

- scoped table status;
- index inventory;
- duplicate/left-prefix detection;
- hot-table index listing for awareness only;
- EXPLAIN preflight probes;
- future `DROP INDEX` statements as comments only.

No executable `DROP INDEX` statements are present.

## Next Wave Recommendation

P9 should be a tiny guarded apply wave for only the safest `mail` duplicate indexes, after production P8 evidence confirms the replacement indexes are selected. Keep `torrent_ratings` and `confirm` as report-only until stronger evidence is collected.
