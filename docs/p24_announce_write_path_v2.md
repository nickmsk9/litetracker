# P24 Announce Write Path v2

## Scope

Wave P24 refactors production announce write-path organization without changing accounting formulas, ratio math, protocol response shape, passkey semantics, intervals, or schema.

Touched files:

- `announce.php`
- `system/functions/functions.announce.php`
- `docs/p24_announce_write_path_v2.md`

The P23 smoke harness was not changed.

## A) Helpers Extracted

Event handlers:

- `announce_handle_started_event(...)`
- `announce_handle_stopped_event(...)`
- `announce_handle_completed_event(...)`
- `announce_handle_regular_event(...)`
- `announce_dispatch_event_write_handler(...)`

Accounting helpers:

- `announce_calculate_transfer_delta(...)`
- `announce_apply_user_transfer_delta(...)`
- `announce_update_snatched_row(...)`
- `announce_maybe_count_completed(...)`
- `announce_update_torrent_counters(...)`
- `announce_update_tracker_counters(...)`

Peer write helpers:

- `announce_insert_peer(...)`
- `announce_update_peer(...)`
- `announce_delete_peer(...)`
- `announce_mark_peer_seeder_state(...)`

Support helpers:

- `announce_write_result(...)`
- `announce_load_peer_write_context(...)`
- `announce_prepare_authenticated_write_result(...)`
- `announce_apply_pending_write_updates(...)`

## B) What Logic Moved

- `announce.php` now parses and validates the request, loads user/torrent/peer write context, dispatches the write handler, then loads the peer pool and builds the response.
- The old mixed write block from `announce_process_event_write_path(...)` was split into explicit event paths.
- Peer insert/update/delete SQL moved into peer helpers.
- User transfer deltas, snatched updates, completion counting, torrent updates, and tracker updates moved into accounting helpers.
- The old `announce_process_event_write_path(...)` and `announce_flush_event_updates(...)` entry points remain as compatibility wrappers.

## C) Accounting Compatibility

Preserved behavior:

- uploaded/downloaded deltas still use `max(0, request - peer row)`;
- `users` update SQL still adds only positive deltas;
- `snatched` upload/download update shape is unchanged;
- completed counting still checks existing `snatched.finished` for authenticated users;
- `torrents.completed` still increments only when completion can be counted;
- `torrents.last_action` still updates when the request is seeding;
- tracker seeder/leecher formulas and guards are unchanged;
- peer insert/update/delete fields and WHERE clauses are unchanged;
- no `BEGIN`, `COMMIT`, or schema changes were introduced.

## D) Smoke Result

Verification commands run:

```sh
php -l announce.php
php -l system/functions/functions.announce.php
php -l tests/announce/announce_smoke.php
docker compose exec -T php php tests/announce/announce_smoke.php
git diff --check
```

Smoke result:

```text
Result: PASS (15 scenarios, 0 failed)
Touched rows: peers_deleted=4 tracker_restored=1 torrent_restored=1 user_restored=1 snatched_restored=0 snatched_deleted=1
```

## E) Transaction-Readiness Notes

`announce_apply_pending_write_updates(...)` is the future transaction boundary. Completed writes now collect tracker/torrent/snatched updates into one ordered flush after immediate peer writes, matching the current MyISAM-era behavior while giving P25 a single place to wrap with transaction handling.

Transactions are intentionally not enabled in P24.

## F) Remaining Bottlenecks

- User transfer update still happens before event dispatch to preserve existing order.
- Peer writes are still immediate SQL calls because current behavior depends on affected rows for tracker counter decisions.
- Cache invalidation remains coupled to tracker/torrent counter updates.
- The write result is structured, but callers do not yet persist or log it.
- Full transaction safety still depends on a future InnoDB/schema wave.

## G) Next Wave

P25 should introduce a transaction-capable execution layer for completed writes behind a feature-disabled or no-op boundary first, then enable it only after confirming engine/schema readiness and adding explicit rollback smoke coverage.
