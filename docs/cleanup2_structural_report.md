# CLEANUP-2: структурная консолидация LiteTracker

Дата: 2026-05-18

## A) Root Endpoint Inventory

| Path | Role | References | Direct URL needed | Move candidate | Wrapper needed | Risk | Action |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `404.php` | error page | `public/index.php`, web server fallback | yes | later | yes | medium | keep |
| `admin.php` | admin dashboard | header/admin links/forms | yes | later | yes | high | keep |
| `announce.php` | BitTorrent announce | clients/tests/config | yes | no | no | critical | keep |
| `autoclean.php` | cron endpoint | footer/docker cron | yes | no | no | high | keep |
| `avatars.php` | static info page | footer/head CSS list | yes | yes | yes | low | moved behind wrapper |
| `browse.php` | torrent catalog | nav/forms/JS | yes | later | yes | high | keep |
| `categories.php` | admin categories | admin links | yes | later | yes | high | keep |
| `check_release.php` | moderation bulk action | `my.releases.php` form | yes | later | yes | high | keep |
| `comments.last.php` | latest comments page | direct URL/head CSS list | yes | yes | yes | low | moved behind wrapper |
| `comments.take.php` | legacy comments handler | templates/comments links | yes | later | yes | high | keep |
| `complaint.php` | legal static page | footer | yes | yes | yes | low | moved behind wrapper |
| `copyright.php` | upload help/static page | `upload.php` link | yes | yes | yes | low | moved behind wrapper |
| `details.php` | torrent details | catalog/cards/notifications | yes | later | yes | critical | keep |
| `disclaimer.php` | legal static page | footer | yes | yes | yes | low | moved behind wrapper |
| `donate.php` | static/donation page | direct URL | yes | possible later | yes | medium | keep |
| `download.php` | torrent download | details/templates | yes | later | yes | critical | keep |
| `edit.php` | edit torrent | details/admin | yes | later | yes | high | keep |
| `edit_priv.php` | privileges admin | admin users flow | yes | later | yes | high | keep |
| `exit.php` | logout endpoint | user menu | yes | possible later | yes | medium | keep |
| `faq.php` | FAQ + comments | footer/direct | yes | later | yes | medium | keep |
| `feedback.php` | feedback endpoint/modal | footer modal | yes | later | yes | medium | keep |
| `index.php` | home page | root route | yes | no | no | critical | keep |
| `ip.util.php` | admin IP tools | admin/multitracker/sessions | yes | later | yes | high | keep |
| `language.php` | language switch handler | `select_language()` | yes | possible later | yes | medium | keep |
| `login.php` | login/reset | auth links | yes | later | yes | critical | keep |
| `messages.php` | admin broadcast | admin users | yes | later | yes | high | keep |
| `multitracker_accounts.php` | moderation/accounting page | admin moderation | yes | later | yes | high | keep |
| `my.book.php` | bookmarks page/action | menu/details | yes | later | yes | high | keep |
| `my.friends.php` | friends page/action | profile/user flows | yes | later | yes | medium | keep |
| `my.mail.php` | mail UI/API-ish POST | user menu/JS | yes | later | yes | high | keep |
| `my.releases.php` | user releases/moderation | profile/admin | yes | later | yes | high | keep |
| `my.setting.php` | settings UI | user menu | yes | later | yes | high | keep |
| `my.setting.take.php` | settings handler | settings form | yes | later | yes | high | keep |
| `news.php` | news archive/editor | RSS/home/direct | yes | later | yes | medium | keep |
| `notifications.php` | notifications UI | user menu/JS | yes | later | yes | medium | keep |
| `notify.php` | legacy notification redirect | direct URL | yes | yes | yes | low | moved behind wrapper |
| `profile.php` | profile/wall | many links | yes | later | yes | high | keep |
| `rating.php` | rating info page | language warning link | yes | yes | yes | low | moved behind wrapper |
| `rss.php` | RSS endpoint | footer/social | yes | no | no | medium | keep |
| `rules.php` | static rules page | footer/signup text | yes | yes | yes | low | moved behind wrapper |
| `scrape.php` | BitTorrent scrape | clients/tests/README | yes | no | no | critical | keep |
| `search_query.php` | search log page | admin/direct | yes | later | yes | medium | keep |
| `sessions.php` | admin sessions | admin links | yes | later | yes | high | keep |
| `shop.php` | shop module | direct/menu legacy | yes | later | yes | high | keep |
| `signup.php` | registration | auth links | yes | later | yes | critical | keep |
| `static_pages.php` | shared static helper | static pages include | compatibility include | yes | yes | low | moved behind wrapper |
| `update.peers.php` | cron/manual tracker update | docker cron/details | yes | no | no | critical | keep |
| `upload.php` | upload flow | browse/menu | yes | later | yes | critical | keep |
| `user_add.php` | admin user add | users admin | yes | later | yes | high | keep |
| `userdetails.php` | legacy alias | old external URLs | yes | already alias | yes | low | keep |
| `users.php` | admin users | admin links | yes | later | yes | high | keep |
| `wall_reports.php` | comment report moderation | admin/header | yes | later | yes | high | keep |

## B) Files Moved

Moved low-risk page logic to `app/Http/Legacy/`:

