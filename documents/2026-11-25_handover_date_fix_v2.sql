-- 2026-11-25 Run the 11-06 handover-date fix again (documents/2026-11-06_handover_date_fix.sql), this time WITH COMMIT.
-- Why: on production the 11-06 file was piped (Get-Content ... | mysql). Its COMMIT was commented out,
--   so the UPDATEs were rolled back. Only the backup tables (*_bak_20261106) were made.
--   Check on 11-25 (documents/2026-11-25_handover_fix_and_role_check.sql): rows_left_3A 79, rows_left_3B 17.
-- What it does (same rules as 11-06; see that file for the Japanese explanation):
--   A. A die with only accessory rows: the one accessory row with an empty note2 becomes the body row (flag 1 -> 0).
--   B. A body row with no asset registration date, but the progress table has a handover date: copy that date
--      (except 2020-01-01, a suspected dummy date).
-- Can be piped as a whole: it makes new backups, changes, COMMITs, then prints the counts again (expected 0 and 0).
-- Safe to run twice: the second run changes nothing (but stops at the backup step, because the backup tables exist).

USE extrusion;

-- 0. Backups (new names; the 20261106 ones stay as they are)
CREATE TABLE t_die_handover_bak_20261125          AS SELECT * FROM t_die_handover;
CREATE TABLE t_die_handover_progress_bak_20261125 AS SELECT * FROM t_die_handover_progress;

-- 1. Before
SELECT COUNT(*) AS rows_3A_before
FROM t_die_handover h
JOIN (
    SELECT die_id FROM t_die_handover
    GROUP BY die_id
    HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
       AND SUM(is_accessory_item_flag = 1 AND IFNULL(TRIM(note2), '') = '') = 1
) x ON x.die_id = h.die_id
WHERE h.is_accessory_item_flag = 1
  AND IFNULL(TRIM(h.note2), '') = '';

-- 2. Change
START TRANSACTION;

-- 2-A. Accessory flag off -> body row
UPDATE t_die_handover h
JOIN (
    SELECT die_id FROM t_die_handover
    GROUP BY die_id
    HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
       AND SUM(is_accessory_item_flag = 1 AND IFNULL(TRIM(note2), '') = '') = 1
) x ON x.die_id = h.die_id
SET h.is_accessory_item_flag = 0
WHERE h.is_accessory_item_flag = 1
  AND IFNULL(TRIM(h.note2), '') = '';

-- 2-B. Count after A, then copy the handover date into the body row
SELECT COUNT(*) AS rows_3B_before
FROM t_die_handover h
JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
  AND p.die_handover_at <> '2020-01-01';

UPDATE t_die_handover h
JOIN t_die_handover_progress p ON p.die_id = h.die_id
SET h.asset_registration_applied_at = p.die_handover_at
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
  AND p.die_handover_at <> '2020-01-01';

COMMIT;

-- 3. After (expected 0 and 0)
SELECT COUNT(*) AS rows_left_3A
FROM t_die_handover h
JOIN (
    SELECT die_id FROM t_die_handover
    GROUP BY die_id
    HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
       AND SUM(is_accessory_item_flag = 1 AND IFNULL(TRIM(note2), '') = '') = 1
) x ON x.die_id = h.die_id
WHERE h.is_accessory_item_flag = 1
  AND IFNULL(TRIM(h.note2), '') = '';

SELECT COUNT(*) AS rows_left_3B
FROM t_die_handover h
JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
  AND p.die_handover_at <> '2020-01-01';

-- 4. Summary of 2026-11-04_handover_date_check.sql (1): progress-only dates should now be only the 2020-01-01 dies
--    and the dies whose accessory rows all have a note (not fixed automatically)
WITH h AS (
  SELECT die_id,
         MAX(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN NULLIF(asset_registration_applied_at, '0000-00-00') END) AS main_asset,
         COUNT(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN 1 END)                                                  AS main_rows
  FROM t_die_handover
  GROUP BY die_id
)
SELECT
  SUM(h.main_asset IS NOT NULL AND p.die_handover_at IS NOT NULL AND h.main_asset =  p.die_handover_at) AS same_date,
  SUM(h.main_asset IS NOT NULL AND p.die_handover_at IS NOT NULL AND h.main_asset <> p.die_handover_at) AS different,
  SUM(h.main_asset IS NULL AND p.die_handover_at IS NOT NULL AND h.main_rows = 0)                      AS progress_only_no_body,
  SUM(h.main_asset IS NULL AND p.die_handover_at IS NOT NULL AND h.main_rows > 0)                      AS progress_only_with_body
FROM h
LEFT JOIN t_die_handover_progress p ON p.die_id = h.die_id;
