# Frontend Assets Audit

Дата: 2026-05-18

## Summary

Фронтенд сейчас смешанный:

- `src/app.js` — Vite entrypoint/fallback module, импортирует legacy scripts from `public/js`.
- `public/js/*` — runtime scripts, многие подключаются напрямую из templates/pages.
- `public/css/*` — split CSS layers, подключаются из `templates/default/head.php`.
- `templates/default/css/my.css` — legacy/base theme CSS, всё ещё активно.
- `public/libs/photoswipe/*` — используется на details page через conditional include.

Удалять CSS/JS массово нельзя: часть assets подключается напрямую, часть через `src/app.js`, часть зависит от runtime page flags.

## Active JS

| File | References | Status |
| --- | --- | --- |
| `src/app.js` | `lt_asset_url('src/app.js')` in `head()` | active Vite/fallback entry |
| `public/js/main.js` | imported by `src/app.js` | active shared legacy JS |
| `public/js/comments.js` | imported by `src/app.js` | active comments AJAX |
| `public/js/details.js` | imported by `src/app.js`, direct include in `tpl.details.php` | active, duplicate loading should be reviewed later |
| `public/js/profile.js` | imported by `src/app.js`, direct include in `tpl.profile.php` | active, duplicate loading should be reviewed later |
| `public/js/browse.js` | imported by `src/app.js` | active browse/home behavior |
| `public/js/notifications.js` | direct include in `head()` | active notifications |
| `public/js/lt.ajax.js` | direct include in `foot.php` | active feedback/AJAX helper |
| `public/js/metadata-search.js` | direct include in upload/edit pages | active |
| `public/js/tags-suggest.js` | direct include in upload/edit pages | active |
| `public/js/tagto.js` | direct include in edit page | active legacy tag helper |
| `public/js/torrent-description-form.js` | direct include in upload/edit pages | active |
| `public/js/wz_tooltip.js` | direct include in `my.releases.php`, tooltip templates | active legacy |
| `public/js/jquery.js` | direct include in `head()` | active global dependency |

Removed in CLEANUP-2:

- `public/js/main.js:set_rating()` — dead helper, no callers, referenced missing `ajax/rating.php`.

## Active CSS

| File | References | Status |
| --- | --- | --- |
| `public/css/base.css` | `head.php` | active |
| `public/css/layout.css` | `head.php` | active |
| `public/css/components.css` | `head.php` | active |
| `public/css/utilities.css` | `head.php` | active |
| `public/css/legacy.css` | `head.php` | active placeholder/compat layer |
| `public/css/pages/*.css` | conditional `head.php` includes | active |
| `templates/default/css/my.css` | `head.php`, auth modal fallback | active legacy theme |
| `public/css/torrenttable.css` | direct includes in legacy table pages | active |
| `public/css/mail.css` | `my.mail.php` | active |
| `public/libs/photoswipe/photoswipe.css` | details page conditional | active |

## Findings

- `details.js` and `profile.js` may be loaded twice on their pages: once via `src/app.js`, once direct from page templates. Do not remove yet; behavior must be checked with browser events first.
- `legacy.css` is effectively a placeholder but still an intentional layer. Removing it is tiny win and unnecessary risk.
- Some image assets have no static references because category/shop/image names are DB-driven. Keep them.
- Vite build output `public/dist` is not committed; runtime falls back to `src/app.js`. This is intentional today but should be formalized later.

## Next Cleanup Candidates

1. Decide whether Vite bundle or direct legacy scripts are canonical.
2. Remove direct `details.js` / `profile.js` includes only after confirming no double-init dependency.
3. Fold empty/placeholder CSS layers after visual regression checks.
4. Add an asset manifest check to CI if `public/dist` becomes required.
