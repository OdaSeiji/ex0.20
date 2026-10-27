<?php
require_once __DIR__ . "/common.php";

// 梱包入力：箱番号（t_packing_box_number）
//   POST JSON: { action, ... }
//     create:   { order_id, names: ["2610001-01/20", ...] }   この発注の箱番号を作る（exd11 PackingMakeingBoxList の「Make → Agree」）
//     same_pn:  { order_id }   同じ品番の別の発注の一覧（exd11 PackingAddBoxNumber / SelSameOrderPnSheet）
//     use_order:{ order_id }   その発注の箱番号を返す。まだ1つも無ければ「発注数 ÷ 1箱の本数」で作ってから返す（ex0.11 と同じ）
//     delete:   { id }         使っていない箱番号を消す

$b      = pkJsonBody();
$action = $b["action"] ?? "";

function orderRow(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT o.id, o.ordersheet_number, o.production_numbers_id, o.production_quantity, pn.packing_quantity
        FROM m_ordersheet o
        LEFT JOIN m_production_numbers pn ON pn.id = o.production_numbers_id
        WHERE o.id = :id
    ");
    $stmt->execute([":id" => $id]);
    $o = $stmt->fetch();
    if (!$o) pkFail(404, "発注書が見つかりません");
    return $o;
}

function boxesOf(PDO $pdo, int $orderId): array {
    $stmt = $pdo->prepare("
        SELECT bn.id, bn.box_number, bn.m_ordersheet_id,
               IFNULL((SELECT SUM(b.work_quantity) FROM t_packing_box b WHERE b.box_number_id = bn.id), 0) AS qty
        FROM t_packing_box_number bn
        WHERE bn.m_ordersheet_id = :id
        ORDER BY bn.id
    ");
    $stmt->execute([":id" => $orderId]);
    return array_map(fn($r) => [
        "id" => (int)$r["id"], "box_number" => $r["box_number"], "ordersheet_id" => (int)$r["m_ordersheet_id"], "qty" => (int)$r["qty"],
    ], $stmt->fetchAll());
}

function insertBoxes(PDO $pdo, int $orderId, array $names): void {
    $names = array_values(array_unique(array_map(fn($n) => trim((string)$n), $names)));
    if (!$names) pkFail(400, "箱番号がありません");
    foreach ($names as $n) {
        if ($n === "" || mb_strlen($n) > 30) pkFail(400, "箱番号は1〜30文字にしてください");
    }
    $in  = implode(",", array_fill(0, count($names), "?"));
    $stmt = $pdo->prepare("SELECT box_number FROM t_packing_box_number WHERE box_number IN ({$in})");
    $stmt->execute($names);
    $dups = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($dups) pkFail(400, "もう使われている箱番号があります：" . implode(", ", array_slice($dups, 0, 5)));
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO t_packing_box_number (box_number, m_ordersheet_id, created_at) VALUES (:n, :o, CURDATE())");
    foreach ($names as $n) $stmt->execute([":n" => $n, ":o" => $orderId]);
    $pdo->commit();
}

try {
    if ($action === "create") {
        $o = orderRow($pdo, pkInt($b["order_id"] ?? 0));
        insertBoxes($pdo, (int)$o["id"], is_array($b["names"] ?? null) ? $b["names"] : []);
        echo json_encode(["success" => true, "boxes" => boxesOf($pdo, (int)$o["id"])], JSON_UNESCAPED_UNICODE);

    } elseif ($action === "same_pn") {
        $o = orderRow($pdo, pkInt($b["order_id"] ?? 0));
        $stmt = $pdo->prepare("
            SELECT o.id, o.ordersheet_number,
                   DATE_FORMAT(o.delivery_date_at, '%Y-%m-%d') AS delivery_date,
                   o.production_quantity AS order_qty,
                   (SELECT COUNT(*) FROM t_packing_box_number bn WHERE bn.m_ordersheet_id = o.id) AS box_count
            FROM m_ordersheet o
            WHERE o.production_numbers_id = :pn AND o.id <> :id
            ORDER BY o.delivery_date_at DESC
            LIMIT 30
        ");
        $stmt->execute([":pn" => $o["production_numbers_id"], ":id" => $o["id"]]);
        $rows = array_map(function ($r) {
            foreach (["id", "order_qty", "box_count"] as $k) $r[$k] = (int)$r[$k];
            return $r;
        }, $stmt->fetchAll());
        echo json_encode(["success" => true, "orders" => $rows, "per_box" => is_numeric($o["packing_quantity"]) ? (int)$o["packing_quantity"] : null], JSON_UNESCAPED_UNICODE);

    } elseif ($action === "use_order") {
        $o = orderRow($pdo, pkInt($b["order_id"] ?? 0));
        $boxes = boxesOf($pdo, (int)$o["id"]);
        if (!$boxes) {
            $per = (int)$o["packing_quantity"];
            if ($per <= 0) pkFail(400, "品番に1箱の本数（packing_quantity）がありません");
            $count = (int)ceil((int)$o["production_quantity"] / $per);
            if ($count < 1) pkFail(400, "発注数が 0 です");
            insertBoxes($pdo, (int)$o["id"], pkMakeBoxNames($o["ordersheet_number"], $count));
            $boxes = boxesOf($pdo, (int)$o["id"]);
        }
        echo json_encode(["success" => true, "boxes" => $boxes], JSON_UNESCAPED_UNICODE);

    } elseif ($action === "delete") {
        $id = pkInt($b["id"] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM t_packing_box WHERE box_number_id = :id");
        $stmt->execute([":id" => $id]);
        if ((int)$stmt->fetchColumn() > 0) pkFail(400, "この箱にはもう本数が入っているので消せません");
        $stmt = $pdo->prepare("DELETE FROM t_packing_box_number WHERE id = :id");
        $stmt->execute([":id" => $id]);
        echo json_encode(["success" => true]);

    } else {
        pkFail(400, "action が不正です");
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    pkFail(500, $e->getMessage());
}
