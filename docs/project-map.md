# LiteTracker Project Map

## Основные папки

- `app/` — ядро приложения, сервисы, helper-код, части legacy-обработчиков.
- `public/` — публичные assets и файлы (`css/js/images`, `dist`, `downloads`).
- `database/` — SQL-дампы и миграции.
- `storage/` — runtime-файлы (`logs`, `cache`, `tmp`, `backups`, `uploads`).
- `docs/` — документация и архивы (`docs/archive`).

## Root directory policy

- Новые директории в корне запрещены.
- Новые модули размещаются только в `app/`, `public/`, `database/`, `storage/`, `docs/`.
- Низкорисковые dev/tooling переносы Stage 4 завершены: `scripts/` -> `app/tools/`, `src/` -> `app/frontend/`.
- High-risk legacy-каталоги в корне после Stage 6C: отсутствуют.
- `templates/` в корне — legacy public assets fallback (Stage 6B): PHP-шаблоны перенесены в `app/templates/`, root `templates/` остался только для совместимости браузерных URL (`/templates/default/css/`, `/templates/default/images/`).
- Runtime-файлы размещаются только в `storage/*`.
- `cache/` и `logs/` в корне — fallback-совместимость на переходный период.
- `api/` и `ajax/` в корне — только thin compatibility wrappers.
- `modules/` и `languages/` в корне — только thin compatibility wrappers (основной код перенесён в `app/`).
- `system/` в корне — только thin compatibility wrappers; реальная системная логика находится в `app/system/`.

Разрешённые директории в корне:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `modules`, `languages`, `ajax`, `api`

Dot-директории (исключены из проверки guard-скриптом):

- `.github` — GitHub Actions workflows и конфигурация платформы.
- `.githooks` — локальные git-хуки (установка: `git config core.hooksPath .githooks`).

Проверка структуры:

```bash
php app/tools/check-project-structure.php
```

## Path constants и helpers

- Используйте централизованные константы путей:
  - `LT_ROOT_PATH`, `LT_APP_PATH`, `LT_PUBLIC_PATH`, `LT_STORAGE_PATH`, `LT_DATABASE_PATH`, `LT_DOCS_PATH`, `LT_SYSTEM_PATH` (→ `app/system/`), `LT_LEGACY_SYSTEM_PATH` (→ root `system/`), `LT_TEMPLATES_PATH` (→ `app/templates/`), `LT_LEGACY_TEMPLATES_PATH` (→ root `templates/`), `LT_ADMIN_PATH`, `LT_API_PATH`, `LT_MODULES_PATH`, `LT_LANGUAGES_PATH`.
- Для runtime-путей используйте helper-функции:
  - `lt_path()`, `lt_storage_path()`, `lt_cache_path()`, `lt_logs_path()`, `lt_tmp_path()`, `lt_uploads_path()`, `lt_system_path()`, `lt_modules_path()`, `lt_languages_path()`, `lt_templates_path()`.

## Где искать

- Админка: `admin.php` + `app/admin/modules/*`.
- Публичные страницы: корневые `*.php` entrypoints (`index.php`, `browse.php`, `details.php`, ...).
- API логика: `app/api/*`, legacy URL-обёртки: `api/*`.
- AJAX логика: `app/api/ajax/*`, legacy URL-обёртки: `ajax/*`.
- Языки: `app/languages/*` (root `languages/*` — только wrappers).
- Legacy modules: `app/modules/*` (root `modules/*` — только wrappers).
- Функции: `app/system/functions/*` (root `system/functions/*` — только wrappers).
- Классы: `app/system/classes/*` (root `system/classes/*` — только wrappers).
- Шаблоны: `app/templates/default/*` (бизнес PHP-шаблоны; root `templates/` — только public assets fallback).
- Стили: `app/templates/default/css/*` (PHP-путь), `templates/default/css/*` (публичный URL браузера), `public/css/*`.
- Скрипты JS: `public/js/*`, source bundle в `app/frontend/app.js`.
- Изображения: `public/images/*`, `templates/default/images/*`, `public/downloads/images/*`.
- Миграции/SQL: `database/*`.
- Кэш: целевой `storage/cache/`, legacy fallback `cache/` (временная совместимость).
- Логи: целевой `storage/logs/`, legacy fallback `logs/` (временная совместимость).
- Загрузки: `public/downloads/*`, целевой runtime-контур `storage/uploads/`.
- Служебные инструменты: `app/tools/*`.
- Docker infrastructure files: `app/infra/docker/*`.
- Тесты: `app/tests/*`.

