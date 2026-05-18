# P18 Announce v2 Phase 2

Mode: real apply. Production PHP changed, but accounting, ratio, completed semantics, protocol response shape, intervals, and DB schema were not changed.

## Files Changed

- `announce.php`
- `system/functions/functions.announce.php`
- `tests/announce/announce_smoke.php`
- `docs/p18_announce_v2_phase2.md`

## Context Helpers Extracted

File: `system/functions/functions.announce.php`

- `announce_load_ban_context($ip)`
- `announce_load_user_context($passkey, $guest)`
- `announce_load_torrent_context($infoHash)`
- `announce_load_tracker_context(array $torrent)`
- `announce_load_peer_context($torrentId, $peerId, $numwant, $numpeers)`
- `announce_load_peer_pool($torrentId, $peerId, $numwant, $numpeers, $fields = null)`
- `announce_peer_fields()`

The peer loader preserves the old behavior:

- fetch recent peers by `torrent`;
- exclude self from the response candidate list;
- shuffle candidates;
- cap by `numwant`;
- fall back to a self-peer lookup only if self was not in the fetched pool.

## Repeated Queries Removed

Authenticated new-peer path previously did:

1. `SELECT id, slots FROM users WHERE passkey = ?`
2. later, `SELECT id, uploaded, downloaded, class FROM users WHERE passkey = ?`

P18 expands the first user query to include the later stats columns and reuses that loaded context. This removes one user lookup on authenticated new-peer announces while preserving the same loaded values and downstream accounting formulas.

No torrent, tracker, ban, snatched, or peer write semantics were changed.

## Estimated Query Reduction

Approximate DB query count change on cache miss:

| scenario | before | after | change |
| --- | ---: | ---: | ---: |
| invalid passkey length | 0 | 0 | 0 |
| invalid 32-char passkey | 2 | 2 | 0 |
| invalid torrent with valid auth | 3 | 3 | 0 |
| authenticated started/new peer | 6-7 | 5-6 | -1 |
| authenticated regular existing peer | 3-4 | 3-4 | 0 |
| completed | 4-5 | 4-5 | 0 |
| guest started/new peer | 4-5 | 4-5 | 0 |
| scrape | 1 | 1 | 0 |

The important win is small but useful: the `users` hot table is read once instead of twice for the authenticated new-peer path.

## Lightweight Instrumentation

`announce.php?announce_debug=1` now logs local-only debug timing through `error_log` when the request IP is `127.0.0.1`, `::1`, or `0.0.0.0`.

Logged fields:

- event type;
- DB query count;
- announce duration in milliseconds;
- peer count returned.

It is disabled by default and does not alter the bencoded response.

## Smoke Result

Both direct host command with Docker fallback and explicit Docker PHP command passed:

```text
PASS invalid passkey returns safe failure
PASS invalid info_hash returns failure
PASS normal announce shape returns interval and peers
PASS compact=1 response shape
PASS event=started does not crash
PASS event=stopped does not crash
PASS left=0 seeder logic does not crash
PASS scrape.php basic response

Result: PASS (8 scenarios, 0 failed)
```

## Remaining Bottlenecks

- `peers` remains the hottest read/write table.
- `users` still receives ratio counter writes when uploaded/downloaded deltas exist.
- `snatched` completion and traffic accounting are still outside a transaction.
- `torrents.last_action` can still be updated by seeder announces.
- `trackers` counters are still materialized and updated in the announce path.
- Authenticated local smoke is still limited by the known `users.slots` schema/code drift.

## Next Wave

Add focused authenticated fixtures or a safe local fixture bootstrap so completed/accounting paths can be tested. After that, extract the event write path into helpers without changing SQL, then prepare transaction-safe InnoDB behavior behind tests.
