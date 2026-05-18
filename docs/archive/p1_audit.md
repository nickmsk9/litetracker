# LiteTracker Wave P1 Audit

Scope: production readiness, performance, DB, security, cache. CSS/UI and announce protocol intentionally untouched.

## Executive verdict

1. Hot write tables are still MyISAM: `peers`, `trackers`, `snatched`, `users`, `torrents`, `mail`, `sessions`, comments. This is the main lock/stability blocker.
2. `mail` has only a primary key while `my.mail.php` filters and sorts by user/delete/read/date on every page.
3. `browse.php` and `index.php` used row-buffered grouped counts and per-row tracker subqueries; fixed in code.
4. `browse.php` search uses many leading-wildcard `LIKE` predicates across `TEXT` columns; fulltext/normalized metadata is the largest future catalog win.
5. Metadata filters use CSV columns and `FIND_IN_SET`; this cannot scale with indexes.
6. Comment tables are mixed MyISAM/cp1251 and some lack object/date indexes.
7. `users.name`, `users.email`, `users.passkey`, `tags(category,name)` are not unique, but should be checked for duplicates before unique migrations.
8. Money-like `users.bonus`, `users.voice`, `shop.voice` are `float`; migrate to `DECIMAL` after duplicate/rounding audit.
9. `0000-00-00` defaults remain in `bans`, `priv`, `sessions`, `shop`; incompatible with strict SQL modes.
10. File upload flow validates image type, but public writable directories and upload-before-final-id naming still need hardening.

## Table-by-table database report

