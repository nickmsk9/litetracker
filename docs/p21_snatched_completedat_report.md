# P21 snatched.completedat Strict-Mode Fix

Mode: audit to tiny safe apply. No DB schema change, no engine change, no ratio formula change, and no completed-counting logic rewrite.

## A) Schema Finding

`snatched.startedat` and `snatched.completedat` are integer epoch columns:

- `startedat int NOT NULL`
- `completedat int NOT NULL`

`profile.php` confirms the expected type by reading both fields through `FROM_UNIXTIME()` for activity dates.

The existing insert path already used epoch integers:

- `startedat = time()`
- `completedat = 0`

## B) Root Cause

The completed announce path reused `$dt`, which is a quoted SQL datetime string:

```php
$dt = announce_escape(date('Y-m-d H:i:s', time()));
```

That value is correct for `torrents.last_action`, but wrong for `snatched.completedat int NOT NULL`. Under MySQL strict mode the update fails and authenticated completed announces return the generic tracker failure.

## C) Applied Fix

Only the completed-path `snatched.completedat` assignment changed:

- before: `completedat = <SQL datetime string>`
- after: `completedat = <time()>`

No schema changes were applied. Added `database/p21_snatched_completedat.sql` as report-only audit SQL.

## D) Why Accounting Semantics Are Unchanged

- The `can_count_completed` condition is unchanged.
- `finished = 1` is unchanged.
- `torrents.completed = completed + 1` is unchanged.
- uploaded/downloaded delta math is unchanged.
- user ratio updates are unchanged.
- snatched uniqueness and row creation logic are unchanged.

Only the representation of the already-intended completion timestamp now matches the existing integer schema.

## E) Smoke Result

Docker smoke after P21:

```text
PASS invalid passkey returns safe failure
PASS invalid info_hash returns failure
PASS normal announce shape returns interval and peers
PASS compact=1 response shape
PASS event=started does not crash
PASS event=stopped does not crash
PASS left=0 seeder logic does not crash
PASS scrape.php basic response
PASS authenticated fixture ready
PASS authenticated started
PASS authenticated regular announce
PASS authenticated completed
PASS authenticated stopped
PASS authenticated seeder left=0

Result: PASS (14 scenarios, 0 failed)
```

Cleanup summary:

```text
Touched rows: peers_deleted=3 tracker_restored=1 torrent_restored=1 user_restored=1 snatched_restored=0 snatched_deleted=1
```

## F) Next Safe Write-Path Refactor

With authenticated completed now covered, the next safe wave can extract event write-path helpers without changing SQL:

- peer insert/update/delete helper;
- tracker counter update helper;
- snatched update helper;
- torrent update helper.

Keep transaction/InnoDB behavior for a later wave after helper extraction.
