# ENV1: единый конфиг окружения DB/Memcached и debug panel

## A) Как выбирается DB host

LiteTracker теперь использует единый helper `lt_env($key, $default = null)` и `lt_runtime_environment()`.

Приоритет MySQL host:

1. `LITETRACKER_DB_HOST`
2. runtime `docker` -> `db`
3. остальные окружения -> `127.0.0.1`

`localhost` не используется как default, чтобы не попадать в socket/TCP различия на macOS, Windows, OpenServer/OSPanel и MAMP/XAMPP.

Фактический конфиг лежит в `system/config/mysql.php`; старый `system/config/config.mysql.php` сохранён как совместимый include.

## B) Как выбирается Memcached host

Приоритет Memcached host:

1. `LITETRACKER_MEMCACHED_HOST`
2. legacy `LITETRACKER_CACHE_HOST`
3. runtime `docker` -> `memcached`
4. остальные окружения -> `127.0.0.1`

Порт:

1. `LITETRACKER_MEMCACHED_PORT`
2. legacy `LITETRACKER_CACHE_PORT`
3. `11211`

## C) Как работает fallback

- Если PHP extension `Memcached` отсутствует, включается файловый кеш.
- Если Memcached server недоступен, включается файловый кеш.
- Ошибка кеша не становится fatal для сайта.
- Debug panel показывает активный кеш, online/offline Memcached и причину fallback.
- Проверка Memcached выполняется при создании cache driver, не отдельным тяжёлым probe на каждый cache call.

## D) Как запустить в Docker

Обычный Docker compose работает без дополнительных ENV:

```env
LITETRACKER_RUNTIME=docker
LITETRACKER_DB_HOST=db
LITETRACKER_MEMCACHED_HOST=memcached
```

Если `LITETRACKER_RUNTIME` не задан, Docker также определяется через `/.dockerenv`.

## E) Как запустить в OpenServer/OSPanel

Минимальная локальная схема:

```env
LITETRACKER_RUNTIME=local
LITETRACKER_DB_HOST=127.0.0.1
LITETRACKER_DB_PORT=3306
LITETRACKER_MEMCACHED_HOST=127.0.0.1
LITETRACKER_MEMCACHED_PORT=11211
```

Если Memcached не установлен, сайт продолжит работу на файловом кеше.

## F) Как запустить на macOS local

Для MAMP/XAMPP/Homebrew MySQL:

```env
LITETRACKER_RUNTIME=local
LITETRACKER_DB_HOST=127.0.0.1
LITETRACKER_DB_PORT=3306
```

При другом порте, например MAMP, выставить фактический `LITETRACKER_DB_PORT`.

## G) ENV переменные

| ENV | Назначение | Default |
| --- | --- | --- |
| `LITETRACKER_RUNTIME` | `docker`, `local`, `production`; влияет на host defaults | auto |
| `LITETRACKER_DB_HOST` | MySQL host | `db` in Docker, otherwise `127.0.0.1` |
| `LITETRACKER_DB_PORT` | MySQL TCP port | `3306` |
| `LITETRACKER_DB_USER` | MySQL user | `root` |
| `LITETRACKER_DB_PASSWORD` | MySQL password | empty |
| `LITETRACKER_DB_NAME` | MySQL database | `lite` |
| `LITETRACKER_DB_CHARSET` | MySQL charset | `utf8mb4` |
| `LITETRACKER_DB_CONNECT_TIMEOUT` | MySQL connect timeout seconds | `5` |
| `LITETRACKER_DB_TIMEZONE` | MySQL session timezone | `+03:00` |
| `LITETRACKER_MEMCACHED_HOST` | Memcached host | `memcached` in Docker, otherwise `127.0.0.1` |
| `LITETRACKER_MEMCACHED_PORT` | Memcached port | `11211` |
| `LITETRACKER_CACHE_NAMESPACE` | Cache key namespace prefix | `litetracker` |

Legacy `LITETRACKER_CACHE_HOST` and `LITETRACKER_CACHE_PORT` remain supported.

## H) Что изменено в debug panel

- Заголовок: `LiteTracker Отладка`.
- Labels русифицированы: SQL count/time, memory, cache stats, SQL list.
- Добавлены:
  - процент попаданий кеша;
  - общее время запроса;
  - доля SQL во времени запроса;
  - секция `Окружение`.
- Секция `Окружение` показывает:
  - режим запуска;
  - MySQL host/port/database/charset;
  - Memcached host/port/online status;
  - активный кеш;
  - cache namespace;
  - PHP version;
  - OS/PHP SAPI;
  - memory limit;
  - request method;
  - URL страницы.
- Пароли, cookie, session, token/passkey значения не выводятся.

## I) Риски и что проверить вручную

- OpenServer/OSPanel не проверялся в этой среде; совместимость обеспечена config-by-design через `127.0.0.1`, TCP port и отсутствие default `localhost`.
- Если production использует MySQL socket через `localhost`, теперь нужно явно задать `LITETRACKER_DB_HOST=localhost`.
- `LITETRACKER_DB_STRICT` добавлен в конфиг как report-ready флаг, но DB class пока не применяет strict SQL mode автоматически.
- Header/chrome и tracker business logic не менялись.

Ручные проверки:

- OpenServer/OSPanel с MySQL на `127.0.0.1:3306`.
- macOS MAMP/XAMPP с фактическим MySQL port.
- Production Linux с явными ENV для DB/Memcached.
- Memcached down на production-like окружении: сайт должен остаться на файловом кеше.
