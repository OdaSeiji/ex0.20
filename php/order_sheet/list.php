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
        IFNULL(t10.work_quantity, 0) AS cut_quantity,
        IFNULL(t10.total_ng, 0) AS ng_quantity,
        (IFNULL(t10.work_quantity, 0) - IFNULL(t10.total_ng, 0)) AS ok_quantity,
        (IFNULL(t10.work_quantity, 0) - IFNULL(t10.total_ng, 0) - m_ordersheet.production_quantity) AS diff_quantity,
        IFNULL(t20.packed_quantity, 0) AS packed_quantity,
        (m_ordersheet.production_quantity - IFNULL(t20.packed_quantity, 0)) AS remaining_quantity,
        m_ordersheet.note,
        m_ordersheet.is_available,
        m_ordersheet.updated_at,
        IFNULL(t10.press_count, 0) AS press_count
    FROM m_ordersheet
    LEFT JOIN m_production_numbers ON m_ordersheet.production_numbers_id = m_production_numbers.id
    LEFT JOIN (
        -- 発注書ごとに、押出の回数・切断本数・NG 本数をまとめる
        SELECT p.ordersheet_id,
               COUNT(DISTINCT p.id) AS press_count,
               SUM(IFNULL(r.work_quantity, 0)) AS work_quantity,
               SUM(IFNULL(q.ng_sum, 0)) AS total_ng
        FROM t_press p
        LEFT JOIN t_using_aging_rack r ON r.t_press_id = p.id
        LEFT JOIN (
            SELECT using_aging_rack_id, SUM(ng_quantities) AS ng_sum
            FROM t_press_quality
            GROUP BY using_aging_rack_id
        ) q ON q.using_aging_rack_id = r.id
        WHERE p.ordersheet_id IS NOT NULL
        GROUP BY p.ordersheet_id
    ) t10 ON t10.ordersheet_id = m_ordersheet.id
    LEFT JOIN (
        SELECT
            t_packing_box_number.m_ordersheet_id,
            SUM(IFNULL(t_packing_box.work_quantity, 0)) AS packed_quantity
        FROM t_packing_box_number
        LEFT JOIN t_packing_box ON t_packing_box.box_number_id = t_packing_box_number.id
        GROUP BY t_packing_box_number.m_ordersheet_id
    ) t20 ON t20.m_ordersheet_id = m_ordersheet.id
    ORDER BY m_ordersheet.issue_date_at DESC, m_ordersheet.delivery_date_at DESC, m_ordersheet.ordersheet_number DESC
";

// MariaDB の split_materialized（LATERAL DERIVED）が、集計のサブクエリを発注書の行ごとに作り直して
// 何分も終わらなくなる（2026-10）。この一覧ではオフにする
$pdo->exec("SET SESSION optimizer_switch = 'split_materialized=off'");
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
