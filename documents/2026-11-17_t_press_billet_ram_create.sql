-- 2026-11-17 t_press_billet_ram: ram speed / ram pressure / work temperature per billet (one row = one billet).
-- Replaces the 30 columns t_press.no1_..no5_ (1000 / 0200) x (ram_speed, ram_pressure, work_temperature).
-- Work record (Japanese): documents/2026-11-15_t_press_columns.md (sections 6, 9)
--   *_1000 = the "1000/400mm" field of the press daily report (from the waveform: 1000 mm if the measured
--            position is close to 1000 mm, otherwise the 400 mm value; which one is not recorded).
--   *_0200 = the 200 mm field.
--   billet_number = No.1..5 of the press daily report.
--
-- Order on production:
--   1. Run THIS file (create the table and copy the data). The old columns stay, nothing breaks.
--   2. git pull (the press daily report then reads / writes the new table).
--   3. Run 2026-11-17_t_press_billet_ram_drop_old.sql (backup and drop the 30 old columns).
-- Local: run 2026-11-17.

USE extrusion;

-- STEP 1. Create the table
CREATE TABLE t_press_billet_ram (
  id INT NOT NULL AUTO_INCREMENT,
  press_id INT NOT NULL COMMENT 't_press.id',
  billet_number TINYINT NOT NULL COMMENT 'Billet No. (1-5) in the press daily report',
  ram_speed_1000 FLOAT NULL COMMENT 'Ram speed at 1000 mm (or 400 mm)',
  ram_pressure_1000 FLOAT NULL COMMENT 'Ram pressure at 1000 mm (or 400 mm)',
  work_temperature_1000 INT NULL COMMENT 'Work temperature at 1000 mm (or 400 mm)',
  ram_speed_0200 FLOAT NULL COMMENT 'Ram speed at 200 mm',
  ram_pressure_0200 FLOAT NULL COMMENT 'Ram pressure at 200 mm',
  work_temperature_0200 INT NULL COMMENT 'Work temperature at 200 mm',
  PRIMARY KEY (id),
  UNIQUE KEY uk_press_billet (press_id, billet_number),
  CONSTRAINT fk_billet_ram_press FOREIGN KEY (press_id) REFERENCES t_press (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Ram speed / pressure / work temperature per billet (was t_press.no1..no5_*)';

-- STEP 2. Copy No.1..5 from t_press. A billet whose 6 values are all empty (NULL or 0) gets no row.
--         Values are copied as they are (also odd ones such as temperature 8108).
INSERT INTO t_press_billet_ram (press_id, billet_number, ram_speed_1000, ram_pressure_1000, work_temperature_1000,
                                ram_speed_0200, ram_pressure_0200, work_temperature_0200)
SELECT id, 1, no1_1000_ram_speed, no1_1000_ram_pressure, no1_1000_work_temperature,
       no1_0200_ram_speed, no1_0200_ram_pressure, no1_0200_work_temperature
FROM t_press
WHERE COALESCE(no1_1000_ram_speed, 0) <> 0 OR COALESCE(no1_1000_ram_pressure, 0) <> 0 OR COALESCE(no1_1000_work_temperature, 0) <> 0
   OR COALESCE(no1_0200_ram_speed, 0) <> 0 OR COALESCE(no1_0200_ram_pressure, 0) <> 0 OR COALESCE(no1_0200_work_temperature, 0) <> 0
UNION ALL
SELECT id, 2, no2_1000_ram_speed, no2_1000_ram_pressure, no2_1000_work_temperature,
       no2_0200_ram_speed, no2_0200_ram_pressure, no2_0200_work_temperature
FROM t_press
WHERE COALESCE(no2_1000_ram_speed, 0) <> 0 OR COALESCE(no2_1000_ram_pressure, 0) <> 0 OR COALESCE(no2_1000_work_temperature, 0) <> 0
   OR COALESCE(no2_0200_ram_speed, 0) <> 0 OR COALESCE(no2_0200_ram_pressure, 0) <> 0 OR COALESCE(no2_0200_work_temperature, 0) <> 0
UNION ALL
SELECT id, 3, no3_1000_ram_speed, no3_1000_ram_pressure, no3_1000_work_temperature,
       no3_0200_ram_speed, no3_0200_ram_pressure, no3_0200_work_temperature
FROM t_press
WHERE COALESCE(no3_1000_ram_speed, 0) <> 0 OR COALESCE(no3_1000_ram_pressure, 0) <> 0 OR COALESCE(no3_1000_work_temperature, 0) <> 0
   OR COALESCE(no3_0200_ram_speed, 0) <> 0 OR COALESCE(no3_0200_ram_pressure, 0) <> 0 OR COALESCE(no3_0200_work_temperature, 0) <> 0
UNION ALL
SELECT id, 4, no4_1000_ram_speed, no4_1000_ram_pressure, no4_1000_work_temperature,
       no4_0200_ram_speed, no4_0200_ram_pressure, no4_0200_work_temperature
FROM t_press
WHERE COALESCE(no4_1000_ram_speed, 0) <> 0 OR COALESCE(no4_1000_ram_pressure, 0) <> 0 OR COALESCE(no4_1000_work_temperature, 0) <> 0
   OR COALESCE(no4_0200_ram_speed, 0) <> 0 OR COALESCE(no4_0200_ram_pressure, 0) <> 0 OR COALESCE(no4_0200_work_temperature, 0) <> 0
UNION ALL
SELECT id, 5, no5_1000_ram_speed, no5_1000_ram_pressure, no5_1000_work_temperature,
       no5_0200_ram_speed, no5_0200_ram_pressure, no5_0200_work_temperature
FROM t_press
WHERE COALESCE(no5_1000_ram_speed, 0) <> 0 OR COALESCE(no5_1000_ram_pressure, 0) <> 0 OR COALESCE(no5_1000_work_temperature, 0) <> 0
   OR COALESCE(no5_0200_ram_speed, 0) <> 0 OR COALESCE(no5_0200_ram_pressure, 0) <> 0 OR COALESCE(no5_0200_work_temperature, 0) <> 0;

-- STEP 3. Check: rows per billet No., and presses with at least one billet
SELECT billet_number, COUNT(*) AS rows_copied FROM t_press_billet_ram GROUP BY billet_number ORDER BY billet_number;
SELECT COUNT(DISTINCT press_id) AS presses_with_ram_data, COUNT(*) AS total_rows FROM t_press_billet_ram;
