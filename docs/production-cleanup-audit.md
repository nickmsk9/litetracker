# LiteTracker Engine Production Cleanup Audit

Date: 2026-05-24

## Scope

This audit inspected the repository as a production application, ignoring git history and using only real runtime connectivity: root entrypoints, `public/index.php`, `.htaccess`, `require/include`, dynamic module directories, AJAX/API wrappers, JavaScript calls, template references, Docker files, cron calls, and dependency manifests.

## Dependency Graph

Primary runtime graph:

- Web entrypoints: root `*.php` files listed in `public/index.php` and `.htaccess`.
- Main bootstrap: `app/system/init.php` -> config, common functions, helpers, cache support, DB, theme, language, torrent status.
- Announce bootstrap: `announce.php` -> `app/system/init.announce.php` -> announce functions, bencode functions, DB, cache.
- Cron bootstrap: `autoclean.php` and `update.peers.php` -> `app/system/init.autoclean.php`.
- Admin dynamic modules: `admin.php?tab=cache|maintenance|system|ads|reports|database|comments|users_manage` -> `app/admin/modules/*.php`.
- Shop dynamic modules: DB `shop.file` values -> `app/modules/shop/*.php`.
- Public API/AJAX wrappers: `public/api/*`, `public/ajax/*` -> `app/api/*`.
- Templates: `lt_templates_path($config['template'])` -> `app/templates/default/*`; web-visible duplicate theme assets remain under `public/templates/default/*`.
- Frontend: `head()` -> Vite manifest `public/dist/manifest.json` when built, otherwise source entry `app/frontend/app.js`; source entry imports `public/js/*.js`.
- Docker: `Dockerfile` copies `app/infra/docker/apache/*` and `app/infra/docker/logrotate/litetracker`; `docker-compose.yml` runs PHP, MySQL, Memcached, optional phpMyAdmin, scheduler.

## Usage Analysis

The following were treated as KEEP because they are directly or dynamically reachable:

- Root entrypoints in `public/index.php` and `.htaccess`: `admin.php`, `announce.php`, `autoclean.php`, `browse.php`, `details.php`, `download.php`, `edit.php`, `login.php`, `upload.php`, `update.peers.php`, and the remaining listed routes.
- Legacy wrapper routes: `avatars.php`, `comments.last.php`, `complaint.php`, `copyright.php`, `disclaimer.php`, `notify.php`, `rating.php`, `rules.php`, `static_pages.php`.
- `app/Http/Legacy/*`: required by those wrapper routes.
- `app/admin/modules/*`: loaded dynamically by validated admin tabs.
- `app/modules/shop/*`: loaded dynamically from shop DB rows.
- `app/modules/releases.arr.php`: dynamic release-row renderer.
- `public/ajax/*` and `public/api/*`: public wrappers/front controller targets.
- `public/js/*`, `public/css/*`, `public/libs/photoswipe/*`: referenced by templates, frontend entry, or details gallery behavior.
- `public/images/categories/*`, `public/images/shop/*`, rating `*2.gif` icons, `default_avatar.gif`, `up/down/refresh/ok.gif`: referenced directly or via DB/category/shop values.
- `database/litetracker.sql` and `database/migrations/*`: retained as baseline schema and production migrations.

## SAFE TO DELETE

Deleted in this cleanup:

- Local Docker/MySQL runtime state: `docker-data/`.
- Local runtime logs: `logs/mysql_log_May_18_2026.log`, `storage/logs/mysql_log_May_19_2026.log`.
- Dev/test tree: `app/tests/`, `phpunit.xml`.
- Dev CLI tooling not used by runtime/Docker: `.githooks/pre-commit`, `app/tools/check-project-structure.php`, `app/tools/dump-db.sh`, `app/tools/seed_demo_activity.php`, `app/tools/tracker_maintenance.php`.
- Historical audit/archive docs: old `docs/*.md` audit reports and `docs/archive/`.
- Temporary phase SQL files: `database/p*.sql`.
- Demo uploads: tracked sample avatars and sample torrent/image/screen files under `public/avatars/` and `public/downloads/`.
- Orphan images with zero runtime references: `public/images/edit_add.png`, `public/images/loading.gif`, `public/images/messagebox_critical.png`, `public/images/rate/darkgold1.gif`, `public/images/rate/gold1.gif`, `public/images/rate/green1.gif`, `public/images/rate/zero1.gif`.
- Local installed dependencies: ignored `vendor/` and `node_modules/` directories.

## PROBABLY UNUSED

Not deleted because of dynamic/runtime uncertainty:

- `database/litetracker.sql` contains demo-like seed rows as well as schema/bootstrap data. A schema-only production dump would be cleaner, but automatic pruning risks deleting required roles/categories/settings.
- `public/templates/default/*` duplicates `app/templates/default/*`, but login/signup and direct CSS/image URLs use public paths.
- `.htaccess` and Apache configs still deny legacy paths such as `tests` and `docker-data`; this is harmless defense in depth even after cleanup.
- `public/images/categories/*` and `public/images/shop/*` are DB-driven and should stay unless the production DB is audited at the row level.

