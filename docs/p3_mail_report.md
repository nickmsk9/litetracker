# LiteTracker Wave P3 Mail Report

## Audit Summary

- `mail` engine: `MyISAM`.
- Table collation: `utf8mb3_bin`.
- Row format: `Dynamic`.
- Current local rows: 30.
- Zero dates: 0.
- `name` and `text` are `TEXT`; current local maxima are small, but production may contain much larger message bodies.
- `id_user_out = 0` is used for system messages.

## Index Readiness

The P2 indexes cover the current hot paths:

- inbox unread/read filters: `id_user_in, delete_in, reading, date, id`;
- outgoing visibility: `id_user_out, delete_out, date, id`;
- conversation incoming side: `id_user_in, id_user_out, delete_in, date, id`;
- conversation outgoing side: `id_user_out, id_user_in, delete_out, date, id`.

Duplicate/redundant indexes exist from earlier waves:

- `idx_mail_in_visible_read_date` is covered by `idx_mail_in_visible_read_date_id`;
- `idx_mail_out_visible_date` is covered by `idx_mail_out_visible_date_id`;
- `idx_mail_conversation_in` overlaps `idx_mail_in_out_visible_date_id`;
- `idx_mail_conversation_out` overlaps `idx_mail_out_in_visible_date_id`.

P3 does not drop them automatically. Dropping redundant indexes is likely safe after production `EXPLAIN` verification, but it is a separate operational change.

## InnoDB Migration Plan

`database/p3_mail_innodb.sql` contains:

- preflight engine/size check;
- preflight zero-date and text-size check;
- index inventory check;
- guarded conversion procedure for `mail` only;
- rollback note.

The migration should be run only during a maintenance window after backup. MyISAM to InnoDB rebuilds the table and can lock writes while copying.

## Seek Pagination Audit

The conversation modal does not use SQL `OFFSET`. The first view loads the newest 10 messages via reverse `ORDER BY date DESC, id DESC LIMIT 10`, then reorders them ascending for display.

The slow path is `load_older=1` with `all=1`: it calculates `total - limit` and fetches all older messages in one request. On a long conversation this can become an unbounded read and large HTML response.

Recommended seek strategy:

- add cursor params `before_date` and `before_id` to the existing older link;
- query older messages with `(m.date < cursor_date OR (m.date = cursor_date AND m.id < cursor_id))`;
- order by `m.date DESC, m.id DESC LIMIT 20`, then wrap/reorder ascending for display;
- return a next older link only if one more page exists.

This was not applied in P3 because the current frontend removes the older link after one request. A correct seek rollout needs a small route-compatible AJAX contract change so the link can be replaced with the next cursor.

## Risk

InnoDB migration risk is medium operational risk, not code risk. The schema is ready enough for conversion locally, but production table size, backup state, and write window decide whether it is safe to execute.

## Next Best Wave

Run production `EXPLAIN ANALYZE` for mail hot paths, schedule the InnoDB migration, then remove redundant indexes after observing query plans. After that, implement cursor-based older-message pagination.
