# P17 Announce v2 Phase 1

Mode: real apply, refactor only. No DB schema changes, no accounting formula changes, no ratio logic changes, and no protocol interval changes.

## Helpers Extracted

File: `system/functions/functions.announce.php`

- `announce_parse_request($get = null, $server = null)`
- `announce_is_valid_info_hash($value)`
- `announce_is_valid_peer_id($value)`
- `announce_normalize_event($event)`
- `announce_normalize_numwant($get = null, $default = 50)`
- `announce_detect_client_flags($server = null)`
- `announce_failure_response($reason)`
- `announce_success_response($interval, array $peers, $compact, $noPeerId, $peerId)`
- `announce_encode_compact_peers(array $peers)`
- `announce_encode_peer_list(array $peers, $noPeerId)`

`announce_numwant()` remains as a compatibility wrapper around the new normalizer.
`err()` now delegates to `announce_failure_response()` while preserving its die-on-failure behavior.

## What Changed in announce.php

`announce.php` now gets normalized request values from `announce_parse_request()`:

- event
- numwant
- agent
- client/browser header flags

Peer response assembly moved out of the main announce flow and into `announce_success_response()`. The main file still:

- applies rate limits;
- loads ban/user/torrent/peer context;
- enforces slot rules;
- processes started/completed/stopped/regular events;
- writes peers/snatched/trackers/torrents/users exactly through the existing SQL paths;
- emits the final raw bencoded response.

## Intentionally Not Touched

- No `users` uploaded/downloaded formulas changed.
- No `snatched` completion semantics changed.
- No `torrents.completed` behavior changed.
- No tracker counter behavior changed.
- No passkey/auth logic changed.
- No announce interval or min-interval behavior changed.
- No schema changes.
- No event write-path extraction yet.

## Behavior Preservation

The refactor preserves the request defaults and existing validation order:

- `info_hash` and `peer_id` still require exactly 20 bytes.
- `numwant` still accepts `num want`, `numwant`, and `num_want`, clamped to 1..200 with default 50.
- `compact` and `no_peer_id` remain integer-flag compatible.
- browser-like headers still fail before client checks.
- BitComet legacy `private=1` response behavior is preserved.
- compact response remains valid after the P16 fix.

## Smoke Result

Local Docker smoke:

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

## Risks

- P15 is still a smoke harness, not a full protocol conformance suite.
- Completion accounting races from P14 are intentionally unchanged.
- Authenticated successful announce remains limited by the existing `users.slots` schema/code drift until a later cleanup wave.
- The event/write path is still procedural and dense; this wave only isolated parsing and response construction.

## Next Safe Stage

Extract context loading into small helpers without changing SQL:

- ban lookup wrapper;
- user lookup wrapper;
- torrent lookup wrapper;
- peer pool/self-peer collection wrapper.

Keep users/snatched/torrents/trackers accounting in `announce.php` until behavior tests cover authenticated and completed flows more deeply.
