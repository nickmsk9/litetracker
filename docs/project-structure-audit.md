# Project Structure Audit

Дата аудита: 2026-05-19

## Root inventory summary

- В корне присутствуют legacy public entrypoints (`*.php`), которые используются как прямые URL и завязаны на текущий роутинг/формы/редиректы.
- Docker использует bind mount всего репозитория в `/var/www/html` и явные пути к `docker/`.
- Большое количество `require/include` использует относительные пути из корня: `system/`, `templates/`, `modules/`, `languages/`, `admin/`, `ajax/`, `api/`.
- Папки `cache/` и `logs/` используются runtime-логикой и admin diagnostics.

## Detailed table

| Path | Type | Current purpose | Used by | Proposed action | Risk | Notes |
|------|------|-----------------|---------|-----------------|------|-------|
| `/app` | dir | Application services/helpers + partial legacy wrappers | `composer.json` PSR-4, `system/init.php`, root wrappers | keep | low | Уже является целевой основной папкой. |
| `/public` | dir | Public assets, Vite build output, downloads | templates, upload/edit/download, Vite, Apache | keep | low | Уже целевая основная папка. |
| `/database` | dir | SQL dump + migrations | README, Docker setup, manual import | keep | low | Критично не трогать миграции/дампы. |
| `/docs` | dir | Project docs + archive | developers | keep | low | Уже целевая основная папка. |
| `/storage` | dir | Runtime target (`logs/cache/tmp/backups/uploads`) | planned runtime consolidation | keep | low | Создан каркас, без агрессивного переноса runtime в этом этапе. |
| `/admin` | dir | Admin modules | `admin.php` (`__DIR__/admin/modules/*`) | needs manual review | high | Перенос в `app/admin` требует compatibility layer и правки путей. |
| `/ajax` | dir | Legacy AJAX endpoints | templates JS/forms (`ajax/*.php`) | needs manual review | high | Прямые URL и относительные include `../system/init.php`. |
| `/api` | dir | API endpoints | frontend JS (`/api/*`) | needs manual review | medium | Можно переносить только с прозрачной прокладкой URL. |
| `/system` | dir | Core bootstrap/config/functions/classes | почти все root entrypoints | needs manual review | high | Массовый перенос сейчас ломает include/require. |
| `/templates` | dir | Theme templates/CSS/views | `system/functions/functions.php`, root pages | needs manual review | high | Глубокая связка с legacy путями `templates/...`. |
| `/modules` | dir | Shop/releases modules | `shop.php`, `my.releases.php` | needs manual review | high | Путь захардкожен в runtime. |
| `/languages` | dir | Localization packs | `system/init.php`, `system/init.announce.php` | needs manual review | high | Путь строится через `$_SERVER['DOCUMENT_ROOT']/languages/...`. |
| `/scripts` | dir | CLI maintenance/seeding scripts | README, operators, cron/manual runs | move to app | medium | Безопасно только во 2 этапе с обновлением README/ops скриптов и shim. |
| `/src` | dir | Vite source entry (`src/app.js`) | `vite.config.js`, npm build | needs manual review | medium | Можно переехать в `app/frontend` только вместе с Vite config. |
| `/docker` | dir | Apache vhosts/start scripts/logrotate config | Dockerfile `COPY docker/...` | keep | high | Перенос сломает Docker build без одновременной правки Dockerfile. |
| `/tests` | dir | PHPUnit tests | `phpunit.xml`, composer autoload-dev | keep | low | Используется тестовой инфраструктурой. |
| `/cache` | dir | Legacy runtime filecache | `system/config/config.php`, admin diagnostics | needs manual review | high | Подготовить controlled switch на `storage/cache` + fallback. |
| `/logs` | dir | SQL/runtime logs | `system/config/config.php`, admin diagnostics, .htaccess | needs manual review | high | Подготовить controlled switch на `storage/logs` + совместимость. |
| `/*.php (public legacy entrypoints)` | file group | Public pages/actions/announce/scrape/admin | direct URLs, forms, AJAX redirects, scheduler | keep | high | Не переносить массово из корня без compatibility routing layer. |
| `/.htaccess` | file | Rewrite/deny rules and legacy routing behavior | Apache runtime | keep | high | Любое перемещение путей требует синхронного обновления rewrite-правил. |
| `/Dockerfile` | file | PHP/Apache image assembly + docker path copies | docker-compose build | keep | high | Содержит path-sensitive `COPY docker/...`. |
| `/docker-compose.yml` | file | Services, volumes, scheduler endpoints | local/prod docker runs | keep | high | bind mount `./:/var/www/html`, cron URLs к root endpoints. |
| `/README.md` | file | Operational docs and onboarding | developers/operators | keep | low | Обновлён раздел структуры и правило 5 директорий. |
| `/.env.example` | file | Env template | deployment | keep | low | Критично сохранить. |
| `/.gitignore` | file | Ignore runtime/artifacts | git hygiene | keep | low | Добавлены правила под `storage/*`. |
| `/.githooks` | dir | Local git hooks | contributor workflow | archive | low | Можно перенести в `docs/archive` во 2 этапе, если не используется автоматически. |

## Runtime and trash findings

- Low-risk мусор по шаблонам `.DS_Store`, `Thumbs.db`, `*.tmp`, `*.bak`, `*.old` не обнаружен в рабочем дереве.
- Пустые директории вне `.git/*` не обнаружены.

## Safe actions done in this stage

1. Добавлен целевой runtime-каркас `storage/` с подпапками:
   - `storage/logs`
   - `storage/cache`
   - `storage/tmp`
   - `storage/backups`
   - `storage/uploads`
2. В каждую подпапку добавлен `.gitkeep`.
3. Обновлён `.gitignore` для runtime-путей внутри `storage/`.
4. Создан `docs/project-map.md` и обновлён `README.md` для навигации.

## Second-stage refactor candidates

1. Ввести централизованные path-константы и bootstrap-адаптер (`LT_ROOT_PATH`, `LT_APP_PATH`, `LT_PUBLIC_PATH`, `LT_STORAGE_PATH`, `LT_DATABASE_PATH`, `LT_DOCS_PATH`).
2. Перевести runtime пути `cache/logs` на `storage/cache` и `storage/logs` с fallback-совместимостью.
3. Переносить `admin/ajax/api/templates/system/modules/languages` только пакетами с compatibility wrappers и проверкой legacy URL.
4. После compatibility layer постепенно убирать лишние корневые директории.
