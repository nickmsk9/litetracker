# D1 Details Query Budget Report

## A) SQL Before

Starting point from the D1 brief:

- `/details.php?id=1`: approximately `35` SQL queries.
- The main visible waste was repeated runtime schema readiness checks:
  - `SHOW TABLES LIKE ...`
  - `SHOW COLUMNS ...`
  - `SHOW INDEX ...`
  - repeated table/column/index checks for comments, ratings, views, and global chrome helpers.

## B) SQL After

Measured locally through the debug panel as superadmin on `https://localhost/details.php?id=1`.

- Cold schema capability cache miss: `19` SQL queries.
- Warm schema capability cache hit: `18` SQL queries.
- Schema `SHOW` queries on warm request: `0`.

On a cold schema capability miss, the repeated checks are collapsed into three capability-map queries:

```sql
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()
SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
SELECT TABLE_NAME, INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE()
```

These are cached in the `schema` namespace and reused by `lt_table_exists`, `lt_column_exists`, and `lt_schema_has_index`.

## C) SHOW Checks Removed

Removed from the details/comments request path:

- `lt_table_exists(...)` no longer emits `SHOW TABLES LIKE ...`.
- `lt_column_exists(...)` no longer emits `SHOW COLUMNS FROM ... LIKE ...`.
- `comments_index_exists(...)` no longer emits `SHOW INDEX FROM ...`.
- Details readiness checks for `torrent_ratings` and `torrent_views` now use the same cached capability map.
- Comments readiness checks for `comments_torrents`, `comment_pins`, `comment_reactions`, and `comment_edit_history` now share request-level and cache-level schema capabilities.

The capability cache is versioned as `schema:capabilities:v1`, TTL `6h`, with request-level static caching. Existing schema cache deletes also invalidate the capability map.

## D) Queries Still Remaining

Warm measured query list:

- `SET time_zone`
- optional cron cache miss query
- user `last_access` update
- session upsert
- main torrent/details query
- ratings aggregate
- remote trackers
- bookmark count
- files list
- torrent view insert
- torrent view count
- unread mail count
- unread notifications count
- open comment reports count
- comments list
- comment reaction summary
- current-user reactions
- pinned comment lookup

One schema helper outside the D1 allowlist remains in `system/functions/functions.torrent_status.php`: `lt_torrent_index_exists(...)` still uses `SHOW INDEX` on a cold torrent-status schema check. I did not edit that file because it was not in the allowed file list.

## E) Next PR To Reach <= 10

The next PR should bundle the read-side details blocks:

- Fold ratings aggregate, bookmark count, file count/list metadata, and view count into either the main details query or one cached details aggregate.
- Cache remote tracker rows by torrent id with invalidation on tracker refresh/announce updates.
- Cache comments list/reactions/pin summary as one comment payload per `(type, object_id)`, with invalidation from comment/reaction/pin mutations.
- Move notification/mail/report counters into a shared per-user chrome counter cache.

This should remove roughly 7-10 more queries from the warm details page.

## F) Path To <= 5 And 0-1 With Cache

For <= 5 without page cache:

- main torrent/details query
- combined per-torrent aggregate query
- combined comments payload query
- user chrome counters query
- user-specific view/bookmark/rating mutation or lookup

For 0-1 with cache on the main details view:

- cache a complete public torrent details payload by torrent id;
- cache comments payload separately by `(type, object_id)`;
- cache remote trackers/files/ratings aggregate inside the torrent payload or as versioned child keys;
- leave only user-specific counters/actions uncached or served from small per-user caches;
- invalidate via torrent edit, file edit, tracker update, rating write, view write, bookmark write, and comment/reaction/pin mutations.

## Verification

Commands run:

```sh
php -l system/functions/functions.php
php -l system/functions/functions.comments.php
php -l details.php
php -l system/functions/functions.details.php
git diff --check
```

Local page checks:

```sh
curl -sk -H 'X-Forwarded-For: 8.8.8.8' --cookie 'id_user=1; id_password=...' 'https://localhost/details.php?id=1'
```

Result:

- Details page rendered.
- Debug panel query count dropped from the reported `~35` to `19` cold schema-cache / `18` warm schema-cache.
- Warm debug panel showed no `SHOW TABLES`, `SHOW COLUMNS`, or `SHOW INDEX` queries.
