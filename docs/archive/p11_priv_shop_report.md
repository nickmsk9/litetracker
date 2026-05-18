# LiteTracker Wave P11 Priv/Shop Cleanup Report

## Scope

Prepared local-only cleanup for:

| Table | Date field | Local rows before | Engine before | Zero dates before | NULL dates before |
|---|---|---:|---|---:|---:|
| `priv` | `DATE` | 7 | MyISAM | 6 | 0 |
| `shop` | `date` | 2 | MyISAM | 2 | 0 |

Hot tables were not altered: `peers`, `trackers`, `snatched`, `torrents`, `users`.

## Schema And Index Audit

`priv` before rehearsal:

- Engine: MyISAM.
- Date column: `DATE datetime NOT NULL`.
- Indexes: primary key on `id` only.
- Nonzero date range: `2011-01-02 16:32:31` to `2011-01-02 16:32:31`.
- Zero-date rows: classes `id` 1 through 6.

`shop` before rehearsal:

- Engine: MyISAM.
- Date column: `date datetime NOT NULL`.
- Indexes: primary key on `id` only.
- Nonzero date range: none.
- Zero-date rows: products `id` 1 and 2.

## Code Path Audit

`priv` readers and writers:

- `system/functions/functions.common.php`: `get_priv_info()` reads full class rows and builds a guest fallback from `SHOW COLUMNS`.
- `system/functions/functions.php`: `get_user_color()`, `get_user_class_name()`, `get_classes_list()` read class metadata.
- `edit_priv.php`: lists classes, shows `DATE` as "Создан", inserts new classes with `DATE = NOW()`, does not update `DATE` on edits.
- `autoclean.php`, `signup.php`, `ajax/profile.php`, `user_add.php`, `my.setting.take.php`: read class rows/ids for signup, promotion, and admin profile changes.

`shop` readers and writers:

- `shop.php`: lists products with `SELECT * FROM shop ORDER BY date DESC`, edits products, inserts products without setting `date`, deletes products, and loads purchase handler modules.
- `modules/shop/5gb.php`, `modules/shop/15gb.php`, `modules/shop/add_slot.php`: update the purchasing user's account after `shop.php` loads a product.

## Zero-Date Meaning

`0000-00-00 00:00:00` means legacy "created/listed date unknown".

It does not mean "no expiry". Neither `priv.DATE` nor `shop.date` is used as an expiry field. `priv.DATE` is presented as class creation metadata. `shop.date` is only used for listing order and is not displayed.

## Normalization Chosen

Normalize zero-date values to `NULL` and allow `NULL` in both columns:

- `priv.DATE datetime NULL DEFAULT NULL`
- `shop.date datetime NULL DEFAULT NULL`

Reasoning:

- A valid sentinel date would create fake creation history.
- `NULL` naturally represents unknown creation/listing date.
- `edit_priv.php` already renders empty/NULL class dates as "не указано".
- `shop.php` can omit `date` on inserts once the column allows `NULL`; this fixes a latent strict-mode insert problem without PHP changes.
- `ORDER BY shop.date DESC` remains acceptable: dated products sort before legacy undated products.

## SQL Artifact

Created `database/p11_priv_shop_cleanup_innodb.sql`.

The script includes:

- preflight table status, column, index, zero/null, min/max, affected-row, and EXPLAIN checks;
- backup/dump notes;
- temporary captured ID tables for the rows that originally contained zero dates;
- strict-mode-safe cleanup via a valid temporary datetime, then `NULL`;
- guarded nullable-column ALTERs;
- guarded `ALTER TABLE ... ENGINE=InnoDB, ROW_FORMAT=DYNAMIC`;
- postflight status, column, zero/null, index, and EXPLAIN checks;
- rollback notes.

## Local Apply Status

P11 was applied locally against the Docker MySQL database on 2026-05-17.

Postflight locally:

| Table | Engine after | Date field nullable | Zero dates after | NULL dates after |
|---|---|---|---:|---:|
| `priv` | InnoDB | yes | 0 | 6 |
| `shop` | InnoDB | yes | 0 | 2 |

No rows were deleted. No hot tables were touched.

## PHP Compatibility Notes

No PHP change is required for P11.

Compatibility notes:

- `priv.DATE = NULL` is safe for the class list because `edit_priv.php` checks `!empty($arr['DATE'])` before formatting.
- `shop.date = NULL` is safe because it is not displayed and is only used for descending sort.
- New `shop` inserts become more compatible with MySQL strict mode because `date` may now be omitted.
- New `priv` inserts continue to set `DATE = NOW()`.

## Production Risk

Production risk is low for data semantics and medium for operational locking.

Main production risks:

- table rebuild locks during nullable-column ALTER and InnoDB conversion;
- production may contain more `priv` or `shop` rows than local;
- if production has meaningful nonzero dates, they are preserved;
- if production has application customizations that treat zero dates specially, preflight/code review must catch them first.

Production should run preflight first and review the affected zero-date rows before applying cleanup.

## Local Verification

Passed:

- `git diff --check`
- local SQL rehearsal, then idempotent rerun
- postflight `SHOW TABLE STATUS` / `SHOW INDEX`
- smoke `/` HTTP 200
- authenticated smoke `shop.php` HTTP 200
- authenticated smoke `admin.php` HTTP 200
- smoke `browse.php` HTTP 200
- smoke `details.php?id=1` HTTP 200
- smoke `profile.php?id=1` HTTP 200

## Next Wave Recommendation

After P11 is accepted locally, run the same preflight on production and apply during a quiet maintenance window with table-specific dumps. Then move to the next non-hot MyISAM candidates that still have zero-date defaults but no real zero-date rows, keeping hot tracker tables deferred.
