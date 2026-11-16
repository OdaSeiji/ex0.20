-- 2026-11-16 t_press: check the 23 drop-candidate columns on production (READ ONLY, changes nothing).
-- Work record (Japanese): documents/2026-11-15_t_press_columns.md
-- For each column: how many rows have a value, the last press date with a value,
-- and how many rows since 2026-10-01. "Value" means not NULL, not 0, not empty text.
-- Please send back the whole result (24 rows).

USE extrusion;

SELECT 'production_directive_id' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE production_directive_id IS NOT NULL AND production_directive_id <> 0
UNION ALL
SELECT 'no1_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE no1_work_length IS NOT NULL AND no1_work_length <> 0
UNION ALL
SELECT 'no2_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE no2_work_length IS NOT NULL AND no2_work_length <> 0
UNION ALL
SELECT 'no3_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE no3_work_length IS NOT NULL AND no3_work_length <> 0
UNION ALL
SELECT 'no4_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE no4_work_length IS NOT NULL AND no4_work_length <> 0
UNION ALL
SELECT 'no5_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE no5_work_length IS NOT NULL AND no5_work_length <> 0
UNION ALL
SELECT 'cutting_quantities' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE cutting_quantities IS NOT NULL AND cutting_quantities <> 0
UNION ALL
SELECT 'counted_ok_works' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE counted_ok_works IS NOT NULL AND counted_ok_works <> 0
UNION ALL
SELECT 'press_work_length' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE press_work_length IS NOT NULL AND press_work_length <> 0
UNION ALL
SELECT 'die_heating_start' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE die_heating_start IS NOT NULL AND die_heating_start <> '00:00:00'
UNION ALL
SELECT 'die_heating_finished' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE die_heating_finished IS NOT NULL AND die_heating_finished <> '00:00:00'
UNION ALL
SELECT 'diering_temp' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE diering_temp IS NOT NULL AND diering_temp <> 0
UNION ALL
SELECT 'bolter_temp' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE bolter_temp IS NOT NULL AND bolter_temp <> 0
UNION ALL
SELECT 'billet_temp' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE billet_temp IS NOT NULL AND billet_temp <> 0
UNION ALL
SELECT 'press_date_time_at' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE press_date_time_at IS NOT NULL AND press_date_time_at > '1900-01-01'
UNION ALL
SELECT 'billet_lot_number' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE TRIM(billet_lot_number) <> ''
UNION ALL
SELECT 'press_stop_cause_id' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE press_stop_cause_id IS NOT NULL AND press_stop_cause_id <> 0
UNION ALL
SELECT 'aging_check_date' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE aging_check_date IS NOT NULL AND aging_check_date > '1900-01-01'
UNION ALL
SELECT 'scrap_weight' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE scrap_weight IS NOT NULL AND scrap_weight <> 0
UNION ALL
SELECT 'cutting_staff_id' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE cutting_staff_id IS NOT NULL AND cutting_staff_id <> 0
UNION ALL
SELECT 'cutting_date' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE cutting_date IS NOT NULL AND cutting_date > '1900-01-01'
UNION ALL
SELECT 'cutting_start' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE cutting_start IS NOT NULL AND cutting_start <> '00:00:00'
UNION ALL
SELECT 'cutting_finish' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE cutting_finish IS NOT NULL AND cutting_finish <> '00:00:00'
UNION ALL
SELECT 'qc_name_id' AS col, COUNT(*) AS rows_with_value, MAX(press_date_at) AS last_press_date,
       SUM(press_date_at >= '2026-10-01') AS rows_since_oct
FROM t_press WHERE qc_name_id IS NOT NULL AND qc_name_id <> 0;
