# SQL Query Audit

Дата: 2026-05-18

Цель волны: уменьшить количество SQL-запросов без маскировки через Memcached, убрать очевидные N+1/повторные запросы и добавить измеримость SQL-бюджета в debug panel.

## Методика

Замеры сняты локально в Docker через PHP harness с авторизованным пользователем `id=1`. Числа включают глобальный bootstrap/chrome budget: `SET time_zone`, session upsert, user touch/unread/report counters. Поэтому для страниц со сложным chrome целевой пользовательский payload может быть уже 1 запрос, но общий request всё ещё выше.

## Карта страниц

| Page / Endpoint | Before | After | Target | Status | Problems / Notes | Files |
|---|---:|---:|---:|---|---|---|
| `index.php` | ~10 | 6 | 2-3 payload | Improved | Убран отдельный `COUNT(DISTINCT)`; список и total теперь в одном query через `COUNT(*) OVER()`. Остаток: session/chrome/unread/report. | `index.php` |
| `browse.php` | ~6 | 5 | 2-3 payload | Improved | Убран отдельный count-subquery; list + total через window count. Facet counts остаются отдельным payload query при cold cache. | `browse.php` |
| `details.php?id=1` | ~10 | 5-7 warm | 3-5 | Kept | D2-D4 уже убрали основной N+1. Остаток: user rating/bookmark, current user reactions, report counter, session/timezone. | `details.php`, `functions.details.php`, `functions.comments.php` |
| `profile.php?id=1` | ~10 | 5-6 | 2-3 payload | Improved | Для своего профиля убран повторный `SELECT * FROM users`; online state берётся из текущей сессии. Остаток: peer stats, wall reactions, report counter. | `profile.php` |
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

## Debug SQL Instrumentation

Существующая русская debug panel расширена:

- показывает бюджет SQL для текущего route;
- показывает превышение бюджета;
- считает повторные SQL-запросы по fingerprint;
- показывает список повторяющихся SQL-запросов;
- сохраняет маскирование чувствительных значений.

Debug доступен только при включённом debug и только admin/superadmin, как раньше.

## Индексы

Новые индексы в этой волне не добавлялись. Изменённые запросы используют существующие ключи и не требуют schema migration.

## Что осталось в бюджете

- Глобальный bootstrap/chrome: `SET time_zone`, session upsert, unread/report counters. Для цели 2-4 SQL с кешем нужно выносить chrome counters в более общий user-state bundle или async/chrome split.
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
3. Сделать user chrome bundle: unread mail + notifications + open reports одним lightweight state layer.
4. Отдельно пройти comments current-user state и AJAX actions, не смешивая с shared cached payload.
