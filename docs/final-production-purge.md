# Final Production Purge

## REMOVED

- Removed all competing database files and migrations from `database/`; only `database/litetracker.sql` remains.
- Removed demo/test database rows from torrents, files, comments, mail, peers, trackers, sessions, notifications, reports, ratings, bookmarks, search logs, admin notes, moderation logs, news, polls, and user activity tables.
- Removed old production-audit draft report and deleted earlier dev/test/tooling/docs/demo files from the repository cleanup pass.
- Removed phpMyAdmin from `docker-compose.yml`.
- Removed local bind-mounted `docker-data/` usage; MySQL now uses the named Docker volume `db-data`.
- Removed runtime-only `storage/backups/`, `storage/tmp/`, `app/system/cache/`, and generated SQL log files.
- Removed the unused `lt_tmp_path()` helper and `tmp` storage path case.

## KEPT

- Public PHP entrypoints required by current routes and legacy URLs.
- Application code under `app/`, public assets under `public/`, default templates, admin modules, AJAX/API handlers, cron endpoints, and Docker runtime configs.
- Runtime directories only: `storage/cache/`, `storage/logs/`, `storage/uploads/`, each with `.gitkeep`.
- Production frontend bundle in `public/dist/` so the Docker-only install path serves built assets.
- Composer and npm manifests/locks for reproducible validation and frontend build.

## RUNTIME REQUIREMENTS

- Docker with Compose.
- PHP 8.4 Apache image built by `Dockerfile`.
- MySQL 8.4.
- Memcached.
- Import `database/litetracker.sql` after `docker compose up -d --build`.

## DATABASE FINAL STATE

- One source of truth: `database/litetracker.sql`.
- Tables in final dump: 48.
- Seeded data is limited to mandatory production baseline rows:
  - `categories`
  - `cron`
  - `priv`
  - `shop`
  - `users`
  - `site_settings`
- Admin user:
  - username: `admin`
  - password: `change-me`
  - `password_code`: `FORCE_CHANGE_PASSWORD`
- First admin login is forced to change the temporary password.
- Former production migrations are folded into the baseline:
  - `password_reset_tokens`
  - `admin_audit_log`
  - `site_settings`
  - `ad_slots`
  - `ads`
  - `reports`

## RISKS

- The dump intentionally removes all content and activity data. Existing production instances must back up data before replacing a live database.
- `docker compose up -d --build` may warn about old local orphan containers from previous dev runs; the production compose file no longer defines them.
- `storage/logs/` and `storage/cache/` are empty in git but will receive runtime files after boot.

## FINAL PROJECT SIZE

- Source/runtime baseline size, excluding `.git`, `vendor`, and `node_modules`: about 2.8M.

## FINAL FILE COUNT

- Final tracked/source file count, excluding `.git`, `vendor`, and `node_modules`: 245 files including this report.

## VERIFICATION

- `php -l`: passed for all PHP files.
- `composer validate --strict`: passed.
- `npm audit`: passed, 0 vulnerabilities.
- `npm run build`: passed.
- `docker build -t litetracker-production-purge-check .`: passed.
- `docker compose config`: passed.
- `docker compose up -d --build`: passed.
- Database import from `database/litetracker.sql`: passed.
- HTTP boot check: `http://localhost:8094/` returned `200 text/html; charset=utf-8`.
