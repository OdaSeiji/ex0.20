-- 2026-11-25 CP9632T-V04B: make t_die_handover row 1091 the die BODY row (production only; the die is not in the local copy).
-- Production check 11/25 (documents/2026-11-15_die_handover_rows_check.sql, query 5): this die is the only one with parts rows only.
--   1091  die_model_code CP9632T-V04B, note2 "Nửa khuôn", arrived 2026-04-15, asset registration 2026-08-03  -> body row
--   2116  male die / 2117 female die / 2118 ring                                                        -> stay parts rows
-- The 11-06 / 11-25 automatic fix skipped it because note2 is not empty.
-- The WHERE checks the die number too, so nothing happens if id 1091 is a different row. Can be piped as a whole.

USE extrusion;

UPDATE t_die_handover h
JOIN m_dies d ON d.id = h.die_id
SET h.is_accessory_item_flag = 0
WHERE h.id = 1091
  AND d.die_number = 'CP9632T-V04B'
  AND h.is_accessory_item_flag = 1;

SELECT ROW_COUNT() AS changed_rows;   -- expected 1

-- Check: expected 1091 = 0 (body), 2116-2118 = 1 (parts)
SELECT h.id, h.is_accessory_item_flag, h.die_model_code, h.note2, h.die_arrived_at, h.asset_registration_applied_at
FROM t_die_handover h
JOIN m_dies d ON d.id = h.die_id
WHERE d.die_number = 'CP9632T-V04B'
ORDER BY h.id;

-- Check: dies with parts rows only (expected 0)
SELECT COUNT(*) AS dies_with_parts_rows_only
FROM (
  SELECT die_id FROM t_die_handover
  GROUP BY die_id
  HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
) x;
