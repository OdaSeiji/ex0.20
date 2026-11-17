-- 2026-11-17 Compare part numbers in the inventory Excel (stg_inventory_snapshot, latest file)
-- with m_production_numbers.production_number.  READ ONLY: changes nothing.
-- Run on production, send back all results (Q1-Q5).

USE extrusion;
-- (Part numbers are compared with COLLATE utf8mb4_general_ci, because the tables may use different collations.)

SET @d = (SELECT MAX(source_file_date) FROM stg_inventory_snapshot);
SELECT @d AS latest_inventory_file;

-- Q1. Summary: how many inventory part numbers match the master exactly, or only when hyphens/spaces are ignored
SELECT
  COUNT(*) AS inventory_part_numbers,
  SUM(m1.id IS NOT NULL) AS exact_match,
  SUM(m1.id IS NULL AND m2.id IS NOT NULL) AS match_ignoring_hyphen_space,
  SUM(m1.id IS NULL AND m2.id IS NULL) AS no_match
FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
LEFT JOIN m_production_numbers m1 ON m1.production_number = s.pn
LEFT JOIN (SELECT MIN(id) AS id, REPLACE(REPLACE(production_number, '-', ''), ' ', '') AS k
           FROM m_production_numbers GROUP BY k) m2
       ON m2.k = REPLACE(REPLACE(s.pn, '-', ''), ' ', '');

-- Q2. For part numbers with no exact match: how many master part numbers share the beginning
--     (master is the beginning of the Excel number, or the Excel number is the beginning of the master)
--     candidates = 0: nothing similar, 1: one-to-one candidate, 2+: ambiguous
SELECT candidates, COUNT(*) AS part_numbers
FROM (
  SELECT s.pn,
         (SELECT COUNT(*) FROM m_production_numbers m
           WHERE s.pn LIKE CONCAT(m.production_number, '%')
              OR m.production_number LIKE CONCAT(s.pn, '%')) AS candidates
  FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
  WHERE NOT EXISTS (SELECT 1 FROM m_production_numbers m WHERE m.production_number = s.pn)
) x
GROUP BY candidates ORDER BY candidates;

-- Q3. The list: Excel part number and the master candidates (up to 300 rows)
SELECT s.pn AS excel_part_number,
       COUNT(m.id) AS candidates,
       GROUP_CONCAT(m.production_number ORDER BY m.production_number SEPARATOR ' / ') AS master_candidates
FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
LEFT JOIN m_production_numbers m
       ON s.pn LIKE CONCAT(m.production_number, '%') OR m.production_number LIKE CONCAT(s.pn, '%')
WHERE NOT EXISTS (SELECT 1 FROM m_production_numbers e WHERE e.production_number = s.pn)
GROUP BY s.pn
ORDER BY candidates DESC, s.pn
LIMIT 300;

-- Q4. Last part after the last hyphen, for part numbers with no exact match (which suffixes differ)
SELECT SUBSTRING_INDEX(s.pn, '-', -1) AS last_part, COUNT(*) AS part_numbers,
       MIN(s.pn) AS example
FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
WHERE NOT EXISTS (SELECT 1 FROM m_production_numbers m WHERE m.production_number = s.pn)
  AND s.pn LIKE '%-%'
GROUP BY last_part
ORDER BY part_numbers DESC
LIMIT 40;

-- Q5. Master part numbers that are used by several Excel part numbers (one master -> many Excel numbers)
SELECT m.production_number AS master_part_number, COUNT(*) AS excel_part_numbers,
       GROUP_CONCAT(s.pn ORDER BY s.pn SEPARATOR ' / ') AS excel_numbers
FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
JOIN m_production_numbers m
  ON s.pn LIKE CONCAT(m.production_number, '%') OR m.production_number LIKE CONCAT(s.pn, '%')
WHERE NOT EXISTS (SELECT 1 FROM m_production_numbers e WHERE e.production_number = s.pn)
GROUP BY m.id
HAVING COUNT(*) > 1
ORDER BY excel_part_numbers DESC
LIMIT 100;

-- Q6. For part numbers with no exact match: master part numbers with the same text before the last hyphen
--     (e.g. Excel ABC-123-30K and master ABC-123-20K). Up to 300 rows.
SELECT s.pn AS excel_part_number,
       COUNT(m.id) AS candidates,
       GROUP_CONCAT(m.production_number ORDER BY m.production_number SEPARATOR ' / ') AS master_candidates
FROM (SELECT DISTINCT TRIM(production_number) COLLATE utf8mb4_general_ci AS pn FROM stg_inventory_snapshot WHERE source_file_date = @d) s
JOIN m_production_numbers m
  ON m.production_number LIKE '%-%'
 AND s.pn LIKE '%-%'
 AND LEFT(m.production_number, CHAR_LENGTH(m.production_number) - CHAR_LENGTH(SUBSTRING_INDEX(m.production_number, '-', -1)) - 1)
   = LEFT(s.pn, CHAR_LENGTH(s.pn) - CHAR_LENGTH(SUBSTRING_INDEX(s.pn, '-', -1)) - 1)
WHERE NOT EXISTS (SELECT 1 FROM m_production_numbers e WHERE e.production_number = s.pn)
GROUP BY s.pn
ORDER BY candidates DESC, s.pn
LIMIT 300;
