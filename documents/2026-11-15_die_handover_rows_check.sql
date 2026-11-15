-- 2026-11-15 Check that every die has its rows in t_die_handover (read only, nothing is changed).
--   Queries 1-3: dies in m_dies with NO row at all in t_die_handover.
--   Query 4:     dies with only parts rows (no die body row) in t_die_handover.
-- Purpose: before moving the arrival date into t_die_handover (see documents/2026-11-15_die_tables_reorganization.md),
-- make sure every die body has a body row there.

-- ------------------------------------------------------------------
-- A. Dies in m_dies with NO row at all in t_die_handover (neither the die body nor parts)
--    Local (anonymized copy): 60 dies (registered 2023: 1, 2024: 8, 2025: 20, 2026: 31).
--    Locally ALL 60 were copies of another die (die number ending -ZZZ, -ZZZn, -ZZZ-DUN or -20K), and the original
--    die (number without that ending) is in t_die_handover. These look like the copy dies used for one-die /
--    many-lengths (handover 2026-10-25 §4 item 8-2). Query 3 checks this on production.
-- ------------------------------------------------------------------

-- 1. Count by the year the die was registered in m_dies
SELECT YEAR(d.created_at) AS registered_year, COUNT(*) AS dies
FROM m_dies d
WHERE NOT EXISTS (SELECT 1 FROM t_die_handover h WHERE h.die_id = d.id)
GROUP BY YEAR(d.created_at)
ORDER BY registered_year;

-- 2. The list
SELECT
  d.id,
  d.die_number,
  pn.production_number,
  d.created_at                       AS registered_at,
  d.arrival_at                       AS m_dies_arrival_at,
  p.arrival_at                       AS progress_arrival_at,
  p.die_handover_at                  AS progress_handover_at,
  p.mass_production_start_at         AS progress_mass_production_start_at,
  ls.name                            AS lifecycle_status,
  dc.name                            AS die_condition,
  d.is_disable,
  d.asset_no,
  d.budget_id,
  d.die_note
FROM m_dies d
LEFT JOIN m_production_numbers pn   ON pn.id = d.production_number_id
LEFT JOIN t_die_handover_progress p ON p.die_id = d.id
LEFT JOIN m_die_lifecycle_status ls ON ls.id = d.die_lifecycle_status_id
LEFT JOIN m_die_conditions dc       ON dc.id = d.die_condition_id
WHERE NOT EXISTS (SELECT 1 FROM t_die_handover h WHERE h.die_id = d.id)
ORDER BY d.created_at, d.die_number;

-- 3. Is each one a copy of another die? base_die = die number without the copy ending (-ZZZ..., -20K).
--    looks_like_copy = 0 means it is NOT a copy: that die is really missing from the fixed-asset flow.
--    base_die_exists / base_die_in_handover = 1 means the original die exists / is in t_die_handover.
SELECT
  d.die_number,
  REGEXP_REPLACE(d.die_number, '(-ZZZ.*|-20K)$', '') AS base_die,
  d.die_number <> REGEXP_REPLACE(d.die_number, '(-ZZZ.*|-20K)$', '') AS looks_like_copy,
  EXISTS (SELECT 1 FROM m_dies o
          WHERE o.die_number = REGEXP_REPLACE(d.die_number, '(-ZZZ.*|-20K)$', '')) AS base_die_exists,
  EXISTS (SELECT 1 FROM t_die_handover h JOIN m_dies o ON o.id = h.die_id
          WHERE o.die_number = REGEXP_REPLACE(d.die_number, '(-ZZZ.*|-20K)$', '')) AS base_die_in_handover
FROM m_dies d
WHERE NOT EXISTS (SELECT 1 FROM t_die_handover h WHERE h.die_id = d.id)
ORDER BY looks_like_copy, d.die_number;

-- ------------------------------------------------------------------
-- B. Dies that have rows in t_die_handover, but ONLY parts rows (is_accessory_item_flag = 1), no die body row.
--    The 2026-11-06 fix (documents/2026-11-06_handover_date_fix.sql, part A) turned the wrongly flagged
--    body rows back into body rows (69 dies on production), so this should be 0 or close to 0 now.
--    Local (anonymized copy, taken before that fix): 80 dies.
-- ------------------------------------------------------------------

-- 4. Count
SELECT COUNT(*) AS dies_with_parts_rows_only
FROM (
  SELECT die_id
  FROM t_die_handover
  GROUP BY die_id
  HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
) x;

-- 5. The list
SELECT d.die_number, h.id AS handover_id, h.die_model_code, h.note2,
       h.die_arrived_at, h.asset_registration_applied_at
FROM t_die_handover h
JOIN m_dies d ON d.id = h.die_id
WHERE h.die_id IN (
  SELECT die_id FROM t_die_handover
  GROUP BY die_id
  HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
)
ORDER BY d.die_number, h.id;
