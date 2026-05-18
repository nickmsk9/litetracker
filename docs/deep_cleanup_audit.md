# Deep Cleanup Audit

Date: 2026-05-18

## Scope

Audit covered:

- root PHP endpoints
- `require` / `include` chains
- bootstrap/init/announce/autoclean flows
- AJAX/API endpoints
- templates and page assets
- JS/CSS references
- language includes
- dynamic includes
- Docker runtime files
- logs/cache/runtime artifacts
- test and documentation folders

Principle used for cleanup: remove only candidates with confidence >= 95%. Anything dynamic, DB-referenced, URL-addressable, or operationally useful was kept.

## Dependency Graph Summary

Main runtime:

- `index.php`, `browse.php`, `details.php`, admin/user pages -> `system/init.php`
- `system/init.php` -> config, common functions, app helpers/services, DB, cache, comments, notifications, templates
- `announce.php` -> `system/init.announce.php` -> announce helpers + bencode
- `autoclean.php`, `update.peers.php`, `scripts/tracker_maintenance.php` -> `system/init.autoclean.php`
- Templates -> `templates/default/head.php`, `templates/default/foot.php`, page `tpl.*.php`
- JS bundle fallback -> `src/app.js` imports `public/js/main.js`, `comments.js`, `details.js`, `profile.js`, `browse.js`
- Direct page scripts still load `details.js`, `profile.js`, `lt.ajax.js`, `wz_tooltip.js`, `tagto.js`, `torrent-description-form.js`

Dynamic/runtime references kept:

- `modules/shop/*.php` are loaded by DB value `shop.file`
- `public/images/categories/*` and `public/images/shop/*` are DB-configured assets
- root PHP files with low grep counts are still direct URL endpoints or footer/header/menu targets
- `docker-data/` is ignored runtime DB state, not source
- `logs/` is runtime output; `.htaccess` kept

## Folder Matrix

| Folder | Used | Who uses it | Runtime critical | Delete? | Notes |
| --- | --- | --- | --- | --- | --- |
| `.githooks` | yes | git pre-commit -> `scripts/dump-db.sh` | no | no | Dev workflow |
| `ajax` | yes | JS/profile/comments/captcha/tags | yes | no | Runtime endpoints |
| `api` | yes | details/bookmarks/ratings/notifications/metadata/tags JS | yes | no | Runtime API |
| `app` | yes | `system/init.php`, Composer autoload metadata | yes | no | Helpers/services/support |
| `cache` | yes | placeholder for legacy/runtime cache | no | no | `.gitkeep` only |
| `database` | yes | schema dump and migration/history SQL | no | no | `.DS_Store` removed |
| `docker` | yes | Docker compose Apache/logrotate config | yes for Docker | no | Runtime infra |
| `docker-data` | yes local runtime | MySQL container volume | yes for current local DB | no | Ignored; do not clean while DB may run |
| `docs` | yes | audit/history reports | no | no | Not runtime, but useful project record |
| `languages` | yes | `system/init.php` language include | yes | no | Russian language pack |
| `logs` | yes | runtime logs | no | no | old `.log` files removed; `.htaccess` kept |
| `modules` | yes | `my.releases.php`, `shop.php` dynamic include | yes | no | Dynamic DB-backed modules |
| `public` | yes | Apache document root/assets/router | yes | no | Runtime assets |
| `scripts` | yes | maintenance/dev scripts, githook | no | no | Operational |
| `src` | yes | Vite entry fallback in `head()` | yes for module loading | no | Tiny but active |
| `system` | yes | all bootstrap/classes/functions | yes | no | Core |
| `templates` | yes | `head()`, pages | yes | no | Runtime UI |
| `tests` | yes | phpunit/smoke | no | no | Verification harness; `.DS_Store` removed |

## Removal Candidates Reviewed

| Path | Reason | Dependency graph | References | Risk | Confidence | Safe to remove |
| --- | --- | --- | --- | --- | --- | --- |
| `.DS_Store` | macOS metadata | none | not tracked, no refs | low | 100% | YES |
| `database/.DS_Store` | macOS metadata | none | not tracked, no refs | low | 100% | YES |
| `tests/.DS_Store` | macOS metadata | none | not tracked, no refs | low | 100% | YES |
| `logs/mysql_log_May_*.log` | old runtime SQL logs | log output only | ignored by `.gitignore` | low | 99% | YES |
| `.comments-fixes-plan.md` | obsolete root planning note | none | no refs outside git history | low | 99% | YES |
| `public/js/main.js:set_rating()` | dead JS helper | loaded through `src/app.js`, but function is never called | only self-reference; called endpoint `ajax/rating.php` does not exist | low | 99% | YES, remove function only |

