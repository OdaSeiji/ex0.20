<?php
require_once __DIR__ . "/common.php";

// 梱包入力：梱包作業（t_packing）と作業者（t_packing_worker）
//   POST JSON: { action: "add" | "update" | "delete" | "add_worker" | "del_worker", ... }
//     add:        { order_id, packing_date, start, end, press_id }
//     update:     { id, packing_date, start, end, press_id }
//     delete:     { id }               箱の行（t_packing_box）がある作業は消さない（ex0.11 は CASCADE で箱の行ごと消えていた）
//     add_worker: { packing_id, staff_id }
//     del_worker: { id }
//   ex0.11 と同じく、梱包日を入れると選んでいる押出の梱包完了日（t_press.packing_check_date）にもその日を入れる（InsPackDate.php）

$b      = pkJsonBody();
$action = $b["action"] ?? "";

function workFields(array $b): array {
    $date  = $b["packing_date"] ?? "";
    $start = pkTime($b["start"] ?? "");
    $end   = pkTime($b["end"] ?? "");
    if (!pkIsDate($date)) pkFail(400, "梱包日が不正です");
    if ($start === null || $end === null) pkFail(400, "時刻は 8:30 のように入れてください");
    return [$date, $start, $end];
}

// 押出の梱包完了日を入れる（その発注の押出のときだけ）
function setPressPackingDate(PDO $pdo, $pressId, int $orderId, string $date): void {
    $pressId = pkInt($pressId);
    if ($pressId <= 0) return;
    $stmt = $pdo->prepare("UPDATE t_press SET packing_check_date = :d WHERE id = :id AND ordersheet_id = :o");
    $stmt->execute([":d" => $date, ":id" => $pressId, ":o" => $orderId]);
}

try {
    if ($action === "add") {
        $orderId = pkInt($b["order_id"] ?? 0);
        [$date, $start, $end] = workFields($b);
        $stmt = $pdo->prepare("SELECT 1 FROM m_ordersheet WHERE id = :id");
        $stmt->execute([":id" => $orderId]);
        if (!$stmt->fetch()) pkFail(400, "発注書が見つかりません");
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO t_packing (m_ordersheet_id, packing_date, packing_start, packing_end, created_at) VALUES (:o, :d, :s, :e, CURDATE())");
        $stmt->execute([":o" => $orderId, ":d" => $date, ":s" => $start, ":e" => $end]);
        $newId = (int)$pdo->lastInsertId();
        setPressPackingDate($pdo, $b["press_id"] ?? 0, $orderId, $date);
        $pdo->commit();
        echo json_encode(["success" => true, "id" => $newId]);

    } elseif ($action === "update") {
        $id = pkInt($b["id"] ?? 0);
        [$date, $start, $end] = workFields($b);
        $stmt = $pdo->prepare("SELECT m_ordersheet_id FROM t_packing WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $orderId = $stmt->fetchColumn();
        if ($orderId === false) pkFail(404, "梱包作業が見つかりません");
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE t_packing SET packing_date = :d, packing_start = :s, packing_end = :e WHERE id = :id");
        $stmt->execute([":d" => $date, ":s" => $start, ":e" => $end, ":id" => $id]);
        setPressPackingDate($pdo, $b["press_id"] ?? 0, (int)$orderId, $date);
        $pdo->commit();
        echo json_encode(["success" => true]);

    } elseif ($action === "delete") {
        $id = pkInt($b["id"] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM t_packing_box WHERE packing_id = :id");
        $stmt->execute([":id" => $id]);
        if ((int)$stmt->fetchColumn() > 0) pkFail(400, "この作業には箱に入れた記録があります。先に箱の行を消してください");
        $stmt = $pdo->prepare("DELETE FROM t_packing WHERE id = :id");   // 作業者の行は CASCADE で消える
        $stmt->execute([":id" => $id]);
        echo json_encode(["success" => true]);

    } elseif ($action === "add_worker") {
        $packingId = pkInt($b["packing_id"] ?? 0);
        $staffId   = pkInt($b["staff_id"] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM t_packing WHERE id = :id");
        $stmt->execute([":id" => $packingId]);
        if (!$stmt->fetch()) pkFail(404, "梱包作業が見つかりません");
        $stmt = $pdo->prepare("SELECT 1 FROM m_staff WHERE id = :id AND (leave_at IS NULL OR leave_at > CURDATE())");
        $stmt->execute([":id" => $staffId]);
        if (!$stmt->fetch()) pkFail(400, "作業者を選んでください");
        $stmt = $pdo->prepare("SELECT 1 FROM t_packing_worker WHERE t_packing_id = :p AND m_staff_id = :s");
        $stmt->execute([":p" => $packingId, ":s" => $staffId]);
        if ($stmt->fetch()) pkFail(400, "この作業者はもう入っています");
        $stmt = $pdo->prepare("INSERT INTO t_packing_worker (t_packing_id, m_staff_id, created_at) VALUES (:p, :s, CURDATE())");
        $stmt->execute([":p" => $packingId, ":s" => $staffId]);
        echo json_encode(["success" => true]);

    } elseif ($action === "del_worker") {
        $stmt = $pdo->prepare("DELETE FROM t_packing_worker WHERE id = :id");
        $stmt->execute([":id" => pkInt($b["id"] ?? 0)]);
        echo json_encode(["success" => true]);

    } else {
        pkFail(400, "action が不正です");
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    pkFail(500, $e->getMessage());
}