## Куда добавлять новые модули

Пример модуля `shop`:

- `app/admin/modules/shop.php` — админская часть;
- `app/api/shop.php` или `app/api/ajax/shop.php` — AJAX/API;
- `app/system/functions/functions.shop.php` — бизнес-логика;
- `app/templates/default/shop/` — шаблоны;
- `public/js/shop.js` — JS;
- `public/css/shop.css` — CSS;
- `database/migrations/shop.sql` — миграция.

Важно: не создавать новую папку `shop/` в корне проекта без крайней необходимости.
Важно: новые модули нельзя добавлять в root `modules/`; размещайте их в `app/modules/` или соответствующих `app/*` слоях.

Дополнительно: новые runtime-файлы должны использовать `storage/*` через `LT_*` константы и path helpers, а не прямые хрупкие относительные пути.

## Stage 6A languages/modules migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `languages/` | `app/languages/` | moved language packs and switched runtime access to `LT_LANGUAGES_PATH`/`lt_languages_path()` | root `languages/` kept as thin wrapper for legacy direct includes | medium | done |
| `modules/` | `app/modules/` | moved shop/releases modules and switched runtime includes to `LT_MODULES_PATH`/`lt_modules_path()` | root `modules/` kept as thin wrappers for legacy includes | medium | done |

## Stage 6B templates migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `templates/` | `app/templates/` | copied all PHP templates; `LT_TEMPLATES_PATH` updated to `app/templates/`; `lt_templates_path()` helper added with fallback to root `templates/` | root `templates/` kept as legacy public assets fallback (browser URLs `/templates/default/css/`, `/templates/default/images/` remain valid) | medium | done |
| `templates/default/*.php` | `app/templates/default/*.php` | all PHP require/include updated to `lt_templates_path()` | fallback to root `templates/` if file missing in `app/templates/` | medium | done |
| `templates/default/css/my.css` | `app/templates/default/css/my.css` | copied; HTML `<link href>` URLs still point to root `templates/` for browsers | root `templates/` serves as public asset fallback | low | done (root fallback) |
| `templates/default/images/*` | `app/templates/default/images/*` | copied; HTML `<img src>` URLs still point to root `templates/` for browsers | root `templates/` serves as public asset fallback | low | done (root fallback) |

Root `templates/` status after Stage 6B: **legacy public assets fallback only** — no business logic; PHP never includes from here directly; new templates must go in `app/templates/`.

## Stage 6C system migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `system/` | `app/system/` | moved system bootstrap/config/functions/classes as source of truth to `app/system/` | root `system/` kept as thin wrappers to preserve legacy `require/include` paths | high | done |
| `system/init.php` | `app/system/init.php` | root file converted to wrapper | root entrypoints (`index.php`, `browse.php`, `details.php`, `admin.php`, etc.) continue to use legacy include path without breakage | high | done |
| `system/init.announce.php` | `app/system/init.announce.php` | root file converted to wrapper | announce/scrape compatibility preserved | high | done |
| `system/init.autoclean.php` | `app/system/init.autoclean.php` | root file converted to wrapper | autoclean cron bootstrap compatibility preserved | high | done |
| `system/config/*`, `system/functions/*`, `system/classes/*`, `system/bootstrap/*` | `app/system/...` | root files converted to thin wrappers; real logic moved to `app/system/...` | direct legacy includes still resolve through wrappers | high | done |
