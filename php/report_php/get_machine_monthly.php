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
        , 2) AS extrusion_t
    FROM t_press p
    WHERE p.press_machine_no IS NOT NULL
      AND p.press_machine_no != 0
      AND p.press_start_at   IS NOT NULL
      AND p.press_finish_at  IS NOT NULL
    GROUP BY p.press_machine_no, DATE_FORMAT(p.press_date_at, '%Y-%m')
    ORDER BY p.press_machine_no, month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months     = [];
$machines   = [];
$hours      = [];
$counts     = [];
$extrusions = [];

foreach ($rows as $r) {
    $m  = (int)$r['machine'];
    $mo = $r['month'];
    $months[$mo]        = true;
    $machines[$m]       = true;
    $hours[$m][$mo]      = (float)$r['usage_hours'];
    $counts[$m][$mo]     = (int)$r['press_count'];
    $extrusions[$m][$mo] = (float)$r['extrusion_t'];
}

$months   = array_keys($months);
$machines = array_keys($machines);
sort($months);
sort($machines);

echo json_encode([
    "months"     => $months,
    "machines"   => $machines,
    "hours"      => $hours,
    "counts"     => $counts,
    "extrusions" => $extrusions,
]);
