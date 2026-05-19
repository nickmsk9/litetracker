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
- Legacy-папки в корне остаются как временная совместимость; high-risk для следующего этапа: `system/`, `templates/`.
- Runtime-файлы должны использовать `storage/*`; `cache/` и `logs/` в корне — fallback.
- `modules/` и `languages/` в корне оставлены только как thin wrappers/fallback.
- Проверка выполняется командой:

```bash
php app/tools/check-project-structure.php
```

Разрешённые корневые директории:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `modules`, `languages`, `ajax`, `api`

## Next-stage candidates (high-risk, wrappers required first)

- `system/` -> `app/system/`
- `templates/` -> `app/templates/`

## Stage 5 medium-risk root cleanup

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `admin/modules` | `app/admin/modules` | moved business modules | `admin.php` now loads from `LT_APP_PATH.'/admin/modules'` | medium | done |
| `api/*` | `app/api/*` | moved business handlers | root `api/*` kept as thin wrappers (`init` + `require app/api`) | medium | done |
| `ajax/*` | `app/api/ajax/*` | moved business handlers | root `ajax/*` kept as thin wrappers (`init` + `require app/api/ajax`) | medium | done |
| `cache/` | `storage/cache/` | runtime stays storage-first | root `cache/` kept as legacy fallback | medium | partial (fallback kept) |
| `logs/` | `storage/logs/` | runtime stays storage-first | root `logs/` kept as legacy fallback | medium | partial (fallback kept) |
| `docker/` | `app/infra/docker/` | moved Docker support files | `Dockerfile COPY` paths updated | medium | done |
| `tests/` | `app/tests/` | moved test tree | `phpunit.xml`, `composer.json`, smoke path updated | medium | done |
| `system/` | `app/system/` | not moved in this stage | none | high | deferred to Stage 6 |
| `templates/` | `app/templates/` | not moved in this stage | none | high | deferred to Stage 6 |
| `modules/` | `app/modules/` | not moved in this stage | none | high | deferred to Stage 6 |
| `languages/` | `app/languages/` | not moved in this stage | none | high | deferred to Stage 6 |

## Stage 6A languages/modules migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `languages/` | `app/languages/` | moved language packs; switched `system/init.php`, `system/init.announce.php`, `system/functions/functions.php` to `LT_LANGUAGES_PATH`/`lt_languages_path()` | root `languages/` kept as thin wrapper (`languages/Russian/site.php` -> `app/languages/Russian/site.php`) | medium | done |
| `modules/` | `app/modules/` | moved releases/shop modules; switched `shop.php` and `my.releases.php` to `LT_MODULES_PATH`/`lt_modules_path()` | root `modules/` kept as thin wrappers (`modules/*` -> `app/modules/*`) | medium | done |

Wrapper rationale:

- Root `languages/` и `modules/` оставлены временно из-за legacy direct include patterns и для безопасной обратной совместимости.
- Бизнес-логика и реальные файлы теперь находятся в `app/languages/` и `app/modules/`.
- Структурный guard помечает `languages` и `modules` как `Legacy wrappers (временная совместимость)`.

## Stage 6B templates migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `templates/` | `app/templates/` | copied all PHP templates to `app/templates/`; `LT_TEMPLATES_PATH` updated from `root/templates` to `app/templates`; added `LT_LEGACY_TEMPLATES_PATH`; added `lt_templates_path()` helper with fallback | root `templates/` kept as legacy public assets fallback; browser URLs `/templates/default/css/`, `/templates/default/images/` remain valid | medium | done |
| `templates/default/head.php` | `app/templates/default/head.php` | PHP include updated via `lt_templates_path()`; HTML asset URLs (css/images) unchanged | browser `<link href="templates/...">` still resolves via root fallback | low | done |
| `templates/default/foot.php` | `app/templates/default/foot.php` | PHP include updated via `lt_templates_path()` | same | low | done |
| `templates/default/template.php` | `app/templates/default/template.php` | PHP include updated via `lt_templates_path()` | same | low | done |
| `templates/default/tpl.*.php` | `app/templates/default/tpl.*.php` | all 9 tpl files; all require/include updated in news.php, profile.php, details.php, my.setting.php, my.friends.php, index.php, my.book.php, browse.php, app/modules/releases.arr.php | same | low | done |
| `templates/default/css/my.css` | `app/templates/default/css/my.css` | copied to app/templates; browser CSS URL still points to root `templates/` | no breakage | low | done (root fallback) |
| `templates/default/images/forgithub.png` | `app/templates/default/images/forgithub.png` | copied; browser image URL still points to root `templates/` | no breakage | low | done (root fallback) |

Updated constants and helpers (Stage 6B):
- `LT_TEMPLATES_PATH` = `LT_APP_PATH.'/templates'` (was `LT_ROOT_PATH.'/templates'`)
- `LT_LEGACY_TEMPLATES_PATH` = `LT_ROOT_PATH.'/templates'` (new)
- `lt_templates_path($relative)` — new helper; checks `app/templates/` first, falls back to root `templates/`
- `functions.themes.php` — `is_file()` checks updated to use `LT_TEMPLATES_PATH`/`LT_LEGACY_TEMPLATES_PATH`

