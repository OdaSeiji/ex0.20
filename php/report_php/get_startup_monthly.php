<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT 'arrival'     AS metric, DATE_FORMAT(arrival_at,                    '%Y-%m') AS month, COUNT(*) AS cnt
    FROM t_die_handover_progress WHERE arrival_at IS NOT NULL
    GROUP BY DATE_FORMAT(arrival_at, '%Y-%m')
    UNION ALL
    SELECT 'inspection',              DATE_FORMAT(jp_dimensional_inspection_at, '%Y-%m'), COUNT(*)
    FROM t_die_handover_progress WHERE jp_dimensional_inspection_at IS NOT NULL
    GROUP BY DATE_FORMAT(jp_dimensional_inspection_at, '%Y-%m')
    UNION ALL
    SELECT 'handover',                DATE_FORMAT(die_handover_at,              '%Y-%m'), COUNT(*)
    FROM t_die_handover_progress WHERE die_handover_at IS NOT NULL
    GROUP BY DATE_FORMAT(die_handover_at, '%Y-%m')
    UNION ALL
    SELECT 'trial_press',             DATE_FORMAT(press_date_at,                '%Y-%m'), COUNT(*)
    FROM t_press WHERE pressing_type_id = 1
    GROUP BY DATE_FORMAT(press_date_at, '%Y-%m')
    ORDER BY month, metric
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 月リストと metric→月→件数 の2次元配列に変換
$months  = [];
$data    = [];

foreach ($rows as $r) {
    $months[$r['month']] = true;
    $data[$r['metric']][$r['month']] = (int)$r['cnt'];
}

$months = array_keys($months);
sort($months);

echo json_encode([
    "months" => $months,
    "data"   => $data,
]);
