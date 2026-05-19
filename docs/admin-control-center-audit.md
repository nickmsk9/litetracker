# Аудит административной панели (Admin Control Center)

> **Дата аудита:** 2025  
> **Файл:** `admin.php` (1372 строки)  
> **Цель:** Описать текущее состояние, выявить проблемы, спланировать расширение.

---

## 1. Что уже есть в текущей админке

### Структура и вкладки

Административная панель реализована в одном файле (`admin.php`) и содержит **8 вкладок**:

| Вкладка | Описание |
|---|---|
| `overview` | Общая статистика: пользователи, раздачи, активность |
| `content` | Управление контентом (ссылки на categories.php, browse.php) |
| `users` | Управление пользователями (ссылки на users.php) |
| `moderation` | Модерация торрентов: очередь, статусы, moderation_log |
| `monitoring` | Мониторинг: сессии, IP-утилиты, поисковые запросы |
| `site-settings` | Основные настройки сайта (27 полей, файловые) |
| `tracker-settings` | Настройки трекера (announce URL, интервалы) |
| `feature-settings` | Настройки функций (капча, бонусы, поиск) |

### Что работает

- **Модерация торрентов** — очередь, смена статуса, лог (таблица `moderation_log`)
- **Быстрые действия** — сброс кэша, очистка сессий, оптимизация БД, toggle сайта/регистрации
- **Управление настройками** — 3 вкладки с настройками, 27 полей через config.php
- **IP-утилиты** — ip.util.php подключён к мониторингу
- **Отчёты стены** — wall_reports.php для жалоб на комментарии профиля

### Аутентификация и привилегии

- Доступ через `admin_dashboard_can_access()` — проверяет наличие любого PRIV-флага
- Суперадмин = `PRIV['EDIT_PRIV']` (полный доступ)
- PRIV-система хранится в таблице `priv`, загружается при старте сессии пользователя

### Защита форм (CSRF)

- Все формы используют `lt_csrf_input('admin_dashboard')` (scope `admin_dashboard`)
- POST-запросы проверяются через `lt_csrf_validate()`
- GET-действия для toggle siteonline/registeronline переведены на POST ✓

---

## 2. Какие файлы задействованы

### Основные файлы

| Файл | Назначение |
|---|---|
| `admin.php` | Главный файл панели (1372 строки) |
| `system/config/config.php` | Файл конфигурации (настройки читаются и перезаписываются regex) |
| `app/functions.php` | Общие функции включая CSRF, PRIV, вспомогательные |
| `ip.util.php` | IP-утилиты, доступны из вкладки monitoring |
| `users.php` | Управление пользователями (вызывается отдельно) |
| `categories.php` | Управление категориями (вызывается отдельно) |
| `browse.php` | Просмотр и управление раздачами |
| `wall_reports.php` | Жалобы на комментарии профиля |
| `moderation_log` | Таблица БД для логов модерации торрентов |
| `user_admin_notes` | Таблица БД для заметок об пользователях (не интегрирована в панель) |

### Шаблоны

- `templates/` — директория шаблонов Twig/PHP
- Базовый layout подключается через системные include'ы

### Связанные скрипты

| Файл | Назначение |
|---|---|
| `autoclean.php` | Автоочистка (cron) |
| `update.peers.php` | Обновление пиров (cron) |
| `scrape.php` | Scrape-запросы трекера |
| `announce.php` | Announce-обработчик |

---

## 3. Проблемы безопасности

### Критические

| # | Проблема | Риск |
|---|---|---|
| 1 | **Настройки сохраняются regex-заменой в config.php** | Инъекция в конфигурационный файл при спецсимволах в значении |
| 2 | **Нет таблицы `admin_audit_log`** | Любые действия администраторов не логируются; невозможен ретроспективный анализ инцидентов |
| 3 | **Нет таблицы `site_settings` в БД** | Все настройки хардкожены в PHP-файле; риск при race conditions при параллельных сохранениях |

### Средние

