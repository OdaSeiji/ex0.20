-- 2026-11-24 切替業連 No. を品番（m_production_numbers）に移す前の確認（READ ONLY — nothing is changed）
-- Now: t_die_handover_progress.production_site_change_notice (one value per die, varchar, e.g. 'vol.125').
-- Plan: one value per product number in m_production_numbers (user 11/24: a product number never gets two notices).
-- Local (anonymized copy): 501 product numbers have a value; only 1 has different values on its dies (DM245: vol.616 / vol.518).

USE extrusion;

-- 1. Summary per product number
WITH x AS (
  SELECT d.production_number_id AS pn_id, NULLIF(TRIM(h.production_site_change_notice), '') AS v
  FROM m_dies d JOIN t_die_handover_progress h ON h.die_id = d.id
),
g AS (
  SELECT pn_id, COUNT(*) AS dies, COUNT(v) AS filled, COUNT(DISTINCT v) AS kinds
  FROM x GROUP BY pn_id HAVING filled > 0
)
SELECT
  COUNT(*)                                   AS pn_with_value,
  SUM(kinds = 1 AND filled = dies)           AS same_on_all_dies,
  SUM(kinds = 1 AND filled < dies)           AS on_some_dies_only,
  SUM(kinds > 1)                             AS different_values    -- these need a decision
FROM g;

-- 2. Product numbers whose dies have DIFFERENT values (decide which one is right)
WITH x AS (
  SELECT d.production_number_id AS pn_id, NULLIF(TRIM(h.production_site_change_notice), '') AS v
  FROM m_dies d JOIN t_die_handover_progress h ON h.die_id = d.id
)
SELECT
  pn.production_number,
  d.die_number,
  h.production_site_change_notice,
  h.mass_production_start_at,
  h.die_handover_at,
  h.updated_at
FROM m_dies d
JOIN t_die_handover_progress h ON h.die_id = d.id
LEFT JOIN m_production_numbers pn ON pn.id = d.production_number_id
WHERE d.production_number_id IN (SELECT pn_id FROM x GROUP BY pn_id HAVING COUNT(DISTINCT v) > 1)
ORDER BY pn.production_number, d.die_number;

-- 3. Values that do not look like 'vol.<number>' (typos etc.)
SELECT production_site_change_notice, COUNT(*) AS dies
FROM t_die_handover_progress
WHERE production_site_change_notice IS NOT NULL
  AND TRIM(production_site_change_notice) <> ''
  AND production_site_change_notice NOT REGEXP '^vol\\.[0-9]+$'
GROUP BY production_site_change_notice;

-- 4. Dies with a value but no product number (cannot be moved)
SELECT d.id, d.die_number, h.production_site_change_notice
FROM m_dies d JOIN t_die_handover_progress h ON h.die_id = d.id
WHERE d.production_number_id IS NULL
  AND NULLIF(TRIM(h.production_site_change_notice), '') IS NOT NULL;
