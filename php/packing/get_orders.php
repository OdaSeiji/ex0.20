<?php
require_once __DIR__ . "/common.php";

// 梱包入力：発注書の一覧（exd11 DailiReport_OrderSheet の SelSummarySub.php の移植。数え方は order_sheet/list.php と同じ）
//   GET q = 発注番号・品番（部分一致, 省略可）, status = open | all（open = 押出があり、梱包の残りがある）

$q      = trim($_GET["q"] ?? "");
$status = ($_GET["status"] ?? "") === "all" ? "all" : "open";

$where = ["(o.ordersheet_number LIKE :q1 OR pn.production_number LIKE :q2)"];
if ($status === "open") $where[] = "IFNULL(t10.press_count, 0) > 0 AND o.production_quantity - IFNULL(t20.packed_quantity, 0) > 0";
$whereSql = implode(" AND ", $where);

try {
    // MariaDB の split_materialized が集計のサブクエリを行ごとに作り直して遅くなるため、オフにする（order_sheet/list.php と同じ）
    $pdo->exec("SET SESSION optimizer_switch = 'split_materialized=off'");
    $stmt = $pdo->prepare("
        SELECT
            o.id,
            o.ordersheet_number,
            pn.production_number,
            DATE_FORMAT(o.issue_date_at, '%Y-%m-%d')    AS issue_date,
            DATE_FORMAT(o.delivery_date_at, '%Y-%m-%d') AS delivery_date,
            o.production_quantity                         AS order_qty,
            IFNULL(t10.work_quantity, 0) - IFNULL(t10.total_ng, 0) AS ok_qty,
            IFNULL(t20.packed_quantity, 0)                AS packed_qty,
            IFNULL(t10.press_count, 0)                    AS press_count
        FROM m_ordersheet o
        LEFT JOIN m_production_numbers pn ON pn.id = o.production_numbers_id
        LEFT JOIN (
            SELECT p.ordersheet_id,
                   COUNT(DISTINCT p.id) AS press_count,
                   SUM(IFNULL(r.work_quantity, 0)) AS work_quantity,
                   SUM(IFNULL(q.ng_sum, 0)) AS total_ng
            FROM t_press p
            LEFT JOIN t_using_aging_rack r ON r.t_press_id = p.id
            LEFT JOIN (SELECT using_aging_rack_id, SUM(ng_quantities) AS ng_sum FROM t_press_quality GROUP BY using_aging_rack_id) q
                   ON q.using_aging_rack_id = r.id
            WHERE p.ordersheet_id IS NOT NULL
            GROUP BY p.ordersheet_id
        ) t10 ON t10.ordersheet_id = o.id
        LEFT JOIN (
            SELECT bn.m_ordersheet_id, SUM(IFNULL(b.work_quantity, 0)) AS packed_quantity
            FROM t_packing_box_number bn
            LEFT JOIN t_packing_box b ON b.box_number_id = bn.id
            GROUP BY bn.m_ordersheet_id
        ) t20 ON t20.m_ordersheet_id = o.id
        WHERE {$whereSql}
        ORDER BY o.issue_date_at DESC, o.delivery_date_at DESC, o.ordersheet_number DESC
        LIMIT 400
    ");
    $stmt->execute([":q1" => "%{$q}%", ":q2" => "%{$q}%"]);
    $rows = array_map(function ($r) {
        foreach (["id", "order_qty", "ok_qty", "packed_qty", "press_count"] as $k) $r[$k] = (int)$r[$k];
        return $r;
    }, $stmt->fetchAll());
    echo json_encode(["rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}