| # | Проблема | Риск |
|---|---|---|
| 4 | **`sessions_clear` удаляет ВСЕ сессии без фильтра** | Нет возможности сбросить сессии одного пользователя или по IP, только массово |
| 5 | **`user_admin_notes` таблица существует, но не интегрирована** | Функциональность написана, но недоступна через UI |
| 6 | **`moderation_log` только для торрентов** | Нет единого лога для всех административных действий |
| 7 | **Нет режима обслуживания (maintenance mode)** | Нельзя вывести сайт в режим обслуживания с кастомным сообщением |

### Низкие / Архитектурные

| # | Проблема | Риск |
|---|---|---|
| 8 | **Нет общей таблицы `reports`** | `comments_reports` и `comments_users_reports` существуют, но нет единой точки управления жалобами |
| 9 | **Нет управления рекламой** | Нет таблиц `ad_slots` / `ads`, нет UI для баннеров |
| 10 | **Нет системной диагностики** | Нет мониторинга состояния БД, Memcached, дискового пространства |

---

## 4. Опасные действия

Следующие действия необратимы или имеют широкий эффект и требуют дополнительного подтверждения:

| Действие | Текущее состояние | Рекомендация |
|---|---|---|
| **Очистка всех сессий** (`sessions_clear`) | Нет фильтра, нет подтверждения | Добавить confirm-диалог + фильтр по user_id/IP |
| **Оптимизация БД** (`OPTIMIZE TABLE`) | Блокирует таблицы при выполнении | Запускать только в обслуживании; добавить предупреждение |
| **Сброс конфигурации** | Regex-замена в config.php | Перейти на DB-based настройки с backup'ом |
| **Массовый бан пользователей** | Отсутствует | При реализации — добавить подтверждение и аудит-лог |
| **Смена привилегий (PRIV)** | edit_priv.php, вне панели | Интегрировать в панель с аудит-логом |
| **Удаление торрентов** | Возможно через browse.php | При реализации — soft-delete + аудит-лог |

---

## 5. Настройки, которые нельзя менять через сайт

Следующие настройки хардкожены в `system/config/config.php` и **не должны** редактироваться через веб-интерфейс без соответствующих мер защиты:

### Критически важные (только через сервер)

```php
DB_HOST, DB_USER, DB_PASS, DB_NAME   // Параметры подключения к БД
MEMCACHED_HOST, MEMCACHED_PORT        // Параметры Memcached
SECRET_KEY / CSRF_SECRET              // Секретные ключи
```

### Настройки, требующие миграции в БД (`site_settings`)

| Ключ | Тип | Описание |
|---|---|---|
| `sitename` | text | Название сайта |
| `siteonline` | bool | Сайт включён/выключен |
| `registeronline` | bool | Регистрация открыта/закрыта |
| `gzip` | bool | Сжатие ответов |
| `default_theme` | select | Тема по умолчанию |
| `begin_money` | int | Начальный баланс новых пользователей |
| `announce_url` | text | URL announce-эндпоинта |
| `local_retracker_url` | text | URL локального ретрекера |
| `announce_interval` | int | Интервал announce (секунды) |
| `releases_news` | bool | Автосоздание новостей для релизов |
| `captcha_*` | text/bool | Настройки CAPTCHA |
| `search_*` | text/int | Настройки поиска |
| `bonus_*` | int | Настройки бонусной системы |

---

## 6. Таблицы для новой админки

### Новые таблицы (требуют создания)

#### `admin_audit_log` — лог всех действий администраторов

```sql
id, admin_id, action, module, target_type, target_id,
old_value, new_value, ip, user_agent, created_at
```

Примеры `action`: `user.ban`, `user.unban`, `torrent.delete`, `setting.update`, `session.clear`

#### `site_settings` — DB-backed настройки сайта

```sql
id, setting_key (UNIQUE), setting_value, setting_type (text/int/bool/json/select),
description, is_public, updated_by, updated_at
```

#### `ad_slots` — рекламные блоки (слоты)

```sql
id, code (UNIQUE), title, description, is_active, created_at, updated_at
```

Примеры `code`: `header_banner`, `sidebar_top`, `torrent_page_bottom`

#### `ads` — рекламные объявления

```sql
id, slot_id, title, html_code, image_url, target_url,
is_active, show_to_guests, show_to_users,
show_on_desktop, show_on_mobile,
starts_at, ends_at, created_by, created_at, updated_at
```

