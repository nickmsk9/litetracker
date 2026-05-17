# P14 Announce System Audit + V2 Redesign Plan

Mode: audit/design only. No PHP changes, no SQL applied.

Local baseline after P13:

| table | engine | local rows | announce role |
| --- | --- | ---: | --- |
| peers | MyISAM | 0 | hot live peer state |
| trackers | InnoDB | 5 | local/external peer counters |
| snatched | MyISAM | 0 | per-user torrent accounting |
| torrents | MyISAM | 1 | torrent metadata and completed count |
| users | MyISAM | 3 | passkey lookup and ratio accounting |
| bans | InnoDB | 0 | IP range ban lookup |
| retrackers | InnoDB | 0 | torrent announce-url generation |

## A) Current announce flow

1. `announce.php` loads `system/init.announce.php`, then `announce_parse_request()` from `system/functions/functions.announce.php`.
2. Request parsing requires `info_hash` and `peer_id` to be exactly 20 bytes. It reads `event`, `ip`, `localip`, `port`, `downloaded`, `uploaded`, `left`, `passkey`, `compact`, `no_peer_id`, and `numwant`.
3. A blank passkey marks a guest announce. A non-guest passkey must be 32 characters. The current error includes the supplied passkey in the failure string.
4. IP is taken from `getip()`, which only accepts IPv4 and falls back to `0.0.0.0`. IPv6 announce is not supported.
5. Rate limits run through Memcached-backed `lt_rate_limit_hit()`:
   - `announce` by passkey or IP: 180 hits per 300 seconds.
   - `announce_ip` by IP: 600 hits per 300 seconds.
6. Ban lookup checks `bans` by converted IPv4 integer and is cached by `announce_fetch_ip_ban()`.
7. `numwant` is clamped to 1..200 with default 50.
8. Validation checks port range, event in `started|stopped|completed|''`, uploaded/downloaded/left are nonnegative and each <= 1 TiB, client headers are not browser-like, and `checkclient($peer_id)` does not reject the client.
9. Non-guest users are loaded by passkey with `announce_fetch_user_by_passkey()`.
   - Local blocker found: this function selects `id, slots`, but local `users` has no `slots` column. A real announce with a passkey will fail unless production schema differs.
10. Torrent lookup converts the binary info hash to hex and queries `torrents` joined to the local `trackers` row:
    - `WHERE torrents.infohash = <hex string> AND trackers.tracker = "localhost"`.
    - If the local tracker row is missing, the torrent is treated as invalid.
11. Seeder status is `left == 0`. Any `left` greater than torrent size fails.
12. Peer pool is loaded from `peers WHERE torrent = ? ORDER BY last_action DESC LIMIT N`; `N` is `max(100, min(1000, numwant * 4))`, capped by cached local tracker peer count.
13. Response peer list is built before writes:
    - Compact mode emits 6-byte IPv4 peers only.
    - Non-compact mode emits dictionaries with `ip`, `port`, and optionally `peer id`.
    - Self peer is skipped if present in the pool.
    - For BitComet peer IDs beginning `-BC0`, the response adds `private=1`; other clients do not receive it.
14. If self peer was not found in the pool, a second lookup checks `peers WHERE torrent = ? AND peer_id = ?`.
15. Existing peers must wait 15 minutes between announces based on `last_action`; too-early announces fail.
16. New non-guest peers run passkey slot control:
    - Count peers by `(torrent, passkey)`.
    - More than 1 active leecher or 3 active seeders deletes all matching passkey peers for that torrent, decrements `trackers`, and fails.
    - User stats are then fetched again by passkey.
17. `event=stopped` deletes the self peer and decrements local tracker counters if a row was deleted.
18. `event=completed` is allowed only with `left=0`.
    - It checks `snatched.finished`.
    - If not finished, it queues `snatched.finished=1`, `snatched.completedat=<datetime string>`, and `torrents.completed=torrents.completed+1`.
19. For existing non-stopped peers:
    - Uploaded/downloaded deltas are `max(0, current - stored)`.
    - User ratio totals are updated when deltas are positive.
    - `peers` is updated with current cumulative values, offsets, `to_go`, `last_action`, and `seeder`.
    - `snatched` is updated with deltas when user traffic changed.
    - If seeder state changed, local `trackers` counters are adjusted.
