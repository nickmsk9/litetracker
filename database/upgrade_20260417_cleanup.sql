UPDATE users
SET confirm = 1
WHERE confirm = 0;

UPDATE users
SET email = ''
WHERE email IS NULL;

ALTER TABLE users
  MODIFY email varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT '';

DELETE FROM orbital_blocks
WHERE blockfile = 'block-poll.php';

DROP TABLE IF EXISTS confirm;
DROP TABLE IF EXISTS polls_voting;
DROP TABLE IF EXISTS polls_questions;
DROP TABLE IF EXISTS polls;

ALTER TABLE priv
  DROP COLUMN IF EXISTS polls_moderate;
