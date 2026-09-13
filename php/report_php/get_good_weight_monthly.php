<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        p.pressing_type_id                      AS pressing_type_id,
        mpt.pressing_type                       AS pressing_type,
        p.billet_size                           AS billet_size,
        DATE_FORMAT(p.press_date_at, '%Y-%m')   AS month,
        SUM(
            (IFNULL(t20.work_quantity, 0) - IFNULL(t10.total_ng, 0))
            * IFNULL(mpn.specific_weight, 0)
            * COALESCE(p.first_actual_length, mpn.production_length * 1000)
            / 1000000
        )                                        AS weight_t
    FROM t_press p
    JOIN (
        SELECT t_press_id, SUM(work_quantity) AS work_quantity
        FROM t_using_aging_rack
        GROUP BY t_press_id
    ) t20 ON t20.t_press_id = p.id
    LEFT JOIN (
        SELECT
            t_using_aging_rack.t_press_id,
            SUM(t_press_quality.ng_quantities) AS total_ng
        FROM t_using_aging_rack
        LEFT JOIN t_press_quality ON t_press_quality.using_aging_rack_id = t_using_aging_rack.id
        GROUP BY t_using_aging_rack.t_press_id
    ) t10 ON t10.t_press_id = p.id
    LEFT JOIN m_dies mdie ON p.dies_id = mdie.id
    LEFT JOIN m_production_numbers mpn ON mdie.production_number_id = mpn.id
    LEFT JOIN m_pressing_type mpt ON mpt.id = p.pressing_type_id
    WHERE p.press_date_at <> '0000-00-00'
      AND p.billet_size > 0
    GROUP BY p.pressing_type_id, mpt.pressing_type, p.billet_size, month
    ORDER BY p.pressing_type_id, p.billet_size, month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$groups = [];

foreach ($rows as $r) {
    $mo  = $r['month'];
    $key = $r['pressing_type_id'] . "_" . $r['billet_size'];

    $months[$mo] = true;

    if (!isset($groups[$key])) {
        $groups[$key] = [
            "pressingTypeId" => (int)$r['pressing_type_id'],
            "pressingType"   => $r['pressing_type'],
            "billetSize"     => (int)$r['billet_size'],
            "values"         => [],
        ];
    }
    $groups[$key]["values"][$mo] = round((float)$r['weight_t'], 2);
}

$months = array_keys($months);
sort($months);

echo json_encode([
    "months" => $months,
    "rows"   => array_values($groups),
], JSON_UNESCAPED_UNICODE);