20. For new non-stopped peers:
    - Blacklisted ports are rejected.
    - Optional connectability probe uses `fsockopen()` and caches the result.
    - A row is inserted into `peers`.
    - A `snatched` row is inserted for authenticated users with `ON DUPLICATE KEY UPDATE startedat=IF(startedat=0, ...)`.
    - Local `trackers` seeders/leechers counters are incremented.
21. Seeder announces queue `torrents.last_action=<now>` on every non-stopped seeder announce.
22. Queued writes are executed after event handling:
    - `trackers` update and announce torrent cache delete.
    - `torrents` update and announce torrent cache delete.
    - `snatched` update.
23. Raw bencoded response is emitted with `Content-Type: text/plain` and `Pragma: no-cache`.

### Scrape flow

`scrape.php` requires `info_hash`, rate-limits by IP, caches by hash, then selects `torrents` joined to `trackers`. It does not filter `trackers.tracker='localhost'`, so multi-tracker torrents can return arbitrary external tracker counters.

### Remote tracker flow

`update.peers.php` updates non-local `trackers` rows from scrape/announce responses. Batch mode scans `trackers.tracker <> 'localhost'` ordered by torrent id descending. On the local dataset this uses a full index scan plus temporary/filesort.

### Maintenance flow

`autoclean.php` deletes stale peers, recounts `peers` into local `trackers`, and performs user maintenance. `scripts/tracker_maintenance.php` can do dry-run peer cleanup and tracker recounts from CLI.

## B) Reads/writes per scenario

Approximate database work on cache miss, excluding Memcached rate-limit/cache operations:

| scenario | SELECT | INSERT | UPDATE | DELETE | touched tables |
| --- | ---: | ---: | ---: | ---: | --- |
| invalid passkey length | 0 | 0 | 0 | 0 | none |
| invalid passkey value | 2 | 0 | 0 | 0 | bans, users |
| invalid torrent | 3 | 0 | 0 | 0 | bans, users, torrents, trackers |
| started leecher, new peer | 6-7 | 2 | 1 | 0 | bans, users, torrents, trackers, peers, snatched |
| regular leecher, existing peer | 3-4 | 0 | 1-3 | 0 | bans, users, torrents, trackers, peers, snatched |
| completed | 4-5 | 0 | 3-5 | 0 | bans, users, torrents, trackers, peers, snatched |
| seeder announce | 3-7 | 0-2 | 2-4 | 0 | bans, users, torrents, trackers, peers, snatched |
| stopped | 3-4 | 0 | 0-1 | 1 | bans, users, torrents, trackers, peers |
| scrape | 1 | 0 | 0 | 0 | torrents, trackers |

Heaviest hot-path queries:

- `peers WHERE torrent=? ORDER BY last_action DESC LIMIT N`.
- `users WHERE passkey=?` repeated twice for new authenticated peers.
- `users` traffic update on announce deltas.
- `torrents.last_action` update on every seeder announce.
- `snatched` accounting update on every traffic delta.
- `trackers` counter updates on start/stop/state transitions.

## C) Bottlenecks

- `users` is MyISAM and updated on traffic deltas. This turns ratio accounting into a hot table lock.
- `torrents` is MyISAM and gets `last_action` writes from seeder announces. Popular torrents can produce avoidable table locks.
- `peers` is the main hot table and still MyISAM. Inserts, updates, deletes, cleanup deletes, and peer-list reads compete at table level.
- `snatched` is MyISAM and updated from the announce hot path, while also holding completion state.
- No transaction wraps users/peers/snatched/trackers/torrents writes. Partial accounting is possible after mid-flow failure.
- New authenticated peers query `users` twice by passkey.
- Peer list and self lookup can become two `peers` reads per announce.
- Completed accounting is read-then-write without row locking or atomic conditional update.
- `update.peers.php` remote batch scan lacks an index for `tracker <> 'localhost'`, `lastchecked`, and torrent ordering. It is acceptable locally with five rows but not proven safe in production.
- `autoclean.php` recounts all peers with `GROUP BY torrent,seeder`; that is a background full read over the hottest table.
- `scrape.php` caches possibly wrong tracker rows because it does not constrain the join to `localhost`.

## D) Protocol/correctness risks

