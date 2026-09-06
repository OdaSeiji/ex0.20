<?php
require_once "../db.php";
header("Content-Type: application/json; charset=UTF-8");

$sql = "
    SELECT
      d.id, d.die_number, d.die_condition_id, c.name AS condition_name,
      IFNULL(tpl.total_profile_length, 0) AS total_profile_length,
      tpl.last_press_date_at,
      lp.last_billet_quantities,
      IFNULL(tpl.total_billet_quantities, 0) AS total_billet_quantities
    FROM m_dies d
    LEFT JOIN m_die_conditions c ON d.die_condition_id = c.id
    LEFT JOIN (
      SELECT
        t_press.dies_id,
        ROUND(SUM(
          (3.141459 * POWER(t_press.billet_size * 25.4 / 2, 2)
            * t_press.billet_length * 0.001 * 2.70
            * t_press.actual_billet_quantities / 1000)
          / dp.specific_weight / 1000 / dp.hole
        ), 1) AS total_profile_length,
        MAX(t_press.press_date_at) AS last_press_date_at,
        SUM(t_press.actual_billet_quantities) AS total_billet_quantities
      FROM t_press
      JOIN (
        SELECT m_dies.id AS dies_id, m_dies.hole, m_production_numbers.specific_weight
        FROM m_dies
        LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
      ) dp ON t_press.dies_id = dp.dies_id
      GROUP BY t_press.dies_id
    ) tpl ON tpl.dies_id = d.id
    LEFT JOIN (
      SELECT dies_id, actual_billet_quantities AS last_billet_quantities
      FROM (
        SELECT
          dies_id, actual_billet_quantities,
          ROW_NUMBER() OVER (PARTITION BY dies_id ORDER BY press_date_at DESC, id DESC) AS rn
        FROM t_press
      ) ranked
      WHERE rn = 1
    ) lp ON lp.dies_id = d.id
    WHERE d.die_number LIKE 'CQ%'
       OR d.die_number LIKE 'CP96%'
       OR d.die_number LIKE 'MXS%'
       OR d.die_number LIKE 'MXQ%'
       OR d.die_number LIKE 'CXS%'
       OR d.die_number LIKE 'MGP%'
       OR d.die_number LIKE 'PS%'
    ORDER BY d.die_number
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
