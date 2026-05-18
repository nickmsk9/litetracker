# LiteTracker Wave P2 Report

## What changed

- Replaced the conversation-list N+1 in `my.mail.php` with bulk loading:
  - one summary query using `UNION ALL` for visible incoming/outgoing mail;
  - one `IN (...)` query for last messages;
  - one `IN (...)` query for partners.
- Replaced direct legacy `user_$id` mail cache deletes with `lt_cache_invalidate_user()` through a local compatibility wrapper.
- Hardened `download.php` `Content-Disposition`:
  - strips CR/LF/NUL;
  - strips quotes and backslashes;
  - provides ASCII `filename`;
  - preserves Cyrillic via RFC 5987 `filename*`.
- Added `database/p2_mail_optimization.sql` with additive mail indexes for inbox, outbox, conversation, read/delete filters, and date/id ordering.

## Why faster

The mail inbox list no longer performs two queries per conversation. For N conversations it now does a fixed set of queries: summary, last messages, partner users. The new indexes are aligned with the exact filters used by read/unread, delete visibility, and conversation ordering.

## Risk level

Low. The route structure, templates, form actions, CSRF behavior, read/delete semantics, and HTML output shape are preserved. The SQL changes are additive indexes only.

## Remaining mail bottlenecks

- Conversation modal still counts then loads slices; acceptable for now, but deep conversations would benefit from seek pagination.
- Mail is still MyISAM, so write/read lock contention remains under load.
- The summary query still scans all visible mail for a user; with very large inboxes, a materialized conversation table would be the next architectural step.

## Next best wave

Move `mail` to InnoDB after duplicate/index verification, add seek pagination for long conversations, and consider a `mail_conversations` materialized table only if inbox size and traffic justify the extra write complexity.
