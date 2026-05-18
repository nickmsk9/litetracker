# P19 Announce Authenticated Smoke Coverage

Mode: real apply for test/smoke tooling only. No production announce behavior, DB schema, accounting logic, ratio logic, or protocol response logic changed.

## A) Scenarios Added

Added authenticated smoke scenarios to `tests/announce/announce_smoke.php`:

- authenticated fixture readiness
- authenticated `event=started`
- authenticated regular announce with same peer id
- authenticated `event=completed` with `left=0`
- authenticated `event=stopped`
- authenticated seeder announce with `left=0`

The existing guest/failure/scrape scenarios remain in place.

## B) Fixture Strategy

The harness discovers existing local fixtures only:

- first `users` row with non-empty `passkey`
- first `torrents` row with non-empty `infohash`
- matching local `trackers` row where `tracker='localhost'`

It does not create users, torrents, trackers, or schema. If a fixture is missing or incompatible, authenticated scenarios are marked `SKIP` with a concrete reason.

Current local skip reason:

```text
users.slots column is missing; current authenticated announce lookup cannot run
```

That matches the known announce/schema drift: production auth lookup currently selects `users.slots`.

## C) Safety / Cleanup Strategy

The authenticated flow uses peer ids prefixed with `-P19SMK-`.

The harness snapshots and restores:

- local `trackers` counters for the selected torrent
- selected torrent `completed` and `last_action`
- selected user `uploaded` and `downloaded`
- selected `snatched` row for `(torrent, userid)` if it existed before the run

Cleanup deletes only generated smoke peer ids:

- `-P15SMK-%`
- `-P19SMK-%`

If the selected `snatched` row did not exist before the run, the harness deletes only that exact `(torrent, userid)` row after the run. It never deletes users, torrents, trackers, or unrelated peers.

The regular/completed/stopped authenticated chain ages only its own smoke peer row between announces to avoid the current 15-minute announce wait.

## D) Current Result

Local Docker run:

```text
PASS invalid passkey returns safe failure
PASS invalid info_hash returns failure
PASS normal announce shape returns interval and peers
PASS compact=1 response shape
PASS event=started does not crash
PASS event=stopped does not crash
PASS left=0 seeder logic does not crash
PASS scrape.php basic response
SKIP authenticated fixture ready
SKIP authenticated started
SKIP authenticated regular announce
SKIP authenticated completed
SKIP authenticated stopped
SKIP authenticated seeder left=0

Result: PASS (14 scenarios, 0 failed)
Touched rows: peers_deleted=2 tracker_restored=1 torrent_restored=1 user_restored=1 snatched_restored=0 snatched_deleted=0
```

Post-run check confirmed no `-P15SMK-` or `-P19SMK-` peers remained.

## E) Remaining Uncovered Behavior

- Authenticated success path is still skipped locally until `users.slots` drift is resolved or a compatible local fixture/schema exists.
- Completed accounting is not yet proven under authenticated success.
- Concurrent completed announces are not covered.
- Ratio delta correctness is not asserted beyond snapshot/restore safety.
- IPv6 and `peers6` remain uncovered.

## F) Next Safe Write-Path Refactor

Before extracting write-path/accounting helpers, resolve the authenticated fixture blocker in a separate minimal wave. Once authenticated smoke can run, add assertions around:

- user uploaded/downloaded restoration
- snatched row creation/restoration
- tracker counter restoration
- torrent completed/last_action restoration

Then extract event write-path helpers without changing SQL semantics.
