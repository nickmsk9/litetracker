# P25 Snatched InnoDB Rehearsal Report

## A) Preflight Result

Local `snatched` state before rehearsal:

- Engine: `MyISAM`
- Row count: `0`
- Data length: `0`
- Index length: `1024`
- Collation: `utf8mb3_general_ci`
- Primary key: `id`
- Unique key: `snatch (torrent, userid)`
- Secondary key: `idx_userid_finished (userid, finished)`
- Duplicate `(torrent, userid)` groups: `0`
- `userid IS NULL` rows: `0`
- `startedat` / `completedat`: `int` epoch columns
- zero/negative epoch anomalies in current data: `0`
- uploaded/downloaded negative values: `0`
- finished distribution: empty table locally

Compatibility checks:

- Current announce completed path writes `completedat = time()` and `startedat = time()` epochs.
- Profile downloaded tab reads `completedat`/`startedat` with `FROM_UNIXTIME(...)`.
- The existing unique `(torrent, userid)` index is compatible with InnoDB for current local data.
- `userid` is nullable in schema, but local data has no NULL rows. Production must keep the NULL guard as a hard stop before applying.

Current queries reviewed:

- `system/functions/functions.announce.php`: `SELECT finished`, `INSERT INTO snatched ... ON DUPLICATE KEY UPDATE`, `UPDATE snatched SET ... WHERE torrent = ... AND userid = ...`
- `profile.php`: downloaded history query orders by `completedat`, `startedat`, `id`
- `tests/announce/announce_smoke.php`: P23 cleanup/accounting assertions
- `scripts/seed_demo_activity.php`: demo data insert/delete only

## B) Local Apply Status

Local apply was safe and was performed with `database/p25_snatched_innodb_apply.sql`.

Guard values before local ALTER:

- duplicate groups: `0`
- NULL userid rows: `0`
- epoch type blockers: `0`
- unique `snatch` index columns present: `2`
- row count: `0`, below rehearsal threshold `1000000`

Postflight:

- Engine: `InnoDB`
- Row format: `Dynamic`
- Row count after smoke cleanup: `0`
- Duplicate groups after smoke cleanup: `0`
- NULL userid rows after smoke cleanup: `0`
- Indexes preserved: primary `id`, unique `snatch`, `idx_userid_finished`
- Charset/collation preserved: `utf8mb3` / `utf8mb3_general_ci`

No PHP compatibility fix was needed.

## C) Production Risk

- Production must be dumped/backed up before any engine conversion.
- Production must hard-stop on any duplicate `(torrent, userid)` groups.
- Production must hard-stop on any `userid IS NULL` rows because unique indexes allow multiple NULL values.
- Table size and lock/conversion time must be reviewed; `ALTER TABLE ... ENGINE=InnoDB` can be disruptive.
- The nullable `userid` column is a schema risk to track, though P25 intentionally does not change columns.

## D) Transaction-Readiness Notes

The local rehearsal confirms `snatched` can run as InnoDB while the current announce completed accounting remains green.

This prepares one future transaction participant, but transactions are still not enabled in announce. The completed path still writes across `users`, `torrents`, `trackers`, `peers`, and `snatched`; full transaction readiness requires the remaining write tables to be InnoDB or a carefully staged mixed-engine plan.

## E) Next Wave

P26 should audit the remaining completed-path tables (`users`, `torrents`, `trackers`, `peers`) for engine, duplicate/key safety, and transaction suitability before enabling any `BEGIN`/`COMMIT` in announce.

## Verification

Commands run:

```sh
docker compose exec -T db mysql -uroot lite < database/p25_snatched_innodb_apply.sql
docker compose exec -T php php tests/announce/announce_smoke.php
git diff --check
```

Smoke result:

```text
Result: PASS (15 scenarios, 0 failed)
PASS authenticated completed - accounting deltas asserted; completed_count=yes; tracker seeders=2 leechers=1
```
