-- 2026-11-17 t_press: backup and drop the 30 old ram columns no1_..no5_ (moved to t_press_billet_ram).
-- Run AFTER 2026-11-17_t_press_billet_ram_create.sql AND after git pull
-- (the press daily report must use t_press_billet_ram before these columns are dropped).
-- Work record (Japanese): documents/2026-11-15_t_press_columns.md (section 9)
-- Local: run 2026-11-17.

USE extrusion;

-- STEP 0. Check: number of columns now (expected 70 if 2026-11-17_t_press_drop_unused_columns.sql was run, else 93)
SELECT COUNT(*) AS t_press_columns_before
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press';

-- STEP 0b. (added 2026-11-18) Presses entered BEFORE git pull but AFTER the create SQL were still saved into the old
--          columns only (production: create SQL run 11/18, pull later). Copy those presses now.
--          Only presses that have NO row yet in t_press_billet_ram are copied, so nothing entered after the pull is overwritten.
--          Run this right before STEP 1 (the same day as the pull is best).
SELECT COUNT(*) AS presses_to_copy
FROM t_press p
WHERE NOT EXISTS (SELECT 1 FROM t_press_billet_ram b WHERE b.press_id = p.id)
  AND (COALESCE(no1_1000_ram_speed, 0) <> 0 OR COALESCE(no1_0200_ram_speed, 0) <> 0
    OR COALESCE(no1_1000_ram_pressure, 0) <> 0 OR COALESCE(no1_0200_ram_pressure, 0) <> 0
    OR COALESCE(no1_1000_work_temperature, 0) <> 0 OR COALESCE(no1_0200_work_temperature, 0) <> 0);

INSERT INTO t_press_billet_ram (press_id, billet_number, ram_speed_1000, ram_pressure_1000, work_temperature_1000,
                                ram_speed_0200, ram_pressure_0200, work_temperature_0200)
SELECT x.* FROM (
  SELECT id AS press_id, 1 AS billet_number, no1_1000_ram_speed AS s1, no1_1000_ram_pressure AS p1, no1_1000_work_temperature AS t1,
         no1_0200_ram_speed AS s2, no1_0200_ram_pressure AS p2, no1_0200_work_temperature AS t2 FROM t_press
  UNION ALL SELECT id, 2, no2_1000_ram_speed, no2_1000_ram_pressure, no2_1000_work_temperature,
         no2_0200_ram_speed, no2_0200_ram_pressure, no2_0200_work_temperature FROM t_press
  UNION ALL SELECT id, 3, no3_1000_ram_speed, no3_1000_ram_pressure, no3_1000_work_temperature,
         no3_0200_ram_speed, no3_0200_ram_pressure, no3_0200_work_temperature FROM t_press
  UNION ALL SELECT id, 4, no4_1000_ram_speed, no4_1000_ram_pressure, no4_1000_work_temperature,
         no4_0200_ram_speed, no4_0200_ram_pressure, no4_0200_work_temperature FROM t_press
  UNION ALL SELECT id, 5, no5_1000_ram_speed, no5_1000_ram_pressure, no5_1000_work_temperature,
         no5_0200_ram_speed, no5_0200_ram_pressure, no5_0200_work_temperature FROM t_press
) x
WHERE NOT EXISTS (SELECT 1 FROM t_press_billet_ram b WHERE b.press_id = x.press_id)
  AND (COALESCE(x.s1, 0) <> 0 OR COALESCE(x.p1, 0) <> 0 OR COALESCE(x.t1, 0) <> 0
    OR COALESCE(x.s2, 0) <> 0 OR COALESCE(x.p2, 0) <> 0 OR COALESCE(x.t2, 0) <> 0);

SELECT COUNT(*) AS t_press_billet_ram_rows FROM t_press_billet_ram;

-- STEP 1. Backup of the 30 columns (id + values)
CREATE TABLE bak_t_press_ram_cols_20261117 AS
SELECT id, press_date_at,
       no1_1000_ram_speed, no1_1000_ram_pressure, no1_1000_work_temperature, no1_0200_ram_speed, no1_0200_ram_pressure, no1_0200_work_temperature,
       no2_1000_ram_speed, no2_1000_ram_pressure, no2_1000_work_temperature, no2_0200_ram_speed, no2_0200_ram_pressure, no2_0200_work_temperature,
       no3_1000_ram_speed, no3_1000_ram_pressure, no3_1000_work_temperature, no3_0200_ram_speed, no3_0200_ram_pressure, no3_0200_work_temperature,
       no4_1000_ram_speed, no4_1000_ram_pressure, no4_1000_work_temperature, no4_0200_ram_speed, no4_0200_ram_pressure, no4_0200_work_temperature,
       no5_1000_ram_speed, no5_1000_ram_pressure, no5_1000_work_temperature, no5_0200_ram_speed, no5_0200_ram_pressure, no5_0200_work_temperature
FROM t_press;

SELECT COUNT(*) AS backup_rows FROM bak_t_press_ram_cols_20261117;   -- same as the number of rows in t_press

-- STEP 2. Drop
ALTER TABLE t_press
  DROP COLUMN no1_1000_ram_speed, DROP COLUMN no1_1000_ram_pressure, DROP COLUMN no1_1000_work_temperature,
  DROP COLUMN no1_0200_ram_speed, DROP COLUMN no1_0200_ram_pressure, DROP COLUMN no1_0200_work_temperature,
  DROP COLUMN no2_1000_ram_speed, DROP COLUMN no2_1000_ram_pressure, DROP COLUMN no2_1000_work_temperature,
  DROP COLUMN no2_0200_ram_speed, DROP COLUMN no2_0200_ram_pressure, DROP COLUMN no2_0200_work_temperature,
  DROP COLUMN no3_1000_ram_speed, DROP COLUMN no3_1000_ram_pressure, DROP COLUMN no3_1000_work_temperature,
  DROP COLUMN no3_0200_ram_speed, DROP COLUMN no3_0200_ram_pressure, DROP COLUMN no3_0200_work_temperature,
  DROP COLUMN no4_1000_ram_speed, DROP COLUMN no4_1000_ram_pressure, DROP COLUMN no4_1000_work_temperature,
  DROP COLUMN no4_0200_ram_speed, DROP COLUMN no4_0200_ram_pressure, DROP COLUMN no4_0200_work_temperature,
  DROP COLUMN no5_1000_ram_speed, DROP COLUMN no5_1000_ram_pressure, DROP COLUMN no5_1000_work_temperature,
  DROP COLUMN no5_0200_ram_speed, DROP COLUMN no5_0200_ram_pressure, DROP COLUMN no5_0200_work_temperature;

-- STEP 3. Check (expected 40, or 63 if the 23-column drop was not run yet)
SELECT COUNT(*) AS t_press_columns_after
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press';