- `avatars.php` -> `app/Http/Legacy/avatars.page.php`
- `complaint.php` -> `app/Http/Legacy/complaint.page.php`
- `disclaimer.php` -> `app/Http/Legacy/disclaimer.page.php`
- `rules.php` -> `app/Http/Legacy/rules.page.php`
- `copyright.php` -> `app/Http/Legacy/copyright.page.php`
- `rating.php` -> `app/Http/Legacy/rating.page.php`
- `comments.last.php` -> `app/Http/Legacy/comments.last.page.php`
- `notify.php` -> `app/Http/Legacy/notify.page.php`
- `static_pages.php` -> `app/Http/Legacy/static_pages.php`

High-risk root pages were intentionally kept in place.

## C) Wrappers Created

Root wrappers now keep old URLs stable:

- `avatars.php`
- `complaint.php`
- `disclaimer.php`
- `rules.php`
- `copyright.php`
- `rating.php`
- `comments.last.php`
- `notify.php`
- `static_pages.php`

Wrapper shape is intentionally thin: comment + `require`/`require_once`.

## D) Docs Archived

Created `docs/archive/` and moved temporary wave/query/schema reports there:

- `docs/d1_*` through `docs/d4_*`
- `docs/p1_*` through `docs/p25_*`

Kept current root docs:

- `docs/env_config_report.md`
- `docs/deep_cleanup_audit.md`
- `docs/frontend_assets_audit.md`
- `docs/cleanup2_structural_report.md`

## E) Runtime Folder Policy

Current policy:

- `docker-data/` is runtime DB state and is ignored. It must not be cleaned automatically while Docker DB may depend on it.
- `logs/*` is ignored; `logs/.htaccess` is kept.
- `cache/*` is ignored; `cache/.gitkeep` and new `cache/.htaccess` are kept.
- `system/cache/*.cache` and `system/cache/locks/*` are ignored; `system/cache/locks/.gitkeep` is kept.
- `public/downloads/*` is ignored except `.gitkeep` placeholders.

Change made:

- Added `cache/.htaccess` and unignored it in `.gitignore`.

Future external-volume recommendation:

- Keep Docker MySQL data in a named Docker volume instead of repo-local `docker-data/` for cleaner worktrees.
- Migration must be explicit and documented, not automatic.

## F) Frontend Asset Findings

See `docs/frontend_assets_audit.md`.

Important findings:

- `src/app.js` is active as Vite/fallback entry.
- `public/js/details.js` and `public/js/profile.js` may be duplicate-loaded via `src/app.js` and direct page includes; do not remove without browser event checks.
- DB-driven images cannot be proven unused by grep alone.
- Removed only previously identified dead helper `set_rating()` in CLEANUP-1.

## G) Compatibility Guarantees

- Old URLs remain present at root.
- Root wrappers preserve `$_SERVER['SCRIPT_NAME']` as the legacy URL.
- Moved page scripts still run from the same project working directory, so existing relative paths continue to resolve.
- No DB schema, announce accounting, auth, upload, admin, comments, AJAX, or API behavior was intentionally changed.

## H) Verification Results

Commands:

- `php -l` on moved wrappers and legacy page scripts: PASS.
- `php -l` on all PHP files: PASS.
- `git diff --check`: PASS.

Smoke URLs:

- `/`: HTTP 200
- `/browse.php`: HTTP 200
- `/details.php?id=1`: HTTP 200
- `/login.php`: HTTP 200
- `/upload.php`: HTTP 200
- `/admin.php`: HTTP 200
- `/comments.last.php`: HTTP 200

Other checks:

- AJAX comments refresh: HTTP 200, JSON `ok=1`, HTML present.
- Rating/bookmark API GET probes: HTTP 405, expected method guard, no fatal output.
- Announce smoke: PASS, 15 scenarios, 0 failed.
- Cache runtime: Memcached active/online in Docker.
- Debug panel: current Docker/web ENV has `LITETRACKER_DEBUG=0` / `DEBUG=0`, so panel is intentionally not rendered. CLEANUP-2 did not touch debug code.

## I) Risks

Low for moved files:

- They are simple pages/redirect/helper with stable root wrappers.
- Direct URL compatibility is preserved.

Medium/high areas intentionally not moved:

- `browse.php`, `details.php`, `admin.php`, `upload.php`, `download.php`, mail/settings/profile/admin pages.
- These contain page-local functions, POST handlers, redirects, and template coupling.

## J) Manual Review Needed

- Confirm whether `notify.php`, `rating.php`, and `copyright.php` are still desired public URLs long-term.
- Decide whether old wave docs should stay archived in git or move to an external changelog/wiki.
- Decide whether Docker DB should move from `docker-data/` to a named volume.
- Browser-test details/profile pages for duplicate JS initialization before simplifying asset includes.

## K) Next Cleanup Candidates

1. Move one medium-risk family at a time:
   - static/help pages first (`donate.php`, `feedback.php`, `faq.php` if desired).
   - admin utility pages second (`sessions.php`, `search_query.php`).
2. Extract big root controllers only after page-local functions are moved into `app/Http/Legacy/*` with tests:
   - `browse.php`
   - `details.php`
   - `profile.php`
   - `admin.php`
3. Normalize frontend loading:
   - choose Vite bundle or direct legacy scripts as canonical.
4. Add route smoke script for direct URL compatibility before future moves.
