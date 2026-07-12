<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$numbers = is_array($data["numbers"] ?? null) ? $data["numbers"] : [];

if (!$numbers) {
    echo json_encode(["existing" => []]);
    exit;
}

$placeholders = implode(",", array_fill(0, count($numbers), "?"));
$stmt = $pdo->prepare("
    SELECT ordersheet_number
    FROM m_ordersheet
    WHERE ordersheet_number IN ($placeholders)
");
$stmt->execute(array_values($numbers));

echo json_encode(["existing" => $stmt->fetchAll(PDO::FETCH_COLUMN)], JSON_UNESCAPED_UNICODE);