Reviewed but kept:

- `docker-data/mysql/#innodb_redo/*_tmp`: runtime DB internals; ignored, but active local MySQL state risk.
- `system/cache/locks/*.lock`: runtime locks; mtimes are recent and may coordinate cron.
- `public/images/categories/*`, `public/images/shop/*`: can be DB referenced.
- root endpoints with low grep count: direct URL endpoints and compatibility aliases.
- old wave docs and SQL migration plans: not runtime, but useful audit/migration record; no deletion without separate docs-retention policy.

## A) Files Removed

- `.DS_Store`
- `database/.DS_Store`
- `tests/.DS_Store`
- `logs/mysql_log_May_01_2026.log`
- `logs/mysql_log_May_11_2026.log`
- `logs/mysql_log_May_16_2026.log`
- `logs/mysql_log_May_17_2026.log`
- `.comments-fixes-plan.md`

## B) Folders Removed

None. No empty removable source folders remained after cleanup.

## C) Archived Candidates

None. No candidate below 95% confidence was moved. Lower-confidence items were kept in place because moving them could break DB-driven assets, Docker state, or direct URL compatibility.

## D) Broken References Fixed

- Removed dead `set_rating()` from `public/js/main.js`.
- This function referenced missing `ajax/rating.php`, and no templates or JS called `set_rating()`.
- Current details rating path uses `api/ratings.php`.

## E) Dead Code Removed

- Removed obsolete root comment-fixes plan markdown.
- Removed unused JS rating helper.

## F) Duplicate Systems Removed

None. Duplicate-looking areas are still active compatibility surfaces:

- `config.mysql.php` is a compatibility include for `mysql.php`.
- `userdetails.php` is a legacy URL alias for `profile.php`.
- `static_pages.php` is shared by legal/static pages.

## G) Legacy TBDev Garbage Removed

- Removed legacy-style root planning note.
- Removed stale JS helper from older torrent rating UI path.
- Kept TBDev-compatible endpoints that still serve direct URLs or language/template links.

## H) Size Reduction

Removed file payload measured before deletion:

- 66,644 bytes from metadata/log/obsolete markdown files.
- Additional small reduction from deleting the unused JS function in `public/js/main.js`.

Large ignored runtime payload kept:

- `docker-data/` ~119 MB: active local DB volume, not safe to remove automatically.

## I) Risk Assessment

Overall risk: low.

Why:

- Removed files were untracked OS/runtime artifacts or a tracked obsolete plan with no runtime references.
- JS removal eliminated a broken reference to a missing endpoint and no callers existed.
- No page templates, DB schema, business logic, announce accounting, or core PHP flows were removed.

Residual risk:

- Direct URL endpoints can be externally referenced even if grep counts are low, so they were intentionally retained.
- DB-backed asset references cannot be proven from static grep alone, so category/shop images were retained.
- Docker runtime internals were retained to avoid corrupting local DB state.

## J) Manual Review Needed

- Decide a docs retention policy before archiving/removing old wave reports.
- Decide whether `docker-data/` should be fully externalized from the workspace in future local setups.
- Review whether old direct endpoints like `copyright.php`, `rating.php`, and `comments.last.php` remain part of intended navigation.
- Review whether future asset manifest builds should replace direct `src/app.js` fallback with committed `public/dist` assets.

## Verification

- `php -l` over all PHP files: PASS.
- `git diff --check`: PASS.
- Smoke URLs:
  - `/`: HTTP 200
  - `/details.php?id=1`: HTTP 200
  - `/browse.php`: HTTP 200
  - `/login.php`: HTTP 200
  - `/upload.php`: HTTP 200
  - `/admin.php`: HTTP 200
- AJAX comments refresh: HTTP 200, `ok=1`, HTML present.
- Announce smoke: PASS, 15 scenarios, 0 failed.
- Cache runtime: Memcached active and online in Docker.
