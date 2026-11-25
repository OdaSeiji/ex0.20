-- 2026-11-25 Were 2026-11-06_handover_date_fix.sql and 2026-11-05_staff_role_packing_qc.sql applied on production? (READ ONLY)
-- Background: running 11-06 again stopped at line 8 ("t_die_handover_bak_20261106 already exists")
--   -> STEP 0 ran before. But the UPDATEs only stay if COMMIT was typed by hand (COMMIT is commented out in the file).
--   If the file was piped (Get-Content ... | mysql), the transaction was rolled back at the end.

USE extrusion;

-- 1. Backup tables of 11-06 (when were they made?)
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME LIKE 't_die_handover%bak_20261106';

-- 2. Rows that 3-A would still change (0 = 3-A is applied; production before the fix: 69 dies)
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

-- 3. Rows that 3-B would still change (0 = 3-B is applied; production before the fix: 12 dies, 10 without 2020-01-01)
SELECT COUNT(*) AS rows_left_3B
FROM t_die_handover h
JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
  AND p.die_handover_at <> '2020-01-01';

-- 4. 11-05: m_staff.role has 'packing' and 'quality_control'?
SELECT COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'extrusion' AND TABLE_NAME = 'm_staff' AND COLUMN_NAME = 'role';
