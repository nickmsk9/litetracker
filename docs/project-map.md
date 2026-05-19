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
- High-risk legacy-каталоги в корне остаются до отдельного этапа с compatibility wrappers/shims.
- Runtime-файлы размещаются только в `storage/*`.
- `cache/` и `logs/` в корне — fallback-совместимость на переходный период.
- `api/` и `ajax/` в корне — только thin compatibility wrappers.

Разрешённые директории в корне:

- `app`, `public`, `database`, `storage`, `docs`
- `cache`, `logs`
- `system`, `templates`, `modules`, `languages`, `ajax`, `api`

Проверка структуры:

```bash
php app/tools/check-project-structure.php
```

## Path constants и helpers

- Используйте централизованные константы путей:
  - `LT_ROOT_PATH`, `LT_APP_PATH`, `LT_PUBLIC_PATH`, `LT_STORAGE_PATH`, `LT_DATABASE_PATH`, `LT_DOCS_PATH`, `LT_SYSTEM_PATH`, `LT_TEMPLATES_PATH`, `LT_ADMIN_PATH`, `LT_API_PATH`.
- Для runtime-путей используйте helper-функции:
  - `lt_path()`, `lt_storage_path()`, `lt_cache_path()`, `lt_logs_path()`, `lt_tmp_path()`, `lt_uploads_path()`.

## Где искать

- Админка: `admin.php` + `app/admin/modules/*`.
- Публичные страницы: корневые `*.php` entrypoints (`index.php`, `browse.php`, `details.php`, ...).
- API логика: `app/api/*`, legacy URL-обёртки: `api/*`.
- AJAX логика: `app/api/ajax/*`, legacy URL-обёртки: `ajax/*`.
- Функции: `system/functions/*`.
- Классы: `system/classes/*`.
- Шаблоны: `templates/default/*`.
- Стили: `templates/default/css/*`, `public/css/*`.
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

Дополнительно: новые runtime-файлы должны использовать `storage/*` через `LT_*` константы и path helpers, а не прямые хрупкие относительные пути.
