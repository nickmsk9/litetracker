# LiteTracker Wave P4 Low-risk InnoDB Report

## Executive Summary

P4 targets tiny support tables only. Hot announce and large content tables are explicitly excluded:

- `peers`, `trackers`, `snatched`;
- `torrents`, `users`, `sessions`;
- `comments_torrents`, `comments_users`.

All candidate tables are currently MyISAM locally and contain 0-11 rows. No checked DATETIME columns contain zero dates.

## Tables Approved For Safe InnoDB

Approved because they are tiny/local support tables with no announce hot-path ownership:

| Table | Rows local | Current collation | Hot-path relation | Notes |
|---|---:|---|---|---|
| `birthday_rewards` | 0 | `utf8mb3_bin` | autoclean only | Low write frequency. |
| `books` | 1 | `utf8mb3_bin` | browse/bookmark joins | Tiny locally; added reverse lookup index for torrent cleanup/detail checks. |
| `comment_edit_history` | 8 | `utf8mb3_bin` | comment moderation history | Append-only support history. |
| `comment_pins` | 3 | `utf8mb3_bin` | details/profile comment UI | Tiny pin metadata. |
| `comments_reports` | 1 | mixed ascii/cp1251 | moderation support | Preserves charset; no charset migration. |
| `comments_users_reports` | 1 | mixed ascii/cp1251 | moderation support | Preserves charset; no charset migration. |
| `confirm` | 0 | `utf8mb3_unicode_ci` | signup legacy/disabled | Duplicate `id` index detected, report-only. |
| `cron` | 11 | `utf8mb3_general_ci` | every request via cached cron config | Tiny and cache-backed. |
| `files` | 2 | `utf8mb3_unicode_ci` | details/download metadata | Existing `id_torrent` index is adequate. |
| `forgot` | 0 | `utf8mb3_unicode_ci` | password reset | Added indexes for `code`, `email`, `date`. |
| `friends` | 0 | `utf8mb3_general_ci` | profile/friends pages | Added `friendid,status` index. |
| `moderation_log` | 7 | `utf8mb3_general_ci` | admin dashboard | Append-only support log. |
| `polls` | 0 | `utf8mb3_unicode_ci` | polls feature | Empty locally. |
| `polls_questions` | 0 | `utf8mb3_unicode_ci` | polls feature | Added `id_poll` index. |
| `retrackers` | 0 | `utf8mb3_general_ci` | torrent file generation | Added `sort` index for `ORDER BY sort`. |
| `torrent_ratings` | 2 | `utf8mb3_general_ci` | details rating widget | Existing torrent/user indexes are adequate. |
| `user_admin_notes` | 5 | `utf8mb3_bin` | profile/admin support | Existing `user_id,created_at` index is adequate. |
| `users_blacklist` | 0 | `utf8mb3_bin` | profile/mail permission checks | Existing unique/user lookup indexes are adequate. |

## Deferred

No candidate table is deferred from the P4 SQL plan.

Report-only cleanup:

- `confirm` has duplicate equivalent indexes on `id`: `PRIMARY(id)` and unique key `id(id)`. P4 does not drop it because destructive index drops are outside this wave.

## SQL File

Created `database/p4_low_risk_innodb.sql`.

The migration includes:

- preflight engine/size inventory;
- preflight index inventory;
- zero-date inventory with abort guard;
- guarded `ALTER TABLE ... ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- guarded additive indexes only;
- rollback examples.

## Applied Locally

Applied locally on the Docker MySQL 8.4 database after clean preflight checks.

Local result:

- all 18 candidate support tables converted to `InnoDB`;
- forbidden hot tables remained unchanged: `peers`, `trackers`, `snatched`, `torrents`, `users`, `sessions`, `comments_torrents`, `comments_users`;
- additive indexes were created on `forgot`, `retrackers`, `books`, `friends`, and `polls_questions`.

Verification:

- `git diff --check` passed;
- `SHOW TABLE STATUS` confirmed P4 candidate tables are `InnoDB`;
- `SHOW INDEX` confirmed additive indexes;
- smoke routes returned `200` without PHP fatal/warning output: `/`, `browse.php`, `details.php?id=1`, `profile.php?id=1`, `login.php`, `signup.php`, `admin.php`.

## Additive Indexes

Added to the plan:

- `forgot(code)` for reset-link lookup/delete;
- `forgot(email)` for duplicate reset request checks;
- `forgot(date)` for autoclean expiry;
- `retrackers(sort)` for ordered retracker list;
- `books(id_torrent,id_user)` for torrent-detail/bookmark checks and cleanup;
- `friends(friendid,status)` for incoming friend request/list lookups;
- `polls_questions(id_poll)` for parent poll relation.

## Risk

Low for local and small support-table production datasets. Operational risk becomes medium if any table is unexpectedly large or receives heavy writes during migration, because MyISAM to InnoDB rebuilds the table.

No charset/collation changes are included. This intentionally preserves `cp1251` report snapshots and mixed `utf8mb3` collations.

## Next Wave

After observing production table sizes:

1. Apply P4 during a quiet window.
2. Re-check `SHOW TABLE STATUS` and hot route smoke tests.
3. Separately remove duplicate `confirm.id` index if production confirms no dependency.
4. Move to medium-risk tables only after P4 has run cleanly.
