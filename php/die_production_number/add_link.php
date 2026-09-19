<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$body = json_decode(file_get_contents("php://input"), true);
$dieId = (int)($body["die_id"] ?? 0);
$productionNumberId = (int)($body["production_number_id"] ?? 0);

if ($dieId === 0 || $productionNumberId === 0) {
    echo json_encode(["status" => "error", "message" => "die_id and production_number_id are required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO m_die_production_number_variants (die_id, production_number_id)
        VALUES (:die_id, :production_number_id)
    ");
    $stmt->execute([
        ":die_id" => $dieId,
        ":production_number_id" => $productionNumberId,
    ]);
    echo json_encode(["status" => "ok", "id" => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
