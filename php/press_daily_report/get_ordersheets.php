<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 生産指示書（m_ordersheet）の選択用一覧。
// - 品番で絞り込むとき（by_pn=1）：押出指示書（directive_id）の品番、無ければ金型（die_id）に紐づく品番すべての発注書を、
//   納期に関係なく新しい順に5件出す
// - 絞り込まないとき：納期(delivery_date_at)が month（YYYY-MM。未指定なら当月）の発注書を出す
// OK数・梱包済み数は、先に表示する発注書を決めてから、その分だけ集計する
// （全部の押出・ラック・梱包をまとめて集計すると 30 秒以上かかっていた）
$pnIds = [];
if (($_GET["by_pn"] ?? "") === "1") {
    $directiveId = (int)($_GET["directive_id"] ?? 0);
    $dieId       = (int)($_GET["die_id"] ?? 0);
    if ($directiveId > 0) {
        $st = $pdo->prepare("
            SELECT v.production_number_id
            FROM t_press_directive d
            JOIN m_die_production_number_variants v ON v.id = d.die_production_number_variant_id
            WHERE d.id = :id
        ");
        $st->execute([":id" => $directiveId]);
        $pnIds = array_map("intval", $st->fetchAll(PDO::FETCH_COLUMN));
    }
    if (!$pnIds && $dieId > 0) {
        $st = $pdo->prepare("SELECT DISTINCT production_number_id FROM m_die_production_number_variants WHERE die_id = :id");
        $st->execute([":id" => $dieId]);
        $pnIds = array_map("intval", $st->fetchAll(PDO::FETCH_COLUMN));
    }
}
$month = isset($_GET["month"]) && preg_match('/^\d{4}-\d{2}$/', $_GET["month"])
    ? $_GET["month"]
    : date("Y-m");

$params = [];
if ($pnIds) {
    $ph = [];
    foreach ($pnIds as $i => $v) {
        $ph[] = ":pn{$i}";
        $params[":pn{$i}"] = $v;
    }
    $where = "o.production_numbers_id IN (" . implode(",", $ph) . ")";
    $limit = 5;   // 新しい順に5件（ユーザー指定）
} else {
    $where = "o.delivery_date_at >= :month_start AND o.delivery_date_at < :month_end";
    $params[":month_start"] = $month . "-01";
    $params[":month_end"]   = date("Y-m-d", strtotime($month . "-01 +1 month"));
    $limit = 300;
}

$stmt = $pdo->prepare("
    SELECT
        os.id,
        os.ordersheet_number,
        p.production_number,
        os.production_quantity,
        os.delivery_date_at,
        IFNULL(ok.ok_quantity, 0)         AS ok_quantity,
        IFNULL(pk.packed_quantity, 0)     AS packed_quantity,
        os.note
    FROM (
        SELECT o.id, o.ordersheet_number, o.production_numbers_id, o.production_quantity, o.delivery_date_at, o.note
        FROM m_ordersheet o
        WHERE o.is_available = 1 AND {$where}
        ORDER BY o.delivery_date_at DESC, o.ordersheet_number DESC
        LIMIT {$limit}
    ) os
    LEFT JOIN m_production_numbers p ON p.id = os.production_numbers_id
    -- OK数：この発注書の押出のラックの本数 − NG
    LEFT JOIN (
        SELECT tp.ordersheet_id,
               SUM(IFNULL(r.work_quantity, 0))
                 - SUM(IFNULL((SELECT SUM(q.ng_quantities) FROM t_press_quality q WHERE q.using_aging_rack_id = r.id), 0)) AS ok_quantity
        FROM m_ordersheet o2
        JOIN t_press tp ON tp.ordersheet_id = o2.id
        JOIN t_using_aging_rack r ON r.t_press_id = tp.id
        WHERE o2.is_available = 1 AND " . str_replace("o.", "o2.", $where) . "
        GROUP BY tp.ordersheet_id
    ) ok ON ok.ordersheet_id = os.id
    -- 梱包済み数
    LEFT JOIN (
        SELECT bn.m_ordersheet_id, SUM(IFNULL(b.work_quantity, 0)) AS packed_quantity
        FROM m_ordersheet o3
        JOIN t_packing_box_number bn ON bn.m_ordersheet_id = o3.id
        JOIN t_packing_box b ON b.box_number_id = bn.id
        WHERE o3.is_available = 1 AND " . str_replace("o.", "o3.", $where) . "
        GROUP BY bn.m_ordersheet_id
    ) pk ON pk.m_ordersheet_id = os.id
    ORDER BY os.delivery_date_at DESC, os.ordersheet_number DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 絞り込みに使った品番（画面に出す）
$pnNames = [];
if ($pnIds) {
    $st = $pdo->prepare("SELECT production_number FROM m_production_numbers WHERE id IN (" . implode(",", $pnIds) . ") ORDER BY production_number");
    $st->execute();
    $pnNames = $st->fetchAll(PDO::FETCH_COLUMN);
}

echo json_encode(["by_pn" => (bool)$pnIds, "production_numbers" => $pnNames, "rows" => $rows], JSON_UNESCAPED_UNICODE);
