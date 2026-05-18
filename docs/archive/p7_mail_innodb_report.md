# LiteTracker Wave P7 Mail InnoDB Report

## Preflight Result

Local preflight before applying P7:

- table: `mail`
- engine before: `MyISAM`
- collation: `utf8mb3_bin`
- row format before: `Dynamic`
- local row count: 31
- local size: about 0.0052 MB
- zero dates: 0
- null dates: 0
- max subject chars: 32
- max body chars: 189

The local dataset is small enough for rehearsal. No unexpected schema blocker was found.

## Index Audit

Current indexes are sufficient for P2/P3 mail paths, but redundant indexes remain:

- `idx_mail_conversation_in` duplicates `idx_mail_in_out_visible_date_id`;
- `idx_mail_conversation_out` duplicates `idx_mail_out_in_visible_date_id`;
- `idx_mail_in_visible_read_date` is covered by `idx_mail_in_visible_read_date_id`;
- `idx_mail_out_visible_date` is covered by `idx_mail_out_visible_date_id`.

P7 intentionally does not drop any index. Redundant index cleanup should be a separate wave after production `EXPLAIN`.

## SQL File

Created `database/p7_mail_innodb_apply.sql`.

The script contains:

- backup/dump notes;
- preflight table status;
- preflight data checks;
- index inventory;
- duplicate/equivalent index report;
- EXPLAIN for inbox summary query;
- EXPLAIN for dialog query;
- guarded `ALTER TABLE mail ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- postflight table status, indexes, and EXPLAIN;
- rollback note.

## Local Apply Status

Applied locally on the Docker MySQL 8.4 database after clean preflight.

Result:

- engine after: `InnoDB`;
- collation unchanged: `utf8mb3_bin`;
- row format after: `Dynamic`;
- indexes preserved;
- no destructive index cleanup was performed.

Verification:

- `git diff --check` passed;
- `php -l my.mail.php` passed;
- `SHOW TABLE STATUS` confirmed `mail` is `InnoDB`;
- `SHOW INDEX` confirmed mail indexes are still present;
- smoke routes returned `200` without PHP fatal/warning output:
  - `my.mail.php`;
  - `my.mail.php?act=conversation&id_user=2`;
  - `/`;
  - `browse.php`;
  - `details.php?id=1`.

## Before/After EXPLAIN Notes

Before migration:

- inbox summary used indexes on incoming/outgoing visibility and still required temporary/filesort for derived grouping;
- dialog query used the conversation index and still required filesort for the OR + display ordering pattern.

After migration:

- inbox summary plan remained effectively the same;
- dialog plan remained effectively the same;
- small row-estimate differences appeared because the storage engine changed;
- temporary/filesort remained where expected.

This is expected. InnoDB conversion changes lock/crash behavior, not the logical query shape.

## Production Risk

Risk: medium.

Why:

- MyISAM to InnoDB rebuilds the table;
- mail writes and unread counter updates may block during the ALTER;
- redundant indexes increase rebuild work;
- rolling back after new writes is safer from backup than by converting back to MyISAM.

## Exact Production Checklist

1. Run `database/p7_mail_innodb_apply.sql` on staging first.
2. Take full DB backup.
3. Take table-specific dump:
   `mysqldump --single-transaction --routines --triggers DB_NAME mail > mail.before_innodb.sql`
4. Capture production `SHOW TABLE STATUS LIKE 'mail'`.
5. Capture production `SHOW INDEX FROM mail`.
6. Confirm zero/null date checks are 0.
7. Confirm production row count and size fit the maintenance window.
8. Capture EXPLAIN output for inbox/dialog paths.
9. Pause non-essential jobs touching mail/notifications if any.
10. Apply during a quiet window.
11. Run postflight `SHOW TABLE STATUS`, `SHOW INDEX`, and EXPLAIN.
12. Smoke test inbox, dialog, send message, mark read, delete/restore.
13. Watch PHP logs, MySQL error log, slow query log, and lock waits.

## Next Wave Recommendation

After P7 succeeds in production, run a report-only redundant mail index audit using production `EXPLAIN`. Then remove only confirmed duplicate indexes in a separate guarded migration.
