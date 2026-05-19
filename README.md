<p align="center">
  <img src="https://raw.githubusercontent.com/nickmsk9/litetracker/main/templates/default/images/forgithub.png" width="400"/>
</p>

<h1 align="center">LiteTracker Engine</h1>

<p align="center">
  Docker-ready BitTorrent tracker на PHP + Memcached
</p>

# LiteTracker Engine

LiteTracker Engine is a Docker-ready PHP BitTorrent tracker with a classic web interface, torrent announce/scrape endpoints, user profiles, comments, ratings, bookmarks, moderation tools, and a seeded demo database.

Русская версия: [README на русском](#readme-на-русском)
English version: [English README](#english-readme)

---

## README на русском

### О проекте

LiteTracker Engine - это PHP-движок BitTorrent-трекера с локальным announce, каталогом релизов и пользовательским интерфейсом. Проект адаптирован для запуска в Docker и включает готовую инфраструктуру: Apache + PHP 8.4, MySQL 8.4, Memcached, phpMyAdmin и отдельный контейнер-планировщик для фоновых задач.

Репозиторий содержит SQL-дамп с демонстрационными данными, поэтому проект можно быстро поднять локально и проверить основные сценарии без ручного наполнения базы.

### Основные возможности

- Каталог torrent-релизов с категориями, сортировкой, поиском и страницами деталей.
- Загрузка, редактирование и оформление релизов.
- Локальный BitTorrent announce endpoint: `announce.php`.
- Scrape endpoint: `scrape.php`.
- Генерация torrent-файлов и magnet-ссылок.
- Поддержка локального retracker URL.
- Учет скачанного/отданного, сидов, личеров и статистики пользователей.
- Рейтинги релизов, комментарии, ответы и закладки.
- Профили пользователей, стена профиля, друзья, личные сообщения и черный список.
- Блоки главной страницы.
- Административная панель, роли и права доступа.
- Модерация комментариев и жалоб на стену профиля.
- Автоочистка, обновление пиров и проверка внешних трекеров.
- Кэширование через Memcached или файловый кэш.
- Настройки локальной CAPTCHA, почты, часового пояса и публичных URL через переменные окружения.

### Технологии

- PHP 8.4 + Apache
- MySQL 8.4
- Memcached
- phpMyAdmin
- Docker Compose
- HTML/CSS/JavaScript (опциональная сборка JS через Vite)

### Структура проекта

Основные директории проекта:

- `app/` — ядро приложения, системная логика, админские и API-компоненты.
  - `app/admin/modules/` — модули админки.
  - `app/modules/` — legacy business modules (shop/releases).
  - `app/languages/` — языковые пакеты.
  - `app/api/` — бизнес-логика API.
  - `app/api/ajax/` — бизнес-логика AJAX.
  - `app/infra/docker/` — Docker-конфигурация для сборки образа.
  - `app/tests/` — PHPUnit и smoke-тесты.
- `public/` — публичные assets (`css`, `js`, `images`, `static`, `dist`).
- `database/` — схема/дампы/миграции/seed-данные.
- `storage/` — runtime-данные (`logs`, `cache`, `tmp`, `backups`, `uploads`).
- `docs/` — документация и архив устаревших материалов.

Runtime policy:

- новые runtime-файлы должны использовать `storage/*`;
- `cache/` и `logs/` в корне считаются legacy fallback путями на переходный период.

Служебные корневые файлы:

- `Dockerfile`
- `docker-compose.yml`
- `.env.example`
- `.gitignore`

Правило: новые модули не должны создавать новые папки в корне. Размещайте код и ресурсы в `app/`, `public/`, `database/`, `storage/` и `docs/`.

Примечание по legacy routing: публичные PHP entrypoints в корне (`index.php`, `browse.php`, `details.php`, `upload.php`, `admin.php` и др.) пока остаются на месте для совместимости URL.

Path constants и helpers:

- Используйте `LT_*` константы (`LT_ROOT_PATH`, `LT_APP_PATH`, `LT_PUBLIC_PATH`, `LT_STORAGE_PATH`, `LT_DATABASE_PATH`, `LT_DOCS_PATH`, `LT_SYSTEM_PATH`, `LT_LEGACY_SYSTEM_PATH`, `LT_TEMPLATES_PATH`, `LT_ADMIN_PATH`, `LT_API_PATH`, `LT_MODULES_PATH`, `LT_LANGUAGES_PATH`).
- Для runtime/compatibility путей используйте `lt_path()`, `lt_storage_path()`, `lt_cache_path()`, `lt_logs_path()`, `lt_tmp_path()`, `lt_uploads_path()`, `lt_system_path()`, `lt_modules_path()`, `lt_languages_path()`.

## Root directory policy

- Новые папки в корне запрещены.
- Новые модули раскладываются только по `app/`, `public/`, `database/`, `storage/`, `docs/`.
- Низкорисковые dev/tooling переносы Stage 4 уже выполнены (`scripts/` -> `app/tools/`, `src/` -> `app/frontend/`).
- High-risk legacy-папки в корне после Stage 6C: отсутствуют.
- `templates/` в корне — legacy public assets fallback (Stage 6B): PHP-шаблоны перенесены в `app/templates/`, root `templates/` оставлен для браузерных URL (`/templates/default/css/`, `/templates/default/images/`).
- Runtime-файлы должны идти только в `storage/*`.
- `cache/` и `logs/` в корне — только fallback на переходный период.
- `api/` и `ajax/` в корне — только thin compatibility wrappers для legacy URL.
- `modules/` и `languages/` в корне — только thin compatibility wrappers (бизнес-логика и языки перенесены в `app/`).
- `system/` в корне — только thin compatibility wrappers; реальная системная логика находится в `app/system/`.

Разрешённые корневые директории:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `modules`, `languages`, `ajax`, `api`

## Stage 6A languages/modules migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `languages/` | `app/languages/` | moved language packs and updated init/functions path usage to `LT_LANGUAGES_PATH` | root `languages/` kept as thin wrapper (`languages/Russian/site.php` -> `app/languages/Russian/site.php`) | medium | done |
| `modules/` | `app/modules/` | moved shop/releases modules and switched includes to `LT_MODULES_PATH` via `lt_modules_path()` | root `modules/` kept as thin wrappers (`modules/*` -> `app/modules/*`) | medium | done |

## Stage 6B templates migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `templates/` | `app/templates/` | copied PHP templates; `LT_TEMPLATES_PATH` → `app/templates/`; `lt_templates_path()` added | root `templates/` kept as legacy public assets fallback | medium | done |
| PHP include/require | `lt_templates_path(...)` | all `require 'templates/...'` updated in functions.php, news.php, profile.php, details.php, my.setting.php, my.friends.php, index.php, my.book.php, browse.php, app/modules/releases.arr.php | fallback to root `templates/` if missing in `app/templates/` | medium | done |

## Stage 6C system migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `system/` | `app/system/` | moved system bootstrap/config/functions/classes into `app/system/`; updated bootstrap constants to app-first | root `system/` kept as thin wrappers for legacy `require 'system/...';` paths | high | done |
| `system/init.php`, `system/init.announce.php`, `system/init.autoclean.php` | `app/system/*.php` | moved real bootstrap code to `app/system/`; root files converted to wrappers | legacy root entrypoints keep working without URL/path changes | high | done |
| `system/config/*`, `system/functions/*`, `system/classes/*`, `system/bootstrap/*` | `app/system/...` | real files moved to `app/system/`; root mirror converted to thin wrappers | direct legacy includes remain backward compatible | high | done |

## Stage 8 cache/logs runtime cleanup

| Path | Current usage | Action | Risk | Result |
|------|---------------|--------|------|--------|
| `storage/cache/` | основной runtime cache target через `lt_cache_path()` | keep as primary runtime target | low | done |
| `storage/logs/` | основной runtime logs target через `lt_logs_path()` | keep as primary runtime target | low | done |
| `cache/` | legacy fallback при недоступности `storage/cache/` | keep fallback placeholders (`.gitkeep`, `.htaccess`) | medium | kept (compatibility fallback) |
| `logs/` | legacy fallback при недоступности `storage/logs/` | keep fallback placeholder (`.htaccess`) | medium | kept (compatibility fallback) |

Итог Stage 8: runtime остаётся storage-first (`storage/cache`, `storage/logs`), root `cache/` и `logs/` не удаляются из-за fallback-совместимости.

Проверка структуры:

```bash
php app/tools/check-project-structure.php
```

### Быстрый старт через Docker

Требования:

- Docker
- Docker Compose v2
- Свободные локальные порты `443`, `8094`, `8095`, `3309`, `11213` или переопределение портов через переменные окружения

1. Клонируйте репозиторий:

```bash
git clone <repository-url>
cd litetracker
```

2. Запустите контейнеры:

```bash
docker compose up -d --build
```

3. Импортируйте базу данных:

```bash
docker compose exec -T db mysql -uroot < database/litetracker.sql
```

4. Откройте приложение:

- Site: <https://localhost>
- phpMyAdmin: <http://localhost:8095>

HTTP-порт `8094` также опубликован, но Apache перенаправляет HTTP-запросы на HTTPS. В браузере может появиться предупреждение о самоподписанном сертификате - это ожидаемо для локального окружения.

### Демо-данные

Файл `database/litetracker.sql` содержит готовую схему, категории, пользователей, релизы, комментарии, закладки и активность для проверки интерфейса.

Дополнительно можно пересоздать демонстрационную активность:

```bash
docker compose exec php php app/tools/seed_demo_activity.php
```

Скрипт создает демо-пользователей и демо-релизы. Пароль для пользователей, созданных этим скриптом:

```text
demo12345
```

### Полезные команды

Сборка фронтенд-ассетов (опционально):

```bash
npm ci
npm run build
```

Примечание: приложение работает и без предварительной сборки. Если `public/dist/manifest.json` отсутствует, LiteTracker автоматически подключает исходный entrypoint `app/frontend/app.js`.

Запуск проекта:

```bash
docker compose up -d
```

Остановка проекта:

```bash
docker compose down
```

Просмотр логов PHP/Apache:

```bash
docker compose logs -f php
```

Просмотр логов MySQL:

```bash
docker compose logs -f db
```

Пересборка контейнера PHP:

```bash
docker compose build php
docker compose up -d php
```

Экспорт текущей базы в `database/litetracker.sql`:

```bash
./app/tools/dump-db.sh
```

Полная очистка локальной MySQL-базы Docker:

```bash
docker compose down
rm -rf docker-data/mysql
docker compose up -d
docker compose exec -T db mysql -uroot < database/litetracker.sql
```

### Настройка окружения

Основные переменные окружения задаются в `docker-compose.yml` или передаются при запуске Docker Compose.

| Переменная                     | Назначение                                | Значение по умолчанию                |
| ------------------------------ | ----------------------------------------- | ------------------------------------ |
| `LITETRACKER_HOST_HTTP_PORT`   | HTTP-порт на хосте                        | `8094`                               |
| `LITETRACKER_HOST_HTTPS_PORT`  | HTTPS-порт на хосте                       | `443`                                |
| `LITETRACKER_PUBLIC_SCHEME`    | Публичная схема сайта                     | `http` в Docker Compose              |
| `LITETRACKER_PUBLIC_HOST`      | Публичный host сайта                      | `localhost:8094`                     |
| `LITETRACKER_ANNOUNCE_HOST`    | Host для announce URL                     | `localhost:8094`                     |
| `LITETRACKER_ANNOUNCE_URL`     | Полный announce URL                       | `http://localhost:8094/announce.php` |
| `LITETRACKER_DB_HOST`          | Host MySQL                                | `db`                                 |
| `LITETRACKER_DB_USER`          | Пользователь MySQL                        | `root`                               |
| `LITETRACKER_DB_PASSWORD`      | Пароль MySQL                              | пусто                                |
| `LITETRACKER_DB_ALLOW_EMPTY_PASSWORD` | Разрешить пустой root-пароль MySQL локально | `yes`                         |
| `LITETRACKER_DB_NAME`          | Имя базы данных                           | `lite`                               |
| `LITETRACKER_CACHE_DRIVER`     | Драйвер кэша: `memcached` или `filecache` | `memcached`                          |
| `LITETRACKER_CACHE_HOST`       | Host Memcached                            | `memcached`                          |
| `LITETRACKER_CACHE_PORT`       | Порт Memcached внутри сети Docker         | `11211`                              |
| `LITETRACKER_CRON_MODE`        | Режим фоновых задач                       | `external`                           |
| `LITETRACKER_CRON_TOKEN`       | Токен для защищенных cron-запросов        | `litetracker-local-cron-token`       |
| `LITETRACKER_SQL_DEBUG`        | Логирование SQL-ошибок                    | `0`                                  |
| `LITETRACKER_TIMEZONE`         | Часовой пояс приложения                   | `Europe/Moscow`                      |
| `LITETRACKER_CAPTCHA_ENABLED`  | Включить локальную CAPTCHA                | `0`                                  |
| `LITETRACKER_CAPTCHA_SIGNUP`   | CAPTCHA при регистрации                   | `1`                                  |
| `LITETRACKER_CAPTCHA_LOGIN`    | CAPTCHA при входе                         | `0`                                  |
| `LITETRACKER_CAPTCHA_DOWNLOAD` | CAPTCHA при скачивании                    | `0`                                  |
| `LITETRACKER_MAIL_FROM`        | From-адрес для писем                      | `admin@localhost`                    |
| `LITETRACKER_MAIL_LOGIN`       | Логин SMTP                                | пусто                                |
| `LITETRACKER_MAIL_PASSWORD`    | Пароль SMTP                               | пусто                                |

Пример запуска на другом порту:

```bash
LITETRACKER_HOST_HTTP_PORT=8080 \
LITETRACKER_HOST_HTTPS_PORT=8443 \
LITETRACKER_PUBLIC_HOST=localhost:8080 \
LITETRACKER_ANNOUNCE_HOST=localhost:8080 \
LITETRACKER_ANNOUNCE_URL=http://localhost:8080/announce.php \
docker compose up -d
```

После такого запуска сайт будет доступен по адресу <https://localhost:8443>.

### Фоновые задачи

В Docker Compose есть сервис `scheduler`. Он раз в минуту вызывает `autoclean.php`, а каждые 10 минут - `update.peers.php`. Запросы защищены заголовком `X-Cron-Token`, значение которого берется из `LITETRACKER_CRON_TOKEN`.

Основные задачи:

- `autoclean.php` - очистка устаревших сессий/пиров, начисление бонусов активным пользователям и сервисное обслуживание.
- `update.peers.php` - обновление статистики внешних трекеров и пиров.

Для production-окружения можно заменить контейнер `scheduler` системным cron, но токен должен совпадать с настройкой приложения.

### База данных

Основной дамп:

```text
database/litetracker.sql
```

### Публичный announce URL

Tracker URL для новых torrent-файлов задается через:

```text
LITETRACKER_ANNOUNCE_URL
```

Для локальной разработки обычно достаточно:

```text
http://localhost:8094/announce.php
```

Для production укажите реальный домен:

```bash
LITETRACKER_PUBLIC_SCHEME=https
LITETRACKER_PUBLIC_HOST=example.com
LITETRACKER_ANNOUNCE_HOST=example.com
LITETRACKER_ANNOUNCE_URL=https://example.com/announce.php
```

После смены announce URL новые скачиваемые torrent-файлы будут получать актуальный адрес. Старые файлы при необходимости нужно пересоздать или перезаписать.

### Безопасность перед публикацией

Перед размещением проекта в публичном GitHub-репозитории и тем более перед production-запуском проверьте:

- Не публикуйте реальные пользовательские данные, приватные passkey, e-mail, сессии и логи.
- Замените локальные пароли и токены: `LITETRACKER_DB_PASSWORD`, `LITETRACKER_CRON_TOKEN`, `LITETRACKER_COOKIE_SALT`.
- Отключите SQL debug в production: `LITETRACKER_SQL_DEBUG=0`.
- Настройте настоящий TLS-сертификат через reverse proxy или инфраструктуру хостинга.
- Проверьте права на директории `public/downloads/`, `storage/cache/`, `storage/logs/`.
- Включите локальную CAPTCHA, если регистрация открыта для интернета.
- Ограничьте доступ к phpMyAdmin или не запускайте его в production.
- Проверьте сторонние JavaScript/PHP-библиотеки и их лицензии.

### Лицензия

В репозитории нет отдельного файла лицензии. Перед публичным распространением рекомендуется добавить `LICENSE` и явно указать условия использования проекта и сторонних компонентов.

---

## English README

### About

LiteTracker Engine is a PHP BitTorrent tracker engine with a local announce endpoint, torrent catalog, and classic web UI. The project is prepared for Docker-based development and includes Apache + PHP 8.4, MySQL 8.4, Memcached, phpMyAdmin, and a scheduler container for background maintenance tasks.

The repository includes an SQL dump with demo data, so the application can be started locally and tested without filling the database manually.

### Features

- Torrent catalog with categories, sorting, search, and detail pages.
- Uploading, editing, and formatting torrent releases.
- Local BitTorrent announce endpoint: `announce.php`.
- Scrape endpoint: `scrape.php`.
- Torrent file and magnet link generation.
- Optional local retracker URL.
- Upload/download accounting, seeders, leechers, and user statistics.
- Torrent ratings, comments, replies, and bookmarks.
- User profiles, profile walls, friends, private messages, and blacklist.
- Chat and homepage/sidebar blocks.
- Admin panel, roles, and permissions.
- Comment moderation and profile wall report moderation.
- Autoclean, peer updates, and remote tracker checks.
- Memcached or file-based cache.
- Environment-based configuration for local CAPTCHA, mail, timezone, and public URLs.

### Stack

- PHP 8.4 + Apache
- MySQL 8.4
- Memcached
- phpMyAdmin
- Docker Compose
- HTML/CSS/JavaScript (optional JS bundling with Vite)

### Project Structure

Primary directories:

- `app/` — core application logic, system helpers, admin/API components.
  - `app/admin/modules/` — admin panel modules.
  - `app/modules/` — legacy business modules (shop/releases).
  - `app/languages/` — localization packs.
  - `app/api/` — API business logic.
  - `app/api/ajax/` — AJAX business logic.
  - `app/infra/docker/` — Docker image config files.
  - `app/tests/` — PHPUnit and smoke tests.
- `public/` — public assets (`css`, `js`, `images`, `static`, `dist`).
- `database/` — schema, dumps, migrations, and seed data.
- `storage/` — runtime data (`logs`, `cache`, `tmp`, `backups`, `uploads`).
- `docs/` — project documentation and archives.

Runtime policy:

- new runtime files should use `storage/*`;
- root-level `cache/` and `logs/` are legacy fallback paths during migration.

Root-level operational files:

- `Dockerfile`
- `docker-compose.yml`
- `.env.example`
- `.gitignore`

Rule: new modules must not create new root folders. Place code/resources under `app/`, `public/`, `database/`, `storage/`, and `docs/`.

Legacy routing note: root PHP public entrypoints (`index.php`, `browse.php`, `details.php`, `upload.php`, `admin.php`, etc.) remain in place for URL compatibility.

Path constants and helpers:

- Use `LT_*` path constants (`LT_ROOT_PATH`, `LT_APP_PATH`, `LT_PUBLIC_PATH`, `LT_STORAGE_PATH`, `LT_DATABASE_PATH`, `LT_DOCS_PATH`, `LT_SYSTEM_PATH`, `LT_LEGACY_SYSTEM_PATH`, `LT_TEMPLATES_PATH`, `LT_ADMIN_PATH`, `LT_API_PATH`, `LT_MODULES_PATH`, `LT_LANGUAGES_PATH`).
- For runtime/compatibility paths use `lt_path()`, `lt_storage_path()`, `lt_cache_path()`, `lt_logs_path()`, `lt_tmp_path()`, `lt_uploads_path()`, `lt_system_path()`, `lt_modules_path()`, `lt_languages_path()`.

## Root directory policy

- New root directories are prohibited.
- New modules must be placed under `app/`, `public/`, `database/`, `storage/`, and `docs/`.
- Low-risk dev/tooling Stage 4 moves are already done (`scripts/` -> `app/tools/`, `src/` -> `app/frontend/`).
- High-risk legacy root folders after Stage 6C: none.
- Runtime files should only use `storage/*`.
- Root-level `cache/` and `logs/` are transition fallback paths only.
- Root-level `api/` and `ajax/` are thin compatibility wrappers for legacy URLs.
- Root-level `modules/` and `languages/` are thin compatibility wrappers only (business logic moved to `app/`).
- Root-level `system/` is a thin compatibility wrapper only (real system logic moved to `app/system/`).

Allowed root directories:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `modules`, `languages`, `ajax`, `api`

## Stage 6A languages/modules migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `languages/` | `app/languages/` | moved language packs and updated init/functions path usage to `LT_LANGUAGES_PATH` | root `languages/` kept as thin wrapper (`languages/Russian/site.php` -> `app/languages/Russian/site.php`) | medium | done |
| `modules/` | `app/modules/` | moved shop/releases modules and switched includes to `LT_MODULES_PATH` via `lt_modules_path()` | root `modules/` kept as thin wrappers (`modules/*` -> `app/modules/*`) | medium | done |

## Stage 8 cache/logs runtime cleanup

| Path | Current usage | Action | Risk | Result |
|------|---------------|--------|------|--------|
| `storage/cache/` | primary runtime cache target via `lt_cache_path()` | keep as primary runtime target | low | done |
| `storage/logs/` | primary runtime logs target via `lt_logs_path()` | keep as primary runtime target | low | done |
| `cache/` | legacy fallback when `storage/cache/` is unavailable | keep fallback placeholders (`.gitkeep`, `.htaccess`) | medium | kept (compatibility fallback) |
| `logs/` | legacy fallback when `storage/logs/` is unavailable | keep fallback placeholder (`.htaccess`) | medium | kept (compatibility fallback) |

Stage 8 result: runtime is storage-first (`storage/cache`, `storage/logs`), while root `cache/` and `logs/` stay as controlled compatibility fallback.

Structure check:

```bash
php app/tools/check-project-structure.php
```

### Quick Start with Docker

Requirements:

- Docker
- Docker Compose v2
- Free local ports `443`, `8094`, `8095`, `3309`, `11213`, or custom port overrides through environment variables

1. Clone the repository:

```bash
git clone <repository-url>
cd litetracker
```

2. Start the containers:

```bash
docker compose up -d --build
```

3. Import the database:

```bash
docker compose exec -T db mysql -uroot < database/litetracker.sql
```

4. Open the application:

- Site: <https://localhost>
- phpMyAdmin: <http://localhost:8095>

HTTP port `8094` is also exposed, but Apache redirects HTTP requests to HTTPS. Your browser may show a self-signed certificate warning, which is expected in the local environment.

### Demo Data

`database/litetracker.sql` contains the schema, categories, users, releases, comments, bookmarks, and demo activity.

You can also regenerate demo activity:

```bash
docker compose exec php php app/tools/seed_demo_activity.php
```

The password for users created by this script is:

```text
demo12345
```

### Useful Commands

Build frontend assets (optional):

```bash
npm ci
npm run build
```

Note: the app also works without a prebuilt bundle. If `public/dist/manifest.json` is missing, LiteTracker automatically falls back to the source entrypoint `app/frontend/app.js`.

Start the project:

```bash
docker compose up -d
```

Stop the project:

```bash
docker compose down
```

Follow PHP/Apache logs:

```bash
docker compose logs -f php
```

Follow MySQL logs:

```bash
docker compose logs -f db
```

Rebuild the PHP container:

```bash
docker compose build php
docker compose up -d php
```

Export the current database to `database/litetracker.sql`:

```bash
./app/tools/dump-db.sh
```

Reset the local Docker MySQL data:

```bash
docker compose down
rm -rf docker-data/mysql
docker compose up -d
docker compose exec -T db mysql -uroot < database/litetracker.sql
```

### Environment Configuration

The main environment variables are configured in `docker-compose.yml` or passed when running Docker Compose.

| Variable                       | Purpose                                  | Default                              |
| ------------------------------ | ---------------------------------------- | ------------------------------------ |
| `LITETRACKER_HOST_HTTP_PORT`   | Host HTTP port                           | `8094`                               |
| `LITETRACKER_HOST_HTTPS_PORT`  | Host HTTPS port                          | `443`                                |
| `LITETRACKER_PUBLIC_SCHEME`    | Public site scheme                       | `http` in Docker Compose             |
| `LITETRACKER_PUBLIC_HOST`      | Public site host                         | `localhost:8094`                     |
| `LITETRACKER_ANNOUNCE_HOST`    | Host used for announce URL               | `localhost:8094`                     |
| `LITETRACKER_ANNOUNCE_URL`     | Full announce URL                        | `http://localhost:8094/announce.php` |
| `LITETRACKER_DB_HOST`          | MySQL host                               | `db`                                 |
| `LITETRACKER_DB_USER`          | MySQL user                               | `root`                               |
| `LITETRACKER_DB_PASSWORD`      | MySQL password                           | empty                                |
| `LITETRACKER_DB_ALLOW_EMPTY_PASSWORD` | Allow an empty MySQL root password locally | `yes`                         |
| `LITETRACKER_DB_NAME`          | Database name                            | `lite`                               |
| `LITETRACKER_CACHE_DRIVER`     | Cache driver: `memcached` or `filecache` | `memcached`                          |
| `LITETRACKER_CACHE_HOST`       | Memcached host                           | `memcached`                          |
| `LITETRACKER_CACHE_PORT`       | Memcached port inside Docker network     | `11211`                              |
| `LITETRACKER_CRON_MODE`        | Background task mode                     | `external`                           |
| `LITETRACKER_CRON_TOKEN`       | Token for protected cron requests        | `litetracker-local-cron-token`       |
| `LITETRACKER_SQL_DEBUG`        | SQL error logging                        | `0`                                  |
| `LITETRACKER_TIMEZONE`         | Application timezone                     | `Europe/Moscow`                      |
| `LITETRACKER_CAPTCHA_ENABLED`  | Enable local CAPTCHA                     | `0`                                  |
| `LITETRACKER_CAPTCHA_SIGNUP`   | CAPTCHA on signup                        | `1`                                  |
| `LITETRACKER_CAPTCHA_LOGIN`    | CAPTCHA on login                         | `0`                                  |
| `LITETRACKER_CAPTCHA_DOWNLOAD` | CAPTCHA on download                      | `0`                                  |
| `LITETRACKER_MAIL_FROM`        | Mail sender address                      | `admin@localhost`                    |
| `LITETRACKER_MAIL_LOGIN`       | SMTP login                               | empty                                |
| `LITETRACKER_MAIL_PASSWORD`    | SMTP password                            | empty                                |

Example with a custom local port:

```bash
LITETRACKER_HOST_HTTP_PORT=8080 \
LITETRACKER_HOST_HTTPS_PORT=8443 \
LITETRACKER_PUBLIC_HOST=localhost:8080 \
LITETRACKER_ANNOUNCE_HOST=localhost:8080 \
LITETRACKER_ANNOUNCE_URL=http://localhost:8080/announce.php \
docker compose up -d
```

After that, the site will be available at <https://localhost:8443>.

### Background Tasks

Docker Compose includes a `scheduler` service. It calls `autoclean.php` once per minute and `update.peers.php` every 10 minutes. Requests are protected with the `X-Cron-Token` header, using the value from `LITETRACKER_CRON_TOKEN`.

Main tasks:

- `autoclean.php` cleans old sessions/peers, grants activity bonuses, and performs maintenance.
- `update.peers.php` updates remote tracker and peer statistics.

In production, the `scheduler` container can be replaced with system cron, as long as the token matches the application configuration.

### Database

Main dump:

```text
database/litetracker.sql
```

### Public Announce URL

The tracker URL for newly downloaded torrent files is configured through:

```text
LITETRACKER_ANNOUNCE_URL
```

For local development:

```text
http://localhost:8094/announce.php
```

For production, use your real domain:

```bash
LITETRACKER_PUBLIC_SCHEME=https
LITETRACKER_PUBLIC_HOST=example.com
LITETRACKER_ANNOUNCE_HOST=example.com
LITETRACKER_ANNOUNCE_URL=https://example.com/announce.php
```

After changing the announce URL, newly downloaded torrent files will use the updated address. Existing files may need to be regenerated or rewritten.

### Security Before Publishing

Before publishing this project on GitHub, and especially before running it in production, check the following:

- Do not publish real user data, private passkeys, e-mails, sessions, or logs.
- Replace local passwords and tokens: `LITETRACKER_DB_PASSWORD`, `LITETRACKER_CRON_TOKEN`, `LITETRACKER_COOKIE_SALT`.
- Disable SQL debug in production: `LITETRACKER_SQL_DEBUG=0`.
- Configure a real TLS certificate through a reverse proxy or hosting infrastructure.
- Check permissions for `public/downloads/`, `storage/cache/`, and `storage/logs/`.
- Enable local CAPTCHA if public registration is open.
- Restrict access to phpMyAdmin or do not run it in production.
- Review third-party JavaScript/PHP libraries and their licenses.

### License

No standalone license file is currently present in this repository. Before public distribution, add a `LICENSE` file and explicitly define the usage terms for the project and bundled third-party components.
