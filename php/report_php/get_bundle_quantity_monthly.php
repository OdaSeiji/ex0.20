<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        p.pressing_type_id                      AS pressing_type_id,
        mpt.pressing_type                       AS pressing_type,
        p.billet_size                           AS billet_size,
        b.mfg                                   AS mfg,
        DATE_FORMAT(p.press_date_at, '%Y-%m')   AS month,
        SUM(
            PI() * POWER(p.billet_size * 25.4 / 2, 2)
            * p.billet_length
            * b.quantity
            * 2.7
            / 1000000000
        )                                        AS weight_t
    FROM t_press p
    JOIN t_bundle b ON b.press_id = p.id
    LEFT JOIN m_pressing_type mpt ON mpt.id = p.pressing_type_id
    WHERE p.press_date_at <> '0000-00-00'
      AND b.mfg IN (1, 2)
      AND p.billet_size > 0
    GROUP BY p.pressing_type_id, mpt.pressing_type, p.billet_size, b.mfg, month
    ORDER BY p.pressing_type_id, p.billet_size, b.mfg, month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$groups = [];

foreach ($rows as $r) {
    $mo  = $r['month'];
    $key = $r['pressing_type_id'] . "_" . $r['billet_size'] . "_" . $r['mfg'];

    $months[$mo] = true;

    if (!isset($groups[$key])) {
        $groups[$key] = [
            "pressingTypeId"    => (int)$r['pressing_type_id'],
            "pressingType"      => $r['pressing_type'],
            "billetSize"        => (int)$r['billet_size'],
            "mfg"               => (int)$r['mfg'],
            "values"            => [],
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
