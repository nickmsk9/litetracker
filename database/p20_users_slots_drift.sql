-- LiteTracker P20 users.slots drift audit
-- Report-only SQL. Do not run as a migration.

SELECT 'P20 users.slots column check' AS section;
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'slots';

SELECT 'P20 users passkey fixture check' AS section;
SELECT id, passkey <> '' AS has_passkey
FROM users
WHERE passkey IS NOT NULL AND passkey <> ''
ORDER BY id
LIMIT 5;

SELECT 'P20 shop add_slot compatibility note' AS section;
SELECT COUNT(*) AS shop_items_using_add_slot
FROM shop
WHERE file = 'add_slot.php';

-- Decision note:
-- announce.php does not use users.slots for auth, slot enforcement, accounting,
-- ratio, or response generation. The safe fix is to remove slots from the
-- announce auth SELECT and avoid changing users schema.
--
-- Suggested only if the shop add_slot product is intentionally supported later:
-- ALTER TABLE users ADD COLUMN slots int NOT NULL DEFAULT 0;
