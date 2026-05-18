# LiteTracker Wave P5 Comments InnoDB Report

## Audit Summary

P5 audited only comment-related tables:

- `comments_torrents`
- `comments_users`
- `comments_news`
- `comments_faq`
- `comment_reactions`
- `comment_pins`
- `comment_edit_history`
- `comments_reports`
- `comments_users_reports`

Forbidden tables were not touched: `peers`, `trackers`, `snatched`, `torrents`, `users`, `sessions`, `mail`.

## Approved Tables

All candidate comment tables are approved for local InnoDB conversion.

| Table | Local rows | Current engine before P5 | Collation | Notes |
|---|---:|---|---|---|
| `comments_torrents` | 27 | MyISAM | `cp1251_bin` | Main torrent comments; small locally, higher production risk. |
| `comments_users` | 20 | MyISAM | `cp1251_bin` | Profile wall comments; small locally, higher production risk. |
| `comments_news` | 15 | MyISAM | `cp1251_bin` | News comments. |
| `comments_faq` | 0 | MyISAM | `cp1251_bin` | Empty locally. |
| `comment_reactions` | 7 | MyISAM | `utf8mb3_bin` | Small reaction metadata. |
| `comment_pins` | 3 | InnoDB | `utf8mb3_bin` | Already converted in P4. |
| `comment_edit_history` | 8 | InnoDB | `utf8mb3_bin` | Already converted in P4. |
| `comments_reports` | 1 | InnoDB | `utf8mb3_bin` | Already converted in P4. |
| `comments_users_reports` | 1 | InnoDB | `utf8mb3_bin` | Already converted in P4. |

No zero DATETIME values were found in checked date columns. Nullable columns such as `deleted_at`, `resolved_at`, `updated_at`, and some `date_edit` values contain NULLs, which are valid and preserved.

## Index Readiness

Existing indexes cover the basic paths:

- object/date listing for `comments_users`, `comments_news`, `comments_faq`;
- parent/thread lookup indexes;
- report moderation indexes;
- reaction uniqueness and reaction counts;
- pin and edit-history lookups.

Gaps found:

- `comments.last.php` orders `comments_torrents` globally by `date DESC` and had no global `date,id` index.
- `comments_torrents` had `id_torrents,date` without `id`, while render queries order by `date,id`.
- duplicate-recent checks query by object + `id_user` then order by date/id.

## Additive Indexes

P5 adds only guarded additive indexes:

- `comments_torrents(date,id)` for latest-comments page;
- `comments_torrents(id_torrents,date,id)` for stable object comment ordering;
- `comments_torrents(id_torrents,id_user,date,id)` for duplicate-recent checks;
- `comments_users(id_users,id_user,date,id)` for duplicate-recent checks;
- `comments_news(id_news,id_user,date,id)` for duplicate-recent checks;
- `comments_faq(id_faq,id_user,date,id)` for duplicate-recent checks.

No destructive index drops are included.

## Deferred Tables

No candidate table is deferred from the P5 SQL plan.

Production execution is deferred until a maintenance window because `comments_torrents` and `comments_users` may be materially larger on a real tracker.

## SQL File

Created `database/p5_comments_innodb.sql`.

The migration contains:

- preflight table engine/size inventory;
- preflight index inventory;
- zero-date guard;
- guarded `ALTER TABLE ... ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- guarded additive indexes;
- rollback examples.

## Local Apply Status

Applied locally on the Docker MySQL 8.4 database after clean zero-date preflight.

Local result:

- all 9 candidate comment tables are now `InnoDB`;
- forbidden tables remained unchanged: `peers`, `trackers`, `snatched`, `torrents`, `users`, `sessions`, `mail`;
- additive indexes were created on `comments_torrents`, `comments_users`, `comments_news`, and `comments_faq`;
- `cp1251_bin` comment text tables stayed `cp1251_bin`.

Verification:

- `git diff --check` passed;
- `SHOW TABLE STATUS` confirmed candidate tables are `InnoDB`;
- `SHOW INDEX` confirmed additive indexes;
- smoke routes returned `200` without PHP fatal/warning output: `/`, `details.php?id=1`, `profile.php?id=1`, `comments.last.php`;
- `ajax/comments.php` returned JSON `200` for a basic request path without PHP fatal/warning output.

## Production Risk

Local risk is low because all candidate tables are tiny in the Docker dataset.

Production risk is medium for `comments_torrents` and `comments_users`: MyISAM to InnoDB rebuilds the table, may lock writes, and should be run after backup during a quiet window.

No charset/collation changes are made. Existing `cp1251_bin` comment text remains unchanged.

## Next Wave

1. Run production `SHOW TABLE STATUS` for comment row counts and sizes.
2. Apply P5 during a quiet window.
3. Run `EXPLAIN` for `comments.last.php`, details comments, profile wall comments, reactions, and report moderation pages.
4. Consider seek pagination for long comment threads after InnoDB is stable.
