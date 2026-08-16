<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 対象月（YYYY-MM）。未指定なら当月。納期(delivery_date_at)をこの月に絞り込むことで
// GROUP BY 集計の対象件数を減らし、応答速度を大幅に改善する。
$month = isset($_GET["month"]) && preg_match('/^\d{4}-\d{2}$/', $_GET["month"])
    ? $_GET["month"]
    : date("Y-m");

$prepare = $pdo->prepare("
    SELECT
        o.id,
        o.ordersheet_number,
        p.production_number,
        o.production_quantity,
        o.delivery_date_at,
        (IFNULL(SUM(t10.work_quantity), 0) - IFNULL(SUM(t10.total_ng), 0)) AS ok_quantity,
        IFNULL(t20.packed_quantity, 0) AS packed_quantity,
        o.note
    FROM m_ordersheet o
    LEFT JOIN m_production_numbers p ON o.production_numbers_id = p.id
    LEFT JOIN t_press ON t_press.ordersheet_id = o.id
    LEFT JOIN (
        SELECT
            t_using_aging_rack.t_press_id,
            SUM(IFNULL(t_using_aging_rack.work_quantity, 0)) AS work_quantity,
            SUM(IFNULL(tpq.ng_sum, 0)) AS total_ng
        FROM t_using_aging_rack
        LEFT JOIN (
            SELECT using_aging_rack_id, SUM(ng_quantities) AS ng_sum
            FROM t_press_quality
            GROUP BY using_aging_rack_id
        ) tpq ON tpq.using_aging_rack_id = t_using_aging_rack.id
        GROUP BY t_using_aging_rack.t_press_id
    ) t10 ON t10.t_press_id = t_press.id
    LEFT JOIN (
        SELECT
            t_packing_box_number.m_ordersheet_id,
            SUM(IFNULL(t_packing_box.work_quantity, 0)) AS packed_quantity
        FROM t_packing_box_number
        LEFT JOIN t_packing_box ON t_packing_box.box_number_id = t_packing_box_number.id
        GROUP BY t_packing_box_number.m_ordersheet_id
    ) t20 ON t20.m_ordersheet_id = o.id
    WHERE o.is_available = 1
      AND o.delivery_date_at >= :month_start
      AND o.delivery_date_at <  :month_end
    GROUP BY o.id
    ORDER BY o.delivery_date_at DESC, o.ordersheet_number DESC
    LIMIT 300
");
$prepare->bindValue(":month_start", $month . "-01");
$prepare->bindValue(":month_end", date("Y-m-d", strtotime($month . "-01 +1 month")));
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
