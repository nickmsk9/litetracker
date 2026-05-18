# LiteTracker Wave P12 Remaining MyISAM Report

## Executive Summary

After P4-P11, the only remaining local MyISAM tables are the five hot/final tracker tables:

| Table | Engine | Local rows | Local size MB | Avg row length | Collation | Hotness |
|---|---|---:|---:|---:|---|---|
| `torrents` | MyISAM | 1 | 0.0184 | 1932 | `utf8mb3_general_ci` | HIGH |
| `users` | MyISAM | 3 | 0.0104 | 209 | `utf8mb3_bin` | HIGH |
| `trackers` | MyISAM | 5 | 0.0102 | 83 | `utf8mb3_general_ci` | MEDIUM/HIGH |
| `peers` | MyISAM | 0 | 0.0010 | 0 | `utf8mb3_general_ci` | HIGH |
| `snatched` | MyISAM | 0 | 0.0010 | 0 | `utf8mb3_general_ci` | HIGH |

There are no remaining low-risk support MyISAM tables locally. `SAFE NEXT` is empty if interpreted as "can migrate immediately".

## Table Detail

### peers

- Purpose: live announce peer state.
- Write intensity: very high in production; announce inserts, updates, and deletes.
- Routes/code: `announce.php`, `autoclean.php`, `update.peers.php`, `scripts/tracker_maintenance.php`, `details.php`, `profile.php`, `multitracker_accounts.php`.
- Indexes: primary `id`; unique `(torrent, peer_id)`; secondary `torrent`, `(torrent,seeder)`, `last_action`, `connectable`, `userid`, `(torrent,userid)`, `(torrent,passkey)`, `(torrent,last_action)`.
- Zero dates locally: none in `started`, `last_action`, `prev_action`.
- Zero-date defaults: none.
- Strict blockers: `started`, `last_action`, `prev_action`, `peer_id`, `ip`, `agent`, `passkey` are `NOT NULL` with no default; app writes currently provide values.
- Charset blockers: mixed `cp1251` for `peer_id`, `utf8mb3_unicode_ci` for `ip`, `utf8mb3_general_ci` for `agent/passkey`.
- Index notes: `torrent` is prefix-covered by multiple compound indexes, but this is hot announce territory; do not drop without production EXPLAIN and query digest.
- Hotness score: HIGH / last.

### trackers

- Purpose: per-torrent tracker aggregate counts and remote tracker status.
- Write intensity: medium/high; announce updates localhost row, `update.peers.php` updates remote tracker rows.
- Routes/code: `announce.php`, `update.peers.php`, `autoclean.php`, `browse.php`, `index.php`, `details.php`, `download.php`, `upload.php`, `edit.php`, `my.releases.php`, `my.book.php`, `profile.php`.
- Indexes: primary `id`; unique `(torrent, tracker)`; redundant unique `id`.
- Zero dates locally: none. `lastchecked` is integer epoch; local zero count is 0.
- Zero-date defaults: none.
- Strict blockers: `torrent` and `state` are `NOT NULL` with no default; app writes currently provide values.
- Charset blockers: `tracker/state` are `utf8mb3_general_ci`.
- Index notes: redundant `UNIQUE KEY id(id)` duplicates the primary key. `update.peers.php` probe by `tracker <> 'localhost' AND lastchecked < ... ORDER BY torrent DESC` currently has no `lastchecked`-oriented index.
- Hotness score: MEDIUM/HIGH. Safest next hot candidate, but still requires rehearsal.

### snatched

- Purpose: per-user per-torrent transfer/completion accounting.
- Write intensity: high around announce completion/accounting; can be large historically in production.
- Routes/code: `announce.php`, `profile.php`, `scripts/seed_demo_activity.php`.
- Indexes: primary `id`; unique `(torrent, userid)`; secondary `(userid, finished)`.
- Zero dates locally: no datetime columns. `startedat/completedat` are integer epoch fields; local zero count is 0 because table is empty.
- Zero-date defaults: none.
- Nullable issues: `userid int DEFAULT 0` is nullable while part of unique `(torrent, userid)`. Multiple `NULL` values would bypass uniqueness in InnoDB; production must confirm `userid IS NULL = 0`.
- Strict blockers: `startedat` and `completedat` are `NOT NULL` with no default; announce writes provide values.
- Charset blockers: none in table columns.
- Hotness score: HIGH.

