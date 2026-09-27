<?php
require_once __DIR__ . "/common.php";

// NG の追加・更新・削除（t_press_quality）
//   POST JSON:
//     { action: "add",    rack_id, process_id, quality_code_id, qty, staff_id, note }
//     { action: "update", id, process_id, quality_code_id, qty, staff_id, note }
//     { action: "delete", id }
//   期間外（1か月より前）の押出は古いページで扱うので受け付けない

$body   = qrJsonBody();
$action = $body["action"] ?? "";

// 対象のラックが属する押出（期間内か確認するため）
function pressIdOfRack(PDO $pdo, int $rackId): ?int {
    $stmt = $pdo->prepare("SELECT t_press_id FROM t_using_aging_rack WHERE id = :id");
    $stmt->execute([":id" => $rackId]);
    $v = $stmt->fetchColumn();
    return $v === false ? null : (int)$v;
}
function pressIdOfNg(PDO $pdo, int $ngId): ?int {
    $stmt = $pdo->prepare("
        SELECT r.t_press_id FROM t_press_quality q
        JOIN t_using_aging_rack r ON r.id = q.using_aging_rack_id
        WHERE q.id = :id AND q.process_id IN (1, 2, 3)
    ");
    $stmt->execute([":id" => $ngId]);
    $v = $stmt->fetchColumn();
    return $v === false ? null : (int)$v;
}
// ラックの NG 合計（寸法・エッチング・時効）がラックの本数を超えないか。$exceptNgId は更新中の自分自身を除くため
function checkRackCapacity(PDO $pdo, int $rackId, int $qty, int $exceptNgId = 0): void {
    $stmt = $pdo->prepare("
        SELECT IFNULL(r.work_quantity, 0) AS rack_qty,
               (SELECT IFNULL(SUM(q.ng_quantities), 0) FROM t_press_quality q
                 WHERE q.using_aging_rack_id = r.id AND q.process_id IN (1, 2, 3) AND q.id <> :except) AS ng
        FROM t_using_aging_rack r WHERE r.id = :rack
    ");
    $stmt->execute([":rack" => $rackId, ":except" => $exceptNgId]);
    $r = $stmt->fetch();
    if ($r && (int)$r["ng"] + $qty > (int)$r["rack_qty"]) {
        qrFail(400, "NGの合計（" . ((int)$r["ng"] + $qty) . "本）がラックの本数（" . (int)$r["rack_qty"] . "本）を超えます");
    }
}

function validateFields(PDO $pdo, array $b): array {
    $processId = (int)($b["process_id"] ?? 0);
    $codeId    = (int)($b["quality_code_id"] ?? 0);
    $qty       = $b["qty"] ?? null;
    $staffId   = (int)($b["staff_id"] ?? 0);
    $note      = trim((string)($b["note"] ?? ""));

    if (!in_array($processId, QR_PROCESSES, true)) qrFail(400, "工程が不正です");
    $stmt = $pdo->prepare("SELECT quality_code FROM m_quality_code WHERE id = :id AND (quality_code REGEXP '^[0-9]{3}$' OR quality_code = '0')");
    $stmt->execute([":id" => $codeId]);
    $code = $stmt->fetchColumn();
    if ($code === false) qrFail(400, "NGコードが不正です");
    if ($code === "0") {
        // 「不良なし」は本数0で記録する（チェックした人を残すための記録）
        $qty = 0;
    } elseif (!preg_match('/^\d+$/', (string)$qty) || (int)$qty < 1 || (int)$qty > 500) {
        qrFail(400, "NG本数は1〜500の整数で入力してください");
    }
    if (!qrIsActiveInspector($pdo, $staffId)) qrFail(400, "判定者は在籍中の検査員から選んでください");
    if (mb_strlen($note) > 100) qrFail(400, "備考は100文字以内で入力してください");

    return [
        ":process_id"      => $processId,
        ":quality_code_id" => $codeId,
        ":ng_quantities"   => (int)$qty,
        ":staff_id"        => $staffId,
        ":note"            => $note === "" ? null : $note,
    ];
}

try {
    if ($action === "add") {
        $rackId  = (int)($body["rack_id"] ?? 0);
        $pressId = pressIdOfRack($pdo, $rackId);
        if ($pressId === null) qrFail(400, "ラックが見つかりません");
        if (!qrPressInWindow($pdo, $pressId)) qrFail(409, "1か月より前の押出は、旧画面で修正してください");

        $params = validateFields($pdo, $body);
        checkRackCapacity($pdo, $rackId, $params[":ng_quantities"]);
        $stmt = $pdo->prepare("
            INSERT INTO t_press_quality (process_id, using_aging_rack_id, quality_code_id, ng_quantities, staff_id, note, created_at)
            VALUES (:process_id, :rack_id, :quality_code_id, :ng_quantities, :staff_id, :note, CURDATE())
        ");
        $stmt->execute($params + [":rack_id" => $rackId]);
        echo json_encode(["success" => true, "id" => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);

    } elseif ($action === "update" || $action === "delete") {
        $id      = (int)($body["id"] ?? 0);
        $pressId = pressIdOfNg($pdo, $id);
        if ($pressId === null) qrFail(404, "NGの記録が見つかりません");
        if (!qrPressInWindow($pdo, $pressId)) qrFail(409, "1か月より前の押出は、旧画面で修正してください");

        if ($action === "update") {
            $params = validateFields($pdo, $body);
            $stmt = $pdo->prepare("SELECT using_aging_rack_id FROM t_press_quality WHERE id = :id");
            $stmt->execute([":id" => $id]);
            checkRackCapacity($pdo, (int)$stmt->fetchColumn(), $params[":ng_quantities"], $id);
            $stmt = $pdo->prepare("
                UPDATE t_press_quality
                SET process_id = :process_id, quality_code_id = :quality_code_id,
                    ng_quantities = :ng_quantities, staff_id = :staff_id, note = :note
                WHERE id = :id
            ");
            $stmt->execute($params + [":id" => $id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM t_press_quality WHERE id = :id");
            $stmt->execute([":id" => $id]);
        }
        echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);

    } else {
        qrFail(400, "不正な操作です");
    }
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
