# Wave D4: details user-state + chrome query bundling

## A) Было после D3

- Warm repeated `/details.php?id=1`: 7 SQL queries in the D3 measurement.
- Remaining details/comments/chrome queries were user rating, bookmark state, `torrent_views` write, unread mail count, open reports count, and current user comment reactions.
- `SET time_zone` stayed as global bootstrap budget.

## B) Стало после D4

Measured authenticated `/details.php?id=1` for `user_id=1` after invalidating `torrents`, `comments`, and `users` cache namespaces:

- Cold: 18 SQL queries.
- Warm first same-session: 4 SQL queries.
- Warm repeated same-session: 4 SQL queries.

Repeated SQL list:

1. `SET time_zone`
2. bundled details user-state query: current user rating + bookmark state
3. open wall/comment reports count
4. current user comment reactions

No runtime `SHOW TABLES/COLUMNS/INDEX` queries were present.

## C) Какие queries объединены/закешированы

- Current user rating and bookmark state are now loaded by `lt_details_user_state()` in one request-level cached query.
- `lt_details_prepare_rating()` and `lt_details_prepare_bookmark()` reuse that state instead of issuing separate queries.
- `torrent_views` now uses a `details:view-seen:{torrentId}:{visitorHash}:v1` cache throttle before `INSERT IGNORE`, TTL 600 seconds.
- Unread mail count is cached per user with `user:unread_mail_count:{userId}:v1`, TTL 20 seconds.
- Mail cache invalidation is connected to `lt_sync_user_unread_messages()`, `send_msg()`, and `my.mail.php` user cache invalidation.
- Current user comment reactions now have request-level reuse for the same context/user/comment set.
- Added admin open reports cache key/invalidation helpers and invalidated them on new comment reports.

## D) Что осталось в SQL budget

- `SET time_zone` is bootstrap/global.
- Details user-state remains one live query because bookmark state has no allowed write-path invalidation in this wave.
- Open reports count is still emitted by the header template directly on details pages; the cache helper exists, but wiring it would require touching the template, which was outside the D4 allowlist.
- Current user reactions remain live because they are user-specific and mutation-sensitive.

## E) Честный план до 2-4 SQL с кешем

- Wire `user_wall_reports_open_count()` into the header template in a dedicated chrome-budget wave.
- Add event invalidation for bookmark add/delete, then short-cache `lt_details_user_state()` across requests.
- Add per-user current reaction cache with invalidation on `react`.
- Move `SET time_zone` out of per-request counted work only if the DB/bootstrap layer can guarantee connection timezone once per connection.

## F) Почему 1-2 без кеша невозможно без denormalization/async/chrome split

The page still has independent live concerns: user state, user reactions, chrome counters, session/bootstrap state, and view accounting. Getting to 1-2 SQL without cache would require denormalized aggregates, async/write-behind view accounting, or splitting global chrome out of the main details request. D4 keeps semantics intact and only removes repeat work through bundling and short TTL cache.
