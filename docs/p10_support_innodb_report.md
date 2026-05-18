# LiteTracker Wave P10 Support InnoDB Report

## Approved Tables

Approved for local rehearsal:

| Table | Rows local | Engine before | Collation | Notes |
|---|---:|---|---|---|
| `news` | 3 | MyISAM | `utf8mb3_bin` | News archive/detail support table. |
| `torrent_views` | 11 | MyISAM | `utf8mb3_general_ci` | Details view counter support table. |
| `search_query` | 7 | MyISAM | `cp1251_general_ci` | Search monitoring/suggestions. |
| `categories` | 6 | MyISAM | `utf8mb3_bin` | Category/admin metadata. |
| `tags` | 3 | MyISAM | `utf8mb3_unicode_ci` | Upload/edit tag suggestions. |
| `bans` | 0 | MyISAM | `cp1251_bin` | IP ban support; empty locally. |
| `chat` | 0 | MyISAM | `cp1251_general_ci` | Empty locally. |
| `faq` | 0 | MyISAM | `utf8mb3_unicode_ci` | FAQ support table. |
| `polls_voting` | 0 | MyISAM | `utf8mb3_unicode_ci` | Poll voting support table. |

All approved tables are tiny locally and have no zero/null date rows.

## Deferred Tables

Deferred from local apply:

- `priv`: 6 real zero values in `DATE`.
- `shop`: 2 real zero values in `date`.

These need explicit data cleanup before InnoDB conversion. P10 does not normalize real stored zero dates.

## Schema Findings

- `bans.date` had a legacy zero DATETIME default but no rows. The P10 SQL drops the default before InnoDB conversion.
- No duplicate/redundant indexes were found among candidate tables.
- Existing `search_query` and `torrent_views` indexes are retained.
- No charset/collation conversion is included.

## Additive Indexes

P10 adds only clearly useful indexes:

- `tags(category, name)` for upload/edit tag lookup ordered by name;
- `faq(added)` for FAQ listing ordered by newest first.

No destructive drops are included.

## Local Apply Status

P10 was applied locally against the Docker MySQL database on 2026-05-17.

Converted locally to InnoDB:

- `news`
- `torrent_views`
- `search_query`
- `categories`
- `tags`
- `bans`
- `chat`
- `faq`
- `polls_voting`

Deferred and still MyISAM locally:

- `priv`
- `shop`

No rows were deleted. No hot tables were touched.

Postflight changes:

- `bans.date` no longer has the legacy `0000-00-00 00:00:00` default.
- `tags.idx_tags_category_name(category, name)` exists.
- `faq.idx_faq_added(added)` exists.

Production must run preflight first and must not treat the local rehearsal as production approval.

## Production Risk

Risk is low for the approved tables if production sizes are similarly small. Risk becomes medium if `search_query`, `torrent_views`, `news`, or `tags` are unexpectedly large.

Main production concern is not data semantics but table rebuild locking during `ALTER TABLE ... ENGINE=InnoDB`.

## Deferred Cleanup

Deferred cleanup:

- normalize `priv.DATE` zero values;
- normalize `shop.date` zero values;
- review whether `shop.voice` should remain `float`;
- consider bounded varchar conversions for legacy text metadata in a separate charset/schema wave.

## Production Checklist

1. Full DB backup.
2. Table-specific dump for approved P10 tables.
3. Run P10 preflight SELECTs on production.
4. Confirm approved tables have zero/null date counts of 0.
5. Confirm `priv` and `shop` are intentionally deferred unless cleaned.
6. Confirm row counts/sizes fit maintenance window.
7. Apply during quiet window.
8. Run postflight `SHOW TABLE STATUS` and `SHOW INDEX`.
9. Smoke `/`, `browse.php`, `details.php?id=1`, `news.php`, `faq.php`, `donate.php`, `search_query.php`, `admin.php`.

## Local Verification

Passed:

- `git diff --check`
- postflight `SHOW TABLE STATUS` / `SHOW INDEX`
- smoke `/` HTTP 200
- smoke `browse.php` HTTP 200
- smoke `details.php?id=1` HTTP 200
- smoke `news.php` HTTP 200
- smoke `faq.php` HTTP 200
- smoke `donate.php` HTTP 200
- smoke `search_query.php` HTTP 200
- smoke `admin.php` HTTP 200

## Next Wave Recommendation

After production P10, run a dedicated cleanup wave for `priv` and `shop` zero dates. Only then prepare rehearsals for medium/high-risk tracker tables.
