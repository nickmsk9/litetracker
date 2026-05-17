# D2 Details View-Model Query Budget Report

## A) After D1

D1 removed repeated runtime schema checks from the details/comments path.

Measured D1 state:

- cold schema capability cache: about `19` SQL queries;
- warm schema capability cache: about `18` SQL queries;
- `SHOW TABLES` / `SHOW COLUMNS` / `SHOW INDEX`: `0` on warm requests.

## B) After D2

Measured locally on `https://localhost/details.php?id=1` as superadmin.

- cold details view-model cache after `lt_cache_invalidate_torrent(1)`: `17` SQL queries;
- warm details cache, first same-session request: `11` SQL queries;
- warm details cache, repeated same-session request: `10` SQL queries.

The repeated same-session warm count is the best browser-like measurement because session writes are already throttled by the existing session touch cache.

## C) Queries Removed From Warm Details

The warm request no longer queries:

- main torrent row plus tracker aggregate;
- rating summary aggregate;
- remote tracker rows;
- files list;
- torrent view count.

The view insert remains live by design.

## D) Cache Keys Added

Namespace: `torrents`.

- `details:static:{torrentId}:v1`, TTL `300s`
- `details:files:{torrentId}:v1`, TTL `600s`
- `details:trackers:{torrentId}:v1`, TTL `60s`
- `details:rating-summary:{torrentId}:v1`, TTL `120s`
- `details:view-count:{torrentId}:v1`, TTL `60s`

`lt_cache_invalidate_torrent($torrentId)` now invalidates these keys. Tracker updates still primarily rely on the short tracker TTL unless the caller explicitly invalidates the torrent cache.

## E) Remaining SQL Budget

Warm repeated request query list:

- `SET time_zone`
- current user rating
- current user bookmark state
- torrent view insert side effect
- unread mail count
- open comment reports count
- comments list
- comment reactions summary
- current user reactions
- comment pin lookup

The remaining budget is now mostly user-specific chrome and comments. D2 intentionally did not move comments to AJAX-only, did not hide blocks, and did not cache current-user rating/bookmark state.

## F) Path To <=5 And 0-1 With Cache

For `<=5` without full page cache:

- combine current user rating and bookmark state into one user-details query;
- cache or batch user chrome counters (`mail`, notifications, reports);
- build a comments payload cache with rows, reaction summary, and pin in one invalidated object;
- keep view insert as the one live side effect.

For `0-1` with cache:

- cache complete public details static payload by torrent id;
- cache comments payload by `(type, object_id)` with invalidation on comment/reaction/pin mutations;
- keep current-user state in a small user-details payload or one live query;
- leave only view insert as the unavoidable write unless view logging is queued.

## Verification

Commands run:

```sh
php -l details.php
php -l system/functions/functions.details.php
php -l system/functions/functions.php
php -l app/Support/CacheKeys.php
php -l app/Support/CacheInvalidation.php
git diff --check
```

Local page check:

```sh
curl -sk -c /tmp/d2_cookies.txt -b /tmp/d2_cookies.txt \
  -H 'X-Forwarded-For: 8.8.8.8' \
  --cookie 'id_user=1; id_password=...' \
  'https://localhost/details.php?id=1'
```

Result:

- page rendered;
- warm repeated query count: `10`;
- files, trackers, rating, comments, and category markers were present in the rendered HTML.
