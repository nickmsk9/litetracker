# LiteTracker Wave P13 Trackers InnoDB Report

## Scope

Table in scope: `trackers`.

Explicitly not touched: `peers`, `snatched`, `torrents`, `users`, `sessions`, `mail`, and PHP logic.

## Preflight Result

Local preflight before rehearsal:

| Item | Value |
|---|---:|
| Engine | MyISAM |
| Rows | 5 |
| Size | 0.0102 MB |
| Avg row length | 83 |
| Collation | `utf8mb3_general_ci` |
| Duplicate `(torrent, tracker)` groups | 0 |
| `lastchecked = 0` rows | 0 |
| Nullable column issues | none |
| Zero-date fields | none; `lastchecked` is unsigned epoch int |

Columns:

- `id int unsigned NOT NULL AUTO_INCREMENT`
- `torrent int unsigned NOT NULL`
- `tracker varchar(255) NOT NULL DEFAULT 'localhost'`
- `seeders int unsigned NOT NULL DEFAULT 0`
- `leechers int unsigned NOT NULL DEFAULT 0`
- `lastchecked int unsigned NOT NULL DEFAULT 0`
- `state varchar(300) NOT NULL`

Strict-mode notes:

- `torrent` and `state` are `NOT NULL` with no default. Current code paths provide both values.
- No zero-date blockers exist.

## Code Path Audit

Write paths:

- `announce.php`: updates localhost tracker seed/leech counters by `(torrent, tracker='localhost')`.
- `update.peers.php`: selects and updates remote trackers, either for one torrent or batch cron mode.
- `edit.php`: deletes and reinserts tracker rows when a torrent file is replaced.
- `upload.php` via `lt_torrent_store_trackers()` in `system/functions/functions.benc.php`: inserts external tracker rows.
- `autoclean.php` and `scripts/tracker_maintenance.php`: recount/update localhost tracker counters.

Read paths:

- `index.php`, `browse.php`: aggregate seed/leech counts and external tracker count.
- `details.php` / `system/functions/functions.details.php`: tracker summary and external tracker rows.
- `download.php`: does not read `trackers` directly, but download/magnet behavior is adjacent to tracker URLs.
- `my.releases.php`, `my.book.php`, `profile.php`: aggregate counts through joins/subqueries.
- `update.peers.php`: batch remote scan.

## Index Decision

Existing preflight indexes:

- `PRIMARY KEY (id)`
- `UNIQUE KEY torrent (torrent, tracker)`
- `UNIQUE KEY id (id)`

Decision:

- Drop redundant `UNIQUE KEY id(id)` locally. It is fully duplicated by `PRIMARY KEY(id)`.
- Keep `UNIQUE(torrent, tracker)`. It protects the application's update assumptions.
- Do not add a remote-scan index in P13.

Remote scan finding:

`update.peers.php` batch scan uses:

```sql
WHERE trackers.lastchecked < ...
  AND trackers.tracker <> 'localhost'
ORDER BY torrents.id DESC
LIMIT ...
```

Local EXPLAIN shows a full scan/filesort, but the local table has only 5 rows. That is not enough evidence to add an index. Production should capture EXPLAIN and row distribution first. Candidate for production-only review:

```sql
-- ALTER TABLE `trackers` ADD KEY `idx_trackers_lastchecked_tracker_torrent` (`lastchecked`, `tracker`, `torrent`);
```

## SQL Artifact

Created `database/p13_trackers_innodb_apply.sql`.

It includes:

- backup/dump notes;
- preflight table, column, index, duplicate, zero/default, strict-mode checks;
- EXPLAIN probes for localhost tracker update, single-torrent remote scan, batch remote scan, browse/index aggregate, and details tracker summary;
- guarded duplicate abort;
- guarded redundant `id` index drop;
- guarded `ALTER TABLE trackers ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- postflight checks;
- rollback notes.

## Local Apply Status

P13 was applied locally against the Docker MySQL database on 2026-05-17.

Postflight locally:

- `trackers` engine: InnoDB.
- redundant unique `id` index absent.
- `PRIMARY(id)` and `UNIQUE(torrent, tracker)` retained.
- row count remains 5.
- `lastchecked = 0` remains 0.

No forbidden hot tables were altered by the migration script.

## Local Verification

Passed:

- local SQL rehearsal;
- postflight `SHOW TABLE STATUS` / `SHOW INDEX`;
- smoke `/` HTTP 200;
- smoke `browse.php` HTTP 200;
- smoke `details.php?id=1` HTTP 200;
- authenticated smoke `download.php?id=1` HTTP 200 and returned a BitTorrent file;
- smoke `profile.php?id=1` HTTP 200;
- `update.peers.php?id=1&ajax=1` with local cron token returned `{"success":true,"updated_trackers":4}`;
- `git diff --check`.

Announce smoke note: an existing pure `AnnounceTest` test-safe path exists, but local `vendor/bin/phpunit` is not installed, so it was not run.

## Production Risk

Risk is medium.

Data semantics are low risk: no duplicate `(torrent, tracker)` rows and no zero-date fields. Operational risk is from table rebuild locking while announce/update jobs may read or update tracker counters.

Main production concern:

- `announce.php` updates localhost row.
- `update.peers.php` batch remote checks can overlap the migration.
- `edit.php`/`upload.php` can rewrite tracker rows for a torrent.

## Exact Production Checklist

1. Take full DB backup.
2. Take `trackers` table dump:
   `mysqldump --single-transaction --routines --triggers DB_NAME trackers > p13_trackers.before_innodb.sql`
3. Run P13 preflight sections only on production first.
4. Confirm `duplicate_torrent_tracker_groups = 0`.
5. Confirm production row count and size fit the maintenance window.
6. Capture production EXPLAIN for localhost update, batch remote scan, browse/index aggregate, and details tracker summary.
7. Pause scheduler or ensure `update.peers.php` is not running.
8. Reduce/avoid announce traffic if possible.
9. Apply during a quiet window.
10. Run postflight `SHOW TABLE STATUS` and `SHOW INDEX`.
11. Smoke `/`, `browse.php`, `details.php?id=1`, `download.php?id=1`, `profile.php?id=1`.
12. Run `update.peers.php` only with cron authorization and only if remote tracker checks are safe in that environment.
13. Watch DB locks, PHP logs, announce errors, and tracker count drift.

## Next Wave Recommendation

After production P13, rehearse `snatched` next. It is smaller and structurally simpler than `torrents`, `users`, or `peers`, but production must first confirm `userid IS NULL = 0` and duplicate `(torrent, userid)` groups are absent.
