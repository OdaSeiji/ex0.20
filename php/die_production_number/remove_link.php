<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$body = json_decode(file_get_contents("php://input"), true);
$id = (int)($body["id"] ?? 0);

if ($id === 0) {
    echo json_encode(["status" => "error", "message" => "id is required"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM m_die_production_number_variants WHERE id = :id");
$stmt->execute([":id" => $id]);

echo json_encode(["status" => "ok"]);
