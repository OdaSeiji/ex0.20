<?php
require_once "../db.php";
header("Content-Type: application/json; charset=UTF-8");

/*
 * t_using_aging_rack/t_press_quality の集計をメインクエリに直接ネストすると、
 * MariaDBがLATERAL DERIVEDと判断しt_pressの行ごとに再計算してしまい極端に遅くなる
 * (確認済み: 数十秒〜数分)。一時テーブルで1回だけ集計してから結合することで回避する。
 */
$pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_press_qty");
$pdo->exec("
    CREATE TEMPORARY TABLE tmp_press_qty (
      press_id INT PRIMARY KEY,
      work_quantity INT,
      total_ng INT
    ) ENGINE=MEMORY
");
$pdo->exec("
    INSERT INTO tmp_press_qty (press_id, work_quantity, total_ng)
    SELECT
      t_using_aging_rack.t_press_id,
      SUM(IFNULL(t_using_aging_rack.work_quantity, 0)),
      SUM(IFNULL(tpq.ng_sum, 0))
    FROM t_using_aging_rack
    LEFT JOIN (
      SELECT using_aging_rack_id, SUM(ng_quantities) AS ng_sum
      FROM t_press_quality
      GROUP BY using_aging_rack_id
    ) tpq ON tpq.using_aging_rack_id = t_using_aging_rack.id
    GROUP BY t_using_aging_rack.t_press_id
");

$sql = "
    SELECT
      d.id, d.die_number, hp.die_handover_at, d.die_condition_id, c.name AS condition_name,
      IFNULL(tpl.total_profile_length, 0) AS total_profile_length,
      tpl.last_press_date_at,
      lp.last_pressing_type,
      lp.last_billet_quantities,
      IFNULL(tpl.total_billet_quantities, 0) AS total_billet_quantities,
      IFNULL(tpl.cut_quantity, 0) AS cut_quantity,
      IFNULL(tpl.good_quantity, 0) AS good_quantity
    FROM m_dies d
    LEFT JOIN m_die_conditions c ON d.die_condition_id = c.id
    LEFT JOIN t_die_handover_progress hp ON hp.die_id = d.id
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
        SUM(t_press.actual_billet_quantities) AS total_billet_quantities,
        SUM(CASE WHEN t_press.pressing_type_id != 1 THEN IFNULL(q.work_quantity, 0) ELSE 0 END) AS cut_quantity,
        SUM(CASE WHEN t_press.pressing_type_id != 1 THEN IFNULL(q.work_quantity, 0) - IFNULL(q.total_ng, 0) ELSE 0 END) AS good_quantity
      FROM t_press
      JOIN (
        SELECT m_dies.id AS dies_id, m_dies.hole, m_production_numbers.specific_weight
        FROM m_dies
        LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
      ) dp ON t_press.dies_id = dp.dies_id
      LEFT JOIN tmp_press_qty q ON q.press_id = t_press.id
      GROUP BY t_press.dies_id
    ) tpl ON tpl.dies_id = d.id
    LEFT JOIN (
      SELECT
        ranked.dies_id,
        ranked.actual_billet_quantities AS last_billet_quantities,
        mpt.pressing_type AS last_pressing_type
      FROM (
        SELECT
          dies_id, actual_billet_quantities, pressing_type_id,
          ROW_NUMBER() OVER (PARTITION BY dies_id ORDER BY press_date_at DESC, id DESC) AS rn
        FROM t_press
      ) ranked
      LEFT JOIN m_pressing_type mpt ON mpt.id = ranked.pressing_type_id
      WHERE ranked.rn = 1
    ) lp ON lp.dies_id = d.id
    WHERE d.die_number LIKE 'CA%'
       OR d.die_number REGEXP '^CQ[A-Z0-9]{2}T3-'
       OR d.die_number REGEXP '^CP96[A-Z0-9]{2}T-'
       OR d.die_number REGEXP '^MSQ[A-Z0-9]{2}B2-'
       OR d.die_number REGEXP '^MXS[A-Z0-9]{2}B-'
       OR d.die_number LIKE 'MXQ%'
       OR d.die_number REGEXP '^CXS[A-Z0-9]{2}S[23]-'
       OR d.die_number LIKE 'MGP%'
       OR d.die_number LIKE 'PS%'
       OR d.die_number REGEXP '^XLA[A-Z0-9]{2}B2-'
    ORDER BY d.die_number
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_press_qty");
echo json_encode($rows);