### torrents

- Purpose: central release catalog and moderation state.
- Write intensity: medium/high; uploads, edits, downloads, moderation, announce stats.
- Routes/code: `/`, `browse.php`, `details.php`, `download.php`, `upload.php`, `edit.php`, `admin.php`, `categories.php`, `check_release.php`, `comments.last.php`, `my.book.php`, `my.releases.php`, `profile.php`, `scrape.php`, `update.peers.php`, `system/functions/functions.details.php`, `system/functions/functions.torrent_status.php`, `system/functions/functions.upload.php`.
- Indexes: primary `id`; unique `infohash`; browse/status indexes on `(banned,added)`, `(id_category,banned,added)`, `(id_user,added)`, `(news,added)`, `type`, `content_type`, `name(191)`, `(status,added)`, `(id_user,status)`.
- Zero dates locally: none in `added`, `last_action`, `reviewed_at`, `submitted_at`, `hidden_at`, `deleted_at`.
- Nullable dates: `hidden_at` and `deleted_at` are NULL for the active local torrent; expected.
- Zero-date defaults: none.
- Strict blockers: many `NOT NULL` text/varchar/datetime/int columns have no default; current upload/edit paths provide values, but production import/backfill scripts must be checked.
- Charset blockers: mixed collations across metadata columns (`utf8mb3_bin`, `utf8mb3_unicode_ci`, `utf8mb3_general_ci`).
- Runtime schema blocker: `system/functions/functions.torrent_status.php` still contains guarded `ALTER TABLE` and `UPDATE` schema-readiness code for `torrents`. That should be retired or made maintenance-only before final hot migration.
- Hotness score: HIGH.

### users

- Purpose: auth, identity, profile, passkey, permissions, ratio/accounting.
- Write intensity: high; login/session touches, profile edits, mail counters, announce accounting, signup, admin changes.
- Routes/code: almost site-wide. Key paths include `login.php`, `signup.php`, `profile.php`, `users.php`, `admin.php`, `my.setting.php`, `my.setting.take.php`, `ajax/profile.php`, `announce.php`, `download.php`, `my.mail.php`, `messages.php`, `shop.php`, `autoclean.php`, `edit_priv.php`, comment/profile helpers.
- Indexes: primary `id`; non-unique `name`; non-unique `email`; non-unique `passkey`.
- Zero dates locally: none in `last_access`, `added`, `birthday_date`.
- Zero-date defaults: none.
- Strict blockers: many `NOT NULL` columns have no default; current app inserts provide values. `modules/shop/add_slot.php` references `slots`, which is absent locally, but that is not a P12 migration blocker unless the shop item is enabled.
- Float smells: `bonus float`, `voice float` are money/points candidates for decimal conversion in a separate cleanup wave.
- Business-key smell: local duplicates are 0 for `name`, `email`, and non-empty `passkey`, but indexes are non-unique. These should become unique only after production duplicate cleanup and after confirming empty passkey policy.
- Charset blockers: mixed `utf8mb3_bin` and `utf8mb3_unicode_ci`.
- Hotness score: HIGH.

## Categories

### A) SAFE NEXT

None. All remaining MyISAM tables are hot/final tables.

The safest next candidate inside the remaining set is `trackers`, but it belongs in a rehearsal wave, not an immediate production migration.

### B) MEDIUM RISK

- `trackers`: smallest and simplest remaining table, but touched by announce and remote-update jobs.

### C) CLEANUP FIRST

- `users`: duplicate-data review before unique business keys; `bonus` and `voice` float-to-decimal cleanup; charset cleanup later.
- `torrents`: remove/retire runtime schema writes from `functions.torrent_status.php`; charset/schema cleanup later.
- `snatched`: verify `userid IS NULL = 0`; consider making `userid` `NOT NULL`.
- `trackers`: drop redundant unique `id` in same rehearsed wave or before engine conversion; consider index for remote-update scan.
- `peers`: no data cleanup locally, but needs production peak row/write-rate audit and online migration strategy.

### D) HOT / LAST

- `peers`
- `trackers`
- `snatched`
- `torrents`
- `users`

