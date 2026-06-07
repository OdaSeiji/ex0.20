<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE t_die_handover
    SET ordered_at = ?, shipped_at = ?
    WHERE id = ?
");

$updated = 0;
foreach ($rows as $row) {
    $id = intval($row["id"]);
    if (!$id) continue;
    $stmt->execute([
        $row["ordered_at"] ?: null,
        $row["shipped_at"] ?: null,
        $id,
    ]);
    $updated++;
}

echo json_encode(["status" => "ok", "updated" => $updated]);