#### `reports` — общие жалобы/обращения

```sql
id, reporter_id, target_type, target_id, reason,
status (new/in_progress/resolved/rejected),
assigned_to, moderator_comment,
created_at, updated_at, resolved_at
```

Примеры `target_type`: `torrent`, `user`, `comment`, `news`

### Существующие таблицы (использовать в расширенной панели)

| Таблица | Применение в панели |
|---|---|
| `moderation_log` | Лог модерации торрентов (вкладка moderation) |
| `user_admin_notes` | Заметки о пользователях (интегрировать в user management) |
| `bans` | Управление банами (вкладка users_manage) |
| `sessions` | Мониторинг и очистка сессий |
| `comments_reports` | Жалобы на комментарии (унифицировать через reports) |
| `comments_users_reports` | Жалобы на комментарии профиля (унифицировать) |
| `cron` | Мониторинг задач cron |
| `priv` | Управление привилегиями пользователей |

---

## 7. Изменения в БД

### Миграция: `database/migrations/admin_control_center.sql`

Создать 5 новых таблиц:
1. `admin_audit_log`
2. `site_settings`
3. `ad_slots`
4. `ads`
5. `reports`

Заполнить `site_settings` дефолтными значениями через `INSERT IGNORE`:

- Настройки обслуживания: `maintenance_mode`, `maintenance_message`, `maintenance_allowed_admins`, `maintenance_starts_at`, `maintenance_ends_at`
- Мета сайта: `site_description`, `admin_email`
- Функциональность: `email_confirmation`, `invites_enabled`, `comments_enabled`, `ratings_enabled`, `bookmarks_enabled`, `chat_enabled`, `ads_enabled`
- TTL кэша: `cache_ttl_main` (300с), `cache_ttl_categories` (600с), `cache_ttl_stats` (300с), `cache_ttl_torrent` (1800с), `cache_ttl_user` (300с)

### Дополнительные изменения (будущее)

```sql
-- Пример: добавить индекс для ускорения выборки жалоб по статусу
-- (при наполнении таблицы reports >10k строк)
ALTER TABLE `reports` ADD INDEX `idx_status_created` (`status`, `created_at`);
```

---

## 8. Поэтапный план внедрения

### Фаза 1 — Инфраструктура (приоритет: высокий)

**Цель:** Создать фундамент для расширения без изменения существующего поведения.

- [ ] Запустить миграцию `admin_control_center.sql` (5 таблиц + default settings)
- [ ] Создать `app/functions.admin.php` с хелпером `admin_audit()`:
  ```php
  function admin_audit(string $action, string $module, string $target_type = '',
                        int $target_id = 0, $old = null, $new = null): void
  ```
- [ ] Создать хелпер `lt_setting(string $key, $default = null)` для чтения из `site_settings` с fallback на `config.php`
- [ ] Создать хелпер `lt_setting_set(string $key, $value, int $admin_id)` с записью в аудит-лог
- [ ] Добавить вызов `admin_audit()` в существующие действия admin.php (toggle siteonline, clear sessions, optimize DB)

### Фаза 2 — Новые вкладки в admin.php (приоритет: средний)

**Цель:** Добавить недостающие разделы в существующий admin.php.

| Вкладка | Функциональность |
|---|---|
| `users_manage` | Поиск/просмотр пользователей, бан/разбан, смена класса, сброс passkey, заметки |
| `torrents_manage` | Расширенное управление торрентами: редактирование заголовка/описания/категории |
| `comments_manage` | Список комментариев с фильтрами, удаление/правка, история изменений |
| `reports` | Единый список жалоб из таблицы `reports`, фильтр по типу/статусу |
| `ads` | Управление слотами (`ad_slots`) и объявлениями (`ads`) |
| `maintenance` | Включение режима обслуживания, кастомное сообщение, расписание |
| `system` | Диагностика: версия PHP/MySQL, Memcached статус, дисковое пространство |
| `database` | Статистика таблиц, CHECK/ANALYZE/OPTIMIZE отдельных таблиц |
| `cache` | Memcached статистика, flush отдельных ключей/namespace'ов |
| `audit_log` | Просмотр `admin_audit_log` с фильтрами |