- Local schema drift: `announce_fetch_user_by_passkey()` selects `users.slots`, but the local `users` table has no `slots`.
- Completion timestamp type mismatch: `snatched.completedat` is an integer, but `event=completed` assigns a quoted datetime string.
- Completion counting is race-prone: two completed announces can both see unfinished state before either writes.
- User ratio accounting updates `users` before `peers`/`snatched` are fully persisted. A later failure can overcount traffic.
- `torrents.completed` and `snatched.finished` are not updated atomically.
- IPv6 is unsupported: `getip()` and compact peer encoding are IPv4-only; no `peers6` response exists.
- `ip` and `localip` request parameters are parsed but ignored.
- `scrape.php` can report external tracker counts instead of local counts.
- `announce` response lacks optional `min interval`, `complete`, and `incomplete` fields. That is protocol-compatible but less informative.
- The `private=1` response key is only emitted for BitComet-style peer IDs, which is inconsistent.
- Too-early announces fail hard at 15 minutes. Some clients retry aggressively after failure, which can increase load.
- Uploaded/downloaded/left values are capped at 1 TiB each. Long-lived torrents or very large content can exceed that cumulative value.
- Passkey length failure includes the passkey value in the failure message.
- Peer uniqueness is `(torrent, peer_id)`. That matches existing code, but clients reusing peer IDs across IP/port changes can replace identity in surprising ways.
- Guest announces are allowed with empty passkey and `userid=0`; they affect peers and local tracker counters but not user/snatched accounting.

## E) Schema/index readiness

### peers

Current useful indexes:

- `UNIQUE(torrent, peer_id)` for self lookup and duplicate prevention.
- `(torrent, last_action)` for peer pool ordering.
- `(torrent, passkey)` for slot enforcement.
- `(torrent, userid)` and `userid` for profile/user pages.
- `(torrent, seeder)` for counts and peer pages.
- `last_action` for cleanup.

Readiness:

- Keep all hot-path indexes until production EXPLAIN proves redundancy.
- `torrent` alone overlaps several composite indexes but should not be dropped before production query review.
- `connectable` alone is likely low value but needs production usage check.
- InnoDB migration needs rehearsal around lock time, auto-increment behavior, row size, and cleanup/autoclean timing.

### trackers

P13 local state is already InnoDB with:

- `PRIMARY(id)`
- `UNIQUE(torrent, tracker)`

Readiness:

- Local announce updates are covered by `(torrent, tracker)`.
- Remote tracker scan may need a production-proven helper index, but not before real EXPLAIN/cardinality from production.

### snatched

Current indexes:

- `PRIMARY(id)`
- `UNIQUE(torrent, userid)`
- `(userid, finished)`

Readiness:

- `userid` is nullable even though code treats it as a concrete user id.
- `completedat`/`startedat` are integer epoch fields, while announce writes a datetime string for completion.
- Before InnoDB: make completion timestamp semantics explicit and fix code/schema compatibility in a separate wave.

### torrents

Current useful indexes:

- `UNIQUE(infohash)` for announce/scrape lookup.
- browse/index/profile indexes for category/status/user/news views.

Readiness:

- `infohash` storage must be verified: code compares it to a hex string, while the column is `VARBINARY(40)`.
- `added` and `last_action` are `datetime NOT NULL` with no defaults; strict-mode insert paths must always provide values.
- Avoid extra hot writes to this MyISAM table before InnoDB.

### users

Current indexes:

- `PRIMARY(id)`
- non-unique `name`
- non-unique `email`
- non-unique `idx_users_passkey(passkey)`

Readiness:

- `passkey` should be unique for tracker auth, but production duplicate checks must run first.
- `name` and `email` are business-key candidates for unique constraints, outside announce scope.
- `uploaded`/`downloaded` are signed `BIGINT`; leave until broader user accounting design.
- `bonus` and `voice` are `FLOAT` smells, but not announce-blocking.
- Missing `slots` column must be resolved before a reliable authenticated announce v2 rehearsal.

## F) Proposed announce v2 architecture

Keep procedural PHP and the current request surface. Introduce small, testable functions before changing SQL behavior:

