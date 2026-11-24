-- 2026-11-24 Check after the t_press column drops on production (READ ONLY — nothing is changed).
-- Background: the drop_old SQL run on production may not have had STEP 0b
-- (copy presses entered between the create SQL (11/18) and git pull into t_press_billet_ram).
-- If such presses exist, their values are still in the backup table bak_t_press_ram_cols_20261117.

USE extrusion;

-- 1. Number of t_press columns (expected 40)
SELECT COUNT(*) AS t_press_columns
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press';

-- 2. Backup tables exist?
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME LIKE 'bak_t_press_%';

-- 3. Presses that have ram values in the backup but NO row in t_press_billet_ram
--    (expected 0. If not 0, tell Claude: a copy SQL from the backup will be made.)
SELECT COUNT(*) AS presses_missing, MIN(k.press_date_at) AS first_date, MAX(k.press_date_at) AS last_date
FROM bak_t_press_ram_cols_20261117 k
WHERE NOT EXISTS (SELECT 1 FROM t_press_billet_ram b WHERE b.press_id = k.id)
  AND (COALESCE(no1_1000_ram_speed, 0) <> 0 OR COALESCE(no1_0200_ram_speed, 0) <> 0
    OR COALESCE(no1_1000_ram_pressure, 0) <> 0 OR COALESCE(no1_0200_ram_pressure, 0) <> 0
    OR COALESCE(no1_1000_work_temperature, 0) <> 0 OR COALESCE(no1_0200_work_temperature, 0) <> 0);

-- 4. Rows in t_press_billet_ram per day since 11/17 (new presses after the pull should appear here)
SELECT p.press_date_at, COUNT(DISTINCT b.press_id) AS presses, COUNT(*) AS billet_rows
FROM t_press_billet_ram b
JOIN t_press p ON p.id = b.press_id
WHERE p.press_date_at >= '2026-11-17'
GROUP BY p.press_date_at
ORDER BY p.press_date_at;
