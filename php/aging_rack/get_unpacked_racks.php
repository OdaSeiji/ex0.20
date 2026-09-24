<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 未梱包の時効ラック一覧
//   OK本数   = t_using_aging_rack.work_quantity − NG本数（t_press_quality）
//   梱包済み = t_packing_box.work_quantity の合計
//   残本数   = OK本数 − 梱包済み
//   残本数 > 0 のラックを返す（梱包記録なし＝未梱包 / 梱包記録あり＝一部梱包）
//   対象は押出日が期間内のラック。経過日数は押出日（＝使用開始日）から今日まで

$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

$to   = $_GET["to"]   ?? "";
$from = $_GET["from"] ?? "";
if (!$isDate($to))   $to   = date("Y-m-d");
if (!$isDate($from)) $from = date("Y-m-d", strtotime($to . " -29 days"));
if ($from > $to) [$from, $to] = [$to, $from];

$sql = "
    SELECT
        r.id,
        DATE_FORMAT(p.press_date_at, '%Y-%m-%d')   AS press_date,
        DATEDIFF(CURDATE(), p.press_date_at)       AS days,
        p.press_machine_no                         AS machine,
        p.id                                       AS press_id,
        p.ordersheet_id,
        d.die_number,
        COALESCE(pnv.production_number, pnd.production_number) AS production_number,
        r.rack_number,
        r.order_number,
        IFNULL(r.work_quantity, 0)                 AS work_qty,
        IFNULL(q.ng, 0)                            AS ng_qty,
        IFNULL(b.packed, 0)                        AS packed_qty,
        IFNULL(r.work_quantity, 0) - IFNULL(q.ng, 0) - IFNULL(b.packed, 0) AS remain_qty,
        b.packed IS NOT NULL                       AS is_partial
    FROM t_using_aging_rack r
    JOIN t_press p ON p.id = r.t_press_id
    LEFT JOIN m_dies d ON d.id = p.dies_id
    LEFT JOIN m_die_production_number_variants v ON v.id = p.die_production_number_variant_id
    LEFT JOIN m_production_numbers pnv ON pnv.id = v.production_number_id
    LEFT JOIN m_production_numbers pnd ON pnd.id = d.production_number_id
    LEFT JOIN (
        SELECT using_aging_rack_id, SUM(ng_quantities) AS ng
        FROM t_press_quality
        WHERE using_aging_rack_id IS NOT NULL
        GROUP BY using_aging_rack_id
    ) q ON q.using_aging_rack_id = r.id
    LEFT JOIN (
        SELECT using_aging_rack_id, SUM(work_quantity) AS packed
        FROM t_packing_box
        WHERE using_aging_rack_id IS NOT NULL
        GROUP BY using_aging_rack_id
    ) b ON b.using_aging_rack_id = r.id
    WHERE p.press_date_at BETWEEN :from AND :to
      AND IFNULL(r.work_quantity, 0) - IFNULL(q.ng, 0) - IFNULL(b.packed, 0) > 0
    ORDER BY p.press_date_at, p.press_machine_no, p.id, r.order_number
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":from" => $from, ":to" => $to]);
    $rows = array_map(function ($r) {
        foreach (["id", "days", "machine", "press_id", "ordersheet_id", "rack_number", "order_number",
                  "work_qty", "ng_qty", "packed_qty", "remain_qty"] as $k) {
            $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        }
        $r["is_partial"] = (bool)$r["is_partial"];
        return $r;
    }, $stmt->fetchAll());

    // 一覧に出たラックの生産指示（指示全体の梱包済み本数も付ける）
    $ids = array_values(array_unique(array_filter(array_column($rows, "ordersheet_id"))));
    $ordersheets = [];
    if ($ids) {
        $in = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $pdo->prepare("
            SELECT
                o.id,
                o.ordersheet_number,
                pn.production_number,
                o.production_quantity,
                DATE_FORMAT(o.delivery_date_at, '%Y-%m-%d') AS delivery_date,
                (SELECT IFNULL(SUM(b.work_quantity), 0)
                   FROM t_press p
                   JOIN t_using_aging_rack r ON r.t_press_id = p.id
                   JOIN t_packing_box b      ON b.using_aging_rack_id = r.id
                  WHERE p.ordersheet_id = o.id) AS packed_total
            FROM m_ordersheet o
            LEFT JOIN m_production_numbers pn ON pn.id = o.production_numbers_id
            WHERE o.id IN ($in)
        ");
        $stmt->execute($ids);
        foreach ($stmt as $o) {
            $o["id"] = (int)$o["id"];
            $o["production_quantity"] = (int)$o["production_quantity"];
            $o["packed_total"] = (int)$o["packed_total"];
            $ordersheets[$o["id"]] = $o;
        }
    }

    echo json_encode([
        "from" => $from, "to" => $to, "rows" => $rows,
        "ordersheets" => (object)$ordersheets,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
