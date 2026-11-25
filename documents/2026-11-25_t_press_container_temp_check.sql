-- 2026-11-25 What is in the 4 container temperature columns of t_press? (READ ONLY — nothing is changed)
--   container_upside_stemside_temperature    上側・ステム側
--   container_upside_dieside_temperature     上側・ダイ側
--   container_downside_stemside_temperature  下側・ステム側
--   container_downside_dieide_temperature    下側・ダイ側（列名は "dieide"。打ち間違いのまま）
-- Written by press_daily_report.html (押出日報 → コンテナ温度). The 🍓 button fills them from the PLC log (t_plc_web_log).
-- Note: the save code turns an empty box into 0 ((int) cast), so 0 = "not entered" in most cases.

USE extrusion;

-- 1. Per year: how many presses have a value (not NULL and not 0)
SELECT
  YEAR(press_date_at) AS y,
  COUNT(*) AS presses,
  SUM(IFNULL(container_upside_stemside_temperature, 0)   <> 0) AS up_stem,
  SUM(IFNULL(container_upside_dieside_temperature, 0)    <> 0) AS up_die,
  SUM(IFNULL(container_downside_stemside_temperature, 0) <> 0) AS down_stem,
  SUM(IFNULL(container_downside_dieide_temperature, 0)   <> 0) AS down_die
FROM t_press
GROUP BY YEAR(press_date_at)
ORDER BY y;

-- 2. Per month since 2026-01 and per machine (when did input start, which machines)
SELECT
  DATE_FORMAT(press_date_at, '%Y-%m') AS ym,
  press_machine_no AS machine,
  COUNT(*) AS presses,
  SUM(IFNULL(container_upside_stemside_temperature, 0) <> 0) AS with_value
FROM t_press
WHERE press_date_at >= '2026-01-01'
GROUP BY ym, press_machine_no
ORDER BY ym, machine;

-- 3. Range of the values (only non-zero values), per machine
SELECT
  press_machine_no AS machine,
  COUNT(*) AS n,
  MIN(container_upside_stemside_temperature)   AS up_stem_min,   MAX(container_upside_stemside_temperature)   AS up_stem_max,   ROUND(AVG(container_upside_stemside_temperature))   AS up_stem_avg,
  MIN(container_upside_dieside_temperature)    AS up_die_min,    MAX(container_upside_dieside_temperature)    AS up_die_max,    ROUND(AVG(container_upside_dieside_temperature))    AS up_die_avg,
  MIN(container_downside_stemside_temperature) AS down_stem_min, MAX(container_downside_stemside_temperature) AS down_stem_max, ROUND(AVG(container_downside_stemside_temperature)) AS down_stem_avg,
  MIN(container_downside_dieide_temperature)   AS down_die_min,  MAX(container_downside_dieide_temperature)   AS down_die_max,  ROUND(AVG(container_downside_dieide_temperature))   AS down_die_avg
FROM t_press
WHERE IFNULL(container_upside_stemside_temperature, 0) <> 0
GROUP BY press_machine_no
ORDER BY machine;

-- 4. The latest 20 presses with a value (to see real examples)
SELECT
  p.id, p.press_date_at, p.press_machine_no AS machine, d.die_number,
  p.container_upside_stemside_temperature   AS up_stem,
  p.container_upside_dieside_temperature    AS up_die,
  p.container_downside_stemside_temperature AS down_stem,
  p.container_downside_dieide_temperature   AS down_die
FROM t_press p
LEFT JOIN m_dies d ON d.id = p.dies_id
WHERE IFNULL(p.container_upside_stemside_temperature, 0) <> 0
ORDER BY p.press_date_at DESC, p.id DESC
LIMIT 20;
