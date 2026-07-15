<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$sql = "
    SELECT
        m_ordersheet.id,
        m_ordersheet.ordersheet_number,
        m_ordersheet.production_numbers_id,
        m_production_numbers.production_number,
        m_ordersheet.issue_date_at,
        m_ordersheet.delivery_date_at,
        m_ordersheet.production_quantity,
        IFNULL(SUM(t10.work_quantity), 0) AS cut_quantity,
        IFNULL(SUM(t10.total_ng), 0) AS ng_quantity,
        (IFNULL(SUM(t10.work_quantity), 0) - IFNULL(SUM(t10.total_ng), 0)) AS ok_quantity,
        (IFNULL(SUM(t10.work_quantity), 0) - IFNULL(SUM(t10.total_ng), 0) - m_ordersheet.production_quantity) AS diff_quantity,
        IFNULL(t20.packed_quantity, 0) AS packed_quantity,
        (m_ordersheet.production_quantity - IFNULL(t20.packed_quantity, 0)) AS remaining_quantity,
        m_ordersheet.note,
        m_ordersheet.updated_at,
        COUNT(DISTINCT t_press.id) AS press_count
    FROM m_ordersheet
    LEFT JOIN m_production_numbers ON m_ordersheet.production_numbers_id = m_production_numbers.id
    LEFT JOIN t_press ON t_press.ordersheet_id = m_ordersheet.id
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
    ) t20 ON t20.m_ordersheet_id = m_ordersheet.id
    GROUP BY m_ordersheet.id
    ORDER BY m_ordersheet.issue_date_at DESC, m_ordersheet.delivery_date_at DESC, m_ordersheet.ordersheet_number DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
