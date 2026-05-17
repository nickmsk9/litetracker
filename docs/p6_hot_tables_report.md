# LiteTracker Wave P6 Hot Tables Audit

## Current State

Local database after P4/P5 still has these MyISAM tables:

| Table | Engine | Rows local | Size local | Indexes | Risk | Recommended action |
|---|---|---:|---:|---:|---|---|
| `sessions` | MyISAM | 354 | 0.0655 MB | 4 | Low/medium | Safest next hot-ish migration if sessions can be cleared or users can be logged out. |
| `torrents` | MyISAM | 1 | 0.0184 MB | 10 | High | Delay; central browse/details/download table. |
| `users` | MyISAM | 3 | 0.0104 MB | 4 | High | Delay; auth/profile/cache/announce identity dependency. |
| `trackers` | MyISAM | 5 | 0.0101 MB | 3 | Medium/high | Delay until announce/update.peers smoke is rehearsed. |
| `mail` | MyISAM | 30 | 0.0052 MB | 9 | Medium | Use P3 plan after production backup/window and redundant index review. |
| `peers` | MyISAM | 0 | 0.0010 MB | 10 | Very high | Do not touch yet; announce hot write table. |
| `snatched` | MyISAM | 0 | 0.0010 MB | 3 | High | Delay; announce completion/accounting table. |

Largest local tables overall are `notifications` (already InnoDB), `sessions`, `torrents`, `users`, `trackers`, then migrated comments/support tables. Production ordering must be based on production sizes, not local demo rows.

## Remaining MyISAM Blockers

Hot/risky blockers:

- `peers`
- `trackers`
- `snatched`
- `torrents`
- `users`
- `sessions`
- `mail`

Other remaining MyISAM tables outside the hot-table focus:

- `news`, `torrent_views`, `search_query`, `priv`, `categories`, `shop`, `tags`, `bans`, `chat`, `faq`, `polls_voting`

## Data Readiness

Zero-date checks found no invalid zero DATETIME values in hot table columns.

Nullable date findings:

- `torrents.hidden_at` is NULL where not hidden.
- `torrents.deleted_at` is NULL where not deleted.

These are expected and should remain nullable.

Duplicate data checks found no local duplicates for:

- `users.name`
- `users.email`
- `users.passkey`
- `torrents.infohash`
- `trackers(torrent,tracker)`
- `snatched(torrent,userid)`
- `sessions.session_id`
- `peers(torrent,peer_id)`

## Index Readiness

Report-only redundant/equivalent indexes:

- `mail` has overlapping P1/P2 indexes:
  - `idx_mail_conversation_in` equals `idx_mail_in_out_visible_date_id`
  - `idx_mail_conversation_out` equals `idx_mail_out_in_visible_date_id`
  - `idx_mail_in_visible_read_date` is covered by `idx_mail_in_visible_read_date_id`
  - `idx_mail_out_visible_date` is covered by `idx_mail_out_visible_date_id`
- `trackers` has duplicate `PRIMARY(id)` and unique `id(id)`.
- `peers.torrent` is prefix-covered by several compound torrent indexes, but this is hot announce territory and should not be dropped without production `EXPLAIN`.

No destructive index cleanup is included in P6.

## Charset/Collation Issues

The database still has mixed legacy charsets:

- `sessions` uses `cp1251` text/session fields.
- `peers.peer_id` is `cp1251`, while `ip/passkey/agent` are `utf8mb3`.
- `torrents` mixes `utf8mb3_bin`, `utf8mb3_unicode_ci`, and `utf8mb3_general_ci`.
- `users` mixes `utf8mb3_bin` and `utf8mb3_unicode_ci`.
- `mail` is `utf8mb3_bin`.

P6 does not recommend charset conversion together with engine conversion. Keep charset changes as a separate, tested wave.

## Migration Order

Low risk next:

1. `sessions`, if production policy allows clearing sessions or accepting user logout risk.

Medium risk:

2. `mail`, using the P3 migration plan, after backup and redundant-index review.
3. Remaining non-hot MyISAM support tables such as `news`, `torrent_views`, `search_query`, `priv`, `categories`, `shop`, `tags`, `faq`, `polls_voting`.

High risk:

4. `trackers`
5. `snatched`
6. `torrents`
7. `users`

Do not touch yet:

8. `peers`, unless announce traffic is paused or an online schema migration tool is used.

## Hot Table Detail

### sessions

Why risky: every request can create/update session tracking; migration can interrupt active sessions.

Preflight:

- row count and size;
- zero dates on `last_access`;
- duplicate `session_id`;
- stale sessions older than retention window;
- `EXPLAIN` for `session_id`, `user_id,last_access`, and cleanup queries.

Backup:

- logical table dump is enough if sessions are disposable;
- otherwise include full DB backup.

Lock/rebuild risk:

- table rebuild; likely acceptable if session table is small or cleared first.

Smoke:

