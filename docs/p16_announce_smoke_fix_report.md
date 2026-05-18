# P16 Announce Smoke Blockers Tiny Fix

Mode: minimal patch. No DB schema change, no accounting refactor, no ratio logic change, and no protocol flow rewrite.

## What Changed

### Compact response

File: `announce.php`

- Split compact and non-compact response finalization.
- Compact responses now append the compact peer string and close the response dictionary once.
- Non-compact responses keep the existing list-close + dictionary-close behavior.
- The legacy BitComet `private=1` branch is preserved for both compact and non-compact output.

Result: `compact=1` no longer emits trailing bencode bytes after the dictionary.

### `peers.prev_action`

File: `announce.php`

- Added `prev_action` to the existing `INSERT INTO peers (...)` column list.
- Inserts `NOW()` for `started`, `last_action`, and `prev_action` on a new peer row.

This is compatible with the current new-peer semantics: there is no previous announce yet, so the initial previous action timestamp is initialized with the same time as the first action. No accounting fields, ratio fields, or event decisions were changed.

### Smoke harness expectation

File: `tests/announce/announce_smoke.php`

- Removed the P15 skip guard for `peers.prev_action`, because the production insert now supplies the column.
- The harness still cleans only its own `-P15SMK-...` peers and restores local tracker/torrent counters.

## Smoke Result

Before P16, local P15 smoke result:

```text
PASS invalid passkey returns safe failure
PASS invalid info_hash returns failure
PASS normal announce shape returns interval and peers
FAIL compact=1 response shape
SKIP event=started does not crash
PASS event=stopped does not crash
SKIP left=0 seeder logic does not crash
PASS scrape.php basic response
```

After P16, local Docker result:

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

## Why Accounting Is Unchanged

- No `users`, `snatched`, `trackers`, or `torrents` accounting expressions were changed.
- No event branching was changed.
- No passkey/auth logic was changed.
- No schema change was made.
- `prev_action` is only initialized for the new `peers` row so MySQL strict mode accepts the same insert path.

## Remaining Risks

- P15 is still a smoke harness, not a full correctness suite.
- Completion accounting race conditions from P14 are still present.
- Authenticated announce remains limited by the existing `users.slots` schema/code drift until that is handled in a later wave.
- IPv6 and `peers6` remain unsupported.

## Next Safe Announce Refactor

Extract pure request parsing and validation helpers behind the existing behavior, with P15 smoke as the first tripwire before and after the refactor.
