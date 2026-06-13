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
$updated = 0;

foreach ($rows as $row) {
    $stmt->execute([
        $row["ordered_at"]              ?: null,
        $row["shipped_at"]              ?: null,
        $row["instruction_created_at"]  ?: null,
        $row["inspection_number"]       ?: null,
        $row["inspection_passed_at"]    ?: null,
        $row["submitted_to_japan_at"]   ?: null,
        $row["asset_registration_applied_at"] ?: null,
        $row["is_accessory_item_flag"] !== null ? (int)$row["is_accessory_item_flag"] : null,
        $row["note2"]                   ?: null,
        $row["invoice_number"]          ?: null,
        $row["die_arrived_at"]          ?: null,
        $row["unusable_flag"] !== null  ? (int)$row["unusable_flag"] : null,
        $row["id"],
    ]);
    $updated++;
}

echo json_encode(["status" => "ok", "updated" => $updated]);
