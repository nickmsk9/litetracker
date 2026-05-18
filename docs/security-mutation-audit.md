# Legacy Mutation Security Audit

Wave: Legacy Mutation Security Cleanup
Date: 2026-05-18

Goal: remaining state-changing legacy actions must use POST with CSRF. GET should only render pages, forms, and lists.

## Fixed in this pass

| File | Action | Before | After | CSRF | Notes |
|---|---|---|---|---|---|
| `search_query.php` | `send` | GET `?send=1&id=...` | POST `action=send` | yes | Existing notification/message behavior preserved. |
| `search_query.php` | `clean` | GET `?clean=1` | POST `action=clean` | yes | Still restricted to `EDIT_PRIV`. |
| `faq.php` | topic delete | GET `act=del` | POST `act=del` | yes | GET edit/view links remain read-only. |
| `faq.php` | topic add/edit save | POST without explicit CSRF | POST | yes | Save flow unchanged except token validation. |
| `comments.take.php` | `add`, `report`, `delete` | `REQUEST` allowed GET | POST only | yes | Existing CSRF scopes retained. Edit GET still opens the edit form; POST saves. |
| `templates/default/tpl.comments.php` | comment delete link | GET link | POST form | yes | Legacy non-AJAX template no longer emits a delete GET link. |
| `news.php` | news delete | GET `act=delete` | POST `act=delete` | yes | Existing manage-news permission retained. |
| `news.php` | news add/edit save | POST without explicit CSRF | POST | yes | Publication logic and cache invalidation unchanged. |
| `templates/default/tpl.news.php` | news delete link | GET link | POST form | yes | Edit link remains GET because it only opens a form. |
| `shop.php` | buy service | GET `act=voicing` | POST `act=voicing` | yes | Existing module handler and balance deduction preserved. |
| `shop.php` | delete service | GET `act=delete` | POST `act=delete` | yes | Existing `EDIT_PRIV` permission retained. |

## Already Safe

| File | Action | Current state | Notes |
|---|---|---|---|
| `ajax/comments.php` | add/edit/delete/restore/report/react/pin/unpin | POST + CSRF + JSON | Mutating actions are method-guarded. |
| `api/ratings.php` | rating vote | POST + CSRF + JSON | Uses API POST/CSRF helpers. |
| `api/bookmarks.php` | bookmark add/delete | POST + CSRF + JSON | Legacy GET flow rejects mutation. |
| `sessions.php` | clean old sessions | POST + CSRF | List remains GET. |
| `check_release.php` | bulk status actions | POST + CSRF | Permission checks retained. |
| `categories.php` | category delete | GET confirmation, POST mutation | GET only opens confirmation screen. |
| `edit_priv.php` | privilege delete | GET confirmation, POST mutation | GET only opens confirmation screen. |
| `rating.php` | list view | read-only GET | Rating mutation is handled through `api/ratings.php`. |

## Deferred

| File | Action | Risk | Reason |
|---|---|---|---|
| `edit.php` | `delete_image`, `delete_screen` | medium | Currently GET with CSRF. Converting media controls to POST is safe but touches upload/edit UI and should be a small dedicated follow-up. |
| `my.friends.php` | friend delete/check action | medium | Legacy account page flow needs a focused pass to preserve friendship status UX. |
| `my.setting.take.php` | `foto_delete` | medium | Currently GET with CSRF. Should be converted with the settings avatar UI in a focused pass. |
| `system/functions/functions.comments.php` | AJAX fallback hrefs for delete/report | low | Server now blocks GET mutations; JS path remains POST. Fallback hrefs can be changed to inert links in a UI-safe follow-up. |

## Notes

- No database schema changes were made.
- No announce/accounting logic was touched.
- Existing business logic and permission checks were preserved; only the transport method and CSRF validation were tightened.
