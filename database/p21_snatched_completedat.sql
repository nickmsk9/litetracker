-- LiteTracker P21 snatched.completedat strict-mode audit
-- Report-only SQL. Do not run as a migration.

SELECT 'P21 snatched columns' AS section;
SHOW FULL COLUMNS FROM snatched;

SELECT 'P21 snatched table definition' AS section;
SHOW CREATE TABLE snatched;

SELECT 'P21 startedat/completedat value checks' AS section;
SELECT COUNT(*) AS rows_total,
       SUM(startedat IS NULL) AS startedat_null,
       SUM(startedat = 0) AS startedat_zero,
       MIN(startedat) AS startedat_min,
       MAX(startedat) AS startedat_max,
       SUM(completedat IS NULL) AS completedat_null,
       SUM(completedat = 0) AS completedat_zero,
       MIN(completedat) AS completedat_min,
       MAX(completedat) AS completedat_max
FROM snatched;

SELECT 'P21 finished/completed consistency checks' AS section;
SELECT COUNT(*) AS finished_without_completedat
FROM snatched
WHERE finished = 1 AND completedat = 0;

SELECT 'P21 profile usage note' AS section;
SELECT 'profile.php reads completedat/startedat through FROM_UNIXTIME(), so these fields are epoch integers.' AS note;

-- Decision note:
-- snatched.completedat is INT NOT NULL and profile.php uses FROM_UNIXTIME().
-- The completed announce path should write an epoch integer, not a SQL DATETIME string.
-- No ALTER/UPDATE/DELETE/INSERT is part of P21.
