<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        p.press_machine_no                            AS machine,
        DATE_FORMAT(p.press_date_at, '%Y-%m')         AS month,
        COUNT(*)                                      AS press_count,
        ROUND(
            SUM(
                CASE
                    WHEN p.press_finish_at >= p.press_start_at
                        THEN TIME_TO_SEC(TIMEDIFF(p.press_finish_at, p.press_start_at))
                    ELSE
                        TIME_TO_SEC(TIMEDIFF(p.press_finish_at, p.press_start_at)) + 86400
                END
            ) / 3600
        , 1) AS usage_hours,
        ROUND(
            SUM(
                PI() * POWER(p.billet_size * 25.4 / 2, 2)
                * p.billet_length
                * p.actual_billet_quantities
                * 2.7
                / 1000000000
            )
        , 2) AS extrusion_t,
        ROUND(
            SUM(
                (IFNULL(t20.work_quantity, 0) - IFNULL(t10.total_ng, 0))
                * IFNULL(mpn.specific_weight, 0)
                * COALESCE(p.first_actual_length, mpn.production_length * 1000)
                / 1000000
            )
        , 2) AS good_weight_t
    FROM t_press p
    LEFT JOIN m_dies mdie ON p.dies_id = mdie.id
    LEFT JOIN m_production_numbers mpn ON mdie.production_number_id = mpn.id
    LEFT JOIN (
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
    WHERE p.press_machine_no IS NOT NULL
      AND p.press_machine_no != 0
      AND p.press_start_at   IS NOT NULL
      AND p.press_finish_at  IS NOT NULL
    GROUP BY p.press_machine_no, DATE_FORMAT(p.press_date_at, '%Y-%m')
    ORDER BY p.press_machine_no, month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months      = [];
$machines    = [];
$hours       = [];
$counts      = [];
$extrusions  = [];
$goodWeights = [];

foreach ($rows as $r) {
    $m  = (int)$r['machine'];
    $mo = $r['month'];
    $months[$mo]          = true;
    $machines[$m]         = true;
    $hours[$m][$mo]        = (float)$r['usage_hours'];
    $counts[$m][$mo]       = (int)$r['press_count'];
    $extrusions[$m][$mo]   = (float)$r['extrusion_t'];
    $goodWeights[$m][$mo]  = (float)$r['good_weight_t'];
}

$months   = array_keys($months);
$machines = array_keys($machines);
sort($months);
sort($machines);

echo json_encode([
    "months"      => $months,
    "machines"    => $machines,
    "hours"       => $hours,
    "counts"      => $counts,
    "extrusions"  => $extrusions,
    "goodWeights" => $goodWeights,
]);