## KEEP

Critical production files and directories:

- `Dockerfile`, `docker-compose.yml`, `.env.example`, `.htaccess`, `public/index.php`.
- `app/system/init.php`, `app/system/init.announce.php`, `app/system/init.autoclean.php`.
- `app/system/config/*`, `app/system/functions/*`, `app/system/classes/*`, `app/system/bootstrap/*`.
- `app/helpers/*`, `app/Support/*`, `app/core/*`, `app/api/*`, `app/admin/modules/*`, `app/modules/*`.
- Root public route files and `public/api/*`, `public/ajax/*`.
- `public/js/*`, `public/css/*`, `public/libs/photoswipe/*`, retained `public/images/*`.
- `storage/*/.gitkeep` and `public/downloads/*/.gitkeep` placeholders.
- `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.

## DUPLICATES

- `app/templates/default/*` vs `public/templates/default/*`: intentional split between PHP include templates and web-visible CSS/fonts/images.
- `app/api/*` vs `public/api/*`, and `app/api/ajax/*` vs `public/ajax/*`: intentional wrapper split for front-controller and legacy URL compatibility.
- Root legacy wrappers vs `app/Http/Legacy/*`: intentional URL compatibility layer.

## LEGACY

Retained because currently reachable:

- Root PHP entrypoints are legacy-compatible public routes.
- `app/Http/Legacy/*` static page wrappers.
- TBDev-style helper functions in `app/system/functions/functions.php`, comments, bencode, and template helpers remain in use through broad procedural runtime.
- `functions.htmLawed.php` and `functions.textbb.php` are old-style but active sanitization/BBCode dependencies.
- `class.phpmailer.php` and `class.smtp.php` are legacy mail classes but still loaded/used.

## UNUSED DEPENDENCIES

Composer:

- Removed `phpunit/phpunit` from `require-dev`.
- Regenerated `composer.lock`; both `packages` and `packages-dev` are now empty.
- Composer audit after removal has no locked packages to audit.

NPM:

- `vite` remains as a dev-only frontend build dependency and was upgraded to the current safe major.
- `esbuild` is now explicit because the Vite 8 build uses `minify: "esbuild"`.
- `npm audit` returned zero vulnerabilities.
- `node_modules/` was removed from the working tree; reinstall with `npm ci` when building assets.

## CLEANUP PLAN

Applied order:

1. Build inventory from tracked files, public route lists, include/require, admin dynamic includes, API/AJAX wrappers, JS/template references, Docker and cron references.
2. Classify files as USED / UNUSED / MAYBE based on direct and dynamic reachability.
3. Remove only confirmed non-runtime files and local artifacts.
4. Remove test/dev Composer dependency and regenerate lock file.
5. Replace stale README content with production-focused deployment notes.
6. Run syntax/dependency validation.

Recommended next production hardening:

1. Create a schema-only `database/litetracker.sql` or add a separate `database/seed-demo.sql`.
2. Run `npm ci && npm run build` during deployment so `public/dist/manifest.json` exists and frontend modules are served from `public/dist`.
3. Audit production DB rows for category/shop image references before deleting any DB-driven assets.
4. If the project wants zero dev tooling in production archives, ship with `composer install --no-dev --classmap-authoritative` and omit `node_modules/`.

## RISKS

- Removing tests means this repository is now production-minimal; future automated regression checks require restoring a test harness or running external smoke checks.
- `database/litetracker.sql` still contains seed/demo content; using it directly in production may create demo users/content unless sanitized.
- Deleting dynamic assets without DB inspection is risky; that is why category/shop images and public template assets were retained.
- Without a Vite build artifact, the fallback source module path may be blocked by production web rules. Production deployment should include `npm run build`.

## Rollback Notes

- Deleted tracked files are recoverable from git with `git restore <path>`.
- Deleted ignored local state (`docker-data/`, `vendor/`, `node_modules/`) should be recreated by Docker, Composer, and NPM.
- Runtime uploads are now represented only by `.gitkeep` placeholders; real production uploads should come from persistent storage, not the repository.

## Verification

- `php -l` passed for every remaining PHP file outside ignored dependency/runtime directories.
- `composer validate --strict --no-interaction` passed.
- `composer update --no-install --no-interaction --ignore-platform-req=php` regenerated the lock file with no runtime/dev packages.
- `npm audit` passed with zero vulnerabilities after upgrading Vite and adding explicit `esbuild`.
- `npm run build` passed with Vite 8 and generated a valid `public/dist/manifest.json` during verification; generated files were removed afterward because `public/dist` is a build artifact.
- PHPUnit was not run because the test tree and PHPUnit dependency were intentionally removed as dev-only cleanup.
