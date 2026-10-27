<?php
require_once __DIR__ . "/common.php";

// 梱包入力：時効ラックごとの 梱包の NG（t_press_quality, process_id = 4）と 箱に入れた本数（t_packing_box）
//   POST JSON: { action, ... }
//     ng_add:    { rack_id, code_id, qty }
//     ng_update: { id, qty }
//     ng_delete: { id }
//     box_add:   { packing_id, box_number_id, rack_id, qty }   本数は ラックの OK本数 − 梱包済み まで（ex0.11 の画面のチェックと同じ）
//     box_delete:{ id }
//   exd11 の InsQalityInformation（process 4）/ UpdateNgQty / DelNgData / InsPackingBox / DelBoxData の移植

$b      = pkJsonBody();
$action = $b["action"] ?? "";

function ngQty($v): int {
    if (!preg_match('/^\d+$/', (string)$v) || (int)$v < 1 || (int)$v > 500) pkFail(400, "NG の本数は 1〜500 の数字で入れてください");
    return (int)$v;
}

// 梱包の NG の行か（ほかの工程の NG はこの画面では触らない）
function packingNgRow(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT id, using_aging_rack_id, ng_quantities FROM t_press_quality WHERE id = :id AND process_id = " . PK_PROCESS);
    $stmt->execute([":id" => $id]);
    $r = $stmt->fetch();
    if (!$r) pkFail(404, "梱包の NG の記録が見つかりません");
    return $r;
}

try {
    if ($action === "ng_add") {
        $rack = pkRackQty($pdo, pkInt($b["rack_id"] ?? 0));
        if (!$rack) pkFail(404, "ラックが見つかりません");
        $qty = ngQty($b["qty"] ?? "");
        $stmt = $pdo->prepare("SELECT 1 FROM m_quality_code WHERE id = :id AND quality_code REGEXP '^[0-9]{3}$'");
        $stmt->execute([":id" => pkInt($b["code_id"] ?? 0)]);
        if (!$stmt->fetch()) pkFail(400, "NG コードを選んでください");
        if ($qty > $rack["ok_qty"] - $rack["packed_qty"]) pkFail(400, "NG の本数が、まだ梱包していない本数（" . ($rack["ok_qty"] - $rack["packed_qty"]) . "本）を超えます");
        $stmt = $pdo->prepare("INSERT INTO t_press_quality (process_id, using_aging_rack_id, quality_code_id, ng_quantities, created_at) VALUES (" . PK_PROCESS . ", :r, :c, :q, CURDATE())");
        $stmt->execute([":r" => $rack["id"], ":c" => pkInt($b["code_id"]), ":q" => $qty]);
        echo json_encode(["success" => true]);

    } elseif ($action === "ng_update") {
        $row  = packingNgRow($pdo, pkInt($b["id"] ?? 0));
        $qty  = ngQty($b["qty"] ?? "");
        $rack = pkRackQty($pdo, (int)$row["using_aging_rack_id"]);
        $room = $rack["ok_qty"] + (int)$row["ng_quantities"] - $rack["packed_qty"];   // この行を除いた残り
        if ($qty > $room) pkFail(400, "NG の本数が、まだ梱包していない本数（{$room}本）を超えます");
        $stmt = $pdo->prepare("UPDATE t_press_quality SET ng_quantities = :q WHERE id = :id");
        $stmt->execute([":q" => $qty, ":id" => (int)$row["id"]]);
        echo json_encode(["success" => true]);

    } elseif ($action === "ng_delete") {
        $row = packingNgRow($pdo, pkInt($b["id"] ?? 0));
        $stmt = $pdo->prepare("DELETE FROM t_press_quality WHERE id = :id");
        $stmt->execute([":id" => (int)$row["id"]]);
        echo json_encode(["success" => true]);

    } elseif ($action === "box_add") {
        $packingId = pkInt($b["packing_id"] ?? 0);
        $boxId     = pkInt($b["box_number_id"] ?? 0);
        $rack      = pkRackQty($pdo, pkInt($b["rack_id"] ?? 0));
        $qty       = $b["qty"] ?? "";
        if (!$rack) pkFail(404, "ラックが見つかりません");
        $stmt = $pdo->prepare("SELECT 1 FROM t_packing WHERE id = :id");
        $stmt->execute([":id" => $packingId]);
        if (!$stmt->fetch()) pkFail(400, "梱包作業を選んでください");
        $stmt = $pdo->prepare("SELECT 1 FROM t_packing_box_number WHERE id = :id");
        $stmt->execute([":id" => $boxId]);
        if (!$stmt->fetch()) pkFail(400, "箱番号を選んでください");
        $room = $rack["ok_qty"] - $rack["packed_qty"];
        if (!preg_match('/^\d+$/', (string)$qty) || (int)$qty < 1) pkFail(400, "本数は 1 以上の数字で入れてください");
        if ((int)$qty > $room) pkFail(400, "本数が、このラックのまだ梱包していない本数（{$room}本）を超えます");
        $stmt = $pdo->prepare("INSERT INTO t_packing_box (packing_id, box_number_id, using_aging_rack_id, work_quantity, created_at) VALUES (:p, :b, :r, :q, CURDATE())");
        $stmt->execute([":p" => $packingId, ":b" => $boxId, ":r" => $rack["id"], ":q" => (int)$qty]);
        echo json_encode(["success" => true]);

    } elseif ($action === "box_delete") {
        $stmt = $pdo->prepare("DELETE FROM t_packing_box WHERE id = :id");
        $stmt->execute([":id" => pkInt($b["id"] ?? 0)]);
        echo json_encode(["success" => true]);

    } else {
        pkFail(400, "action が不正です");
    }
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}
