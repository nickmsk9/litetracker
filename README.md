# LiteTracker Engine

Production-ready PHP BitTorrent tracker with a classic web UI, announce/scrape endpoints, torrent catalog, user profiles, comments, ratings, bookmarks, notifications, moderation tools, and Docker deployment.

## Runtime Stack

- PHP 8.1-8.4 with Apache, mysqli, GD, memcached extension.
- MySQL 8.4.
- Memcached.
- Optional Vite build for the frontend bundle.

## Project Layout

- `app/` - application code, templates, helpers, admin modules, API handlers, and system bootstrap.
- `public/` - public web assets and API/AJAX compatibility wrappers.
- `database/` - baseline schema dump and retained production migrations.
- `storage/` - runtime cache/log/upload placeholders.
- root `*.php` files - stable legacy/public entrypoints used by existing URLs.
- `app/infra/docker/` - Apache and logrotate files copied into the Docker image.

## Deployment

1. Copy `.env.example` to `.env` and set production values:

```bash
cp .env.example .env
```

2. Start the stack:

```bash
docker compose up -d --build
```

3. Import the database on a fresh install:

```bash
docker compose exec -T db mysql -uroot -p lite < database/litetracker.sql
```

4. Build frontend assets for production:

```bash
npm ci
npm run build
```

The app can be served by Apache through the root entrypoints or through `public/index.php`, depending on the web-root strategy. Sensitive paths are denied by `.htaccess` and by the front controller.

## Configuration

Important environment variables:

- `LITETRACKER_PUBLIC_SCHEME` and `LITETRACKER_PUBLIC_HOST` - public site URL.
- `LITETRACKER_ANNOUNCE_URL` - public announce endpoint.
- `LITETRACKER_DB_HOST`, `LITETRACKER_DB_NAME`, `LITETRACKER_DB_USER`, `LITETRACKER_DB_PASSWORD` - database connection.
- `LITETRACKER_CACHE_DRIVER`, `LITETRACKER_CACHE_HOST`, `LITETRACKER_CACHE_PORT` - cache backend.
- `LITETRACKER_CRON_TOKEN` - scheduler protection token.

## Background Jobs

The Docker scheduler calls:

- `autoclean.php` every minute, internally gated by cron settings.
- `update.peers.php` every 10 minutes.

Both endpoints validate the configured cron token when external cron mode is enabled.

## Dependencies

Composer has no runtime packages beyond the PHP platform requirement. NPM is used only for the Vite frontend build.

For production deploys, install PHP dependencies with:

```bash
composer install --no-dev --classmap-authoritative
```

## Cleanup Audit

The production cleanup report is stored at `docs/production-cleanup-audit.md`.
