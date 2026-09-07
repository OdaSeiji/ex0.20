<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        o.id,
        o.ordersheet_number,
        o.production_numbers_id,
        p.production_number,
        o.production_quantity,
        o.delivery_date_at,
        (IFNULL(SUM(t10.work_quantity), 0) - IFNULL(SUM(t10.total_ng), 0)) AS ok_quantity,
        IFNULL(t20.packed_quantity, 0) AS packed_quantity,
        (o.production_quantity - IFNULL(t20.packed_quantity, 0)) AS remaining_quantity
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
    WHERE o.is_available = 1 AND o.production_numbers_id IS NOT NULL
    GROUP BY o.id
    HAVING remaining_quantity > 0
    ORDER BY o.delivery_date_at ASC, o.ordersheet_number ASC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