- `announce_v2_parse_request()`
- `announce_v2_validate_request()`
- `announce_v2_load_context()`
- `announce_v2_fetch_peer_context()`
- `announce_v2_build_peer_list()`
- `announce_v2_apply_event()`
- `announce_v2_apply_accounting()`
- `announce_v2_write_peer()`
- `announce_v2_adjust_counters()`
- `announce_v2_response()`
- `announce_v2_fail()`

SQL strategy after InnoDB readiness:

- Keep torrent/passkey/ban/context reads simple and indexed.
- Use a short transaction for the event write unit: current peer row, peer mutation, snatched/user deltas, tracker/torrent counter decisions.
- Use `SELECT ... FOR UPDATE` for the current peer/snatched row when calculating deltas and completion state.
- Use `INSERT ... ON DUPLICATE KEY UPDATE` carefully for peer creation, but do not compute traffic deltas without locking or reading prior values.
- Make completed accounting atomic by updating `snatched` only when `finished=0`, then incrementing `torrents.completed` only if that update changed a row.
- Reduce writes to `torrents.last_action`: update only on first active seeder, state transition, or a time threshold; otherwise let maintenance refresh materialized freshness.
- Keep local tracker counters materialized for UI speed, but reconcile from `peers` in cron and treat counters as eventually repairable.
- Do not cache self peer rows or mutable accounting rows.
- Cache bans and torrent context with short TTL and precise invalidation.
- Fix scrape to cache localhost tracker counts only.
- Add feature flagging and shadow logging so v2 can compare decisions with v1 before taking traffic.

Graceful fallback:

- Keep v1 path available behind a config flag.
- Log v2 validation/accounting decisions without emitting them first.
- On unexpected v2 exception, emit a protocol-valid failure or fall back to v1 before any write transaction starts.

## G) Migration roadmap

1. **P15: announce behavior tests / smoke harness**
   - Add request fixtures and bencode assertions.
   - Cover invalid passkey, invalid torrent, started, regular, completed, stopped, compact, non-compact, guest, and scrape.
   - No schema or behavior change.

2. **P16: pure refactor, no SQL behavior change**
   - Extract parse/validate/context/response helpers.
   - Preserve exact query order and bencode output where possible.
   - Add logging around failure reasons and write counts.

3. **P17: schema drift and correctness cleanup rehearsal**
   - Resolve `users.slots` drift.
   - Resolve `snatched.completedat` timestamp type mismatch.
   - Fix scrape localhost tracker selection.
   - Keep production changes gated by tests.

4. **P18: indexes + InnoDB readiness for snatched/trackers/peers**
   - Production EXPLAIN for hot queries.
   - Rehearse snatched cleanup and nullability changes.
   - Decide whether remote tracker scan needs an index.

5. **P19: write-path optimization**
   - Remove duplicate user passkey lookup.
   - Avoid unnecessary `torrents.last_action` writes.
   - Introduce atomic completed accounting under InnoDB.

6. **P20: counter/materialized stats strategy**
   - Define which counters are authoritative and which are cached/materialized.
   - Add reconciliation checks for `peers` -> `trackers` and `snatched` -> `torrents.completed`.

7. **P21: snatched/users/torrents InnoDB rehearsal**
   - Convert accounting tables before `peers` when blockers are fixed.
   - Add transaction-safe write path behind feature flag.

8. **P22: final peers InnoDB / online migration plan**
   - Quiet-window or online schema migration plan.
   - Cron pause/recount procedure.
   - Rollback and counter repair plan.

## H) First safe PR

The first safe PR should be a behavior test/smoke harness only:

- No protocol change.
- No accounting change.
- No schema change.
- No production migration.
- Add a small fixture runner that can exercise `announce.php`/`scrape.php` inputs in a controlled local environment.
- Add a minimal bencode response decoder/assertion helper for tests.
- Capture expected responses and expected table deltas for started, completed, stopped, compact, non-compact, invalid passkey, and invalid torrent.

This gives later refactors a tripwire before touching the hot path.

## Biggest production blockers

- `users.slots` schema/code drift must be verified against production.
- `snatched.completedat` type mismatch must be resolved.
- `peers`, `snatched`, `torrents`, and `users` remain MyISAM and are part of the announce write path.
- Completion and ratio accounting need transaction semantics before aggressive optimization.
- Production EXPLAIN is required before dropping peer indexes or adding a remote tracker scan index.
