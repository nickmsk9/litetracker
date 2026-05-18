# SQL Query Audit

Дата: 2026-05-18

Цель волны: уменьшить количество SQL-запросов без маскировки через Memcached, убрать очевидные N+1/повторные запросы и добавить измеримость SQL-бюджета в debug panel.

## Методика

Замеры сняты локально в Docker через PHP harness с авторизованным пользователем `id=1`. Числа включают глобальный bootstrap/chrome budget: `SET time_zone`, session upsert, user touch/unread/report counters. Поэтому для страниц со сложным chrome целевой пользовательский payload может быть уже 1 запрос, но общий request всё ещё выше.

## Карта страниц

| Page / Endpoint | Before | After | Target | Status | Problems / Notes | Files |
|---|---:|---:|---:|---|---|---|
| `index.php` | ~10 | 4-6 | 2-3 payload | Improved | Убран отдельный `COUNT(DISTINCT)`; список и total теперь в одном query через `COUNT(*) OVER()`. Chrome counters объединены в один query. | `index.php`, `head.php` |
| `browse.php` | ~6 | 5-6 | 2-3 payload | Improved | Убран отдельный count-subquery; list + total через window count. Facet counts остаются отдельным payload query при cold cache. | `browse.php`, `head.php` |
| `details.php?id=1` | ~10 | 7-12 | 3-5 | Kept | D2-D4 уже убрали основной N+1. Chrome counters объединены; comments warm/cold cache меняет фактический count. | `details.php`, `functions.details.php`, `functions.comments.php`, `head.php` |
| `profile.php?id=1` | ~10 | 5-8 | 2-3 payload | Improved | Для своего профиля убран повторный `SELECT * FROM users`; online state берётся из текущей сессии. Chrome counters объединены. | `profile.php`, `head.php` |
| `my.book.php` | ~5 | 4 | 2 payload | Improved | Убран отдельный count query; закладки + total через `COUNT(*) OVER()`. Также убран per-row subquery для local seeders. | `my.book.php` |
| `search_query.php` | ~5 | 5 | 3-4 | Deferred | Админская страница: count + list + chrome. Нужна отдельная волна для pagination/query history actions. | `search_query.php` |
| `sessions.php` | ~5 | 5 | 3-4 | Deferred | Count + list + chrome. Можно перевести на window count позже. | `sessions.php` |
| `rating.php` | ~3 | 3 | 2-3 | OK | Уже близко к бюджету. | `rating.php` |
| `faq.php` | ~4 | 4 | 2-3 | Deferred | CRUD/admin branch и list query. Низкий пользовательский риск, но не главный hot path. | `faq.php` |
| `shop.php` | ~4 | 4 | 3-4 | OK | Основной list query + chrome. Runtime `SHOW TABLE STATUS` остаётся только в admin add branch. | `shop.php` |
| `check_release.php` | ~3 | 3 | 2-3 | OK | Admin/moderation flow, не hot path. | `check_release.php` |
| `scrape.php` | N+1 risk | 1 payload | 1 | OK | Несколько `info_hash` теперь обрабатываются одним `WHERE infohash IN (...)`. | `scrape.php` |
| `announce.php` | 15 smoke pass | unchanged | correctness first | Deferred | Write-path уже выделен в P24. Здесь не менялся accounting/response; транзакционная волна отдельно. | `announce.php`, `functions.announce.php` |

## Убранные N+1 / Повторы

- `scrape.php`: повторяемый потенциальный запрос на каждый `info_hash` заменён одним batched query.
- `my.book.php`: per-row subquery для `local_seeders` заменён агрегатом `SUM(CASE WHEN tracker='localhost' ...)`.
- `index.php`, `browse.php`, `my.book.php`: отдельные count-запросы заменены window count в основном list query.
- `profile.php`: повторное чтение текущего пользователя из `users` убрано для собственного профиля.
- `get_user_info()` и `get_priv_info()`: добавлена request-level memoization, чтобы один request не повторял одинаковые справочные чтения при cache miss.
- `templates/default/head.php`: `mail` + `notifications` counters теперь собираются единым lightweight chrome bundle query.
- `templates/default/head.php`: report counter больше не читается в header, потому что текущий HTML не отображает число открытых жалоб.
- `lt_torrent_preload_author_users()`: текущий пользователь берётся из `$USER`, если он уже загружен, без повторного `SELECT users`.

