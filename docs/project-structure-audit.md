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
| `/app/tools` | dir | CLI maintenance/seeding scripts | README, operators, pre-commit hook, cron/manual runs | keep | low | Stage 3: перенесено из legacy `/scripts`. |
| `/app/frontend` | dir | Vite source entry (`app/frontend/app.js`) | `vite.config.js`, npm build, PHP fallback asset URL | keep | low | Stage 3: перенесено из legacy `/src`. |
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

## Stage 4 execution results (low-risk physical moves)

### 1) Проверка `scripts/`

- В корне `scripts/` отсутствует: перенос уже выполнен ранее в `app/tools/`.
- Проверены Docker и compose-конфиги: ссылок на `scripts/` нет.
- README использует только `app/tools/*`.
- Scheduler в `docker-compose.yml` вызывает root entrypoints (`/autoclean.php`, `/update.peers.php`), а не `scripts/`.
- Вывод: дополнительный перенос не требуется, риска для URL/runtime нет.

### 2) Проверка `src/`

- В корне `src/` отсутствует: перенос уже выполнен ранее в `app/frontend/`.
- `vite.config.js` использует `app/frontend/app.js` как вход.
- `package.json` scripts (`build/dev/preview`) совместимы с текущей структурой.
- Build output остаётся `public/dist`.
- Вывод: дополнительный перенос не требуется.

### 3) Проверка временных/архивных папок в корне

- Проверены кандидаты: `tmp/`, `old/`, `backup/`, `backups/`, `docs_old/`, `archive/`, `temporary/`, `test-old/`.
- В корне репозитория такие папки не обнаружены.
- Действия по переносу/удалению не требуются.

### 4) High-risk папки, которые остаются в корне

- `system/`, `templates/`, `admin/`, `modules/`, `languages/`, `ajax/`, `api/`, `cache/`, `logs/`, `docker/`, `tests/` остаются в корне.
- Причины и зависимости описаны в таблице ниже (прямые URL, include/require, Docker COPY, phpunit/autoload-dev, runtime fallback).
- Для будущего переноса потребуются wrappers/shims: bootstrap shim, template/module resolver, language-path adapter, URL proxy для `ajax/api`, Docker path migration, test config migration.

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

## Stage 3 physical moves plan

| Current path | Target path | Required compatibility wrapper | Risk | Notes |
|-------------|-------------|--------------------------------|------|-------|
| `admin/` | `app/admin/` | root-level loader/proxy for `admin.php` module paths | high | Много include/require и модульных зависимостей. |
| `ajax/` | `app/api/ajax/` | URL proxies `/ajax/*.php` -> new handlers | high | Прямые AJAX URL в шаблонах и JS. |
| `api/` | `app/api/` | URL proxies `/api/*.php` | medium | Требуется сохранить текущие endpoint URLs. |
| `system/` | `app/system/` | bootstrap shim + constants migration | high | Критическая инициализация всего проекта. |
| `templates/` | `app/templates/` | template resolver compatibility layer | high | Много жестких путей `templates/...`. |
| `modules/` | `app/modules/` | module include resolver | high | Используется shop/releases runtime include. |
| `languages/` | `app/languages/` | language-path adapter in init | high | Сейчас путь строится через DOCUMENT_ROOT. |
| `scripts/` | `app/tools/` | CLI wrapper scripts and README updates | low | Stage 3: выполнено. |
| `src/` | `app/frontend/` | Vite config path update | low | Stage 3: выполнено. |
| `docker/` | `docs/docker/` или `infra/docker/` | Dockerfile COPY path migration | high | Сейчас Dockerfile ожидает `docker/...` в корне. |
| `tests/` | `app/tests/` или `docs/archive/tests/` (если не используется) | phpunit config update | medium | Не удалять/не переносить без подтверждения use-case. |
| `cache/` | `storage/cache/` | runtime fallback helper to legacy cache | medium | Stage 2: уже включён storage-first fallback. |
| `logs/` | `storage/logs/` | runtime fallback helper to legacy logs | medium | Stage 2: уже включён storage-first fallback. |

## Root directory policy

- Новые папки в корне запрещены.
- Новые модули и компоненты должны размещаться в `app/`, `public/`, `database/`, `storage/`, `docs/`.
- Legacy-папки в корне остаются как временная совместимость до отдельного high-risk этапа с wrappers/shims.
- Runtime-файлы должны использовать `storage/*`; `cache/` и `logs/` в корне — fallback.
- Проверка выполняется командой:

```bash
php app/tools/check-project-structure.php
```

Разрешённые корневые директории:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `admin`, `modules`, `languages`, `ajax`, `api`
- `docker`, `tests`

## Next-stage candidates (high-risk, wrappers required first)

- `system/` -> `app/system/`
- `templates/` -> `app/templates/`
- `admin/` -> `app/admin/`
- `modules/` -> `app/modules/`
- `languages/` -> `app/languages/`
- `ajax/` -> `app/api/ajax/`
- `api/` -> `app/api/`
- `docker/` -> `docs/docker/` или `infra/docker/` (только вместе с обновлением Dockerfile)
- `tests/` -> `app/tests/` (только после подтверждения CI/tooling-сценариев)