| Table | Issue / impact | SQL proposal | Risk | Est. impact |
|---|---|---|---|---|
| `bans` | MyISAM, cp1251, zero date; announce IP ban reads can lock behind writes. | `ALTER TABLE bans ENGINE=InnoDB; ALTER TABLE bans MODIFY date DATETIME NULL;` | Medium | Stability |
| `birthday_rewards` | MyISAM write table with unique user/year. | `ALTER TABLE birthday_rewards ENGINE=InnoDB;` | Low | Lock reduction |
| `books` | MyISAM; existing index OK for bookmarks. | `ALTER TABLE books ENGINE=InnoDB;` | Low | Lock reduction |
| `categories` | MyISAM, `name` is TEXT; small reference table. | Later: `name VARCHAR(191)`, utf8mb4, InnoDB. | Medium | Low |
| `chat` | MyISAM, cp1251, no date index. | `ALTER TABLE chat ADD KEY idx_chat_date (date);` | Low | Medium if chat enabled |
| `comment_edit_history` | MyISAM write log; indexes OK. | `ALTER TABLE comment_edit_history ENGINE=InnoDB;` | Low | Lock reduction |
| `comment_pins` | MyISAM, unique OK. | `ALTER TABLE comment_pins ENGINE=InnoDB;` | Low | Lock reduction |
| `comment_reactions` | MyISAM hot write table; indexes OK. | `ALTER TABLE comment_reactions ENGINE=InnoDB;` | Low/Medium | High under reactions |
| `comments_faq` | MyISAM, cp1251, no object/date index. | Added proposal in `database/p1_safe_indexes.sql`. | Low | High for FAQ comments |
| `comments_news` | MyISAM, cp1251, missing `id_news,date,id`. | Added proposal in `database/p1_safe_indexes.sql`. | Low | High for news comments |
| `comments_reports` | MyISAM moderation write table; indexes OK. | `ALTER TABLE comments_reports ENGINE=InnoDB;` | Low | Lock reduction |
| `comments_torrents` | MyISAM hot table; object/date index exists. | Later InnoDB migration; optional `(id_torrents,is_deleted,date,id)`. | Medium | High |
| `comments_users` | MyISAM, cp1251; object/date missing. | Added proposal in `database/p1_safe_indexes.sql`. | Low | High for profile walls |
| `comments_users_reports` | MyISAM; indexes OK. | `ALTER TABLE comments_users_reports ENGINE=InnoDB;` | Low | Lock reduction |
| `confirm` | MyISAM; duplicate unique `id` over PK. | `ALTER TABLE confirm DROP INDEX id, ENGINE=InnoDB;` | Low | Low |
| `cron` | MyISAM config table. | `ALTER TABLE cron ENGINE=InnoDB;` | Low | Stability |
| `faq` | MyISAM, utf8mb3; no date index. | `ALTER TABLE faq ADD KEY idx_faq_added (added);` | Low | Low |
| `files` | MyISAM; torrent index OK. | `ALTER TABLE files ENGINE=InnoDB;` | Low | Low |
| `forgot` | MyISAM; missing email/code/date indexes. | `ALTER TABLE forgot ADD KEY idx_forgot_email (email), ADD KEY idx_forgot_code (code), ADD KEY idx_forgot_date (date);` | Low | Medium |
| `friends` | MyISAM; unique OK, but reverse lookup may scan. | `ALTER TABLE friends ADD KEY idx_friendid_status (friendid,status);` | Low | Medium |
| `mail` | MyISAM and no indexes except PK; current mail list is O(N) plus N+1. | Added proposal in `database/p1_safe_indexes.sql`. | Low | Very high |
| `moderation_log` | MyISAM write log; indexes OK. | `ALTER TABLE moderation_log ENGINE=InnoDB;` | Low | Lock reduction |
| `news` | MyISAM; date index OK; `name` TEXT. | Later `name VARCHAR(255)`, utf8mb4, InnoDB. | Medium | Low |
| `notifications` | InnoDB good; list filters archived/read by user and orders id. | Added proposal in `database/p1_safe_indexes.sql`. | Low | High for notification list |
| `peers` | MyISAM hottest announce table; table locks dominate scale. Some duplicate left-prefix indexes. | Report only: migrate to InnoDB after announce load test; later drop redundant `torrent`. | High | Very high |
| `polls` | MyISAM; small. | `ALTER TABLE polls ENGINE=InnoDB;` | Low | Low |
| `polls_questions` | MyISAM; missing poll index. | `ALTER TABLE polls_questions ADD KEY idx_poll (id_poll);` | Low | Medium if polls used |
| `polls_voting` | MyISAM; should prevent duplicate votes. | `ALTER TABLE polls_voting ADD UNIQUE KEY uniq_vote (id_poll,id_user);` after duplicate check. | Medium | Correctness |
| `priv` | MyISAM, zero dates; reference table. | `ALTER TABLE priv ENGINE=InnoDB;` and zero-date cleanup. | Medium | Stability |
| `retrackers` | MyISAM; small; sort queries need index. | `ALTER TABLE retrackers ADD KEY idx_sort (sort);` | Low | Low |
| `search_query` | MyISAM, cp1251, TEXT searched/grouped; leading wildcard not indexable. | Added low-risk prefix/date indexes; future hash/normalized query key. | Low now, Medium future | Medium |
| `sessions` | MyISAM hot writes, cp1251, zero date; lock risk. | Report only: InnoDB plus narrower `user_agent/php_self VARCHAR(255)`. | Medium | High |
| `shop` | MyISAM, `voice float`, zero date, file/name TEXT. | `ALTER TABLE shop MODIFY voice DECIMAL(12,2) NOT NULL DEFAULT 0.00;` after rounding check. | Medium | Correctness |
| `snatched` | MyISAM announce write path; unique OK. | Report only: InnoDB after announce test. | High | Very high |
| `tags` | MyISAM; no unique category/name. | `ALTER TABLE tags ADD UNIQUE KEY uniq_category_name (category,name);` after duplicate check. | Medium | Medium |
| `torrent_ratings` | MyISAM write table; unique OK. | `ALTER TABLE torrent_ratings ENGINE=InnoDB;` | Low | Lock reduction |
| `torrent_views` | MyISAM details write path; unique OK, count can grow. | InnoDB; later cached/materialized counts. | Medium | High on details |
| `torrents` | MyISAM central table; CSV metadata/FIND_IN_SET; many TEXT filters. | Report only: InnoDB + fulltext/metadata bridge in later wave. | High | Very high |
| `trackers` | MyISAM announce write path; unique OK; duplicate unique `id` over PK. | Report only: InnoDB after announce test; `DROP INDEX id`. | High | Very high |
| `user_admin_notes` | MyISAM; index OK. | `ALTER TABLE user_admin_notes ENGINE=InnoDB;` | Low | Low |
| `users` | MyISAM hot table; non-unique name/email/passkey; `bonus/voice float`. | Duplicate check, then unique indexes; DECIMAL for money-like fields. | Medium | Correctness/high |
| `users_blacklist` | MyISAM; indexes OK. | `ALTER TABLE users_blacklist ENGINE=InnoDB;` | Low | Lock reduction |