### Фаза 3 — Модульная архитектура (приоритет: низкий)

**Цель:** Разбить монолитный admin.php на модули для удобства поддержки.

```
admin/
├── modules/
│   ├── users.php         // Управление пользователями
│   ├── torrents.php      // Управление торрентами
│   ├── comments.php      // Управление комментариями
│   ├── reports.php       // Жалобы
│   ├── ads.php           // Реклама
│   ├── maintenance.php   // Режим обслуживания
│   ├── system.php        // Диагностика
│   ├── database.php      // Управление БД
│   ├── cache.php         // Кэш
│   └── audit.php         // Аудит-лог
└── index.php             // Router для модулей
```

### Фаза 4 — Миграция настроек в БД (приоритет: средний)

**Цель:** Перевести настройки из config.php в `site_settings`.

- [ ] Реализовать `lt_setting()` / `lt_setting_set()` с кэшированием в Memcached
- [ ] Перенести поля из вкладок `site-settings`, `tracker-settings`, `feature-settings` на чтение из `site_settings`
- [ ] Добавить кнопку «Синхронизировать с config.php» для первоначальной миграции значений
- [ ] Обновить сохранение настроек: вместо regex-замены — `UPDATE site_settings`
- [ ] Оставить config.php только для DB/Memcached credentials

### Фаза 5 — Роли и привилегии (приоритет: высокий)

**Цель:** Формализовать роли для разграничения доступа в расширенной панели.

| Роль | Флаги PRIV | Доступ к вкладкам |
|---|---|---|
| `owner` | EDIT_PRIV=1 + все | Всё |
| `administrator` | EDIT_PRIV=1 | Всё кроме смены PRIV других администраторов |
| `moderator` | edit_release, comments_edit, comments_delete, ip_util | moderation, comments_manage, monitoring |
| `content_manager` | cats, news_add, edit_news, faq_moderate, edit_release | content, torrents_manage |
| `support` | setting_user, users_view, ip_util | users_manage (read), monitoring |
| `readonly_admin` | sessions_view, search_query | monitoring (read-only) |

### Фаза 6 — Документация и тесты (приоритет: низкий)

- [ ] Обновить PHPUnit тесты для новых хелперов
- [ ] Добавить документацию по API хелперов `lt_setting()`, `admin_audit()`
- [ ] Написать README для `admin/` директории

---

## Приложение: Полный список PRIV-флагов

| Флаг | Описание |
|---|---|
| `EDIT_PRIV` | Суперадмин: изменение привилегий других пользователей |
| `edit_release` | Редактирование раздач |
| `cats` | Управление категориями |
| `news_add` | Добавление новостей |
| `edit_news` | Редактирование новостей |
| `faq_moderate` | Модерация FAQ |
| `user_add` | Добавление пользователей |
| `setting_user` | Изменение настроек пользователей |
| `messages` | Системные сообщения |
| `ip_util` | IP-утилиты |
| `sessions_view` | Просмотр сессий |
| `sessions_clear` | Очистка сессий |
| `search_query` | Просмотр поисковых запросов |
| `multitracker_accounts` | Управление аккаунтами мультитрекера |
| `comments_edit` | Редактирование комментариев |
| `comments_delete` | Удаление комментариев |
| `upload` | Загрузка торрентов |
| `download_torrent` | Скачивание .torrent файлов |
| `download_magnet` | Использование magnet-ссылок |
| `details_view` | Просмотр деталей раздачи |
| `users_view` | Просмотр списка пользователей |

---

## Приложение: Существующие таблицы БД (43 шт.)

```
bans, birthday_rewards, books, categories, chat,
comment_edit_history, comment_pins, comment_reactions,
comments_faq, comments_news, comments_reports,
comments_torrents, comments_users, comments_users_reports,
confirm, cron, faq, files, forgot, friends, mail,
moderation_log, news, notifications, peers, polls,
polls_questions, polls_voting, priv, retrackers,
search_query, sessions, shop, snatched, tags,
torrent_ratings, torrent_views, torrents, trackers,
user_admin_notes, users, users_blacklist
```