- `/`
- `login.php`
- `profile.php?id=1`
- authenticated `admin.php`
- logout/login cycle

### mail

Why risky: user-facing inbox writes and unread counters; current table also has redundant indexes.

Preflight:

- P3 preflight;
- zero dates on `date`;
- count rows and max message body size;
- duplicate/equivalent index review;
- `EXPLAIN` inbox summary and dialog queries.

Backup:

- full DB backup plus `mail` table dump.

Lock/rebuild risk:

- medium; rebuild blocks mail writes.

Smoke:

- `my.mail.php`
- inbox route
- dialog route
- send message
- mark read/delete/restore

### trackers

Why risky: announce/update.peers reads/writes tracker aggregate counts.

Preflight:

- duplicate `(torrent, tracker)`;
- row count and size;
- `EXPLAIN` by `torrent`;
- compare aggregate counts with `peers` if possible.

Backup:

- full DB backup plus `trackers` dump.

Lock/rebuild risk:

- medium/high on production because update.peers and announce may touch it frequently.

Smoke:

- `announce.php` with valid passkey/info_hash test;
- `update.peers.php`;
- `details.php?id=1`;
- `browse.php`;
- `download.php?id=1`

### snatched

Why risky: announce completion/accounting table; unique `(torrent, userid)` must remain clean.

Preflight:

- duplicate `(torrent, userid)`;
- row count and size;
- `EXPLAIN` by `torrent,userid` and `userid,finished`;
- check nullable `userid`.

Backup:

- full DB backup plus `snatched` dump.

Lock/rebuild risk:

- high if production has many historical snatch rows.

Smoke:

- announce started/completed/stopped;
- profile stats;
- details stats;
- ratio/accounting checks.

### torrents

Why risky: central browse/details/download/upload/edit/moderation table with large text columns and many indexes.

Preflight:

- duplicate `infohash`;
- zero dates on `added`, `last_action`, status dates;
- row count and data/index size;
- max text/metadata sizes;
- `EXPLAIN` browse filters, details lookup, owner/status filters.

Backup:

- full DB backup plus `torrents` dump and torrent file storage backup.

Lock/rebuild risk:

- high; table rebuild can block most site workflows.

Smoke:

- `/`
- `browse.php`
- `details.php?id=1`
- `download.php?id=1`
- upload/edit moderation workflows
- announce scrape/details count sanity

### users

Why risky: auth, permissions, profile, passkey, cache invalidation, announce identity.

Preflight:

- duplicate `name`, `email`, `passkey`;
- zero dates on `last_access`, `added`, birthday edge cases;
- row count and size;
- `EXPLAIN` by id/name/email/passkey;
- inspect money-like float columns `bonus`, `voice` for later wave.

Backup:

- full DB backup plus encrypted/offline `users` dump due password/passkey sensitivity.

Lock/rebuild risk:

- high; blocks login/profile/announce identity lookups.

Smoke:

- login/logout;
- profile;
- admin user edit;
- announce with passkey;
- mail identity;
- cache invalidation checks.

### peers

Why risky: hottest announce write table; high churn insert/update/delete.

Preflight:

- current row count at peak;
- duplicate `(torrent, peer_id)`;
- zero dates on `started`, `last_action`, `prev_action`;
- `EXPLAIN` announce paths by torrent/passkey/userid/seeder/last_action;
- write rate from logs/metrics.

Backup:

- full DB backup not enough for minimal downtime if table is hot;
- consider table can be rebuilt from announces, but coordinate with tracker semantics.

Lock/rebuild risk:

- very high. A direct `ALTER TABLE peers ENGINE=InnoDB` can stall announce.

Smoke:

- announce started/completed/stopped;
- scrape;
- update.peers.php;
- details seed/leech counts;
- tracker response latency under concurrent announce.

## SQL Plan

Created `database/p6_hot_tables_plan.sql`.

It contains:

- table status inventory;
- largest tables;
- remaining MyISAM tables;
- index inventory;
- charset/collation inventory;
- zero-date checks;
- duplicate data checks;
- duplicate/prefix index reports;
- EXPLAIN probes;
- suggested ALTER statements commented out.

No hot-table ALTER statements are executed.

## Production Checklist

Before any hot-table migration:

1. Take full DB backup.
2. Take table-specific logical dump.
3. Capture production `SHOW TABLE STATUS`.
4. Capture production `SHOW INDEX`.
5. Run P6 duplicate/zero-date checks.
6. Run `EXPLAIN` for the target table's hot paths.
7. Choose maintenance window.
8. Pause or reduce cron/announce activity where relevant.
9. Run migration on staging copy first.
10. Prepare rollback or restore plan.
11. Run smoke tests immediately after migration.
12. Watch error logs, slow query log, and DB locks.

## Next Wave Recommendation

P7 should migrate `sessions` first only if product accepts logout/session churn risk, or if stale sessions are cleared before the migration.

If session churn is not acceptable, P7 should apply the already prepared `mail` InnoDB plan during a quiet window.
