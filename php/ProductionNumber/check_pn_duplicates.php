<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

$numbers = json_decode(file_get_contents("php://input"), true);
if (!$numbers || !is_array($numbers)) {
    echo json_encode([]);
    exit;
}

$numbers = array_values(array_unique($numbers));
$placeholders = implode(",", array_fill(0, count($numbers), "?"));
$stmt = $pdo->prepare("SELECT production_number FROM m_production_numbers WHERE production_number IN ($placeholders)");
$stmt->execute($numbers);
$existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

$result = [];
foreach ($numbers as $pn) {
    $result[$pn] = in_array($pn, $existing);
}
echo json_encode($result);
