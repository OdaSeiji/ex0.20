-- 2026-11-25 Move 切替業連 No. (production site change notice) from each die to the product number.
--   From: t_die_handover_progress.production_site_change_notice (one value per die)
--   To:   m_production_numbers.change_notice_no            (one value per product number)
-- Background: documents/2026-11-15_die_tables_reorganization.md section 5.
--   Production check 11/25 (documents/2026-11-24_change_notice_by_pn_check.sql):
--   522 product numbers have a value, 0 product numbers have different values on their dies.
-- The old column stays for now (drop it later, after the page has worked for a while).
--
-- Order on production: THIS SQL first, then git pull.
--   (After the pull, the page reads the new column. Before this SQL it would not exist.)
-- Can be piped as a whole (Get-Content ... | mysql ...). It COMMITs by itself.

USE extrusion;

-- 1. New column
ALTER TABLE m_production_numbers
  ADD COLUMN change_notice_no VARCHAR(100) NULL DEFAULT NULL
  COMMENT 'Production site change notice No. (e.g. vol.125). One per product number. Moved from t_die_handover_progress 2026-11-25'
  AFTER stock_part_number;

-- 2. Copy: for each product number, the value on its dies.
--    Product numbers whose dies have DIFFERENT values are NOT copied (production: none; they are listed in 4).
START TRANSACTION;

UPDATE m_production_numbers pn
JOIN (
  SELECT d.production_number_id AS pn_id, MAX(NULLIF(TRIM(h.production_site_change_notice), '')) AS v
  FROM m_dies d
  JOIN t_die_handover_progress h ON h.die_id = d.id
  WHERE d.production_number_id IS NOT NULL
  GROUP BY d.production_number_id
  HAVING COUNT(DISTINCT NULLIF(TRIM(h.production_site_change_notice), '')) = 1
) x ON x.pn_id = pn.id
SET pn.change_notice_no = x.v;

COMMIT;

-- 3. Check: number of product numbers with a value (production expected 522)
SELECT COUNT(*) AS pn_with_change_notice
FROM m_production_numbers
WHERE change_notice_no IS NOT NULL;

-- 4. Product numbers NOT copied because their dies have different values (production expected: nothing shown)
SELECT pn.production_number, d.die_number, h.production_site_change_notice
FROM m_dies d
JOIN t_die_handover_progress h ON h.die_id = d.id
JOIN m_production_numbers pn ON pn.id = d.production_number_id
WHERE pn.change_notice_no IS NULL
  AND NULLIF(TRIM(h.production_site_change_notice), '') IS NOT NULL
ORDER BY pn.production_number, d.die_number;
