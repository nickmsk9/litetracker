# LiteTracker Wave P9 Sessions InnoDB Report

## Preflight Result

Local preflight before applying P9:

- table: `sessions`
- engine before: `MyISAM`
- collation: `cp1251_general_ci`
- row format before: `Dynamic`
- local row count: 542
- local size: about 0.0655 MB
- zero `last_access`: 0
- null `last_access`: 0
- legacy `last_access` default before rehearsal: `0000-00-00 00:00:00`
- max `session_id` length: 32
- max `user_agent` length: 151
- max `php_self` length: 32

The local dataset is small enough for rehearsal. No unexpected schema blocker was found.

Rehearsal found one MySQL 8.4 DDL blocker: even with no zero-date rows, the `last_access` column had a legacy zero DATETIME default. The P9 SQL now drops that default before the InnoDB conversion. Inserts already provide `last_access`, so this is a low-risk compatibility fix for the migration path.

## Query Pattern Audit

Session writes use:

- `INSERT INTO sessions (...) ON DUPLICATE KEY UPDATE ...`
- unique key: `session_id`

Session reads/use cases:

- online check: `user_id,last_access`;
- active-user bonus: `last_access` plus `user_id > 0`;
- admin sessions page: `ORDER BY last_access DESC`;
- admin/session cleanup: full table delete when explicitly requested.

Current indexes:

- `PRIMARY(id)`
- unique `session_id(session_id)`
- `idx_sessions_user_access(user_id,last_access)`
- `idx_sessions_last_access(last_access)`

No duplicate or redundant indexes were found.

## Cleanup Audit

Local stale sessions:

- older than 7 days: 57
- older than 30 days: 0

P9 does not delete sessions automatically. Production may optionally clear stale sessions before migration to reduce rebuild time.

## SQL File

Created `database/p9_sessions_innodb_apply.sql`.

The script contains:

- backup/dump notes;
- preflight table status;
- data and large-column checks;
- cleanup candidate counts without deleting;
- per-user session distribution;
- index inventory;
- EXPLAIN for session touch/read/admin/cron paths;
- guarded removal of the legacy `last_access` zero-date default;
- guarded `ALTER TABLE sessions ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- postflight table status, indexes, and EXPLAIN;
- rollback note.

## Local Apply Status

Applied locally on the Docker MySQL 8.4 database after adjusting the SQL for the legacy `last_access` default blocker.

Result:

- engine after: `InnoDB`;
- collation unchanged: `cp1251_general_ci`;
- row format after: `Dynamic`;
- `last_access` remains `datetime NOT NULL`, now with no default;
- indexes preserved;
- no rows were deleted.

Verification:

- `git diff --check` passed;
- lint passed for `system/bootstrap/php_compat.php`, `system/functions/functions.php`, `system/init.php`;
- `SHOW TABLE STATUS` confirmed `sessions` is `InnoDB`;
- `SHOW INDEX` confirmed all session indexes are still present;
- smoke routes returned `200` without PHP fatal/warning output:
  - `/`;
  - `login.php`;
  - `signup.php`;
  - `browse.php`;
  - `details.php?id=1`;
  - `profile.php?id=1`.

## Production Risk

Risk: low/medium.

Why:

- MyISAM to InnoDB rebuilds the table;
- active sessions may be updated while the migration runs;
- failure or rollback may log users out or lose current session tracking;
- table uses `cp1251` text fields and P9 intentionally does not change charset/collation.

## Exact Production Checklist

1. Run `database/p9_sessions_innodb_apply.sql` on staging first.
2. Take full DB backup.
3. Take table-specific dump:
   `mysqldump --single-transaction --routines --triggers DB_NAME sessions > sessions.before_innodb.sql`
4. Capture production `SHOW TABLE STATUS LIKE 'sessions'`.
5. Capture production `SHOW INDEX FROM sessions`.
6. Confirm zero/null `last_access` checks are 0.
7. Check `last_access` column default. If it is `0000-00-00 00:00:00`, drop the default before conversion.
8. Decide whether stale sessions may be cleared before migration.
9. Confirm production row count and size fit the maintenance window.
10. Capture EXPLAIN output for session lookup, online check, admin listing, and active-user cron paths.
11. Apply during a quiet window.
12. Run postflight `SHOW TABLE STATUS`, `SHOW INDEX`, and EXPLAIN.
13. Smoke test `/`, `login.php`, `signup.php`, `browse.php`, `details.php?id=1`, `profile.php?id=1`.
14. Watch PHP logs, MySQL error log, slow query log, and lock waits.

## Deferred Cleanup

Deferred:

- stale session deletion;
- charset/collation cleanup;
- changing `user_agent` / `php_self` from `TEXT` to bounded varchar.

These are not required for InnoDB migration and should be separate waves.

## Next Wave Recommendation

After P9 succeeds in production, migrate the remaining non-hot support MyISAM tables before touching announce/accounting tables. Keep `peers`, `trackers`, `snatched`, `torrents`, and `users` delayed until each has a dedicated rehearsal.
