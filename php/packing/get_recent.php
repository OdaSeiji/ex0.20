<?php
require_once __DIR__ . "/common.php";

// 梱包入力：最近の梱包作業（発注書を選んでいないときに出す。exd11 SelPackingHistoryAllV1 の移植）
//   GET from, to = 梱包日の期間（両方あるときだけ絞る）。無ければ新しい50件

$from = $_GET["from"] ?? "";
$to   = $_GET["to"] ?? "";
$useTerm = pkIsDate($from) && pkIsDate($to);

try {
    $stmt = $pdo->prepare("
        SELECT
            k.id,
            DATE_FORMAT(k.packing_date, '%Y-%m-%d') AS packing_date,
            TIME_FORMAT(k.packing_start, '%H:%i')   AS start_time,
            TIME_FORMAT(k.packing_end, '%H:%i')     AS end_time,
            o.id                                    AS order_id,
            o.ordersheet_number,
            pn.production_number,
            (SELECT COUNT(*) FROM t_packing_worker w WHERE w.t_packing_id = k.id) AS worker_count,
            (SELECT COUNT(DISTINCT b.box_number_id) FROM t_packing_box b WHERE b.packing_id = k.id) AS box_count,
            (SELECT IFNULL(SUM(b.work_quantity), 0) FROM t_packing_box b WHERE b.packing_id = k.id) AS packed_qty
        FROM t_packing k
        LEFT JOIN m_ordersheet o ON o.id = k.m_ordersheet_id
        LEFT JOIN m_production_numbers pn ON pn.id = o.production_numbers_id
        " . ($useTerm ? "WHERE k.packing_date BETWEEN :from AND :to" : "") . "
        ORDER BY k.packing_date DESC, k.packing_start DESC, k.id DESC
        LIMIT " . ($useTerm ? 1000 : 50)
    );
    $stmt->execute($useTerm ? [":from" => $from, ":to" => $to] : []);
    $rows = array_map(function ($r) {
        foreach (["id", "order_id", "worker_count", "box_count", "packed_qty"] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        return $r;
    }, $stmt->fetchAll());
    echo json_encode(["rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}
