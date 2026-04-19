ALTER TABLE users
  ADD COLUMN bonus FLOAT NOT NULL DEFAULT 0 AFTER money;

UPDATE users
SET bonus = voice
WHERE bonus = 0 AND voice > 0;
