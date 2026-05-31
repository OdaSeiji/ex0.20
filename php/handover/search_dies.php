<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$data  = json_decode(file_get_contents("php://input"), true);
$names = $data["names"] ?? [];

if (empty($names)) {
    echo json_encode([]);
    exit;
}

$result = [];
$stmt   = $pdo->prepare("
    SELECT id, die_number
    FROM m_dies
    WHERE die_number LIKE ?
    ORDER BY die_number
    LIMIT 10
");

foreach ($names as $name) {
    $name = trim($name);
    if ($name === "") continue;
    $stmt->execute([$name . '%']);
    $result[$name] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
