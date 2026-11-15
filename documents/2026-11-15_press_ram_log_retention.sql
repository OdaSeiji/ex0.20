-- 2026-11-15 t_press_ram_log (extrusion waveform, 0.1 s samples): keep only the last 1 month.
-- Procedure (Japanese): documents/2026-11-15_press_ram_log_retention.md
-- Run on production as root (or a user with ALTER, DELETE and EVENT on extrusion), step by step, in database extrusion.
-- Growth: about 200 MB / 2.5 million rows per month for machines 1 and 3.

USE extrusion;

-- ------------------------------------------------------------------
-- STEP 1. Check (read only)
-- ------------------------------------------------------------------
SHOW VARIABLES LIKE 'event_scheduler';                       -- ON / OFF
SELECT MIN(date_time) AS oldest, MAX(date_time) AS newest, COUNT(*) AS total_rows
FROM t_press_ram_log;
SELECT COUNT(*) AS rows_older_than_1_month
FROM t_press_ram_log
WHERE date_time < NOW() - INTERVAL 1 MONTH;
SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS size_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press_ram_log';

-- ------------------------------------------------------------------
-- STEP 2. Index on date_time (so the daily delete does not scan the whole table)
--         Local test: 2.6 million rows took 2.4 s. Inserts from the PLC collector can continue meanwhile.
-- ------------------------------------------------------------------
ALTER TABLE t_press_ram_log ADD INDEX idx_date_time (date_time);

-- ------------------------------------------------------------------
-- STEP 3. Turn the event scheduler on (only if STEP 1 showed OFF)
--         This works at once, but goes back to OFF when MariaDB restarts.
--         To keep it ON, also add   event_scheduler=ON   under [mysqld] in C:\xampp\mysql\bin\my.ini
-- ------------------------------------------------------------------
SET GLOBAL event_scheduler = ON;
SHOW VARIABLES LIKE 'event_scheduler';                       -- must be ON

-- ------------------------------------------------------------------
-- STEP 4. First clean-up: delete the rows older than 1 month, 50,000 rows at a time.
--         Run this line again and again until it says "0 rows affected".
-- ------------------------------------------------------------------
DELETE FROM t_press_ram_log WHERE date_time < NOW() - INTERVAL 1 MONTH LIMIT 50000;

-- ------------------------------------------------------------------
-- STEP 5. Daily event: every day at 03:15, delete rows older than 1 month (about 84,000 rows / day).
-- ------------------------------------------------------------------
CREATE EVENT IF NOT EXISTS ev_press_ram_log_purge
  ON SCHEDULE EVERY 1 DAY
  STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 195 MINUTE)
  COMMENT 'Keep t_press_ram_log for 1 month: delete rows older than 1 month'
  DO
    DELETE FROM t_press_ram_log WHERE date_time < NOW() - INTERVAL 1 MONTH;

-- ------------------------------------------------------------------
-- STEP 6. Check
-- ------------------------------------------------------------------
SELECT EVENT_NAME, STATUS, INTERVAL_VALUE, INTERVAL_FIELD, STARTS, LAST_EXECUTED
FROM information_schema.EVENTS
WHERE EVENT_SCHEMA = 'extrusion' AND EVENT_NAME = 'ev_press_ram_log_purge';
SELECT MIN(date_time) AS oldest, COUNT(*) AS total_rows FROM t_press_ram_log;
-- The next day: LAST_EXECUTED should be filled and "oldest" should be about 1 month ago.

-- ------------------------------------------------------------------
-- STEP 7. (Optional, at a quiet time) Shrink the file on disk.
--         Deleting rows does not make the file smaller; the free space is reused by new rows.
--         Run this once only if you want the disk space back now. It rebuilds the table (may take minutes).
-- ------------------------------------------------------------------
-- OPTIMIZE TABLE t_press_ram_log;

-- ------------------------------------------------------------------
-- To stop the daily delete:   DROP EVENT IF EXISTS ev_press_ram_log_purge;
-- (Rows already deleted cannot come back.)
-- ------------------------------------------------------------------