## Hot path audit

| File:line | Issue | Safe fix | Complexity | Risk |
|---|---|---|---|---|
| `browse.php:556` | Count used grouped result buffering and `num_rows()`. | Replaced with derived `COUNT(*)`. | Low | Low |
| `browse.php:583` | Correlated tracker subquery per torrent row. | Replaced with conditional aggregate over joined trackers. | Low | Low |
| `browse.php:90`, `browse.php:499` | `FIND_IN_SET` over CSV metadata/tags cannot use indexes. | Future bridge tables for tags/metadata. | Medium | Medium |
| `browse.php:221-269` | Leading wildcard search over `TEXT` fields. | Future FULLTEXT index or search table. | Medium | Medium |
| `browse.php:569-574` | Search log uses `LIKE '%query%'` for existence/update. | Future exact normalized key/hash. | Low | Medium |
| `index.php:86` | Count joined/grouped trackers unnecessarily. | Replaced with `COUNT(DISTINCT t.id)` without tracker join. | Low | Low |
| `index.php:107` | Correlated tracker subquery per row. | Replaced with conditional aggregate. | Low | Low |
| `my.mail.php:626-653` | Conversation page count + slice queries need composite mail indexes. | `database/p1_safe_indexes.sql`. | Low | Low |
| `my.mail.php:699-722` | Mail list has N+1: one partner lookup and one last-message query per conversation. | Use latest-id derived table + bulk user preload. | Medium | Low |
| `system/functions/functions.comments.php:584-605` | Duplicate comment check sorts by date without object/user/date index. | Add object/date indexes; future `(object,id_user,date)`. | Low | Low |
| `system/functions/functions.comments.php:825-831` | Comments fetch depends on object/date indexes. | `database/p1_safe_indexes.sql`. | Low | Low |
| `system/functions/functions.notifications.php:292-299` | Notification list filters archived/read and orders by id; existing index is created_at-based. | `database/p1_safe_indexes.sql`. | Low | Low |

## Security audit

| File:line | Issue | Status / safe fix |
|---|---|---|
| `system/functions/functions.announce.php:130` | `announce_escape()` left numeric strings unquoted. All-digit passkey/infohash could be compared as numbers. | Fixed: only native int/float are unquoted. |
| `download.php:171` | `Content-Disposition` uses DB filename with only comma/semicolon stripped. | Sanitize CR/LF/quotes and add RFC 5987 `filename*`. Not applied. |
| `upload.php:359-365` | Uses next auto-increment id before insert for public image names; concurrent uploads can collide before retarget. | Use temp random names before DB insert. Not applied. |
| `upload.php:394-458` | DB row can exist if final torrent `move_uploaded_file` fails. | Move to temp first or wrap DB changes in transaction after InnoDB migration. Not applied. |
| `app/helpers/UploadAssetHelper.php:10` | Creates public upload dirs as `0777`. | Use `0755`/configurable umask. Not applied because filesystem permissions may be deployment-specific. |
| `system/functions/functions.php:1473-1492` | Flash `<object>/<embed>` BBCode for YouTube/Rutube is obsolete attack surface. | Replace with iframe allowlist in later compatibility wave. |

## Memcached/cache audit

Quick wins:
- `browse.php:141` facet counts are cached for 60s but no explicit invalidation on upload/edit; stale but bounded. Prefer namespace `browse` invalidation on torrent/tag changes.
- `lt_cache_remember()` has no stampede protection; hot misses on browse facets and announce torrent cache can pile up.
- Mixed legacy keys remain: direct `$memcached->delete('user_'.$id)` in `my.mail.php` and canonical `lt_cache_key_user()` elsewhere. Standardize on `lt_cache_invalidate_user()`.
- `notifications` unread cache uses default namespace and 45s TTL; invalidation exists and is acceptable.
- Filecache fallback serializes values safely with `allowed_classes=false`, but public chmod/ownership and cache directory placement should be reviewed for production.

## Safe apply changes done

- `index.php`: count query no longer joins/groups trackers; local seeders now computed by aggregate.
- `browse.php`: count query no longer transfers grouped rows to PHP; local seeders now computed by aggregate.
- `system/functions/functions.announce.php`: string escaping fixed for all-digit announce strings.
- `database/p1_safe_indexes.sql`: additive index migration for mail/comments/notifications/search_query; applied successfully to the local MySQL 8.4 container.