Root `templates/` status after Stage 6B: **legacy public assets fallback** — PHP never includes from root `templates/` directly; new templates must go in `app/templates/`.

High-risk legacy remaining after Stage 6B: **`system/`** only (Stage 6C candidate).

## Stage 6C system migration

| Current path | Target path | Action | Compatibility | Risk | Result |
|-------------|-------------|--------|---------------|------|--------|
| `system/` | `app/system/` | moved system bootstrap/config/functions/classes/cache helpers into `app/system/` as source of truth | root `system/` kept as thin wrappers for legacy `require/include` compatibility | high | done |
| `system/init.php` | `app/system/init.php` | root file converted to wrapper | all legacy root entrypoints that require `system/init.php` keep working | high | done |
| `system/init.announce.php` | `app/system/init.announce.php` | root file converted to wrapper | announce/scrape flow compatibility preserved | high | done |
| `system/init.autoclean.php` | `app/system/init.autoclean.php` | root file converted to wrapper | cron autoclean bootstrap compatibility preserved | high | done |
| `system/config/*` | `app/system/config/*` | moved real config files; root mirror now wrappers | direct legacy includes stay backward compatible | high | done |
| `system/functions/*` | `app/system/functions/*` | moved real function files; root mirror now wrappers | direct includes like `system/functions/functions.benc.php` stay compatible | high | done |
| `system/classes/*` | `app/system/classes/*` | moved real class files; root mirror now wrappers | direct includes stay compatible | high | done |
| `system/bootstrap/*` | `app/system/bootstrap/*` | moved cache/bootstrap logic; root mirror now wrappers | bootstrap includes stay compatible | high | done |

Updated constants/helpers (Stage 6C):
- `LT_SYSTEM_PATH` now points to `LT_APP_PATH.'/system'`
- `LT_LEGACY_SYSTEM_PATH` points to root `system/`
- `lt_system_path($relative)` helper resolves app/system first and falls back to root system wrappers

Root `system/` status after Stage 6C: **legacy compatibility wrappers only** (plus security `.htaccess`).
High-risk legacy root list after Stage 6C: **empty**.

## Stage 8 cache/logs runtime cleanup

| Path | Current usage | Action | Risk | Result |
|------|---------------|--------|------|--------|
| `storage/cache/` | основной runtime cache target через `lt_cache_path()` и `filecache.dir` | keep as primary runtime target | low | done |
| `storage/logs/` | основной runtime logs target через `lt_logs_path()` и `sql_log_file` | keep as primary runtime target | low | done |
| `cache/` | legacy fallback (helper `lt_runtime_pick_path`) + admin diagnostics checks | keep fallback placeholders (`.gitkeep`, `.htaccess`) | medium | kept |
| `logs/` | legacy fallback (helper `lt_runtime_pick_path`) + admin diagnostics checks | keep fallback placeholder (`.htaccess`) | medium | kept |

Stage 8 blockers for physical root-folder removal:

1. Runtime fallback contract intentionally keeps `LT_ROOT_PATH.'/cache'` and `LT_ROOT_PATH.'/logs'` as secondary writable targets when `storage/*` is unavailable.
2. Admin Control Center system diagnostics explicitly validates `legacy cache/` and `legacy logs/` directories for compatibility visibility.
3. Removing root placeholders now would reduce recovery compatibility for misconfigured hosts (storage permission/path issues), increasing outage risk.

Stage 8 decision: keep root `cache/` and `logs/` as controlled fallback, do not move business logic there, keep storage-first writes only.

## Stage 9 browse hardening and query-layer cleanup

| Area | Action | Compatibility | Risk | Result |
|------|--------|---------------|------|--------|
| `browse.php` | reduced to legacy entrypoint: init, service call, JSON/render dispatch | URL and HTML template kept in place | medium | done |
| `app/Services/Browse/Filters.php` | moved filter parsing, quick filters, selected facet handling, URL helper | keeps old `browse_*` helper names | low | done |
| `app/Services/Browse/QueryBuilder.php` | moved search tokenization, search SQL clause, facet count loading | SQL behavior preserved; ready for later query-layer replacement | medium | done |
| `app/Services/Browse/SuggestService.php` | moved ajax suggest payload builder | response shape preserved | low | done |
| `app/Services/Browse/BrowseService.php` | added request parsing, rate-limit branch, page model assembly, search-query recording | rendering variables match previous `browse.php` names | medium | done |

Bug fix:
- Search rate limiting now checks `lt_rate_limit_hit(... )['blocked']`, matching the cache bootstrap contract. The old `['limited']` check never fired.

Smoke coverage added in `app/tests/BrowseServiceTest.php`:
- browse without parameters
- search
- ajax suggest
- category filter
- invalid sort fallback
- rate-limit branch

Scope note: `announce.php` and `upload.php` were intentionally not changed in Stage 9.
