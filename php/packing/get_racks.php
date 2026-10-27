<?php
require_once __DIR__ . "/common.php";

// 梱包入力：押出の時効ラック（OK本数・梱包済み）と、ラックごとの梱包の NG・箱
//   GET press_id
//   exd11 の SelUgingAgingRack / SelSelRackNgInformation / SelPackingBoxNumberWorkQty の移植

$pressId = (int)($_GET["press_id"] ?? 0);
if ($pressId <= 0) pkFail(400, "press_id が不正です");

try {
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.rack_number,
            r.order_number,
            IFNULL(r.work_quantity, 0) AS work_qty,
            IFNULL(r.work_quantity, 0)
              - IFNULL((SELECT SUM(q.ng_quantities) FROM t_press_quality q WHERE q.using_aging_rack_id = r.id), 0) AS ok_qty,
            IFNULL((SELECT SUM(b.work_quantity) FROM t_packing_box b WHERE b.using_aging_rack_id = r.id), 0) AS packed_qty
        FROM t_using_aging_rack r
        WHERE r.t_press_id = :id
        ORDER BY r.order_number, r.id
    ");
    $stmt->execute([":id" => $pressId]);
    $racks = $stmt->fetchAll();

    $ng = [];
    $boxes = [];
    if ($racks) {
        $ids = implode(",", array_map(fn($r) => (int)$r["id"], $racks));
        foreach ($pdo->query("
            SELECT q.id, q.using_aging_rack_id, q.quality_code_id, c.quality_code, c.description_jp, c.description_vn, q.ng_quantities
            FROM t_press_quality q
            LEFT JOIN m_quality_code c ON c.id = q.quality_code_id
            WHERE q.process_id = " . PK_PROCESS . " AND q.using_aging_rack_id IN ({$ids})
            ORDER BY q.id
        ") as $q) {
            $ng[(int)$q["using_aging_rack_id"]][] = [
                "id" => (int)$q["id"], "code_id" => (int)$q["quality_code_id"], "code" => $q["quality_code"],
                "ja" => $q["description_jp"], "vi" => $q["description_vn"], "qty" => (int)$q["ng_quantities"],
            ];
        }
        foreach ($pdo->query("
            SELECT b.id, b.using_aging_rack_id, b.packing_id, bn.box_number, b.work_quantity,
                   DATE_FORMAT(k.packing_date, '%Y-%m-%d') AS packing_date
            FROM t_packing_box b
            LEFT JOIN t_packing_box_number bn ON bn.id = b.box_number_id
            LEFT JOIN t_packing k ON k.id = b.packing_id
            WHERE b.using_aging_rack_id IN ({$ids})
            ORDER BY b.id
        ") as $b) {
            $boxes[(int)$b["using_aging_rack_id"]][] = [
                "id" => (int)$b["id"], "packing_id" => (int)$b["packing_id"], "box_number" => $b["box_number"],
                "qty" => (int)$b["work_quantity"], "packing_date" => $b["packing_date"],
            ];
        }
    }
    $racks = array_map(function ($r) use ($ng, $boxes) {
        foreach (["id", "order_number", "work_qty", "ok_qty", "packed_qty"] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        $r["ng"] = $ng[$r["id"]] ?? [];
        $r["boxes"] = $boxes[$r["id"]] ?? [];
        return $r;
    }, $racks);

    echo json_encode(["racks" => $racks], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}