## Global / Chrome SQL Budget

| Query group | Before | After | Notes |
|---|---:|---:|---|
| `SET time_zone` | 1 | 1 | Оставлен как infrastructure query: MySQL session timezone влияет на `NOW()`, `FROM_UNIXTIME()` и сортировки по времени. Убирать безопасно только при гарантированной timezone на уровне pool/server. |
| Session touch/upsert | 1 | 1 throttled | Уже есть throttle через `lt_cache_key_session_touch(...), TTL 60`. При активном filecache/Memcached повторные page loads не пишут session чаще раза в минуту. |
| User `last_access` / `ip` touch | 0-1 | 0-1 throttled | Логика уже обновляет `users.last_access` не чаще 10 минут; `ip` пишется только при изменении. Оставлено intentionally. |
| Unread mail count | 1 | bundled | Перенесено в `lt_current_user_chrome_state()`. |
| Unread notifications count | 1 | bundled | Перенесено в тот же `lt_current_user_chrome_state()` query. |
| Report counters | 1 | 0 in header | Header больше не читает report count, так как счётчик не выводится. `user_wall_reports_open_count()` остался conditional и без runtime `CREATE TABLE`. |
| Current user info | 1+ | 0 when `$USER` loaded | `get_user_info($currentUserId)` и torrent author preload используют `$USER`. |
| Privileges | repeated | request-level memoized | `get_priv_info()` memoized per request; дальнейший шаг - preload all needed classes одним запросом для тяжёлых страниц. |

## Current User Chrome Bundle

Добавлен `lt_current_user_chrome_state()`:

- для гостя не делает user-specific SQL;
- для пользователя делает один `SELECT` со scalar subqueries для `mail` и `notifications`;
- report count включается только явным `include_reports => true`, для header выключен;
- результат memoized на время request;
- `$USER['num_messages']` синхронизируется в памяти, без `UPDATE users` на каждом page load.

## Debug SQL Instrumentation

Существующая русская debug panel расширена:

- показывает бюджет SQL для текущего route;
- показывает разбивку `Infrastructure SQL` / `Chrome SQL` / `Payload SQL`;
- показывает превышение бюджета;
- считает повторные SQL-запросы по fingerprint;
- показывает список повторяющихся SQL-запросов;
- сохраняет маскирование чувствительных значений.

Debug доступен только при включённом debug и только admin/superadmin, как раньше.

## Индексы

Новые индексы в этой волне не добавлялись. Изменённые запросы используют существующие ключи и не требуют schema migration.

## Что осталось в бюджете

- Глобальный bootstrap/chrome: `SET time_zone` и session touch остаются infrastructure. Для цели 2-4 SQL total нужно решить, считаем ли их частью page budget или отдельной платформенной стоимостью.
- `browse.php` facet counts: полезная функциональность фильтров, но cold-cache даёт дополнительный query. Для строгого no-cache бюджета нужен materialized/faceted summary или более узкий query по активным фильтрам.
- Comments current-user reactions остаются отдельным user-specific query. Склеивать с shared payload рискованно из-за прав/CSRF/user state.
- Admin pages `sessions.php`, `search_query.php`, `faq.php` можно отдельно перевести на `COUNT(*) OVER()` и pagination helpers.

## Риски

- `COUNT(*) OVER()` требует MySQL 8+, что совпадает с текущим Docker `mysql:8.4`.
- Для пустой страницы за пределами диапазона total count будет `0`, как и фактическая выдача. Обычный page=0 path корректен.
- `scrape.php` теперь возвращает только найденные hashes в `files` dictionary. Если не найден ни один hash, поведение осталось failure reason.

## Следующая волна

1. Вынести общий torrent-list query helper для `index.php`, `browse.php`, `my.book.php`.
2. Перевести `sessions.php` и `search_query.php` на window count.
3. Для страниц `sessions.php` / `search_query.php` перевести count на window count.
4. Отдельно пройти comments current-user state и AJAX actions, не смешивая с shared cached payload.
5. Рассмотреть DB/server-level timezone вместо per-connection `SET time_zone`, если production окружение это гарантирует.
