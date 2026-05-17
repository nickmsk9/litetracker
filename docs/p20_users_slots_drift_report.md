# P20 users.slots Drift Report

Mode: audit to safe apply. No DB schema change was applied.

## A) Where `slots` Is Used

Code search found `slots` in two functional places:

- `system/functions/functions.announce.php`
  - `announce_fetch_user_by_passkey()` selected `id, slots, uploaded, downloaded, class`.
  - The announce path never read `$user['slots']` after loading it.
- `modules/shop/add_slot.php`
  - Runs `UPDATE users SET slots=(slots + 1) WHERE id=...`.
  - This is a legacy shop product feature, separate from announce auth/accounting.

Local schema audit:

- `users.slots` does not exist locally.
- `users.passkey`, `uploaded`, `downloaded`, and `class` do exist and are enough for current announce auth/accounting code.

## B) Chosen Solution

Variant A/C: make announce independent of `slots` and do not change the `users` schema.

Reason:

- announce does not use `slots` for passkey auth;
- announce does not use `slots` for peer limits;
- announce does not use `slots` for ratio/accounting;
- adding a `users.slots` column just for smoke would preserve a shop-only legacy drift in the hot announce path.

## C) What Changed

- `announce_fetch_user_by_passkey()` now selects:
  - `id`
  - `uploaded`
  - `downloaded`
  - `class`
- It no longer selects `slots`.
- Removed an unused `$PRIV = get_priv_info(...)` call from `announce.php`; the value was not used later and could fatal under `init.announce.php`.
- P19 smoke skip logic no longer blocks all authenticated scenarios on missing `users.slots`.
- Added `database/p20_users_slots_drift.sql` as report-only audit SQL.

## D) Why Safe

- No DB schema change.
- No passkey semantics changed.
- No ratio fields changed.
- No uploaded/downloaded formulas changed.
- No peer/snatched/torrent/tracker schema touched.
- The removed `slots` field was not used by announce logic.
- The removed `$PRIV` assignment had no downstream reads in `announce.php`.

`modules/shop/add_slot.php` remains a separate shop drift. If that product is intentionally supported later, it needs its own shop/users schema decision.

## E) Smoke Result

Docker smoke after P20:

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
SKIP authenticated completed
PASS authenticated stopped
PASS authenticated seeder left=0

Result: PASS (14 scenarios, 0 failed)
```

Authenticated completed is no longer blocked by `users.slots`; it reaches the next known blocker: `snatched.completedat` strict-mode write. That is completed-accounting behavior and was intentionally not changed in P20.

## F) Next Safe Write-Path Refactor

Run a dedicated tiny wave for completed accounting compatibility:

- audit `snatched.startedat/completedat` epoch semantics;
- fix the completed write only if confirmed safe;
- rerun P19 so authenticated completed becomes PASS;
- then extract announce write-path helpers with authenticated coverage.
