-- 2026-11-17 t_press: drop 23 columns that nobody writes any more.
-- Work record (Japanese): documents/2026-11-15_t_press_columns.md (sections 1, 5, 7)
-- Checked on production 2026-11-17 with 2026-11-16_t_press_drop_candidates_check.sql:
--   last value 2025-12-08 or earlier, 0 rows since 2026-10-01. ex0.20 code does not use them
--   (billet_lot_number / press_stop_cause_id were only shown in the press daily report list; that list no longer reads them).
-- qc_name_id is NOT dropped (ex0.11 V7 still writes it).
--
-- Order on production:
--   1. git pull first (the press daily report list must stop reading the two columns before they are dropped).
--   2. Run STEP 0 (check) and STEP 1 (backup).
--   3. Run STEP 2 (drop). Then STEP 3 (check).
-- Local: run 2026-11-17.
-- Production: the ram drop (2026-11-17_t_press_billet_ram_drop_old.sql) was run FIRST on 2026-11-25 (93 -> 63),
--   so on production the column counts are 63 -> 40 (not 93 -> 70).

USE extrusion;

-- STEP 0. Check: the foreign key on press_stop_cause_id (local name: t_press_m_press_stop_code).
--         If production shows a different name, tell Claude before STEP 2.
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press' AND COLUMN_NAME = 'press_stop_cause_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT COUNT(*) AS t_press_columns_before   -- expected 93 (production after the ram drop: 63)
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press';

-- STEP 1. Backup of the 23 columns (id + values), just in case. Can be dropped after a few months.
CREATE TABLE bak_t_press_dropped_cols_20261117 AS
SELECT id, press_date_at,
       production_directive_id,
       no1_work_length, no2_work_length, no3_work_length, no4_work_length, no5_work_length,
       cutting_quantities, counted_ok_works, press_work_length,
       die_heating_start, die_heating_finished,
       diering_temp, bolter_temp, billet_temp,
       press_date_time_at, billet_lot_number, press_stop_cause_id,
       aging_check_date, scrap_weight,
       cutting_staff_id, cutting_date, cutting_start, cutting_finish
FROM t_press;

SELECT COUNT(*) AS backup_rows FROM bak_t_press_dropped_cols_20261117;   -- same as the number of rows in t_press

-- STEP 2. Drop. The foreign key first, then the columns (the index on press_stop_cause_id goes with the column).
ALTER TABLE t_press DROP FOREIGN KEY IF EXISTS t_press_m_press_stop_code;

ALTER TABLE t_press
  DROP COLUMN production_directive_id,
  DROP COLUMN no1_work_length,
  DROP COLUMN no2_work_length,
  DROP COLUMN no3_work_length,
  DROP COLUMN no4_work_length,
  DROP COLUMN no5_work_length,
  DROP COLUMN cutting_quantities,
  DROP COLUMN counted_ok_works,
  DROP COLUMN press_work_length,
  DROP COLUMN die_heating_start,
  DROP COLUMN die_heating_finished,
  DROP COLUMN diering_temp,
  DROP COLUMN bolter_temp,
  DROP COLUMN billet_temp,
  DROP COLUMN press_date_time_at,
  DROP COLUMN billet_lot_number,
  DROP COLUMN press_stop_cause_id,
  DROP COLUMN aging_check_date,
  DROP COLUMN scrap_weight,
  DROP COLUMN cutting_staff_id,
  DROP COLUMN cutting_date,
  DROP COLUMN cutting_start,
  DROP COLUMN cutting_finish;

-- STEP 3. Check
SELECT COUNT(*) AS t_press_columns_after   -- expected 70 (production after the ram drop: 40)
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press';
