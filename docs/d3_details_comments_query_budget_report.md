# Wave D3: details comments payload query budget

## A) Было после D2

- До D3 warm repeated `/details.php?id=1`: около 10 SQL queries.
- Основной остаточный comments budget: comments list, reactions summary, current user reactions, comment pin, возможный user preload/priv lookup.
- Runtime schema checks после D1: 0 `SHOW TABLES/COLUMNS/INDEX`.

## B) Стало после D3

Замер через authenticated `/details.php?id=1` для `user_id=1`.

- Full cold после сброса details + comments cache: 16 SQL queries.
- Comments cold при уже warm details cache: 11 SQL queries.
- Warm comments cache: 7 SQL queries.
- Repeated same-session warm: 7 SQL queries.

Оставшиеся repeated queries:

1. `SET time_zone`
2. current user torrent rating
3. bookmark state/count
4. `torrent_views` side-effect insert
5. unread mail count
6. open comment reports count
7. current user reactions for visible comments

## C) Какие comments queries ушли

- Comments rows for `comments_torrents` moved into shared payload cache.
- Reaction summary `COUNT(*) GROUP BY comment_id, reaction` moved into shared reactions summary cache.
- `comment_pins` lookup moved into shared pin cache.
- User preload now benefits from existing user cache on warm requests; no new live user lookup is required on the measured repeated path.
- Priv/class lookup did not appear in repeated SQL after warm cache.

Current user reaction state remains live by design.

## D) Cache keys / invalidation

Added keys in `app/Support/CacheKeys.php`:

- `comments:payload:{contextType}:{contextId}:v1`
- `comments:reactions-summary:{contextType}:{contextId}:v1`
- `comments:pin:{contextType}:{contextId}:v1`

TTL:

- comments payload: 120 seconds
- reactions summary: 60 seconds
- pin: 300 seconds

Invalidation:

- `lt_cache_invalidate_comments($contextType, $contextId)` clears all three keys.
- AJAX invalidates on add/edit/delete/restore/react.
- Pin/unpin invalidates in `comments_set_pin()` / `comments_unpin()`.
- Legacy `comments.take.php` invalidates on add/delete/edit.

## E) Что осталось в SQL budget

- Current user reactions are intentionally live/user-specific.
- User rating and bookmark state are still live/user-specific.
- `torrent_views` insert/count remains side-effect oriented and should not be hidden.
- Mail unread count and open reports count are global chrome/admin counters.
- First comments payload fill still needs comments rows + reaction summary + pin.

## F) План 0-1 query with full cache

- Split user reaction state into a short per-user cache with reaction mutation invalidation.
- Add short TTL/cache invalidation for current user torrent rating and bookmark state.
- Move unread mail/open report counters behind existing user/admin chrome cache.
- Keep `torrent_views` write as the only unavoidable details side-effect, or move view counting to async/write-behind in a separate wave.
- Keep comments payload invalidation event-based; TTL-only is safe fallback but should not be the long-term primary freshness model.
