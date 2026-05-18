# P23 Announce Accounting Assertions

## Scope

Wave P23 only changes the announce smoke harness. Production announce behavior, schema, and accounting logic were not changed.

Touched files:

- `tests/announce/announce_smoke.php`
- `docs/p23_announce_accounting_assertions.md`

## Assertions Added

- Added row snapshot helpers for authenticated announce accounting checks:
  - `users.uploaded`, `users.downloaded`
  - `torrents.completed`, `torrents.last_action`
  - localhost `trackers.seeders`, `trackers.leechers`, plus optional `completed`/`downloaded` columns if a fixture schema has them
  - `snatched` row for `(torrent, userid)`
  - `peers` row for the smoke peer
- Added exact delta assertions for authenticated `event=completed`.
- Added stopped-path assertions that only the target smoke peer is deleted.
- Added cleanup verification for P19/P23 smoke peer prefixes and restored accounting snapshots.

## Completed Accounting Coverage

The authenticated completed scenario now captures before/after state and asserts:

- response has no `failure reason`;
- authenticated peer and snatched rows exist before exact delta checks run;
- `users.uploaded` increases by the request upload delta;
- `users.downloaded` increases by the request download delta;
- `snatched.uploaded` and `snatched.downloaded` increase by the same deltas;
- `torrents.completed` increases by `1` only when current announce logic can count the completion;
- `snatched.finished`/`completedat` reflect the current completed-state rules;
- peer row is updated to `seeder=1`, `to_go=0`, and the requested uploaded/downloaded counters;
- localhost tracker counters covered by the fixture do not become negative.

If the authenticated fixture is missing the rows required for exact deltas, the scenario reports `SKIP` with an explicit reason instead of creating risky data.

## Stopped Cleanup Coverage

The authenticated stopped scenario now inserts a separate P23 smoke peer for the same torrent, then announces `event=stopped` for the main test peer.

Assertions:

- the stopped peer row is deleted;
- the separate smoke peer remains present after stopped;
- tracker counters do not become negative.

The separate peer is removed later by harness cleanup.

## Cleanup And Restoration

The final smoke scenario calls cleanup and verifies:

- no `peers.peer_id` remains with `-P19SMK-` or `-P23SMK-` prefixes;
- `snatched` is restored when it existed before the run;
- `snatched` is deleted when it was created only by the run;
- user uploaded/downloaded counters are restored;
- torrent completed/last_action fields are restored;
- localhost tracker seeders/leechers/lastchecked counters are restored.

Observed verification in the local Docker fixture:

```text
PASS cleanup restores accounting snapshots - peers removed, snapshots restored
Result: PASS (15 scenarios, 0 failed)
Touched rows: peers_deleted=4 tracker_restored=1 torrent_restored=1 user_restored=1 snatched_restored=0 snatched_deleted=1
```

## Remaining Uncovered Accounting Risks

- Guest completed accounting is still only covered by shape/crash checks, not exact write-path deltas.
- Multi-peer same-passkey cleanup guard behavior is not exhaustively asserted beyond the stopped target-peer check.
- Tracker optional `completed`/`downloaded` fields are asserted only if present in the active schema.
- Rate-limit/cache side effects are not isolated beyond the current smoke subprocess behavior.

## Verification

Commands run:

```sh
php -l tests/announce/announce_smoke.php
docker compose exec -T php php tests/announce/announce_smoke.php
git diff --check
```

Results:

- PHP lint passed.
- Announce smoke passed: 15 scenarios, 0 failed.
- Diff whitespace check passed.

## Next Wave Recommendation

P24 should add a focused authenticated regression for repeated completed announces against an already-finished `snatched` row, asserting that user/peer deltas still apply while `torrents.completed` does not increment again.
