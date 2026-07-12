<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data["id"] ?? 0);

if (!$id) {
    echo json_encode(["status" => "error", "message" => "invalid id"]);
    exit;
}

$fieldMap = [
    "issue_date_at" => "issue_date_at",
    "delivery_date_at" => "delivery_date_at",
    "production_quantity" => "production_quantity",
    "note" => "note",
];

$sets = [];
$params = [];
foreach ($fieldMap as $key => $column) {
    if (array_key_exists($key, $data)) {
        $sets[] = "{$column} = ?";
        $params[] = $data[$key];
    }
}

if (!$sets) {
    echo json_encode(["status" => "error", "message" => "no fields to update"]);
    exit;
}

$sets[] = "updated_at = CURDATE()";
$params[] = $id;

$stmt = $pdo->prepare("UPDATE m_ordersheet SET " . implode(", ", $sets) . " WHERE id = ?");
$stmt->execute($params);

echo json_encode(["status" => "ok"]);
