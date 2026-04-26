<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$id = $_POST["id"] ?? null;
$admin_id = $_POST["admin_id"] ?? null;

if (!$id || !$admin_id) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

$sql = "
    UPDATE t_die_diagnosis
    SET 
        approval_status = 'rejected',
        approved_by = ?,
        approved_at = NOW()
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$admin_id, $id]);

echo json_encode(["status" => "success"]);