`peers` should remain absolute last unless announce traffic can be paused or an online schema migration tool is approved.

## Index Findings

Overlapping/redundant:

- `trackers.id` duplicates `PRIMARY(id)` and is safe to drop after rehearsal.
- `peers.torrent` is prefix-covered by several compound indexes. Keep it until production query digest proves it is redundant.
- No equivalent duplicate index shapes remain on `torrents`, `users`, or `snatched`.

Missing/index candidates:

- `trackers(lastchecked, tracker, torrent)` or similar may help `update.peers.php` remote scans. Needs production EXPLAIN because `tracker <> 'localhost'` selectivity may be poor.
- `users.name`, `users.email`, `users.passkey` should likely be unique business keys after production duplicate checks and empty-passkey policy review.
- `torrents` browse/status indexes look adequate locally; production should verify search/filter query plans.

## Strict-Mode Blockers

Current SQL mode includes `STRICT_TRANS_TABLES`, `NO_ZERO_IN_DATE`, and `NO_ZERO_DATE`.

Local real zero-date values: none in remaining MyISAM date columns.

Potential blockers are mainly `NOT NULL` columns without defaults:

- `peers`: peer identity/date fields.
- `snatched`: `startedat`, `completedat`.
- `torrents`: many metadata fields plus `added`, `last_action`.
- `users`: many profile/accounting fields.
- `trackers`: `torrent`, `state`.

These are compatible with current app writes, but any production import, repair, or legacy script must provide explicit values.

## Charset Blockers

Do not combine charset conversion with engine conversion.

Notable mixed charset/collation tables:

- `peers`: `peer_id` is `cp1251`, other string fields are `utf8mb3`.
- `torrents`: mixed `utf8mb3_bin`, `utf8mb3_unicode_ci`, `utf8mb3_general_ci`.
- `users`: mixed `utf8mb3_bin`, `utf8mb3_unicode_ci`.

## Float To Decimal Candidates

- `users.bonus float`
- `users.voice float`

These are accounting/points values. Convert in a separate cleanup wave after deciding precision/scale, for example `DECIMAL(12,4)` or a smaller scale if UI/business rules require whole points.

## InnoDB Benefit

Largest practical benefit:

- `peers`: row-level locking for announce churn; biggest operational win but highest migration risk.
- `users`: row-level locking for auth/profile/announce accounting.
- `trackers`: row-level locking for aggregate count updates.
- `snatched`: transaction safety for accounting rows.
- `torrents`: crash safety and less table-level blocking for catalog writes.

## Recommended Migration Order

1. `trackers` rehearsal: drop redundant `id` index, add/decline remote scan index after EXPLAIN, convert to InnoDB locally/staging, smoke announce/details/browse/update.peers.
2. `snatched` rehearsal: confirm no nullable `userid`, convert, smoke announce completion and profile stats.
3. `torrents` cleanup/rehearsal: retire runtime schema writes, verify browse/search/details plans, then convert.
4. `users` cleanup/rehearsal: duplicate checks, unique key decision, float-to-decimal plan, then convert.
5. `peers` final wave: online migration or announce maintenance window; convert last.

## Proposed P13

P13 should target `trackers` only:

- audit production row count/size and duplicate `(torrent, tracker)`;
- review `update.peers.php` EXPLAIN for remote tracker scan;
- prepare a local/staging rehearsal script for `trackers`;
- optionally drop redundant unique `id`;
- optionally add a remote-scan index if production proves it helps;
- convert `trackers` to InnoDB in rehearsal only, with announce/details/browse/update.peers smoke.

## Biggest Remaining Production Blockers

1. No production row counts/write rates for `peers`, `snatched`, `torrents`, `users`.
2. `peers` needs an online/maintenance strategy; direct blocking ALTER is risky.
3. `users` needs unique-key and float-to-decimal decisions before final migration.
4. `torrents` still has runtime schema mutation code in `functions.torrent_status.php`.
5. Mixed charsets should stay out of engine-conversion waves.
6. Production EXPLAIN/query digest is needed before dropping any prefix-covered hot indexes.

## SQL Artifact

Created `database/p12_remaining_myisam_plan.sql`.

It is report-only and contains no executable migration statements. Potential ALTER examples are commented.
