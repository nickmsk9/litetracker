# LiteTracker Project Map

## Основные папки

- `app/` — ядро приложения, сервисы, helper-код, части legacy-обработчиков.
- `public/` — публичные assets и файлы (`css/js/images`, `dist`, `downloads`).
- `database/` — SQL-дампы и миграции.
- `storage/` — runtime-файлы (`logs`, `cache`, `tmp`, `backups`, `uploads`).
- `docs/` — документация и архивы (`docs/archive`).

## Где искать

- Админка: `admin.php` + `admin/modules/*` (переезд в `app/admin` — второй этап).
- Публичные страницы: корневые `*.php` entrypoints (`index.php`, `browse.php`, `details.php`, ...).
- API/AJAX: `api/*`, `ajax/*`.
- Функции: `system/functions/*`.
- Классы: `system/classes/*`.
- Шаблоны: `templates/default/*`.
- Стили: `templates/default/css/*`, `public/css/*`.
- Скрипты JS: `public/js/*`, source bundle в `src/app.js`.
- Изображения: `public/images/*`, `templates/default/images/*`, `public/downloads/images/*`.
- Миграции/SQL: `database/*`.
- Кэш: legacy `cache/` + целевой `storage/cache/`.
- Логи: legacy `logs/` + целевой `storage/logs/`.
- Загрузки: `public/downloads/*`, целевой runtime-контур `storage/uploads/`.
- Служебные инструменты: `scripts/*` (целевой переезд: `app/tools/` на втором этапе).

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
