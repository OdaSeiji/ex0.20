<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$sql = "
    UPDATE t_die_handover SET
        ordered_at              = ?,
        shipped_at              = ?,
        instruction_created_at  = ?,
        inspection_number       = ?,
        inspection_passed_at    = ?,
        submitted_to_japan_at   = ?,
        asset_registration_applied_at = ?,
        is_accessory_item_flag  = ?,
        note2                   = ?,
        invoice_number          = ?,
        die_arrived_at          = ?,
        unusable_flag           = ?
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);

// 固定資産登録日が「未入力→入力あり」に変わった行を検出するため、更新前の値を取得
$stmtOld = $pdo->prepare("SELECT asset_registration_applied_at FROM t_die_handover WHERE id = ?");

// 固定資産登録日を初めて入力した時点で、金型進捗一覧側の金型移管日にも反映する
// （進捗テーブル側にまだ行が無い金型もあるため、無ければ新規作成する）
$stmtProgress = $pdo->prepare("
    INSERT INTO t_die_handover_progress (die_id, die_handover_at) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE die_handover_at = VALUES(die_handover_at)
");

$updated = 0;

foreach ($rows as $row) {
    $newAssetDate = $row["asset_registration_applied_at"] ?: null;

    $stmtOld->execute([$row["id"]]);
    $oldAssetDate = $stmtOld->fetchColumn();
    $oldAssetDate = ($oldAssetDate && $oldAssetDate !== "0000-00-00") ? $oldAssetDate : null;

    $stmt->execute([
        $row["ordered_at"]              ?: null,
        $row["shipped_at"]              ?: null,
        $row["instruction_created_at"]  ?: null,
        $row["inspection_number"]       ?: null,
        $row["inspection_passed_at"]    ?: null,
        $row["submitted_to_japan_at"]   ?: null,
        $newAssetDate,
        $row["is_accessory_item_flag"] !== null ? (int)$row["is_accessory_item_flag"] : null,
        $row["note2"]                   ?: null,
        $row["invoice_number"]          ?: null,
        $row["die_arrived_at"]          ?: null,
        $row["unusable_flag"] !== null  ? (int)$row["unusable_flag"] : null,
        $row["id"],
    ]);
    $updated++;

    if ($oldAssetDate === null && $newAssetDate !== null && !empty($row["die_id"])) {
        $stmtProgress->execute([(int)$row["die_id"], $newAssetDate]);
    }
}

echo json_encode(["status" => "ok", "updated" => $updated]);
